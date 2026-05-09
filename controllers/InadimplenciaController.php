<?php
require_once __DIR__ . '/../models/Inadimplencia.php';
require_once __DIR__ . '/../config/helpers.php';

class InadimplenciaController
{
    private const TARGET_CITY = 'SIMOES FILHO';
    private const TARGET_STATE = 'BA';
    private const TARGET_COUNTRY = 'BR';
    private const GEO_GROUP_RADIUS_KM = 2.0;
    private const GEO_MAX_REQUESTS = 80;

    private Inadimplencia $model;

    public function __construct()
    {
        $this->model = new Inadimplencia();
    }

    public function index(): array
    {
        $filters = [
            'query' => trim((string) ($_GET['q'] ?? '')),
            'status' => strtoupper(trim((string) ($_GET['status'] ?? ''))),
            'prazo_de' => trim((string) ($_GET['prazo_de'] ?? '')),
            'prazo_ate' => trim((string) ($_GET['prazo_ate'] ?? '')),
        ];

        $records = $this->model->list($filters);
        $routePlanning = $this->buildRoutePlanning($records);

        return [
            'registros' => $records,
            'resumo' => $this->model->summary($filters),
            'filters' => $filters,
            'statusOptions' => $this->model->statusOptions(),
            'routePlanning' => $routePlanning,
        ];
    }

    public function importSpreadsheet(array $data, array $files): void
    {
        $rowsJson = (string) ($data['linhas_importacao_json'] ?? '');
        $replaceAll = isset($data['substituir_base']) && (string) $data['substituir_base'] === '1';
        $sourceFile = isset($files['planilha_inadimplencia']['name'])
            ? sanitizeInput((string) $files['planilha_inadimplencia']['name'])
            : null;

        if (trim($rowsJson) === '') {
            setFlash('danger', 'Nenhuma linha encontrada para importar. Confira a planilha e tente novamente.');
            redirect('inadimplencia');
        }

        $rows = json_decode($rowsJson, true);
        if (!is_array($rows) || empty($rows)) {
            setFlash('danger', 'Falha ao ler os dados da planilha.');
            redirect('inadimplencia');
        }

        if (count($rows) > 10000) {
            setFlash('danger', 'A planilha excede 10.000 linhas. Divida o arquivo e tente novamente.');
            redirect('inadimplencia');
        }

        try {
            $imported = $this->model->importRows($rows, $replaceAll, $sourceFile);

            if ($imported <= 0) {
                setFlash('warning', 'Importacao concluida, mas nenhuma linha valida foi encontrada.');
                redirect('inadimplencia');
            }

            $message = $replaceAll
                ? 'Importacao concluida com sucesso. Base substituida com ' . $imported . ' registro(s).'
                : 'Importacao concluida com sucesso. ' . $imported . ' registro(s) adicionados.';

            setFlash('success', $message);
        } catch (Throwable $e) {
            setFlash('danger', 'Falha ao importar planilha: ' . $e->getMessage());
        }

        redirect('inadimplencia');
    }

    public function clearBase(): void
    {
        try {
            $this->model->clearAll();
            setFlash('success', 'Base limpa com sucesso. Agora voce pode importar uma nova planilha.');
        } catch (Throwable $e) {
            setFlash('danger', 'Falha ao limpar a base: ' . $e->getMessage());
        }

        redirect('inadimplencia');
    }

    public function update(array $data): void
    {
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            setFlash('danger', 'Registro invalido para atualizacao.');
            redirect('inadimplencia');
        }

        $payload = [
            'titular' => sanitizeInput((string) ($data['titular'] ?? '')),
            'equipamento' => sanitizeInput((string) ($data['equipamento'] ?? '')),
            'contato' => sanitizeInput((string) ($data['contato'] ?? '')),
            'endereco' => sanitizeInput((string) ($data['endereco'] ?? '')),
            'prazo' => sanitizeInput((string) ($data['prazo'] ?? '')),
            'status' => sanitizeInput((string) ($data['status'] ?? '')),
            'tentativa_1' => sanitizeInput((string) ($data['tentativa_1'] ?? '')),
            'nova_tentativa' => sanitizeInput((string) ($data['nova_tentativa'] ?? '')),
            'observacoes' => sanitizeInput((string) ($data['observacoes'] ?? '')),
        ];

        if ($payload['titular'] === '' || $payload['equipamento'] === '') {
            setFlash('danger', 'Titular e equipamento sao obrigatorios.');
            redirect('inadimplencia');
        }

        $tentativas = trim((string) $payload['tentativa_1']);
        $novaTentativa = trim((string) $payload['nova_tentativa']);
        if ($novaTentativa !== '') {
            $registro = '[' . date('d/m/Y H:i') . '] ' . $novaTentativa;
            $tentativas = $tentativas !== '' ? ($tentativas . PHP_EOL . PHP_EOL . $registro) : $registro;
        }

        try {
            $payload['tentativa_1'] = $tentativas;
            $this->model->update($id, $payload);
            setFlash('success', 'Registro atualizado com sucesso.');
        } catch (Throwable $e) {
            setFlash('danger', 'Falha ao atualizar registro: ' . $e->getMessage());
        }

        redirect('inadimplencia');
    }

    public function destroy(array $data): void
    {
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            setFlash('danger', 'Registro invalido para exclusao.');
            redirect('inadimplencia');
        }

        try {
            $this->model->delete($id);
            setFlash('success', 'Registro removido com sucesso.');
        } catch (Throwable $e) {
            setFlash('danger', 'Falha ao remover registro: ' . $e->getMessage());
        }

        redirect('inadimplencia');
    }

    private function buildRoutePlanning(array $rows): array
    {
        $mode = strtolower(trim((string) ($_GET['rota_modo'] ?? 'bairro')));
        if ($mode !== 'geo') {
            $mode = 'bairro';
        }

        $maxPorGrupo = (int) ($_GET['rota_limite_grupo'] ?? 8);
        if ($maxPorGrupo < 3) {
            $maxPorGrupo = 3;
        }
        if ($maxPorGrupo > 20) {
            $maxPorGrupo = 20;
        }

        $somenteCidade = !isset($_GET['rota_so_cidade']) || (string) $_GET['rota_so_cidade'] === '1';
        $apiKey = trim((string) getenv('GOOGLE_MAPS_API_KEY'));
        $googleDisponivel = $apiKey !== '';
        $usarGeo = $mode === 'geo' && $googleDisponivel;

        $warnings = [];
        $pending = [];
        $outOfCity = [];

        foreach ($rows as $row) {
            $status = strtoupper(trim((string) ($row['status'] ?? '')));
            if ($status === 'RECOLHIDO' || $status === 'NAO RECOLHER') {
                continue;
            }

            $endereco = trim((string) ($row['endereco'] ?? ''));
            if ($endereco === '') {
                continue;
            }

            $bairro = $this->extractBairro($endereco);
            $isCityMatch = $this->isAddressFromTargetCity($endereco);

            $candidate = [
                'id' => (int) ($row['id'] ?? 0),
                'titular' => (string) ($row['titular'] ?? ''),
                'equipamento' => (string) ($row['equipamento'] ?? ''),
                'contato' => (string) ($row['contato'] ?? ''),
                'endereco' => $endereco,
                'status' => $status,
                'prazo' => (string) ($row['prazo'] ?? ''),
                'bairro' => $bairro,
                'is_city_match' => $isCityMatch,
            ];

            if ($somenteCidade && !$isCityMatch) {
                $outOfCity[] = $candidate;
                continue;
            }

            $pending[] = $candidate;
        }

        if ($usarGeo && !empty($pending)) {
            [$geoGroups, $geoWarnings] = $this->buildGeoGroups($pending, $apiKey, $maxPorGrupo, $somenteCidade);
            $warnings = array_merge($warnings, $geoWarnings);

            if (!empty($geoGroups)) {
                return [
                    'mode' => $mode,
                    'googleAvailable' => $googleDisponivel,
                    'usedGoogle' => true,
                    'maxPerGroup' => $maxPorGrupo,
                    'onlyCity' => $somenteCidade,
                    'targetCityLabel' => 'Simoes Filho/BA',
                    'totalCandidates' => count($pending),
                    'excludedOutOfCity' => count($outOfCity),
                    'warnings' => $warnings,
                    'groups' => $geoGroups,
                ];
            }

            $warnings[] = 'Nao foi possivel montar grupos por geolocalizacao. Exibindo agrupamento por bairro.';
        }

        if ($mode === 'geo' && !$googleDisponivel) {
            $warnings[] = 'Google Maps desativado: defina GOOGLE_MAPS_API_KEY para habilitar agrupamento geografico.';
        }

        return [
            'mode' => $mode,
            'googleAvailable' => $googleDisponivel,
            'usedGoogle' => false,
            'maxPerGroup' => $maxPorGrupo,
            'onlyCity' => $somenteCidade,
            'targetCityLabel' => 'Simoes Filho/BA',
            'totalCandidates' => count($pending),
            'excludedOutOfCity' => count($outOfCity),
            'warnings' => $warnings,
            'groups' => $this->buildBairroGroups($pending, $maxPorGrupo),
        ];
    }

    private function buildBairroGroups(array $pending, int $maxPorGrupo): array
    {
        $byBairro = [];
        foreach ($pending as $item) {
            $key = $item['bairro'] !== '' ? $item['bairro'] : 'BAIRRO NAO IDENTIFICADO';
            if (!isset($byBairro[$key])) {
                $byBairro[$key] = [];
            }
            $byBairro[$key][] = $item;
        }

        uasort($byBairro, static function (array $a, array $b): int {
            return count($b) <=> count($a);
        });

        $groups = [];
        $sequence = 1;

        foreach ($byBairro as $bairro => $items) {
            $chunks = array_chunk($items, $maxPorGrupo);
            foreach ($chunks as $chunkIndex => $chunk) {
                $groups[] = [
                    'label' => count($chunks) > 1 ? ($bairro . ' - lote ' . ($chunkIndex + 1)) : $bairro,
                    'bairro' => $bairro,
                    'size' => count($chunk),
                    'sequence' => $sequence++,
                    'items' => $chunk,
                    'source' => 'bairro',
                ];
            }
        }

        return $groups;
    }

    private function buildGeoGroups(array $pending, string $apiKey, int $maxPorGrupo, bool $somenteCidade): array
    {
        $warnings = [];
        $geocoded = [];
        $geoSkipped = 0;

        foreach ($pending as $idx => $item) {
            if ($idx >= self::GEO_MAX_REQUESTS) {
                $geoSkipped++;
                continue;
            }

            $geo = $this->geocodeAddressWithGoogle($item['endereco'], $apiKey);
            if ($geo === null) {
                continue;
            }

            $cityOk = $this->normalizeText((string) ($geo['city'] ?? '')) === self::TARGET_CITY;
            if ($somenteCidade && !$cityOk) {
                continue;
            }

            $item['bairro'] = $item['bairro'] !== '' ? $item['bairro'] : (string) ($geo['bairro'] ?? '');
            $item['geo_city'] = (string) ($geo['city'] ?? '');
            $item['lat'] = (float) $geo['lat'];
            $item['lng'] = (float) $geo['lng'];
            $geocoded[] = $item;
        }

        if ($geoSkipped > 0) {
            $warnings[] = 'Limite de geocodificacao atingido em ' . self::GEO_MAX_REQUESTS . ' enderecos nesta consulta.';
        }

        if (count($geocoded) < 2) {
            return [[], $warnings];
        }

        usort($geocoded, static function (array $a, array $b): int {
            $bairroA = (string) ($a['bairro'] ?? '');
            $bairroB = (string) ($b['bairro'] ?? '');
            if ($bairroA !== $bairroB) {
                return strcmp($bairroA, $bairroB);
            }
            return strcmp((string) ($a['endereco'] ?? ''), (string) ($b['endereco'] ?? ''));
        });

        $clusters = [];
        foreach ($geocoded as $item) {
            $closestIdx = null;
            $closestDistance = PHP_FLOAT_MAX;

            foreach ($clusters as $clusterIdx => $cluster) {
                if (count($cluster['items']) >= $maxPorGrupo) {
                    continue;
                }

                $distance = $this->haversineKm(
                    $item['lat'],
                    $item['lng'],
                    $cluster['centroid_lat'],
                    $cluster['centroid_lng']
                );

                if ($distance < $closestDistance) {
                    $closestDistance = $distance;
                    $closestIdx = $clusterIdx;
                }
            }

            if ($closestIdx === null || $closestDistance > self::GEO_GROUP_RADIUS_KM) {
                $clusters[] = [
                    'items' => [$item],
                    'centroid_lat' => $item['lat'],
                    'centroid_lng' => $item['lng'],
                ];
                continue;
            }

            $clusters[$closestIdx]['items'][] = $item;
            $count = count($clusters[$closestIdx]['items']);
            $clusters[$closestIdx]['centroid_lat'] = (($clusters[$closestIdx]['centroid_lat'] * ($count - 1)) + $item['lat']) / $count;
            $clusters[$closestIdx]['centroid_lng'] = (($clusters[$closestIdx]['centroid_lng'] * ($count - 1)) + $item['lng']) / $count;
        }

        usort($clusters, static function (array $a, array $b): int {
            return count($b['items']) <=> count($a['items']);
        });

        $groups = [];
        foreach ($clusters as $idx => $cluster) {
            $bairroCount = [];
            foreach ($cluster['items'] as $item) {
                $bairroKey = (string) ($item['bairro'] ?? 'BAIRRO NAO IDENTIFICADO');
                if ($bairroKey === '') {
                    $bairroKey = 'BAIRRO NAO IDENTIFICADO';
                }
                $bairroCount[$bairroKey] = ($bairroCount[$bairroKey] ?? 0) + 1;
            }

            arsort($bairroCount);
            $bairroPrincipal = (string) array_key_first($bairroCount);

            $groups[] = [
                'label' => 'Cluster ' . ($idx + 1) . ' - ' . $bairroPrincipal,
                'bairro' => $bairroPrincipal,
                'size' => count($cluster['items']),
                'sequence' => $idx + 1,
                'items' => $cluster['items'],
                'source' => 'geo',
            ];
        }

        return [$groups, $warnings];
    }

    private function geocodeAddressWithGoogle(string $address, string $apiKey): ?array
    {
        $query = $address . ', Simoes Filho, Bahia, Brasil';
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . rawurlencode($query)
            . '&region=' . rawurlencode(self::TARGET_COUNTRY)
            . '&key=' . rawurlencode($apiKey);

        $response = $this->httpGet($url);
        if ($response === null) {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data) || !isset($data['status']) || $data['status'] !== 'OK' || empty($data['results'][0])) {
            return null;
        }

        $result = $data['results'][0];
        $location = $result['geometry']['location'] ?? null;
        if (!is_array($location) || !isset($location['lat'], $location['lng'])) {
            return null;
        }

        $city = '';
        $bairro = '';
        $state = '';

        $components = $result['address_components'] ?? [];
        if (is_array($components)) {
            foreach ($components as $component) {
                $types = $component['types'] ?? [];
                if (!is_array($types)) {
                    continue;
                }

                if (in_array('administrative_area_level_2', $types, true) || in_array('locality', $types, true)) {
                    $city = (string) ($component['long_name'] ?? $city);
                }

                if (in_array('administrative_area_level_1', $types, true)) {
                    $state = strtoupper(trim((string) ($component['short_name'] ?? '')));
                }

                if (in_array('sublocality', $types, true) || in_array('sublocality_level_1', $types, true) || in_array('neighborhood', $types, true)) {
                    $bairro = (string) ($component['long_name'] ?? $bairro);
                }
            }
        }

        if ($state !== '' && $state !== self::TARGET_STATE) {
            return null;
        }

        return [
            'lat' => (float) $location['lat'],
            'lng' => (float) $location['lng'],
            'city' => $this->normalizeText($city),
            'bairro' => strtoupper(trim($bairro)),
        ];
    }

    private function httpGet(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return null;
            }

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_CONNECTTIMEOUT => 5,
            ]);

            $result = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if (!is_string($result) || $httpCode < 200 || $httpCode >= 300) {
                return null;
            }

            return $result;
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 8,
            ],
        ]);

        $result = @file_get_contents($url, false, $context);
        return is_string($result) ? $result : null;
    }

    private function extractBairro(string $address): string
    {
        $normalized = strtoupper(trim($address));

        if ($normalized === '') {
            return '';
        }

        if (preg_match('/BAIRRO\s+([^,\-]+)/i', $normalized, $matches) === 1) {
            return trim((string) $matches[1]);
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $normalized)), static function ($value): bool {
            return $value !== '';
        }));

        if (count($parts) >= 2) {
            return $parts[1];
        }

        if (count($parts) === 1) {
            $dashParts = array_values(array_filter(array_map('trim', explode('-', $parts[0])), static function ($value): bool {
                return $value !== '';
            }));
            if (count($dashParts) >= 2) {
                return $dashParts[1];
            }
        }

        return '';
    }

    private function isAddressFromTargetCity(string $address): bool
    {
        $normalized = $this->normalizeText($address);
        return strpos($normalized, self::TARGET_CITY) !== false;
    }

    private function normalizeText(string $value): string
    {
        $value = strtoupper(trim($value));
        if ($value === '') {
            return '';
        }

        $replaceFrom = ['A', 'A', 'A', 'A', 'A', 'A', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'C'];
        $replaceTo =   ['A', 'A', 'A', 'A', 'A', 'A', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'C'];

        $value = str_replace(
            ['Á', 'À', 'Â', 'Ã', 'Ä', 'Å', 'É', 'È', 'Ê', 'Ë', 'Í', 'Ì', 'Î', 'Ï', 'Ó', 'Ò', 'Ô', 'Õ', 'Ö', 'Ú', 'Ù', 'Û', 'Ü', 'Ç'],
            $replaceFrom,
            $value
        );

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }
}

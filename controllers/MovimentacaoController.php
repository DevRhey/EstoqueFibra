<?php
require_once __DIR__ . '/../models/Movimentacao.php';
require_once __DIR__ . '/../models/Equipamento.php';
require_once __DIR__ . '/../models/Tecnico.php';
require_once __DIR__ . '/../config/helpers.php';

class MovimentacaoController
{
    private Movimentacao $movimentacaoModel;
    private Equipamento $equipamentoModel;
    private Tecnico $tecnicoModel;

    public function __construct()
    {
        $this->movimentacaoModel = new Movimentacao();
        $this->equipamentoModel = new Equipamento();
        $this->tecnicoModel = new Tecnico();
    }

    public function index(?string $selectedDate = null): array
    {
        $selectedDate = $this->normalizeDate($selectedDate) ?? date('Y-m-d');
        $cardsTecnicos = $this->movimentacaoModel->reportCardsTecnicos($selectedDate);
        $alertasUsoTeste = $this->movimentacaoModel->reportAlertasUsoTeste($selectedDate);
        $recolhimentosSemLastro = $this->movimentacaoModel->reportRecolhimentosSemLastro($selectedDate);
        $integrityIssues = $this->movimentacaoModel->reportMovementIntegrityIssues($selectedDate);

        $usageApply = isset($_GET['usage_apply']) && (string) $_GET['usage_apply'] === '1';
        $usageTecnicoId = (int) ($_GET['usage_tecnico_id'] ?? 0);
        $usageEquipmentType = sanitizeInput((string) ($_GET['usage_equipment_type'] ?? ''));
        $usageStartDefault = date('Y-m-d', strtotime($selectedDate . ' -6 days'));
        $usageEndDefault = $selectedDate;
        $usageStart = $this->normalizeDate((string) ($_GET['usage_start'] ?? '')) ?? $usageStartDefault;
        $usageEnd = $this->normalizeDate((string) ($_GET['usage_end'] ?? '')) ?? $usageEndDefault;

        if ($usageStart > $usageEnd) {
            [$usageStart, $usageEnd] = [$usageEnd, $usageStart];
        }

        $usageReport = [];
        if ($usageApply) {
            $usageReport = $this->movimentacaoModel->reportUsoPorTecnicoPeriodo(
                $usageTecnicoId > 0 ? $usageTecnicoId : null,
                $usageStart,
                $usageEnd,
                $usageEquipmentType !== '' ? $usageEquipmentType : null
            );
        }

        $usageSummary = [
            'total_itens' => count($usageReport),
            'total_quantidade' => 0,
            'total_registros' => 0,
            'dias_periodo' => max(
                1,
                (int) ((new DateTimeImmutable($usageStart))->diff(new DateTimeImmutable($usageEnd))->days ?? 0) + 1
            ),
        ];

        foreach ($usageReport as $row) {
            $usageSummary['total_quantidade'] += (int) ($row['total_usado'] ?? 0);
            $usageSummary['total_registros'] += (int) ($row['total_registros'] ?? 0);
        }

        return [
            'movimentacoes' => $this->movimentacaoModel->allWithRelations($selectedDate),
            'equipamentos' => $this->equipamentoModel->all(),
            'tecnicos' => $this->tecnicoModel->all(),
            'cardsTecnicos' => $cardsTecnicos,
            'alertasUsoTeste' => $alertasUsoTeste,
            'recolhimentosSemLastro' => $recolhimentosSemLastro,
            'integrityIssues' => $integrityIssues,
            'selectedDate' => $selectedDate,
            'usageFilters' => [
                'apply' => $usageApply,
                'tecnico_id' => $usageTecnicoId,
                'equipamento_tipo' => $usageEquipmentType,
                'start' => $usageStart,
                'end' => $usageEnd,
            ],
            'usageReport' => $usageReport,
            'usageSummary' => $usageSummary,
            'automation' => $this->automationAlerts($cardsTecnicos, $alertasUsoTeste),
        ];
    }

    public function dashboard(?string $selectedDate = null): array
    {
        $selectedDate = $this->normalizeDate($selectedDate) ?? date('Y-m-d');
        $movimentacoesDia = $this->movimentacaoModel->allWithRelations($selectedDate);
        $cardsTecnicos = $this->movimentacaoModel->reportCardsTecnicos($selectedDate);

        $resumoDia = [
            'total' => count($movimentacoesDia),
            'entrega' => 0,
            'uso' => 0,
            'uso_teste' => 0,
            'devolucao' => 0,
            'recolhimento' => 0,
            'recolhimento_defeito' => 0,
            'tecnicos_ativos' => 0,
        ];

        $tecnicosAtivos = [];
        foreach ($movimentacoesDia as $mov) {
            $tipo = $mov['tipo'] ?? '';

            if (isset($resumoDia[$tipo])) {
                $resumoDia[$tipo]++;
            }

            $tecnicoNome = trim((string) ($mov['tecnico_nome'] ?? ''));
            if ($tecnicoNome !== '') {
                $tecnicosAtivos[$tecnicoNome] = true;
            }
        }
        $resumoDia['tecnicos_ativos'] = count($tecnicosAtivos);

        return [
            'movimentacoes' => $movimentacoesDia,
            'equipamentos' => $this->equipamentoModel->all(),
            'tecnicos' => $this->tecnicoModel->all(),
            'cardsTecnicos' => $cardsTecnicos,
            'selectedDate' => $selectedDate,
            'resumoDia' => $resumoDia,
            'automation' => $this->automationAlerts($cardsTecnicos),
        ];
    }

    public function store(array $data): void
    {
        $cardsTecnicosCache = null;

        $tecnicoId = (int) ($data['tecnico_id'] ?? 0);
        $equipamentoId = (int) ($data['equipamento_id'] ?? 0);
        $quantidade = (int) ($data['quantidade'] ?? 0);
        $tipo = sanitizeInput($data['tipo'] ?? '');
        $dataMovimentacaoRaw = (string) ($data['data_movimentacao'] ?? '');
        $dataMovimentacaoLocalRaw = (string) ($data['data_movimentacao_local'] ?? '');
        $dataMovimentacao = $this->normalizeDate($dataMovimentacaoRaw);
        $dataMovimentacaoLocal = $this->normalizeDateTime($dataMovimentacaoLocalRaw);
        $selectedDate = $this->normalizeDate((string) ($data['selected_date'] ?? ''));
        $returnRoute = sanitizeInput((string) ($data['return_route'] ?? 'movimentacoes'));
        $localUso = sanitizeInput($data['local_uso'] ?? '');
        $observacoes = sanitizeInput($data['observacoes'] ?? '');
        $motivoDefeito = sanitizeInput((string) ($data['motivo_defeito'] ?? ''));
        $serialEquipamento = sanitizeInput((string) ($data['serial_equipamento'] ?? ''));
        $itensJson = $data['itens_json'] ?? '';

        if (!in_array($returnRoute, ['movimentacoes', 'dashboard'], true)) {
            $returnRoute = 'movimentacoes';
        }

        $tiposValidos = ['entrega', 'devolucao', 'uso', 'uso_teste', 'recolhimento', 'recolhimento_defeito', 'saida', 'entrada'];

        $tecnicoObrigatorio = in_array($tipo, $tiposValidos, true);
        $tecnicoIdFinal = $tecnicoId > 0 ? $tecnicoId : null;

        if ($tecnicoObrigatorio && $tecnicoIdFinal === null) {
            setFlash('danger', 'Selecione um tecnico para registrar esta movimentacao.');
            $this->redirectRouteWithDate($returnRoute, $selectedDate);
        }

        if ($tipo === 'recolhimento_defeito' && $motivoDefeito === '') {
            setFlash('danger', 'Informe o motivo ou defeito do equipamento recolhido com defeito.');
            $this->redirectRouteWithDate($returnRoute, $selectedDate);
        }

        if ($tipo === 'recolhimento_defeito') {
            $partesObservacao = [];
            if (trim($serialEquipamento) !== '') {
                $partesObservacao[] = 'Serial: ' . $serialEquipamento;
            }

            $partesObservacao[] = 'Defeito: ' . $motivoDefeito;

            if (trim($observacoes) !== '') {
                $partesObservacao[] = $observacoes;
            }

            $observacoes = implode(' | ', $partesObservacao);
        }

        if ($dataMovimentacaoRaw !== '' && $dataMovimentacao === null) {
            setFlash('danger', 'Data de movimentacao invalida. Use o formato AAAA-MM-DD.');
            $this->redirectRouteWithDate($returnRoute, $selectedDate);
        }

        if ($dataMovimentacaoLocalRaw !== '' && $dataMovimentacaoLocal === null) {
            setFlash('danger', 'Data/hora local invalida para a movimentacao.');
            $this->redirectRouteWithDate($returnRoute, $selectedDate);
        }

        $dataMovimentacaoFinal = $dataMovimentacaoLocal ?? $dataMovimentacao;

        if (is_string($itensJson) && trim($itensJson) !== '') {
            $itens = json_decode($itensJson, true);

            if (!is_array($itens) || empty($itens) || !in_array($tipo, $tiposValidos, true)) {
                setFlash('danger', 'Dados invalidos para registrar lote de movimentacoes.');
                $this->redirectRouteWithDate($returnRoute, $selectedDate);
            }

            try {
                $itensSanitizados = [];
                foreach ($itens as $index => $item) {
                    if (!is_array($item)) {
                        throw new RuntimeException('Item de lote invalido na posicao ' . ($index + 1) . '.');
                    }

                    $itemEquipamentoId = (int) ($item['equipamento_id'] ?? 0);
                    $itemQuantidade = (int) ($item['quantidade'] ?? 0);
                    $itemLocalUso = sanitizeInput((string) ($item['local_uso'] ?? ''));
                    $itemObservacoes = sanitizeInput((string) ($item['observacoes'] ?? ''));

                    if ($tipo === 'recolhimento_defeito') {
                        $partesObservacaoItem = [];

                        if (trim($serialEquipamento) !== '') {
                            $partesObservacaoItem[] = 'Serial: ' . $serialEquipamento;
                        }

                        $partesObservacaoItem[] = 'Defeito: ' . $motivoDefeito;

                        if (trim($itemObservacoes) !== '') {
                            $partesObservacaoItem[] = $itemObservacoes;
                        }

                        $itemObservacoes = implode(' | ', $partesObservacaoItem);
                    }

                    if ($itemEquipamentoId <= 0 || $itemQuantidade <= 0) {
                        throw new RuntimeException('Item de lote invalido na posicao ' . ($index + 1) . '.');
                    }

                    if (($tipo === 'uso' || $tipo === 'uso_teste') && $itemLocalUso === '') {
                        throw new RuntimeException('Informe o local de uso no item ' . ($index + 1) . '.');
                    }

                    $itensSanitizados[] = [
                        'equipamento_id' => $itemEquipamentoId,
                        'quantidade' => $itemQuantidade,
                        'local_uso' => $itemLocalUso,
                        'observacoes' => $itemObservacoes,
                    ];
                }

                $processados = $this->movimentacaoModel->createBatch($tecnicoIdFinal, $tipo, $itensSanitizados, $dataMovimentacaoFinal);

                setFlash('success', 'Lote registrado com sucesso: ' . $processados . ' item(ns).');
            } catch (Throwable $e) {
                setFlash('danger', 'Falha ao registrar lote: ' . $e->getMessage());
            }

            $this->redirectRouteWithDate($returnRoute, $selectedDate);
        }

        // Fallback: se uso/devolucao vier sem equipamento e houver somente 1 item em mao, seleciona automaticamente.
        if ($tecnicoId > 0 && $equipamentoId <= 0 && in_array($tipo, ['uso', 'uso_teste', 'devolucao'], true)) {
            $cards = $cardsTecnicosCache ?? $this->movimentacaoModel->reportCardsTecnicos();
            $cardsTecnicosCache = $cards;
            foreach ($cards as $card) {
                if ((int) ($card['tecnico_id'] ?? 0) !== $tecnicoId) {
                    continue;
                }

                $itensEmMao = $card['equipamentos_mao'] ?? [];
                if (count($itensEmMao) === 1) {
                    $equipamentoId = (int) ($itensEmMao[0]['equipamento_id'] ?? 0);
                }
                break;
            }
        }

        if ($tecnicoId > 0 && $equipamentoId <= 0 && in_array($tipo, ['uso', 'uso_teste', 'devolucao'], true)) {
            $cards = $cardsTecnicosCache ?? $this->movimentacaoModel->reportCardsTecnicos();
            $cardsTecnicosCache = $cards;
            $itensEmMao = [];
            $tecnicoNome = 'Tecnico selecionado';

            foreach ($cards as $card) {
                if ((int) ($card['tecnico_id'] ?? 0) !== $tecnicoId) {
                    continue;
                }

                $itensEmMao = is_array($card['equipamentos_mao'] ?? null) ? $card['equipamentos_mao'] : [];
                $tecnicoNome = trim((string) ($card['tecnico_nome'] ?? '')) !== ''
                    ? (string) $card['tecnico_nome']
                    : $tecnicoNome;
                break;
            }

            if (count($itensEmMao) === 0) {
                setFlash('danger', 'Nao foi possivel registrar esta movimentacao. ' . $tecnicoNome . ' nao possui equipamentos em mao para este tipo de operacao. Faca uma entrega antes de registrar o uso.');
                $this->redirectRouteWithDate($returnRoute, $selectedDate);
            }

            $itensDisponiveis = [];
            foreach ($itensEmMao as $item) {
                $nome = trim((string) ($item['nome'] ?? ''));
                $saldo = (int) ($item['saldo_mao'] ?? 0);
                if ($nome !== '' && $saldo > 0) {
                    $itensDisponiveis[] = $nome . ' (qtd: ' . $saldo . ')';
                }

                if (count($itensDisponiveis) >= 4) {
                    break;
                }
            }

            $detalhesDisponiveis = '';
            if (!empty($itensDisponiveis)) {
                $detalhesDisponiveis = ' Itens em mao deste tecnico: ' . implode(', ', $itensDisponiveis) . '.';
            }

            setFlash('danger', 'Selecione um equipamento em mao de ' . $tecnicoNome . ' para registrar esta movimentacao.' . $detalhesDisponiveis . ' Se voce precisa usar conector, faca a entrega do conector para este tecnico primeiro.');
            $this->redirectRouteWithDate($returnRoute, $selectedDate);
        }

        if (($tecnicoObrigatorio && $tecnicoIdFinal === null) || $equipamentoId <= 0 || $quantidade <= 0 || !in_array($tipo, $tiposValidos, true)) {
            setFlash('danger', 'Dados invalidos para registrar movimentacao. Verifique tecnico, equipamento, tipo e quantidade.');
            $this->redirectRouteWithDate($returnRoute, $selectedDate);
        }

        if (($tipo === 'uso' || $tipo === 'uso_teste') && $localUso === '') {
            setFlash('danger', 'Informe onde o equipamento foi usado.');
            $this->redirectRouteWithDate($returnRoute, $selectedDate);
        }

        try {
            $this->movimentacaoModel->create(
                $tecnicoIdFinal,
                $equipamentoId,
                $quantidade,
                $tipo,
                $localUso !== '' ? $localUso : null,
                $observacoes !== '' ? $observacoes : null,
                $dataMovimentacaoFinal
            );
            setFlash('success', 'Movimentacao registrada com sucesso.');
        } catch (Throwable $e) {
            setFlash('danger', 'Falha ao registrar movimentacao: ' . $e->getMessage());
        }

        $this->redirectRouteWithDate($returnRoute, $selectedDate);
    }

    public function adjustHandBalance(array $data): void
    {
        $tecnicoId = (int) ($data['tecnico_id'] ?? 0);
        $equipamentoId = (int) ($data['equipamento_id'] ?? 0);
        $novoSaldo = (int) ($data['novo_saldo_mao'] ?? -1);
        $observacoes = sanitizeInput((string) ($data['observacoes'] ?? ''));
        $selectedDate = $this->normalizeDate((string) ($data['selected_date'] ?? ''));

        if ($tecnicoId <= 0 || $equipamentoId <= 0 || $novoSaldo < 0) {
            setFlash('danger', 'Dados invalidos para ajustar o saldo em mao.');
            $this->redirectDashboard($selectedDate);
        }

        try {
            $resultado = $this->movimentacaoModel->adjustHandBalance(
                $tecnicoId,
                $equipamentoId,
                $novoSaldo,
                $observacoes !== '' ? $observacoes : null
            );

            if (($resultado['alterado'] ?? false) === false) {
                setFlash('info', 'Nenhum ajuste necessario. O saldo em mao ja estava correto.');
                $this->redirectDashboard($selectedDate);
            }

            $tipo = (string) ($resultado['tipo_movimentacao'] ?? 'ajuste');
            $quantidade = (int) ($resultado['quantidade_ajustada'] ?? 0);
            setFlash('success', 'Saldo em mao ajustado com sucesso. Movimentacao registrada: ' . $tipo . ' de ' . $quantidade . ' unidade(s).');
        } catch (Throwable $e) {
            setFlash('danger', 'Falha ao ajustar saldo em mao: ' . $e->getMessage());
        }

        $this->redirectDashboard($selectedDate);
    }

    public function destroyUsage(array $data): void
    {
        $movimentacaoId = (int) ($data['movimentacao_id'] ?? 0);
        $tipo = sanitizeInput((string) ($data['tipo'] ?? ''));
        $returnRoute = sanitizeInput((string) ($data['return_route'] ?? 'movimentacoes'));
        $tecnicoId = (int) ($data['tecnico_id'] ?? 0);
        $selectedDate = $this->normalizeDate((string) ($data['selected_date'] ?? ''));

        if (!in_array($returnRoute, ['movimentacoes', 'tecnico_historico'], true)) {
            $returnRoute = 'movimentacoes';
        }

        if ($movimentacaoId <= 0 || !in_array($tipo, ['uso', 'uso_teste'], true)) {
            setFlash('danger', 'Dados invalidos para excluir o uso do tecnico.');
            $this->redirectAfterUsageDelete($returnRoute, $tecnicoId, $selectedDate);
        }

        try {
            $this->movimentacaoModel->deleteUsageMovement($movimentacaoId, $tipo);
            setFlash('success', 'Registro de uso excluido com sucesso.');
        } catch (Throwable $e) {
            setFlash('danger', 'Falha ao excluir registro de uso: ' . $e->getMessage());
        }

        $this->redirectAfterUsageDelete($returnRoute, $tecnicoId, $selectedDate);
    }

    public function destroyDelivery(array $data): void
    {
        $movimentacaoId = (int) ($data['movimentacao_id'] ?? 0);
        $tipo = sanitizeInput((string) ($data['tipo'] ?? ''));
        $returnRoute = sanitizeInput((string) ($data['return_route'] ?? 'movimentacoes'));
        $tecnicoId = (int) ($data['tecnico_id'] ?? 0);
        $selectedDate = $this->normalizeDate((string) ($data['selected_date'] ?? ''));

        if (!in_array($returnRoute, ['movimentacoes', 'tecnico_historico'], true)) {
            $returnRoute = 'movimentacoes';
        }

        if ($movimentacaoId <= 0 || !in_array($tipo, ['entrega', 'saida'], true)) {
            setFlash('danger', 'Dados invalidos para excluir a entrega do tecnico.');
            $this->redirectAfterUsageDelete($returnRoute, $tecnicoId, $selectedDate);
        }

        try {
            $this->movimentacaoModel->deleteDeliveryMovement($movimentacaoId, $tipo);
            setFlash('success', 'Registro de entrega excluido com sucesso. Estoque recomposto automaticamente.');
        } catch (Throwable $e) {
            setFlash('danger', 'Falha ao excluir registro de entrega: ' . $e->getMessage());
        }

        $this->redirectAfterUsageDelete($returnRoute, $tecnicoId, $selectedDate);
    }

    public function updateUsage(array $data): void
    {
        $movimentacaoId = (int) ($data['movimentacao_id'] ?? 0);
        $tipo = sanitizeInput((string) ($data['tipo'] ?? ''));
        $novaQuantidade = (int) ($data['quantidade'] ?? 0);
        $localUso = sanitizeInput((string) ($data['local_uso'] ?? ''));
        $observacoes = sanitizeInput((string) ($data['observacoes'] ?? ''));
        $selectedDate = $this->normalizeDate((string) ($data['selected_date'] ?? ''));

        if ($movimentacaoId <= 0) {
            setFlash('danger', 'ID de movimentacao invalido.');
            $this->redirectMovimentacoes($selectedDate);
            return;
        }

        if (!in_array($tipo, ['uso', 'uso_teste'], true)) {
            setFlash('danger', 'Tipo de movimentacao invalido.');
            $this->redirectMovimentacoes($selectedDate);
            return;
        }

        if ($novaQuantidade <= 0) {
            setFlash('danger', 'Informe uma quantidade valida (maior que zero).');
            $this->redirectMovimentacoes($selectedDate);
            return;
        }

        if ($localUso === '' || trim($localUso) === '') {
            setFlash('danger', 'Informe o local de uso.');
            $this->redirectMovimentacoes($selectedDate);
            return;
        }

        try {
            $this->movimentacaoModel->updateUsageMovement(
                $movimentacaoId,
                $tipo,
                $novaQuantidade,
                $localUso,
                $observacoes !== '' ? $observacoes : null
            );
            setFlash('success', 'Registro de uso atualizado com sucesso.');
        } catch (Throwable $e) {
            setFlash('danger', 'Falha ao atualizar registro de uso: ' . $e->getMessage());
        }

        $this->redirectMovimentacoes($selectedDate);
    }

    public function convertTestToUsage(array $data): void
    {
        $movimentacaoId = (int) ($data['movimentacao_id'] ?? 0);
        $localUso = sanitizeInput((string) ($data['local_uso'] ?? ''));
        $observacoes = sanitizeInput((string) ($data['observacoes'] ?? ''));
        $selectedDate = $this->normalizeDate((string) ($data['selected_date'] ?? ''));

        if ($movimentacaoId <= 0) {
            setFlash('danger', 'Registro de teste invalido para definir uso.');
            $this->redirectTestes($selectedDate);
        }

        try {
            $this->movimentacaoModel->convertTestToUsage(
                $movimentacaoId,
                $localUso !== '' ? $localUso : null,
                $observacoes !== '' ? $observacoes : null
            );
            setFlash('success', 'Uso em teste definido como uso no cliente com sucesso.');
        } catch (Throwable $e) {
            setFlash('danger', 'Falha ao definir uso no cliente: ' . $e->getMessage());
        }

        $this->redirectTestes($selectedDate);
    }

    public function addTestAttempt(array $data): void
    {
        $movimentacaoId = (int) ($data['movimentacao_id'] ?? 0);
        $categoria = sanitizeInput((string) ($data['tipo_tentativa'] ?? 'geral'));
        $descricao = sanitizeInput((string) ($data['nova_tentativa'] ?? ''));
        $selectedDate = $this->normalizeDate((string) ($data['selected_date'] ?? ''));

        if ($movimentacaoId <= 0) {
            setFlash('danger', 'Registro de teste invalido para adicionar tentativa.');
            $this->redirectTestes($selectedDate);
        }

        try {
            $this->movimentacaoModel->appendTestAttemptHistory($movimentacaoId, $categoria, $descricao);
            setFlash('success', 'Tentativa registrada com sucesso no historico do teste.');
        } catch (Throwable $e) {
            setFlash('danger', 'Falha ao registrar tentativa: ' . $e->getMessage());
        }

        $this->redirectTestes($selectedDate);
    }

    private function redirectTestes(?string $selectedDate): void
    {
        if ($selectedDate !== null) {
            header('Location: index.php?route=testes&date=' . urlencode($selectedDate));
            exit;
        }

        redirect('testes');
    }

    private function redirectAfterUsageDelete(string $returnRoute, int $tecnicoId, ?string $selectedDate = null): void
    {
        if ($returnRoute === 'tecnico_historico' && $tecnicoId > 0) {
            if ($selectedDate !== null) {
                header('Location: index.php?route=tecnico_historico&tecnico_id=' . $tecnicoId . '&date=' . urlencode($selectedDate));
                exit;
            }

            header('Location: index.php?route=tecnico_historico&tecnico_id=' . $tecnicoId);
            exit;
        }

        $this->redirectMovimentacoes($selectedDate);
    }

    private function redirectMovimentacoes(?string $selectedDate): void
    {
        if ($selectedDate !== null) {
            header('Location: index.php?route=movimentacoes&date=' . urlencode($selectedDate));
            exit;
        }

        redirect('movimentacoes');
    }

    private function redirectRouteWithDate(string $route, ?string $selectedDate): void
    {
        if ($route === 'dashboard') {
            $this->redirectDashboard($selectedDate);
        }

        $this->redirectMovimentacoes($selectedDate);
    }

    public function reports(): array
    {
        $cardsTecnicos = $this->movimentacaoModel->reportCardsTecnicos();

        return [
            'consumoTecnico' => $this->movimentacaoModel->reportConsumoPorTecnico(),
            'equipMaisUsados' => $this->movimentacaoModel->reportEquipamentosMaisUsados(),
            'estoqueAtual' => $this->movimentacaoModel->reportEstoqueAtual(),
            'reposicao' => $this->movimentacaoModel->reportReposicaoDiaria(),
            'cardsTecnicos' => $cardsTecnicos,
            'automation' => $this->automationAlerts($cardsTecnicos),
        ];
    }

    public function defectiveEquipments(): array
    {
        $selectedDate = $this->normalizeDate((string) ($_GET['date'] ?? ''));
        $tecnicoId = (int) ($_GET['tecnico_id'] ?? 0);
        $query = sanitizeInput((string) ($_GET['q'] ?? ''));

        $registros = $this->movimentacaoModel->reportEquipamentosComDefeito(
            $selectedDate,
            $tecnicoId > 0 ? $tecnicoId : null,
            $query !== '' ? $query : null
        );

        $equipamentosUnicos = [];
        $tecnicosUnicos = [];
        $quantidadeTotal = 0;

        foreach ($registros as $row) {
            $quantidadeTotal += (int) ($row['quantidade'] ?? 0);

            $equipKey = (string) (($row['equipamento_nome'] ?? 'Equipamento') . '|' . ($row['equipamento_tipo'] ?? 'indefinido'));
            $equipamentosUnicos[$equipKey] = true;

            $tecKey = (string) ($row['tecnico_nome'] ?? 'Sem tecnico');
            $tecnicosUnicos[$tecKey] = true;
        }

        return [
            'registros' => $registros,
            'tecnicos' => $this->tecnicoModel->all(),
            'filters' => [
                'date' => $selectedDate,
                'tecnico_id' => $tecnicoId,
                'q' => $query,
            ],
            'resumo' => [
                'registros' => count($registros),
                'quantidade_total' => $quantidadeTotal,
                'tecnicos' => count($tecnicosUnicos),
                'equipamentos' => count($equipamentosUnicos),
            ],
        ];
    }

    public function purchaseSupport(): array
    {
        return $this->movimentacaoModel->getPurchaseSupportData();
    }

    public function savePurchaseSupport(array $data): void
    {
        $config = [
            'prazo_reposicao_dias' => max(1, (int) ($data['prazo_reposicao_dias'] ?? 1)),
        ];

        $consumoItem = [];
        $consumoItemRaw = $data['consumo_item'] ?? [];
        if (is_array($consumoItemRaw)) {
            foreach ($consumoItemRaw as $equipamentoIdRaw => $consumoRaw) {
                $equipamentoId = (int) $equipamentoIdRaw;
                if ($equipamentoId <= 0) {
                    continue;
                }

                $consumoItem[$equipamentoId] = max(0, (int) $consumoRaw);
            }
        }

        try {
            $this->movimentacaoModel->savePurchaseSupportConfig($config, $consumoItem);
            setFlash('success', 'Configuracoes de apoio a compra salvas com sucesso.');
        } catch (Throwable $e) {
            setFlash('danger', 'Falha ao salvar configuracoes de apoio a compra: ' . $e->getMessage());
        }

        redirect('apoio_compra');
    }

    public function testes(): array
    {
        $selectedDate = $this->normalizeDate((string) ($_GET['date'] ?? '')) ?? date('Y-m-d');
        $itens = $this->movimentacaoModel->reportAlertasUsoTeste($selectedDate);

        $resumo = [
            'total' => count($itens),
            'vencidos' => 0,
            'vence_hoje' => 0,
            'proximos_3_dias' => 0,
            'em_andamento' => 0,
        ];

        $porTecnico = [];
        foreach ($itens as &$item) {
            $dias = (int) ($item['dias_restantes'] ?? 0);

            if ($dias < 0) {
                $resumo['vencidos']++;
                $item['faixa'] = 'vencido';
                $item['previsao'] = 'Acao imediata: recolher do cliente para o estoque.';
                $item['badge_class'] = 'text-bg-danger';
            } elseif ($dias === 0) {
                $resumo['vence_hoje']++;
                $item['faixa'] = 'vence_hoje';
                $item['previsao'] = 'Vence hoje: confirmar resultado e recolher o equipamento.';
                $item['badge_class'] = 'text-bg-warning';
            } elseif ($dias <= 3) {
                $resumo['proximos_3_dias']++;
                $item['faixa'] = 'proximo_vencimento';
                $item['previsao'] = 'Vencimento proximo: preparar agenda de recolhimento.';
                $item['badge_class'] = 'text-bg-info';
            } else {
                $resumo['em_andamento']++;
                $item['faixa'] = 'em_andamento';
                $item['previsao'] = 'Teste em andamento: monitorar ate a data de vencimento.';
                $item['badge_class'] = 'text-bg-secondary';
            }

            $tecnicoNome = (string) ($item['tecnico_nome'] ?? 'Sem tecnico');
            $porTecnico[$tecnicoNome] = ($porTecnico[$tecnicoNome] ?? 0) + 1;

            $item = $this->applyAiHeuristicToTeste($item);
        }
        unset($item);

        arsort($porTecnico);

        $alertasAutomacao = $this->automationAlerts(null, $itens);

        return [
            'itens' => $itens,
            'resumo' => $resumo,
            'porTecnico' => $porTecnico,
            'automation' => $alertasAutomacao,
            'selectedDate' => $selectedDate,
        ];
    }

    public function automationAlerts(?array $cardsTecnicos = null, ?array $itensTeste = null): array
    {
        $cardsTecnicos = $cardsTecnicos ?? $this->movimentacaoModel->reportCardsTecnicos();
        $itensTeste = $itensTeste ?? $this->movimentacaoModel->reportAlertasUsoTeste();

        $reposicao = $this->buildReplenishmentAlerts($cardsTecnicos);
        $testes = $this->buildTestAiAlerts($itensTeste);

        return [
            'reposicao' => $reposicao,
            'testes' => $testes,
            'resumo' => [
                'total_alertas' => count($reposicao['itens']) + count($testes['itens']),
                'reposicao_pendente_total' => (int) ($reposicao['resumo']['pendente_total'] ?? 0),
                'testes_criticos' => (int) ($testes['resumo']['critica'] ?? 0),
            ],
        ];
    }

    private function buildReplenishmentAlerts(array $cardsTecnicos): array
    {
        $itens = [];
        $pendenteTotal = 0;

        foreach ($cardsTecnicos as $card) {
            $reporTotal = (int) ($card['repor_total'] ?? 0);
            if ($reporTotal <= 0) {
                continue;
            }

            $pendenteTotal += $reporTotal;
            $nivel = 'media';
            if ($reporTotal >= 8) {
                $nivel = 'critica';
            } elseif ($reporTotal >= 4) {
                $nivel = 'alta';
            }

            $itens[] = [
                'tecnico_id' => (int) ($card['tecnico_id'] ?? 0),
                'tecnico_nome' => (string) ($card['tecnico_nome'] ?? 'Sem tecnico'),
                'repor_total' => $reporTotal,
                'nivel' => $nivel,
                'mensagem' => 'Reposicao pendente de ' . $reporTotal . ' item(ns).',
            ];
        }

        usort($itens, static function (array $a, array $b): int {
            if ((int) $a['repor_total'] !== (int) $b['repor_total']) {
                return (int) $b['repor_total'] <=> (int) $a['repor_total'];
            }

            return strcmp((string) $a['tecnico_nome'], (string) $b['tecnico_nome']);
        });

        return [
            'resumo' => [
                'tecnicos_com_pendencia' => count($itens),
                'pendente_total' => $pendenteTotal,
            ],
            'itens' => array_slice($itens, 0, 8),
        ];
    }

    private function buildTestAiAlerts(array $itensTeste): array
    {
        $itens = [];
        $resumo = [
            'critica' => 0,
            'alta' => 0,
            'media' => 0,
            'baixa' => 0,
        ];

        foreach ($itensTeste as $item) {
            $itemAi = $this->applyAiHeuristicToTeste($item);
            $prioridade = (string) ($itemAi['ai_prioridade'] ?? 'baixa');
            if (isset($resumo[$prioridade])) {
                $resumo[$prioridade]++;
            }

            if ($prioridade === 'baixa') {
                continue;
            }

            $itens[] = [
                'tecnico_id' => (int) ($itemAi['tecnico_id'] ?? 0),
                'tecnico_nome' => (string) ($itemAi['tecnico_nome'] ?? 'Sem tecnico'),
                'equipamento_nome' => (string) ($itemAi['equipamento_nome'] ?? 'Equipamento'),
                'ai_prioridade' => $prioridade,
                'ai_score' => (int) ($itemAi['ai_score'] ?? 0),
                'ai_acao' => (string) ($itemAi['ai_acao'] ?? 'Monitorar diariamente.'),
            ];
        }

        usort($itens, static function (array $a, array $b): int {
            if ((int) $a['ai_score'] !== (int) $b['ai_score']) {
                return (int) $b['ai_score'] <=> (int) $a['ai_score'];
            }

            return strcmp((string) $a['tecnico_nome'], (string) $b['tecnico_nome']);
        });

        return [
            'resumo' => $resumo,
            'itens' => array_slice($itens, 0, 8),
        ];
    }

    private function applyAiHeuristicToTeste(array $item): array
    {
        $dias = (int) ($item['dias_restantes'] ?? 0);
        $quantidade = max(1, (int) ($item['quantidade'] ?? 1));
        $temLocal = trim((string) ($item['local_uso'] ?? '')) !== '';
        $temObs = trim((string) ($item['observacoes'] ?? '')) !== '';

        $score = 5;
        if ($dias < 0) {
            $score += 55;
        } elseif ($dias === 0) {
            $score += 42;
        } elseif ($dias <= 1) {
            $score += 28;
        } elseif ($dias <= 3) {
            $score += 16;
        } else {
            $score += 8;
        }

        $score += min(20, $quantidade * 4);

        if (!$temLocal) {
            $score += 8;
        }

        if (!$temObs) {
            $score += 4;
        }

        $prioridade = 'baixa';
        if ($score >= 70) {
            $prioridade = 'critica';
        } elseif ($score >= 50) {
            $prioridade = 'alta';
        } elseif ($score >= 30) {
            $prioridade = 'media';
        }

        $acao = 'Monitorar diariamente ate o vencimento.';
        if ($prioridade === 'critica') {
            $acao = 'Acionar recolhimento imediato e validar retorno ao estoque hoje.';
        } elseif ($prioridade === 'alta') {
            $acao = 'Agendar recolhimento para hoje e confirmar com o tecnico.';
        } elseif ($prioridade === 'media') {
            $acao = 'Reservar janela de recolhimento para as proximas 24h.';
        }

        $item['ai_score'] = $score;
        $item['ai_prioridade'] = $prioridade;
        $item['ai_acao'] = $acao;

        return $item;
    }

    private function normalizeDate(?string $date): ?string
    {
        if ($date === null) {
            return null;
        }

        $date = trim($date);
        if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $date));
        if (!checkdate($month, $day, $year)) {
            return null;
        }

        return $date;
    }

    private function normalizeDateTime(?string $dateTime): ?string
    {
        if ($dateTime === null) {
            return null;
        }

        $dateTime = trim($dateTime);
        if ($dateTime === '') {
            return null;
        }

        if (preg_match('/^(\d{4}-\d{2}-\d{2})[T\s](\d{2}):(\d{2})(?::(\d{2}))?$/', $dateTime, $matches) !== 1) {
            return null;
        }

        $datePart = $matches[1];
        $hour = (int) $matches[2];
        $minute = (int) $matches[3];
        $second = isset($matches[4]) && $matches[4] !== '' ? (int) $matches[4] : 0;

        $normalizedDate = $this->normalizeDate($datePart);
        if ($normalizedDate === null) {
            return null;
        }

        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59 || $second < 0 || $second > 59) {
            return null;
        }

        return sprintf('%s %02d:%02d:%02d', $normalizedDate, $hour, $minute, $second);
    }

    private function redirectDashboard(?string $selectedDate): void
    {
        if ($selectedDate !== null) {
            header('Location: index.php?route=dashboard&date=' . urlencode($selectedDate));
            exit;
        }

        redirect('dashboard');
    }
}

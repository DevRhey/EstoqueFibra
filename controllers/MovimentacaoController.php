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

    public function index(): array
    {
        $cardsTecnicos = $this->movimentacaoModel->reportCardsTecnicos();
        $alertasUsoTeste = $this->movimentacaoModel->reportAlertasUsoTeste();

        return [
            'movimentacoes' => $this->movimentacaoModel->allWithRelations(),
            'equipamentos' => $this->equipamentoModel->all(),
            'tecnicos' => $this->tecnicoModel->all(),
            'cardsTecnicos' => $cardsTecnicos,
            'alertasUsoTeste' => $alertasUsoTeste,
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
        $tecnicoId = (int) ($data['tecnico_id'] ?? 0);
        $equipamentoId = (int) ($data['equipamento_id'] ?? 0);
        $quantidade = (int) ($data['quantidade'] ?? 0);
        $tipo = sanitize($data['tipo'] ?? '');
        $localUso = sanitize($data['local_uso'] ?? '');
        $observacoes = sanitize($data['observacoes'] ?? '');
        $itensJson = $data['itens_json'] ?? '';

        $tiposValidos = ['entrega', 'devolucao', 'uso', 'uso_teste', 'recolhimento', 'saida', 'entrada'];

        if (is_string($itensJson) && trim($itensJson) !== '') {
            $itens = json_decode($itensJson, true);

            if (!is_array($itens) || empty($itens) || $tecnicoId <= 0 || !in_array($tipo, $tiposValidos, true)) {
                setFlash('danger', 'Dados invalidos para registrar lote de movimentacoes.');
                redirect('movimentacoes');
            }

            try {
                $itensSanitizados = [];
                foreach ($itens as $index => $item) {
                    if (!is_array($item)) {
                        throw new RuntimeException('Item de lote invalido na posicao ' . ($index + 1) . '.');
                    }

                    $itemEquipamentoId = (int) ($item['equipamento_id'] ?? 0);
                    $itemQuantidade = (int) ($item['quantidade'] ?? 0);
                    $itemLocalUso = sanitize((string) ($item['local_uso'] ?? ''));
                    $itemObservacoes = sanitize((string) ($item['observacoes'] ?? ''));

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

                $processados = $this->movimentacaoModel->createBatch($tecnicoId, $tipo, $itensSanitizados);

                setFlash('success', 'Lote registrado com sucesso: ' . $processados . ' item(ns).');
            } catch (Throwable $e) {
                setFlash('danger', 'Falha ao registrar lote: ' . $e->getMessage());
            }

            redirect('movimentacoes');
        }

        // Fallback: se uso/devolucao vier sem equipamento e houver somente 1 item em mao, seleciona automaticamente.
        if ($tecnicoId > 0 && $equipamentoId <= 0 && in_array($tipo, ['uso', 'uso_teste', 'devolucao'], true)) {
            $cards = $this->movimentacaoModel->reportCardsTecnicos();
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

        if ($tecnicoId <= 0 || $equipamentoId <= 0 || $quantidade <= 0 || !in_array($tipo, $tiposValidos, true)) {
            setFlash('danger', 'Dados invalidos para registrar movimentacao. Verifique tecnico, equipamento, tipo e quantidade.');
            redirect('movimentacoes');
        }

        if (($tipo === 'uso' || $tipo === 'uso_teste') && $localUso === '') {
            setFlash('danger', 'Informe onde o equipamento foi usado.');
            redirect('movimentacoes');
        }

        try {
            $this->movimentacaoModel->create(
                $tecnicoId,
                $equipamentoId,
                $quantidade,
                $tipo,
                $localUso !== '' ? $localUso : null,
                $observacoes !== '' ? $observacoes : null
            );
            setFlash('success', 'Movimentacao registrada com sucesso.');
        } catch (Throwable $e) {
            setFlash('danger', 'Falha ao registrar movimentacao: ' . $e->getMessage());
        }

        redirect('movimentacoes');
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

    public function testes(): array
    {
        $itens = $this->movimentacaoModel->reportAlertasUsoTeste();

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
}

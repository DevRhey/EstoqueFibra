<?php
require_once __DIR__ . '/controllers/EquipamentoController.php';
require_once __DIR__ . '/controllers/TecnicoController.php';
require_once __DIR__ . '/controllers/MovimentacaoController.php';

function resolveRoute(string $route): array
{
    $equipamentoController = new EquipamentoController();
    $tecnicoController = new TecnicoController();
    $movimentacaoController = new MovimentacaoController();

    switch ($route) {
        case 'equipamentos':
            return [
                'view' => 'views/equipamentos.php',
                'data' => [
                    'equipamentos' => $equipamentoController->index(),
                ],
            ];

        case 'tecnicos':
            $tecnicos = $tecnicoController->index();
            $selectedTecnico = isset($_GET['tecnico_id']) ? (int) $_GET['tecnico_id'] : 0;

            return [
                'view' => 'views/tecnicos.php',
                'data' => [
                    'tecnicos' => $tecnicos,
                    'selectedTecnico' => $selectedTecnico,
                    'historico' => $tecnicoController->history($selectedTecnico),
                ],
            ];

        case 'tecnico_historico':
            $tecnicoId = isset($_GET['tecnico_id']) ? (int) $_GET['tecnico_id'] : 0;

            return
                [
                    'view' => 'views/tecnico-historico.php',
                    'data' => $tecnicoController->historyPage($tecnicoId),
                ];

        case 'movimentacoes':
            return [
                'view' => 'views/movimentacoes.php',
                'data' => $movimentacaoController->index(),
            ];

        case 'testes':
            return [
                'view' => 'views/testes.php',
                'data' => $movimentacaoController->testes(),
            ];

        case 'relatorios':
            return [
                'view' => 'views/relatorios.php',
                'data' => $movimentacaoController->reports(),
            ];

        default:
            $selectedDate = isset($_GET['date']) ? (string) $_GET['date'] : null;
            $movData = $movimentacaoController->dashboard($selectedDate);
            return [
                'view' => 'views/dashboard-gerencial.php',
                'data' => [
                    'equipamentos' => $movData['equipamentos'],
                    'tecnicos' => $movData['tecnicos'],
                    'cardsTecnicos' => $movData['cardsTecnicos'] ?? [],
                    'selectedDate' => $movData['selectedDate'] ?? date('Y-m-d'),
                    'resumoDia' => $movData['resumoDia'] ?? [],
                ],
            ];
    }
}

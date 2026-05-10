<?php
require_once __DIR__ . '/controllers/EquipamentoController.php';
require_once __DIR__ . '/controllers/TecnicoController.php';
require_once __DIR__ . '/controllers/MovimentacaoController.php';
require_once __DIR__ . '/controllers/LembreteController.php';
require_once __DIR__ . '/controllers/InadimplenciaController.php';

function resolveRoute(string $route): array
{
    $equipamentoController = new EquipamentoController();
    $tecnicoController = new TecnicoController();
    $movimentacaoController = new MovimentacaoController();
    $lembreteController = new LembreteController();
    $inadimplenciaController = new InadimplenciaController();

    switch ($route) {
        case 'equipamentos':
            return [
                'view' => 'views/equipamentos.php',
                'data' => [
                    'equipamentos' => $equipamentoController->index(),
                ],
            ];

        case 'tecnicos':
            $selectedDate = isset($_GET['date']) ? (string) $_GET['date'] : null;
            $tecnicoData = $tecnicoController->index($selectedDate);
            $selectedTecnico = isset($_GET['tecnico_id']) ? (int) $_GET['tecnico_id'] : 0;

            return [
                'view' => 'views/tecnicos.php',
                'data' => [
                    'tecnicos' => $tecnicoData['tecnicos'] ?? [],
                    'cardsTecnicos' => $tecnicoData['cardsTecnicos'] ?? [],
                    'selectedDate' => $tecnicoData['selectedDate'] ?? date('Y-m-d'),
                    'resumoDia' => $tecnicoData['resumoDia'] ?? [],
                    'automation' => $tecnicoData['automation'] ?? null,
                    'selectedTecnico' => $selectedTecnico,
                    'historico' => $tecnicoController->history($selectedTecnico),
                ],
            ];

        case 'lembretes':
            $selectedDate = isset($_GET['date']) ? (string) $_GET['date'] : null;
            $status = isset($_GET['status']) ? (string) $_GET['status'] : null;

            return [
                'view' => 'views/lembretes.php',
                'data' => $lembreteController->index($selectedDate, $status),
            ];

        case 'tecnico_historico':
            $tecnicoId = isset($_GET['tecnico_id']) ? (int) $_GET['tecnico_id'] : 0;
            $selectedDate = isset($_GET['date']) ? (string) $_GET['date'] : null;

            return
                [
                    'view' => 'views/tecnico-historico.php',
                    'data' => $tecnicoController->historyPage($tecnicoId, $selectedDate),
                ];

        case 'movimentacoes':
            $selectedDate = isset($_GET['date']) ? (string) $_GET['date'] : null;
            return [
                'view' => 'views/movimentacoes.php',
                'data' => $movimentacaoController->index($selectedDate),
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

        case 'equipamentos_defeito':
            return [
                'view' => 'views/equipamentos-defeito.php',
                'data' => $movimentacaoController->defectiveEquipments(),
            ];

        case 'apoio_compra':
            return [
                'view' => 'views/apoio-compra.php',
                'data' => $movimentacaoController->purchaseSupport(),
            ];

        case 'inadimplencia':
            return [
                'view' => 'views/inadimplencia.php',
                'data' => $inadimplenciaController->index(),
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
                    'automation' => $movData['automation'] ?? null,
                ],
            ];
    }
}

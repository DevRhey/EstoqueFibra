<?php
session_start();

require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/routes.php';

$route = $_GET['route'] ?? 'dashboard';
$action = $_POST['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== null) {
    $equipamentoController = new EquipamentoController();
    $tecnicoController = new TecnicoController();
    $movimentacaoController = new MovimentacaoController();

    switch ($action) {
        case 'equipamento_store':
            $equipamentoController->store($_POST);
            break;

        case 'equipamento_update':
            $equipamentoController->update($_POST);
            break;

        case 'equipamento_delete':
            $equipamentoController->destroy($_POST);
            break;

        case 'tecnico_store':
            $tecnicoController->store($_POST);
            break;

        case 'tecnico_delete':
            $tecnicoController->destroy($_POST);
            break;

        case 'movimentacao_store':
            $movimentacaoController->store($_POST);
            break;

        default:
            setFlash('danger', 'Acao invalida.');
            redirect($route);
    }
}

$page = resolveRoute($route);
$data = $page['data'];
$automationAlerts = $data['automation'] ?? null;

if (!is_array($automationAlerts)) {
    try {
        $automationAlerts = (new MovimentacaoController())->automationAlerts();
    } catch (Throwable $e) {
        $automationAlerts = null;
    }
}

$currentRoute = $route;
$flash = getFlash();

require __DIR__ . '/views/partials/header.php';
require __DIR__ . '/views/partials/sidebar.php';
require __DIR__ . '/' . $page['view'];
require __DIR__ . '/views/partials/footer.php';

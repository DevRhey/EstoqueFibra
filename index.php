<?php
require_once __DIR__ . '/config/bootstrap.php';
date_default_timezone_set('America/Sao_Paulo');
// session started in bootstrap
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/routes.php';

$route = $_GET['route'] ?? 'dashboard';
$action = $_POST['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== null) {
    $equipamentoController = new EquipamentoController();
    $tecnicoController = new TecnicoController();
    $movimentacaoController = new MovimentacaoController();
    $lembreteController = new LembreteController();
    $inadimplenciaController = new InadimplenciaController();

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

        case 'tecnico_update':
            $tecnicoController->update($_POST);
            break;

        case 'tecnico_delete':
            $tecnicoController->destroy($_POST);
            break;

        case 'movimentacao_store':
            $movimentacaoController->store($_POST);
            break;

        case 'movimentacao_ajuste_mao':
            $movimentacaoController->adjustHandBalance($_POST);
            break;

        case 'movimentacao_delete_uso':
            $movimentacaoController->destroyUsage($_POST);
            break;

        case 'movimentacao_update_uso':
            $movimentacaoController->updateUsage($_POST);
            break;

        case 'movimentacao_converter_teste_uso':
            $movimentacaoController->convertTestToUsage($_POST);
            break;

        case 'movimentacao_adicionar_tentativa_teste':
            $movimentacaoController->addTestAttempt($_POST);
            break;

        case 'movimentacao_delete_entrega':
            $movimentacaoController->destroyDelivery($_POST);
            break;

        case 'lembrete_marcar_lido':
            $lembreteController->markRead($_POST);
            break;

        case 'lembrete_resolver':
            $lembreteController->resolve($_POST);
            break;

        case 'lembrete_store':
            $lembreteController->store($_POST);
            break;

        case 'lembrete_update':
            $lembreteController->update($_POST);
            break;

        case 'apoio_compra_save':
            $movimentacaoController->savePurchaseSupport($_POST);
            break;

        case 'inadimplencia_importar_planilha':
            $inadimplenciaController->importSpreadsheet($_POST, $_FILES);
            break;

        case 'inadimplencia_limpar_base':
            $inadimplenciaController->clearBase();
            break;

        case 'inadimplencia_atualizar':
            $inadimplenciaController->update($_POST);
            break;

        case 'inadimplencia_excluir':
            $inadimplenciaController->destroy($_POST);
            break;

        case 'movimentacao_importar_uso_teste':
            $movimentacaoController->importUsoTesteSpreadsheet($_POST);
            break;

        default:
            setFlash('danger', 'Acao invalida.');
            redirect($route);
    }
}

$page = resolveRoute($route);
$data = $page['data'];
$lembreteController = new LembreteController();
$lembretesResumo = $lembreteController->summary();
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

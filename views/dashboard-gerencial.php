<?php
$equipamentos = $data['equipamentos'] ?? [];
$tecnicos = $data['tecnicos'] ?? [];
$cardsTecnicos = $data['cardsTecnicos'] ?? [];
$selectedDate = $data['selectedDate'] ?? date('Y-m-d');
$equipamentosEmMaoPorTecnico = [];
foreach ($cardsTecnicos as $card) {
    $equipamentosEmMaoPorTecnico[(int) ($card['tecnico_id'] ?? 0)] = $card['equipamentos_mao'] ?? [];
}
$resumoDia = $data['resumoDia'] ?? [
    'total' => 0,
    'entrega' => 0,
    'uso' => 0,
    'uso_teste' => 0,
    'devolucao' => 0,
    'recolhimento' => 0,
    'recolhimento_defeito' => 0,
    'tecnicos_ativos' => 0,
];
$estoqueSeguroLabels = [
    'roteador' => 'Roteadores',
    'onu' => 'ONU',
    'conector_fibra' => 'Conectores',
    'conector_rj' => 'Conector RJ',
    'esticador' => 'Esticador',
];
?>

<section class="mb-4 page-intro reveal">
    <h2 class="page-title">Gerenciamento Centralizado</h2>
    <p class="page-subtitle">Administre equipamentos e tecnicos e acompanhe os dados por data de referencia.</p>
</section>

<div class="card card-soft reveal mb-3">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-2 section-shortcuts">
            <a class="btn btn-sm btn-outline-secondary" href="#dashboard-kpis">Resumo do dia</a>
            <a class="btn btn-sm btn-outline-secondary" href="#dashboard-operacoes">Operacoes</a>
            <a class="btn btn-sm btn-outline-secondary" href="#dashboard-tecnicos">Tecnicos</a>
        </div>
    </div>
</div>

<div class="card card-soft reveal mb-4 dashboard-quick-actions-card">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <div>
                <h5 class="mb-1">Acoes rapidas</h5>
                <p class="mb-0 text-muted">Crie novos cadastros sem navegar por outras telas.</p>
            </div>
            <span class="badge text-bg-info align-self-start align-self-md-center">Cadastro imediato</span>
        </div>
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <button type="button" class="btn dashboard-quick-action dashboard-quick-action-equipment w-100 h-100 text-start" data-bs-toggle="modal" data-bs-target="#novoEquipamentoModal">
                    <div class="dashboard-quick-action-icon">+</div>
                    <div>
                        <strong>Novo equipamento</strong>
                        <div class="small">Cadastrar roteador, ONU, ONT, conector ou insumos</div>
                    </div>
                </button>
            </div>
            <div class="col-12 col-md-6">
                <button type="button" class="btn dashboard-quick-action dashboard-quick-action-tech w-100 h-100 text-start" data-bs-toggle="modal" data-bs-target="#novoTecnicoModal">
                    <div class="dashboard-quick-action-icon">+</div>
                    <div>
                        <strong>Novo tecnico</strong>
                        <div class="small">Adicionar tecnico de campo ao sistema</div>
                    </div>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card card-soft reveal mb-4 sticky-date-filter">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <input type="hidden" name="route" value="dashboard">
            <div class="col-12 col-md-4 col-lg-3">
                <label class="form-label mb-1">Data de Referencia</label>
                <input type="date" name="date" class="form-control" value="<?php echo sanitize($selectedDate); ?>">
            </div>
            <div class="col-12 col-md-8 col-lg-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Aplicar Data</button>
                <a href="index.php?route=dashboard" class="btn btn-outline-secondary">Hoje</a>
            </div>
            <div class="col-12 col-lg-5">
                <small class="text-muted">Exibindo dados do dia: <strong><?php echo date('d/m/Y', strtotime($selectedDate)); ?></strong></small>
            </div>
        </form>
    </div>
</div>

<div class="card card-soft reveal mb-4" id="dashboard-operacoes">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Operacoes de Movimentacao</h5>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#dashboard-operacoes-body" aria-expanded="false" aria-controls="dashboard-operacoes-body" data-label-expand="Expandir" data-label-collapse="Ocultar">Expandir</button>
    </div>
    <div id="dashboard-operacoes-body" class="collapse">
        <div class="card-body pt-2">
            <div class="row g-3">
            <div class="col-12 col-sm-6 col-lg-4 col-xl">
                <button class="btn btn-success w-100 h-100 p-3" data-bs-toggle="modal" data-bs-target="#modal-entrega">
                    <strong>Entregar</strong>
                    <small class="d-block mt-1">Equipamento ao tecnico</small>
                </button>
            </div>
            <div class="col-12 col-sm-6 col-lg-4 col-xl">
                <button class="btn btn-warning w-100 h-100 p-3" data-bs-toggle="modal" data-bs-target="#modal-uso">
                    <strong>Registrar Uso</strong>
                    <small class="d-block mt-1">Uso no cliente</small>
                </button>
            </div>
            <div class="col-12 col-sm-6 col-lg-4 col-xl">
                <button class="btn btn-dark w-100 h-100 p-3" data-bs-toggle="modal" data-bs-target="#modal-uso-teste">
                    <strong>Uso em Teste</strong>
                    <small class="d-block mt-1">Teste de 3 dias</small>
                </button>
            </div>
            <div class="col-12 col-sm-6 col-lg-6 col-xl">
                <button class="btn btn-info w-100 h-100 p-3" data-bs-toggle="modal" data-bs-target="#modal-recolhimento">
                    <strong>Recolher</strong>
                    <small class="d-block mt-1">Cliente para mao do tecnico</small>
                </button>
            </div>
            <div class="col-12 col-sm-6 col-lg-6 col-xl">
                <button class="btn btn-danger w-100 h-100 p-3" data-bs-toggle="modal" data-bs-target="#modal-recolhimento-defeito">
                    <strong>Devolver c/ defeito</strong>
                    <small class="d-block mt-1">Sai da mao e vai para lista</small>
                </button>
            </div>
            <div class="col-12 col-sm-6 col-lg-6 col-xl">
                <button class="btn btn-primary w-100 h-100 p-3" data-bs-toggle="modal" data-bs-target="#modal-devolucao">
                    <strong>Devolver</strong>
                    <small class="d-block mt-1">Retorno ao estoque</small>
                </button>
            </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4 reveal" id="dashboard-kpis">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-soft history-stat-card history-stat-card-total h-100">
            <div class="card-body">
                <div class="history-stat-head">
                    <small class="text-muted d-block">Movimentacoes</small>
                    <span class="history-stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M4 17h3V7H4v10zm6 0h4V4h-4v13zm7 0h3V11h-3v6z" fill="currentColor"/></svg>
                    </span>
                </div>
                <h3 class="mb-0"><?php echo (int) ($resumoDia['total'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-soft history-stat-card history-stat-card-entrega h-100">
            <div class="card-body">
                <div class="history-stat-head">
                    <small class="text-muted d-block">Entregas</small>
                    <span class="history-stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M3 6h13v6h3l2 2v4h-2a2 2 0 0 1-4 0H9a2 2 0 0 1-4 0H3V6zm3 2v2h8V8H6z" fill="currentColor"/></svg>
                    </span>
                </div>
                <h3 class="mb-0"><?php echo (int) ($resumoDia['entrega'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-soft history-stat-card history-stat-card-uso h-100">
            <div class="card-body">
                <div class="history-stat-head">
                    <small class="text-muted d-block">Usos</small>
                    <span class="history-stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M13 2L4 14h6l-1 8 9-12h-6l1-8z" fill="currentColor"/></svg>
                    </span>
                </div>
                <h3 class="mb-0"><?php echo (int) (($resumoDia['uso'] ?? 0) + ($resumoDia['uso_teste'] ?? 0)); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-soft history-stat-card history-stat-card-devolucao h-100">
            <div class="card-body">
                <div class="history-stat-head">
                    <small class="text-muted d-block">Devolucoes</small>
                    <span class="history-stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M10 6l-6 6 6 6v-4h6v-4h-6V6zm10 0h-8v4h8v8h-8v4h12V6h-4z" fill="currentColor"/></svg>
                    </span>
                </div>
                <h3 class="mb-0"><?php echo (int) ($resumoDia['devolucao'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-soft history-stat-card history-stat-card-recolhimento h-100">
            <div class="card-body">
                <div class="history-stat-head">
                    <small class="text-muted d-block">Recolhimentos</small>
                    <span class="history-stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M12 2l7 7h-4v6h-6V9H5l7-7zm-7 15h14v5H5v-5z" fill="currentColor"/></svg>
                    </span>
                </div>
                <h3 class="mb-0"><?php echo (int) ($resumoDia['recolhimento'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-soft history-stat-card history-stat-card-recolhimento-defeito h-100">
            <div class="card-body">
                <div class="history-stat-head">
                    <small class="text-muted d-block">Com defeito</small>
                    <span class="history-stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M12 2 1 21h22L12 2zm0 6 1 6h-2l1-6zm0 11a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z" fill="currentColor"/></svg>
                    </span>
                </div>
                <h3 class="mb-0"><?php echo (int) ($resumoDia['recolhimento_defeito'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-soft history-stat-card history-stat-card-tecnicos h-100">
            <div class="card-body">
                <div class="history-stat-head">
                    <small class="text-muted d-block">Tecnicos Ativos</small>
                    <span class="history-stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M16 11a4 4 0 1 0-3.999-4A4 4 0 0 0 16 11zM8 12a3 3 0 1 0-3-3 3 3 0 0 0 3 3zm8 1c-2.67 0-8 1.34-8 4v3h16v-3c0-2.66-5.33-4-8-4zM8 14c-2.33 0-7 1.17-7 3.5V20h5v-3c0-1.08.62-2.05 1.7-2.86A8.36 8.36 0 0 0 8 14z" fill="currentColor"/></svg>
                    </span>
                </div>
                <h3 class="mb-0"><?php echo (int) ($resumoDia['tecnicos_ativos'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card card-soft reveal">
    <div class="card-header">
        <ul class="nav nav-pills dashboard-tabs-pills card-header-tabs" id="gerencialTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active dashboard-tab-pill" id="equipamentos-tab" data-bs-toggle="tab" data-bs-target="#equipamentos-content" type="button" role="tab" aria-controls="equipamentos-content" aria-selected="true">
                    Equipamentos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link dashboard-tab-pill" id="tecnicos-tab" data-bs-toggle="tab" data-bs-target="#tecnicos-content" type="button" role="tab" aria-controls="tecnicos-content" aria-selected="false">
                    Tecnicos
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content card-body p-0">

        <!-- EQUIPAMENTOS TAB -->
        <div class="tab-pane fade show active" id="equipamentos-content" role="tabpanel" aria-labelledby="equipamentos-tab">
            <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                <h5 class="mb-0">Estoque de Equipamentos</h5>
                <span class="badge text-bg-primary">Cadastro prioritario</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Código de Barras</th>
                        <th>Quantidade</th>
                        <th class="text-end">Acoes</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($equipamentos)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">Nenhum equipamento cadastrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($equipamentos as $eq): ?>
                            <tr>
                                <td><?php echo sanitize($eq['nome']); ?></td>
                                <td><?php echo sanitize($eq['tipo']); ?></td>
                                <td><?php echo !empty($eq['codigo_barras']) ? sanitize($eq['codigo_barras']) : '-'; ?></td>
                                <td>
                                    <span class="badge <?php echo ((int) $eq['quantidade'] < 5) ? 'text-bg-danger' : 'text-bg-success'; ?>">
                                        <?php echo (int) $eq['quantidade']; ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button
                                        class="btn btn-sm btn-outline-primary me-2 btn-edit-equip"
                                        data-id="<?php echo (int) $eq['id']; ?>"
                                        data-nome="<?php echo sanitize($eq['nome']); ?>"
                                        data-tipo="<?php echo sanitize($eq['tipo']); ?>"
                                        data-codigo-barras="<?php echo sanitize((string) ($eq['codigo_barras'] ?? '')); ?>"
                                        data-quantidade="<?php echo (int) $eq['quantidade']; ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editarEquipamentoModal"
                                    >Editar</button>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Deseja excluir este equipamento?');">
                                        <input type="hidden" name="action" value="equipamento_delete">
                                        <input type="hidden" name="id" value="<?php echo (int) $eq['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TECNICOS TAB -->
        <div class="tab-pane fade" id="tecnicos-content" role="tabpanel" aria-labelledby="tecnicos-tab">
            <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                <h5 class="mb-0">Tecnicos de Campo</h5>
                <div class="d-flex gap-2">
                    <a href="index.php?route=movimentacoes" class="btn btn-sm btn-outline-primary">Ir para Movimentacoes</a>
                    <span class="badge text-bg-info align-self-center">Cadastro prioritario</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Cadastro</th>
                        <th class="text-end">Acoes</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($tecnicos)): ?>
                        <tr>
                            <td colspan="3" class="text-center py-4">Nenhum tecnico cadastrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tecnicos as $tec): ?>
                            <tr>
                                <td><?php echo sanitize($tec['nome']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($tec['created_at'])); ?></td>
                                <td class="text-end">
                                        <a href="index.php?route=tecnico_historico&tecnico_id=<?php echo (int) $tec['id']; ?>" class="btn btn-sm btn-outline-info">Historico</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- CARDS DOS TECNICOS -->
<div class="row g-4 mt-4 mb-4" id="dashboard-tecnicos">
    <div class="col-12">
        <h4 class="mb-3 reveal">Informacoes por Tecnico (<?php echo date('d/m/Y', strtotime($selectedDate)); ?>)</h4>
        <div class="mb-3"><?php require __DIR__ . '/partials/tech-risk-legend.php'; ?></div>
    </div>
    <div class="col-12 col-lg-7">
        <label class="form-label small text-muted mb-1">Localizar tecnico</label>
        <input type="text" class="form-control js-dashboard-tech-filter" placeholder="Digite o nome do tecnico">
    </div>
    <div class="col-12 col-lg-5 d-flex align-items-end">
        <div class="small text-muted">Tecnicos visiveis: <strong class="js-dashboard-tech-count"><?php echo count($cardsTecnicos); ?></strong></div>
    </div>
    <div class="col-12">
        <div class="alert alert-dark border d-none js-dashboard-tech-empty mb-0">Nenhum tecnico encontrado para o filtro informado.</div>
    </div>

    <?php if (empty($cardsTecnicos)): ?>
        <div class="col-12 reveal">
            <div class="card card-soft">
                <div class="card-body text-center py-4">Nenhum técnico cadastrado para exibir informações.</div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($cardsTecnicos as $card): ?>
            <?php
            $reporTotal = (int) ($card['repor_total'] ?? 0);
            $techRiskClass = $reporTotal === 0 ? 'tech-name-emphasis-ok' : ($reporTotal <= 3 ? 'tech-name-emphasis-warning' : 'tech-name-emphasis-critical');
            ?>
            <div class="col-12 col-lg-6 col-xl-4 reveal js-dashboard-tech-card" data-tech-card-name="<?php echo strtolower(sanitize((string) ($card['tecnico_nome'] ?? ''))); ?>">
                <div class="card card-soft h-100">
                    <div class="card-header d-flex justify-content-between align-items-center gap-2">
                        <h5 class="mb-0"><span class="tech-name-emphasis <?php echo $techRiskClass; ?>"><?php echo sanitize($card['tecnico_nome']); ?></span></h5>
                        <span class="badge text-bg-primary">Saldo Atual: <?php echo (int) $card['saldo_total_mao']; ?></span>
                    </div>
                    <div class="card-body">
                        <!-- Estatísticas -->
                        <div class="row g-2 mb-3 pb-3 border-bottom">
                            <div class="col-6 col-lg-3 text-center">
                                <small class="text-muted d-block">Entregue</small>
                                <strong><?php echo (int) $card['total_entrega']; ?></strong>
                            </div>
                            <div class="col-6 col-lg-3 text-center">
                                <small class="text-muted d-block">Usado</small>
                                <strong><?php echo (int) $card['total_uso']; ?></strong>
                            </div>
                            <div class="col-6 col-lg-3 text-center">
                                <small class="text-muted d-block">Devolvido</small>
                                <strong><?php echo (int) $card['total_devolvido']; ?></strong>
                            </div>
                            <div class="col-6 col-lg-3 text-center">
                                <small class="text-muted d-block">Recolhido</small>
                                <strong><?php echo (int) ($card['total_recolhido'] ?? 0); ?></strong>
                            </div>
                        </div>

                        <div class="mb-3 pb-3 border-bottom">
                            <h6 class="mb-2"><small>Acoes Rapidas</small></h6>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-action-filled btn-action-entrega" data-bs-toggle="modal" data-bs-target="#modal-entrega" data-tecnico-id="<?php echo (int) ($card['tecnico_id'] ?? 0); ?>">Entrega</button>
                                <button type="button" class="btn btn-sm btn-action-filled btn-action-uso" data-bs-toggle="modal" data-bs-target="#modal-uso" data-tecnico-id="<?php echo (int) ($card['tecnico_id'] ?? 0); ?>">Uso</button>
                                <button type="button" class="btn btn-sm btn-action-filled btn-action-teste" data-bs-toggle="modal" data-bs-target="#modal-uso-teste" data-tecnico-id="<?php echo (int) ($card['tecnico_id'] ?? 0); ?>">Uso Teste</button>
                                <button type="button" class="btn btn-sm btn-action-filled btn-action-devolucao" data-bs-toggle="modal" data-bs-target="#modal-devolucao" data-tecnico-id="<?php echo (int) ($card['tecnico_id'] ?? 0); ?>">Devolucao</button>
                                <button type="button" class="btn btn-sm btn-action-filled btn-action-recolhimento" data-bs-toggle="modal" data-bs-target="#modal-recolhimento" data-tecnico-id="<?php echo (int) ($card['tecnico_id'] ?? 0); ?>">Recolhimento</button>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modal-recolhimento-defeito" data-tecnico-id="<?php echo (int) ($card['tecnico_id'] ?? 0); ?>">Devolver c/ defeito</button>
                            </div>
                        </div>

                        <div class="mb-3 pb-3 border-bottom">
                            <h6 class="mb-2"><small>Estoque Seguro</small></h6>
                            <?php
                            $saldoCategoria = $card['saldo_por_categoria'] ?? [];
                            $saldoEfetivo = $card['saldo_por_categoria_efetivo'] ?? [];
                            $ontEmMao = (int) ($saldoCategoria['ont'] ?? 0);
                            ?>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <span class="badge text-bg-success">Roteadores (efetivo): <?php echo (int) ($saldoEfetivo['roteador'] ?? 0); ?>/3</span>
                                <span class="badge text-bg-primary">ONU (efetivo): <?php echo (int) ($saldoEfetivo['onu'] ?? 0); ?>/2</span>
                                <span class="badge text-bg-secondary">ONT em mão: <?php echo $ontEmMao; ?></span>
                                <span class="badge text-bg-info text-dark">Conectores: <?php echo (int) ($card['saldo_por_categoria']['conector_fibra'] ?? 0); ?>/10</span>
                                <span class="badge text-bg-warning text-dark">Conector RJ: <?php echo (int) ($card['saldo_por_categoria']['conector_rj'] ?? 0); ?>/8</span>
                                <span class="badge text-bg-light text-dark">Esticador: <?php echo (int) ($card['saldo_por_categoria']['esticador'] ?? 0); ?>/8</span>
                            </div>
                            <?php if ($ontEmMao > 0): ?>
                                <small class="text-muted d-block mb-2">Cada ONT em mão substitui 1 ONU e 1 roteador no cálculo de reposição.</small>
                            <?php endif; ?>
                            <?php if (!empty($card['estoque_seguro_ok'])): ?>
                                <p class="text-success mb-0"><small>Estoque seguro atendido para a data selecionada.</small></p>
                            <?php else: ?>
                                <div class="dark-panel-subtle small p-2 rounded">
                                    <?php foreach (($card['reposicao_proximo_dia'] ?? []) as $repor): ?>
                                        <?php if ((int) $repor['faltante'] <= 0) { continue; } ?>
                                        <div>
                                            <strong><?php echo sanitize($estoqueSeguroLabels[$repor['categoria']] ?? $repor['categoria']); ?></strong>:
                                            repor <span class="text-danger fw-bold"><?php echo (int) $repor['faltante']; ?></span>
                                            de <?php echo (int) $repor['necessario']; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Equipamentos em Mão -->
                        <div class="mb-3">
                            <h6 class="mb-2"><small>Equipamentos na Mão</small></h6>
                            <?php if (empty($card['equipamentos_mao'])): ?>
                                <p class="text-muted mb-0"><small>Nenhum item em aberto.</small></p>
                            <?php else: ?>
                                <div class="d-flex flex-column gap-2">
                                    <?php foreach ($card['equipamentos_mao'] as $eq): ?>
                                        <div class="d-flex justify-content-between align-items-center gap-2 dark-panel-subtle rounded p-2">
                                            <span class="badge rounded-pill text-bg-secondary text-truncate" title="<?php echo sanitize($eq['nome']); ?>: <?php echo (int) $eq['saldo_mao']; ?>">
                                                <?php echo sanitize($eq['nome']); ?>: <?php echo (int) $eq['saldo_mao']; ?>
                                            </span>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-light js-ajuste-mao-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#ajusteMaoModal"
                                                data-tecnico-id="<?php echo (int) ($card['tecnico_id'] ?? 0); ?>"
                                                data-tecnico-nome="<?php echo sanitize((string) ($card['tecnico_nome'] ?? '')); ?>"
                                                data-equipamento-id="<?php echo (int) ($eq['equipamento_id'] ?? 0); ?>"
                                                data-equipamento-nome="<?php echo sanitize((string) ($eq['nome'] ?? '')); ?>"
                                                data-saldo-atual="<?php echo (int) ($eq['saldo_mao'] ?? 0); ?>"
                                            >
                                                Ajustar
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Usos Recentes -->
                        <div class="mb-3">
                            <h6 class="mb-2"><small>Usos na Data e Locais</small></h6>
                            <?php if (empty($card['usos_recentes'])): ?>
                                <p class="text-muted mb-0"><small>Sem registros de uso.</small></p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless align-middle mb-0">
                                        <tbody>
                                        <?php foreach ($card['usos_recentes'] as $uso): ?>
                                            <tr>
                                                <td class="py-1">
                                                    <small class="text-muted"><?php echo date('d/m H:i', strtotime($uso['data_movimentacao'])); ?></small>
                                                </td>
                                                <td class="py-1">
                                                    <small><strong><?php echo sanitize($uso['equipamento_nome']); ?></strong> (<?php echo (int) $uso['quantidade']; ?>x)</small>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" class="py-0">
                                                    <small class="text-info">📍 <?php echo !empty($uso['local_uso']) ? sanitize($uso['local_uso']) : 'Local não registrado'; ?></small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Reposicao no proximo dia -->
                        <div class="pt-2 border-top">
                            <h6 class="mb-2"><small>Previsao para o Proximo Dia</small></h6>
                            <?php if (empty($card['reposicao_proximo_dia'])): ?>
                                <p class="text-success mb-0"><small>Nada a repor para iniciar o dia.</small></p>
                            <?php else: ?>
                                <div class="d-flex flex-column gap-2">
                                    <?php foreach ($card['reposicao_proximo_dia'] as $rep): ?>
                                        <?php if ((int) $rep['faltante'] <= 0) { continue; } ?>
                                        <div class="dark-panel-subtle small p-2 rounded">
                                            <strong><?php echo sanitize($estoqueSeguroLabels[$rep['categoria']] ?? $rep['categoria']); ?></strong><br>
                                            <span class="text-muted">Atual: <?php echo (int) $rep['atual']; ?> | Necessario: <?php echo (int) $rep['necessario']; ?></span><br>
                                            <span class="text-danger fw-bold">⚠ Repor: <?php echo (int) $rep['faltante']; ?> unidades</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal: ENTREGA -->
<?php
    $return_route = 'dashboard';
    require __DIR__ . '/partials/modal-entrega.php';
?>

<!-- Modal: USO -->
<?php
    $return_route = 'dashboard';
    $require_local_uso = false;
    require __DIR__ . '/partials/modal-uso.php';
?>

<!-- Modal: USO TESTE -->
<?php
    $return_route = 'dashboard';
    require __DIR__ . '/partials/modal-uso-teste.php';
?>

<!-- Modal: RECOLHIMENTO -->
<?php
    $return_route = 'dashboard';
    $include_batch = true;
    require __DIR__ . '/partials/modal-recolhimento.php';
?>

<!-- Modal: RECOLHIMENTO COM DEFEITO -->
<?php
    $return_route = 'dashboard';
    $include_batch = true;
    require __DIR__ . '/partials/modal-recolhimento-defeito.php';
?>

<!-- Modal: DEVOLUCAO -->
<?php
    $return_route = 'dashboard';
    $include_batch = true;
    require __DIR__ . '/partials/modal-devolucao.php';
?>

<?php require __DIR__ . '/partials/modal-ajuste-mao.php'; ?>

<!-- MODAIS -->

<!-- Modal: Novo Equipamento -->
<?php require __DIR__ . '/partials/modal-novo-equipamento.php'; ?>

<!-- Modal: Editar Equipamento -->
<?php require __DIR__ . '/partials/modal-editar-equipamento.php'; ?>

<!-- Modal: Novo Tecnico -->
<?php require __DIR__ . '/partials/modal-novo-tecnico.php'; ?>

<script type="application/json" id="movement-equipment-map"><?php echo json_encode([
    'all' => array_map(static function (array $eq): array {
        return [
            'id' => (int) $eq['id'],
            'nome' => $eq['nome'],
            'tipo' => $eq['tipo'],
            'codigo_barras' => $eq['codigo_barras'] ?? null,
            'quantidade' => (int) $eq['quantidade'],
        ];
    }, $equipamentos),
    'handByTechnician' => $equipamentosEmMaoPorTecnico,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>


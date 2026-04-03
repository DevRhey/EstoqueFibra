<?php
$equipamentos = $data['equipamentos'] ?? [];
$tecnicos = $data['tecnicos'] ?? [];
$cardsTecnicos = $data['cardsTecnicos'] ?? [];
$selectedDate = $data['selectedDate'] ?? date('Y-m-d');
$resumoDia = $data['resumoDia'] ?? [
    'total' => 0,
    'entrega' => 0,
    'uso' => 0,
    'uso_teste' => 0,
    'devolucao' => 0,
    'recolhimento' => 0,
    'tecnicos_ativos' => 0,
];
$estoqueSeguroLabels = [
    'roteador' => 'Roteadores',
    'onu' => 'ONU',
    'conector_fibra' => 'Conectores',
];
?>

<section class="mb-4 page-intro reveal">
    <h2 class="page-title">Gerenciamento Centralizado</h2>
    <p class="page-subtitle">Administre equipamentos e tecnicos e acompanhe os dados por data de referencia.</p>
</section>

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
                        <div class="small">Cadastrar roteador, ONU, ONT ou conector</div>
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

<div class="card card-soft reveal mb-4">
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

<div class="row g-3 mb-4 reveal">
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
<div class="row g-4 mt-4 mb-4">
    <div class="col-12">
        <h4 class="mb-3 reveal">Informacoes por Tecnico (<?php echo date('d/m/Y', strtotime($selectedDate)); ?>)</h4>
        <div class="mb-3"><?php require __DIR__ . '/partials/tech-risk-legend.php'; ?></div>
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
            <div class="col-12 col-lg-6 col-xl-4 reveal">
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
                                <a href="index.php?route=movimentacoes&tipo=entrega&tecnico_id=<?php echo (int) ($card['tecnico_id'] ?? 0); ?>" class="btn btn-sm btn-action-filled btn-action-entrega">Entrega</a>
                                <a href="index.php?route=movimentacoes&tipo=uso&tecnico_id=<?php echo (int) ($card['tecnico_id'] ?? 0); ?>" class="btn btn-sm btn-action-filled btn-action-uso">Uso</a>
                                <a href="index.php?route=movimentacoes&tipo=uso_teste&tecnico_id=<?php echo (int) ($card['tecnico_id'] ?? 0); ?>" class="btn btn-sm btn-action-filled btn-action-teste">Uso Teste</a>
                                <a href="index.php?route=movimentacoes&tipo=devolucao&tecnico_id=<?php echo (int) ($card['tecnico_id'] ?? 0); ?>" class="btn btn-sm btn-action-filled btn-action-devolucao">Devolucao</a>
                                <a href="index.php?route=movimentacoes&tipo=recolhimento&tecnico_id=<?php echo (int) ($card['tecnico_id'] ?? 0); ?>" class="btn btn-sm btn-action-filled btn-action-recolhimento">Recolhimento</a>
                            </div>
                        </div>

                        <div class="mb-3 pb-3 border-bottom">
                            <h6 class="mb-2"><small>Estoque Seguro</small></h6>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <span class="badge text-bg-success">Roteadores: <?php echo (int) ($card['saldo_por_categoria']['roteador'] ?? 0); ?>/3</span>
                                <span class="badge text-bg-primary">ONU: <?php echo (int) ($card['saldo_por_categoria']['onu'] ?? 0); ?>/2</span>
                                <span class="badge text-bg-info text-dark">Conectores: <?php echo (int) ($card['saldo_por_categoria']['conector_fibra'] ?? 0); ?>/10</span>
                            </div>
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
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($card['equipamentos_mao'] as $eq): ?>
                                        <span class="badge rounded-pill text-bg-secondary text-truncate" title="<?php echo sanitize($eq['nome']); ?>: <?php echo (int) $eq['saldo_mao']; ?>">
                                            <?php echo sanitize($eq['nome']); ?>: <?php echo (int) $eq['saldo_mao']; ?>
                                        </span>
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

<!-- MODAIS -->

<!-- Modal: Novo Equipamento -->
<div class="modal fade" id="novoEquipamentoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" class="needs-validation js-movement-form" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Cadastrar Novo Equipamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="equipamento_store">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" class="form-control" required maxlength="120">
                        <div class="invalid-feedback">Informe o nome.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select" required>
                            <option value="">Selecione</option>
                            <option value="roteador">Roteador</option>
                            <option value="onu">ONU</option>
                            <option value="ont">ONT</option>
                            <option value="conector_fibra">Conector de Fibra</option>
                        </select>
                        <div class="invalid-feedback">Selecione o tipo.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Código de Barras</label>
                        <input type="text" name="codigo_barras" class="form-control" maxlength="64" placeholder="Leia com o scanner ou digite manualmente">
                        <div class="form-text">Opcional. Deve ser único para cada equipamento.</div>
                    </div>
                    <div>
                        <label class="form-label">Quantidade Inicial</label>
                        <input type="number" name="quantidade" class="form-control" required min="0">
                        <div class="invalid-feedback">Informe uma quantidade válida.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Editar Equipamento -->
<div class="modal fade" id="editarEquipamentoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Editar Equipamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="equipamento_update">
                    <input type="hidden" name="id" id="edit-equip-id">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" id="edit-equip-nome" class="form-control" required>
                        <div class="invalid-feedback">Campo obrigatório.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" id="edit-equip-tipo" class="form-select" required>
                            <option value="">Selecione</option>
                            <option value="roteador">Roteador</option>
                            <option value="onu">ONU</option>
                            <option value="ont">ONT</option>
                            <option value="conector_fibra">Conector de Fibra</option>
                        </select>
                        <div class="invalid-feedback">Campo obrigatório.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Código de Barras</label>
                        <input type="text" name="codigo_barras" id="edit-equip-codigo" class="form-control" maxlength="64" placeholder="Leia com o scanner ou digite manualmente">
                        <div class="form-text">Opcional. Deve ser único para cada equipamento.</div>
                    </div>
                    <div>
                        <label class="form-label">Quantidade</label>
                        <input type="number" name="quantidade" id="edit-equip-quantidade" class="form-control" required min="0">
                        <div class="invalid-feedback">Campo obrigatório.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Novo Tecnico -->
<div class="modal fade" id="novoTecnicoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Cadastrar Novo Técnico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="tecnico_store">
                    <div class="mb-3">
                        <label class="form-label">Nome do Técnico</label>
                        <input type="text" name="nome" class="form-control" required maxlength="120">
                        <div class="invalid-feedback">Informe o nome.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div>


<?php
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
    'recolhimento_defeito' => 0,
    'tecnicos_ativos' => 0,
];

$estoqueSeguroLabels = [
    'roteador' => 'Roteadores',
    'onu' => 'ONU',
    'ont' => 'ONT',
    'conector_fibra' => 'Conectores',
    'conector_rj' => 'Conector RJ',
    'esticador' => 'Esticador',
];
?>

<section class="mb-4 page-intro reveal">
    <h2 class="page-title">Tecnicos</h2>
    <p class="page-subtitle">Gerencie os colaboradores de campo e acompanhe saldo em mao, usos, devolucoes e recolhimentos por data de referencia.</p>
</section>

<div class="card card-soft reveal mb-4 sticky-date-filter">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <input type="hidden" name="route" value="tecnicos">
            <div class="col-12 col-md-4 col-lg-3">
                <label class="form-label mb-1">Data de Referencia</label>
                <input type="date" name="date" class="form-control" value="<?php echo sanitize($selectedDate); ?>">
            </div>
            <div class="col-12 col-md-8 col-lg-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Aplicar Data</button>
                <a href="index.php?route=tecnicos" class="btn btn-outline-secondary">Hoje</a>
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

<div class="row g-4 mb-4 reveal">
    <div class="col-12">
        <div class="mb-3"><?php require __DIR__ . '/partials/tech-risk-legend.php'; ?></div>
    </div>
    <div class="col-12 col-lg-6">
        <label class="form-label small text-muted mb-1">Filtrar tecnico</label>
        <input type="text" class="form-control js-tech-card-filter" placeholder="Digite o nome do tecnico para localizar rapido">
    </div>
    <div class="col-12">
        <div class="alert alert-dark border d-none js-tech-card-empty mb-0">Nenhum tecnico encontrado para o filtro informado.</div>
    </div>

    <?php if (empty($cardsTecnicos)): ?>
        <div class="col-12 reveal">
            <div class="card card-soft">
                <div class="card-body text-center py-4">Nenhum tecnico cadastrado para exibir informações.</div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($cardsTecnicos as $card): ?>
            <?php
            $reporTotal = (int) ($card['repor_total'] ?? 0);
            $techRiskClass = $reporTotal === 0 ? 'tech-name-emphasis-ok' : ($reporTotal <= 3 ? 'tech-name-emphasis-warning' : 'tech-name-emphasis-critical');
            $saldoCategoria = $card['saldo_por_categoria'] ?? [];
            $saldoEfetivo = $card['saldo_por_categoria_efetivo'] ?? [];
            $ontEmMao = (int) ($saldoCategoria['ont'] ?? 0);
            ?>
            <div class="col-12 col-lg-6 col-xl-4 reveal js-tech-card" data-tech-card-name="<?php echo strtolower(sanitize((string) ($card['tecnico_nome'] ?? ''))); ?>">
                <div class="card card-soft h-100">
                    <div class="card-header d-flex justify-content-between align-items-center gap-2">
                        <h5 class="mb-0"><span class="tech-name-emphasis <?php echo $techRiskClass; ?>"><?php echo sanitize($card['tecnico_nome']); ?></span></h5>
                        <span class="badge text-bg-primary">Saldo Atual: <?php echo (int) $card['saldo_total_mao']; ?></span>
                    </div>
                    <div class="card-body">
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
                                <a href="index.php?route=movimentacoes&tipo=recolhimento_defeito&tecnico_id=<?php echo (int) ($card['tecnico_id'] ?? 0); ?>" class="btn btn-sm btn-outline-danger">Devolver c/ defeito</a>
                            </div>
                        </div>

                        <div class="mb-3 pb-3 border-bottom">
                            <h6 class="mb-2"><small>Estoque Seguro</small></h6>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <span class="badge text-bg-success">Roteadores (efetivo): <?php echo (int) ($saldoEfetivo['roteador'] ?? 0); ?>/3</span>
                                <span class="badge text-bg-primary">ONU (efetivo): <?php echo (int) ($saldoEfetivo['onu'] ?? 0); ?>/2</span>
                                <span class="badge text-bg-secondary">ONT em mão: <?php echo $ontEmMao; ?></span>
                                <span class="badge text-bg-info text-dark">Conectores: <?php echo (int) ($saldoCategoria['conector_fibra'] ?? 0); ?>/10</span>
                                <span class="badge text-bg-warning text-dark">Conector RJ: <?php echo (int) ($saldoCategoria['conector_rj'] ?? 0); ?>/8</span>
                                <span class="badge text-bg-light text-dark">Esticador: <?php echo (int) ($saldoCategoria['esticador'] ?? 0); ?>/8</span>
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
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

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
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="card card-soft reveal mb-4">
    <div class="card-header">
        <h5 class="mb-0">Cadastro e Lista de Tecnicos</h5>
    </div>
    <div class="card-body">
        <div class="accordion accordion-flush" id="tecnicosAccordion">
            <div class="accordion-item bg-transparent border-0">
                <h2 class="accordion-header" id="headingCadastroTecnico">
                    <button class="accordion-button collapsed bg-transparent text-light shadow-none px-0" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCadastroTecnico" aria-expanded="false" aria-controls="collapseCadastroTecnico">
                        Novo Tecnico
                    </button>
                </h2>
                <div id="collapseCadastroTecnico" class="accordion-collapse collapse" aria-labelledby="headingCadastroTecnico" data-bs-parent="#tecnicosAccordion">
                    <div class="accordion-body px-0 pt-3">
                        <form method="post" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="tecnico_store">
                            <div class="mb-3">
                                <label class="form-label">Nome do Tecnico</label>
                                <input type="text" name="nome" class="form-control" required maxlength="120">
                                <div class="invalid-feedback">Informe o nome do tecnico.</div>
                            </div>
                            <button type="submit" class="btn btn-primary">Cadastrar Tecnico</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="accordion-item bg-transparent border-0 mt-2">
                <h2 class="accordion-header" id="headingListaTecnicos">
                    <button class="accordion-button bg-transparent text-light shadow-none px-0" type="button" data-bs-toggle="collapse" data-bs-target="#collapseListaTecnicos" aria-expanded="true" aria-controls="collapseListaTecnicos">
                        Lista de Tecnicos
                    </button>
                </h2>
                <div id="collapseListaTecnicos" class="accordion-collapse collapse show" aria-labelledby="headingListaTecnicos" data-bs-parent="#tecnicosAccordion">
                    <div class="accordion-body px-0 pt-3">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Cadastro</th>
                                    <th class="text-end">Historico</th>
                                    <th class="text-end">Acoes</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($tecnicos)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4">Nenhum tecnico cadastrado.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($tecnicos as $tec): ?>
                                        <tr>
                                            <td><?php echo sanitize($tec['nome']); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($tec['created_at'])); ?></td>
                                            <td class="text-end">
                                                <a href="index.php?route=tecnico_historico&tecnico_id=<?php echo (int) $tec['id']; ?>" class="btn btn-sm btn-outline-primary">Historico</a>
                                            </td>
                                            <td class="text-end">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-primary me-2 btn-edit-tecnico"
                                                    data-id="<?php echo (int) $tec['id']; ?>"
                                                    data-nome="<?php echo sanitize($tec['nome']); ?>"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editTecnicoModal"
                                                >Editar</button>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Deseja excluir este tecnico?');">
                                                    <input type="hidden" name="action" value="tecnico_delete">
                                                    <input type="hidden" name="id" value="<?php echo (int) $tec['id']; ?>">
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
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/modal-editar-tecnico.php'; ?>

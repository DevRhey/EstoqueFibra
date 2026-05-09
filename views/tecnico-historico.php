<?php
$tecnico = $data['tecnico'] ?? null;
$historico = $data['historico'] ?? [];
$selectedDate = $data['selectedDate'] ?? date('Y-m-d');

$historicoDoDia = [];
foreach ($historico as $item) {
    if (!empty($item['data_movimentacao']) && str_starts_with($item['data_movimentacao'], $selectedDate)) {
        $historicoDoDia[] = $item;
    }
}

$movsEntregaDia = array_values(array_filter($historicoDoDia, static fn (array $item): bool => ($item['tipo'] ?? '') === 'entrega'));
$movsUsoDia = array_values(array_filter($historicoDoDia, static fn (array $item): bool => ($item['tipo'] ?? '') === 'uso'));
$movsUsoTesteDia = array_values(array_filter($historicoDoDia, static fn (array $item): bool => ($item['tipo'] ?? '') === 'uso_teste'));
$movsRecolhimentoDia = array_values(array_filter($historicoDoDia, static fn (array $item): bool => ($item['tipo'] ?? '') === 'recolhimento'));
$movsRecolhimentoDefeitoDia = array_values(array_filter($historicoDoDia, static fn (array $item): bool => ($item['tipo'] ?? '') === 'recolhimento_defeito'));
$movsDevolucaoDia = array_values(array_filter($historicoDoDia, static fn (array $item): bool => ($item['tipo'] ?? '') === 'devolucao'));
$historicoRecente = array_slice($historico, 0, 10);
?>

<section class="mb-4 page-intro reveal">
    <h2 class="page-title">Historico do Tecnico</h2>
    <p class="page-subtitle">Movimentacoes registradas para este tecnico em uma pagina dedicada.</p>
</section>

<div class="card card-soft reveal mb-4">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h4 class="mb-1"><?php echo $tecnico ? sanitize($tecnico['nome']) : 'Tecnico nao encontrado'; ?></h4>
            <div class="text-muted">
                <?php if ($tecnico): ?>
                    Cadastrado em <?php echo date('d/m/Y H:i', strtotime($tecnico['created_at'])); ?>
                <?php else: ?>
                    Nenhum tecnico foi localizado com o identificador informado.
                <?php endif; ?>
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <?php if ($tecnico): ?>
                <form method="get" class="d-flex gap-2" style="margin: 0;">
                    <input type="hidden" name="route" value="tecnico_historico">
                    <input type="hidden" name="tecnico_id" value="<?php echo (int) $tecnico['id']; ?>">
                    <input type="date" name="date" value="<?php echo htmlspecialchars($selectedDate); ?>" class="form-control form-control-sm" style="max-width: 180px;">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Filtrar</button>
                </form>
            <?php endif; ?>
            <a href="index.php?route=tecnicos" class="btn btn-outline-primary">Voltar para Tecnicos</a>
        </div>
    </div>
</div>

<?php if (!$tecnico): ?>
    <div class="card card-soft reveal">
        <div class="card-body text-center py-5">
            Historico indisponivel.
        </div>
    </div>
<?php elseif (empty($historicoDoDia)): ?>
    <div class="card card-soft reveal mb-4">
        <div class="card-body tecnico-history-empty-today text-center py-4">
            <h5 class="mb-2">Sem movimentacoes hoje</h5>
            <p class="text-muted mb-0">Este tecnico nao registrou movimentacoes no dia atual. Ultimos registros abaixo.</p>
        </div>
    </div>

    <?php if (!empty($historicoRecente)): ?>
        <div class="card card-soft reveal">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Ultimas Movimentacoes</h5>
                <span class="badge text-bg-secondary"><?php echo count($historicoRecente); ?> registros</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($historicoRecente as $item): ?>
                        <?php $tipoItem = $item['tipo'] ?? ''; ?>
                        <div class="col-12 col-lg-6">
                            <div class="movement-card-lite h-100">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <strong><?php echo sanitize($item['equipamento_nome']); ?></strong>
                                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($item['data_movimentacao'])); ?></small>
                                </div>
                                <div class="small mb-1">
                                    <span class="text-muted">Tipo:</span>
                                    <span class="badge <?php echo in_array($tipoItem, ['entrega', 'devolucao', 'recolhimento'], true) ? 'text-bg-success' : (($tipoItem === 'recolhimento_defeito') ? 'text-bg-danger' : 'text-bg-warning'); ?>"><?php echo sanitize($tipoItem); ?></span>
                                </div>
                                <div class="small mb-1"><span class="text-muted">Quantidade:</span> <strong><?php echo (int) $item['quantidade']; ?></strong></div>
                                <div class="small mb-1"><span class="text-muted">Equipamento:</span> <?php echo sanitize($item['equipamento_tipo']); ?></div>
                                <div class="small mb-1"><span class="text-muted">Local de uso:</span> <?php echo !empty($item['local_uso']) ? sanitize($item['local_uso']) : '-'; ?></div>
                                <div class="small"><span class="text-muted">Observacoes:</span> <?php echo !empty($item['observacoes']) ? sanitize($item['observacoes']) : '-'; ?></div>
                                <?php if (in_array($tipoItem, ['uso', 'uso_teste'], true)): ?>
                                    <form method="post" class="mt-2" onsubmit="return confirm('Deseja realmente excluir este registro de uso?');">
                                        <input type="hidden" name="action" value="movimentacao_delete_uso">
                                        <input type="hidden" name="movimentacao_id" value="<?php echo (int) ($item['id'] ?? 0); ?>">
                                        <input type="hidden" name="tipo" value="<?php echo sanitize($tipoItem); ?>">
                                        <input type="hidden" name="return_route" value="tecnico_historico">
                                        <input type="hidden" name="tecnico_id" value="<?php echo (int) ($tecnico['id'] ?? 0); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Excluir uso</button>
                                    </form>
                                <?php elseif ($tipoItem === 'entrega'): ?>
                                    <form method="post" class="mt-2" onsubmit="return confirm('Deseja realmente excluir esta entrega? O estoque sera recomposto automaticamente.');">
                                        <input type="hidden" name="action" value="movimentacao_delete_entrega">
                                        <input type="hidden" name="movimentacao_id" value="<?php echo (int) ($item['id'] ?? 0); ?>">
                                        <input type="hidden" name="tipo" value="<?php echo sanitize($tipoItem); ?>">
                                        <input type="hidden" name="return_route" value="tecnico_historico">
                                        <input type="hidden" name="tecnico_id" value="<?php echo (int) ($tecnico['id'] ?? 0); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Excluir entrega</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="card card-soft reveal mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Resumo do Dia</h5>
            <span class="badge text-bg-primary"><?php echo count($historicoDoDia); ?> movimentacoes</span>
        </div>
        <div class="card-body history-summary-grid">
            <div class="row g-3 text-center">
                <div class="col-6 col-md-4 col-xl">
                    <small class="text-muted d-block">Entregas</small>
                    <strong><?php echo count($movsEntregaDia); ?></strong>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <small class="text-muted d-block">Usos</small>
                    <strong><?php echo count($movsUsoDia); ?></strong>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <small class="text-muted d-block">Uso em Teste</small>
                    <strong><?php echo count($movsUsoTesteDia); ?></strong>
                </div>
                <div class="col-6 col-md-6 col-xl">
                    <small class="text-muted d-block">Recolhimentos</small>
                    <strong><?php echo count($movsRecolhimentoDia); ?></strong>
                </div>
                <div class="col-6 col-md-6 col-xl">
                    <small class="text-muted d-block">Recolhidos com Defeito</small>
                    <strong><?php echo count($movsRecolhimentoDefeitoDia); ?></strong>
                </div>
                <div class="col-6 col-md-6 col-xl">
                    <small class="text-muted d-block">Devolucoes</small>
                    <strong><?php echo count($movsDevolucaoDia); ?></strong>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-soft reveal">
        <div class="card-header pb-0">
            <div class="history-day-tabs-wrap">
                <ul class="nav nav-pills history-tabs-pills card-header-tabs" id="historicoDiaTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active history-tab-pill" id="entregas-dia-tab" data-bs-toggle="tab" data-bs-target="#entregas-dia" type="button" role="tab" aria-controls="entregas-dia" aria-selected="true">
                            Entrega do Dia <span class="badge text-bg-success ms-1"><?php echo count($movsEntregaDia); ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link history-tab-pill" id="uso-dia-tab" data-bs-toggle="tab" data-bs-target="#uso-dia" type="button" role="tab" aria-controls="uso-dia" aria-selected="false">
                            Uso do Dia <span class="badge text-bg-warning ms-1"><?php echo count($movsUsoDia); ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link history-tab-pill" id="uso-teste-dia-tab" data-bs-toggle="tab" data-bs-target="#uso-teste-dia" type="button" role="tab" aria-controls="uso-teste-dia" aria-selected="false">
                            Uso em Teste <span class="badge text-bg-dark ms-1"><?php echo count($movsUsoTesteDia); ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link history-tab-pill" id="recolhimento-dia-tab" data-bs-toggle="tab" data-bs-target="#recolhimento-dia" type="button" role="tab" aria-controls="recolhimento-dia" aria-selected="false">
                            Recolhimento do Dia <span class="badge text-bg-info ms-1"><?php echo count($movsRecolhimentoDia); ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link history-tab-pill" id="recolhimento-defeito-dia-tab" data-bs-toggle="tab" data-bs-target="#recolhimento-defeito-dia" type="button" role="tab" aria-controls="recolhimento-defeito-dia" aria-selected="false">
                            Recolhimento com Defeito <span class="badge text-bg-danger ms-1"><?php echo count($movsRecolhimentoDefeitoDia); ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link history-tab-pill" id="devolucao-dia-tab" data-bs-toggle="tab" data-bs-target="#devolucao-dia" type="button" role="tab" aria-controls="devolucao-dia" aria-selected="false">
                            Devolucao do Dia <span class="badge text-bg-primary ms-1"><?php echo count($movsDevolucaoDia); ?></span>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
        <div class="card-body tab-content">
            <?php
            $blocos = [
                'entregas-dia' => ['itens' => $movsEntregaDia, 'vazio' => 'Nenhuma entrega registrada hoje.'],
                'uso-dia' => ['itens' => $movsUsoDia, 'vazio' => 'Nenhum uso registrado hoje.'],
                'uso-teste-dia' => ['itens' => $movsUsoTesteDia, 'vazio' => 'Nenhum uso em teste registrado hoje.'],
                'recolhimento-dia' => ['itens' => $movsRecolhimentoDia, 'vazio' => 'Nenhum recolhimento registrado hoje.'],
                'recolhimento-defeito-dia' => ['itens' => $movsRecolhimentoDefeitoDia, 'vazio' => 'Nenhum recolhimento com defeito registrado hoje.'],
                'devolucao-dia' => ['itens' => $movsDevolucaoDia, 'vazio' => 'Nenhuma devolucao registrada hoje.'],
            ];
            $abaIndex = 0;
            ?>
            <?php foreach ($blocos as $abaId => $config): ?>
                <div class="tab-pane fade <?php echo $abaIndex === 0 ? 'show active' : ''; ?>" id="<?php echo $abaId; ?>" role="tabpanel" aria-labelledby="<?php echo $abaId; ?>-tab">
                    <?php if (empty($config['itens'])): ?>
                        <div class="text-center py-4 text-muted"><?php echo $config['vazio']; ?></div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($config['itens'] as $item): ?>
                                <?php $tipoItem = (string) ($item['tipo'] ?? ''); ?>
                                <div class="col-12 col-lg-6">
                                    <div class="history-item-card h-100">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                            <strong><?php echo sanitize($item['equipamento_nome']); ?></strong>
                                            <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($item['data_movimentacao'])); ?></small>
                                        </div>
                                        <div class="small mb-1">
                                            <span class="text-muted">Tipo:</span>
                                            <span class="badge <?php echo in_array($item['tipo'], ['entrega', 'devolucao', 'recolhimento'], true) ? 'text-bg-success' : (($item['tipo'] === 'recolhimento_defeito') ? 'text-bg-danger' : 'text-bg-warning'); ?>"><?php echo sanitize($item['tipo']); ?></span>
                                        </div>
                                        <div class="small mb-1"><span class="text-muted">Quantidade:</span> <strong><?php echo (int) $item['quantidade']; ?></strong></div>
                                        <div class="small mb-1"><span class="text-muted">Equipamento:</span> <?php echo sanitize($item['equipamento_tipo']); ?></div>
                                        <div class="small mb-1"><span class="text-muted">Local de uso:</span> <?php echo !empty($item['local_uso']) ? sanitize($item['local_uso']) : '-'; ?></div>
                                        <div class="small"><span class="text-muted">Observacoes:</span> <?php echo !empty($item['observacoes']) ? sanitize($item['observacoes']) : '-'; ?></div>
                                        <?php if (in_array($tipoItem, ['uso', 'uso_teste'], true)): ?>
                                            <form method="post" class="mt-2" onsubmit="return confirm('Deseja realmente excluir este registro de uso?');">
                                                <input type="hidden" name="action" value="movimentacao_delete_uso">
                                                <input type="hidden" name="movimentacao_id" value="<?php echo (int) ($item['id'] ?? 0); ?>">
                                                <input type="hidden" name="tipo" value="<?php echo sanitize($tipoItem); ?>">
                                                <input type="hidden" name="return_route" value="tecnico_historico">
                                                <input type="hidden" name="tecnico_id" value="<?php echo (int) ($tecnico['id'] ?? 0); ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Excluir uso</button>
                                            </form>
                                        <?php elseif ($tipoItem === 'entrega'): ?>
                                            <form method="post" class="mt-2" onsubmit="return confirm('Deseja realmente excluir esta entrega? O estoque sera recomposto automaticamente.');">
                                                <input type="hidden" name="action" value="movimentacao_delete_entrega">
                                                <input type="hidden" name="movimentacao_id" value="<?php echo (int) ($item['id'] ?? 0); ?>">
                                                <input type="hidden" name="tipo" value="<?php echo sanitize($tipoItem); ?>">
                                                <input type="hidden" name="return_route" value="tecnico_historico">
                                                <input type="hidden" name="tecnico_id" value="<?php echo (int) ($tecnico['id'] ?? 0); ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Excluir entrega</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php $abaIndex++; ?>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

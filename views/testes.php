<?php
$itens = $data['itens'] ?? [];
$resumo = $data['resumo'] ?? [
    'total' => 0,
    'vencidos' => 0,
    'vence_hoje' => 0,
    'proximos_3_dias' => 0,
    'em_andamento' => 0,
];
$porTecnico = $data['porTecnico'] ?? [];
$automation = $data['automation'] ?? [];
$automationTestesResumo = $automation['testes']['resumo'] ?? [
    'critica' => 0,
    'alta' => 0,
    'media' => 0,
    'baixa' => 0,
];
$automationReposicaoResumo = $automation['reposicao']['resumo'] ?? [
    'tecnicos_com_pendencia' => 0,
    'pendente_total' => 0,
];
?>

<section class="mb-4 page-intro reveal">
    <h2 class="page-title">Equipamentos em Teste</h2>
    <p class="page-subtitle">Monitore todos os testes ativos com alertas de vencimento, previsoes e acoes operacionais.</p>
</section>

<div class="card card-soft reveal mb-4 border-info">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Verificacao IA (Heuristica)</h5>
        <span class="badge text-bg-info">Ativa</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-6 col-lg-3">
                <small class="text-muted d-block">Prioridade Critica</small>
                <strong class="text-danger"><?php echo (int) ($automationTestesResumo['critica'] ?? 0); ?></strong>
            </div>
            <div class="col-6 col-lg-3">
                <small class="text-muted d-block">Prioridade Alta</small>
                <strong class="text-warning"><?php echo (int) ($automationTestesResumo['alta'] ?? 0); ?></strong>
            </div>
            <div class="col-6 col-lg-3">
                <small class="text-muted d-block">Prioridade Media</small>
                <strong class="text-info"><?php echo (int) ($automationTestesResumo['media'] ?? 0); ?></strong>
            </div>
            <div class="col-6 col-lg-3">
                <small class="text-muted d-block">Reposicao Pendente Global</small>
                <strong><?php echo (int) ($automationReposicaoResumo['pendente_total'] ?? 0); ?></strong>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4 reveal">
    <div class="col-6 col-lg-2">
        <div class="card card-soft history-stat-card h-100">
            <div class="card-body">
                <small class="text-muted d-block">Total em Teste</small>
                <h3 class="mb-0"><?php echo (int) ($resumo['total'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card card-soft history-stat-card h-100 border-danger">
            <div class="card-body">
                <small class="text-muted d-block">Vencidos</small>
                <h3 class="mb-0 text-danger"><?php echo (int) ($resumo['vencidos'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card card-soft history-stat-card h-100 border-warning">
            <div class="card-body">
                <small class="text-muted d-block">Vencem Hoje</small>
                <h3 class="mb-0 text-warning"><?php echo (int) ($resumo['vence_hoje'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-soft history-stat-card h-100 border-info">
            <div class="card-body">
                <small class="text-muted d-block">Vencem em ate 3 Dias</small>
                <h3 class="mb-0 text-info"><?php echo (int) ($resumo['proximos_3_dias'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-3">
        <div class="card card-soft h-100">
            <div class="card-body">
                <small class="text-muted d-block mb-2">Testes por Tecnico</small>
                <?php if (empty($porTecnico)): ?>
                    <small class="text-muted">Sem tecnicos com teste ativo.</small>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($porTecnico as $tecnicoNome => $total): ?>
                            <span class="badge text-bg-secondary"><?php echo sanitize($tecnicoNome); ?>: <?php echo (int) $total; ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card card-soft reveal">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Lista Completa de Testes Ativos</h5>
        <span class="badge text-bg-primary js-testes-visible-count"><?php echo count($itens); ?> registro(s)</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($itens)): ?>
            <div class="text-center py-5 text-muted">Nenhum equipamento em teste no momento.</div>
        <?php else: ?>
            <div class="p-3 border-bottom">
                <div class="row g-3">
                    <div class="col-12 col-lg-5">
                        <label class="form-label small text-muted mb-1">Filtrar por tecnico</label>
                        <input type="text" class="form-control js-testes-tech-filter" placeholder="Digite o nome do tecnico">
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label small text-muted mb-1">Status</label>
                        <select class="form-select js-testes-status-filter">
                            <option value="">Todos</option>
                            <option value="vencido">Vencido</option>
                            <option value="vence_hoje">Vence hoje</option>
                            <option value="proximo_vencimento">Proximo vencimento</option>
                            <option value="em_andamento">Em andamento</option>
                        </select>
                    </div>
                    <div class="col-12 col-lg-3 d-flex align-items-end">
                        <div class="d-flex gap-2 w-100">
                            <button type="button" class="btn btn-outline-secondary w-100 js-testes-clear-filters">Limpar filtros</button>
                            <button type="button" class="btn btn-outline-primary w-100 js-testes-toggle-order" data-order="asc">Urgencia ↑</button>
                        </div>
                    </div>
                </div>
                <div class="alert alert-dark border d-none mt-3 mb-0 js-testes-empty-filter" role="status">
                    Nenhum teste encontrado para os filtros informados.
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Tecnico</th>
                        <th>Equipamento</th>
                        <th>Qtd</th>
                        <th>Inicio</th>
                        <th>Vencimento</th>
                        <th>Dias</th>
                        <th>Status</th>
                        <th>IA</th>
                        <th>Local</th>
                        <th>Observacoes</th>
                        <th>Previsao</th>
                        <th>Acao IA</th>
                        <th class="text-end">Acoes</th>
                    </tr>
                    </thead>
                    <tbody class="js-testes-rows">
                    <?php foreach ($itens as $item): ?>
                        <tr class="js-teste-row" data-tech-name="<?php echo strtolower(sanitize($item['tecnico_nome'] ?? '')); ?>" data-status="<?php echo sanitize($item['faixa'] ?? 'em_andamento'); ?>" data-due-ts="<?php echo !empty($item['vencimento_teste']) ? (int) strtotime($item['vencimento_teste']) : 0; ?>">
                            <td><strong><?php echo sanitize($item['tecnico_nome'] ?? '-'); ?></strong></td>
                            <td><?php echo sanitize($item['equipamento_nome'] ?? '-'); ?></td>
                            <td><?php echo (int) ($item['quantidade'] ?? 0); ?></td>
                            <td><?php echo !empty($item['inicio_teste']) ? date('d/m/Y H:i', strtotime($item['inicio_teste'])) : '-'; ?></td>
                            <td><?php echo !empty($item['vencimento_teste']) ? date('d/m/Y H:i', strtotime($item['vencimento_teste'])) : '-'; ?></td>
                            <td><?php echo (int) ($item['dias_restantes'] ?? 0); ?></td>
                            <td>
                                <span class="badge <?php echo sanitize($item['badge_class'] ?? 'text-bg-secondary'); ?>">
                                    <?php
                                    echo match ($item['faixa'] ?? '') {
                                        'vencido' => 'VENCIDO',
                                        'vence_hoje' => 'VENCE HOJE',
                                        'proximo_vencimento' => 'PROXIMO',
                                        default => 'EM ANDAMENTO',
                                    };
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $aiPrioridade = (string) ($item['ai_prioridade'] ?? 'baixa');
                                $aiBadgeClass = match ($aiPrioridade) {
                                    'critica' => 'text-bg-danger',
                                    'alta' => 'text-bg-warning',
                                    'media' => 'text-bg-info',
                                    default => 'text-bg-secondary',
                                };
                                ?>
                                <span class="badge <?php echo $aiBadgeClass; ?>">
                                    <?php echo strtoupper(sanitize($aiPrioridade)); ?>
                                </span>
                                <div class="small text-muted">Score: <?php echo (int) ($item['ai_score'] ?? 0); ?></div>
                            </td>
                            <td><?php echo !empty($item['local_uso']) ? sanitize($item['local_uso']) : '-'; ?></td>
                            <td><?php echo !empty($item['observacoes']) ? sanitize($item['observacoes']) : '-'; ?></td>
                            <td><small><?php echo sanitize($item['previsao'] ?? '-'); ?></small></td>
                            <td><small><?php echo sanitize((string) ($item['ai_acao'] ?? 'Monitorar diariamente.')); ?></small></td>
                            <td class="text-end">
                                <div class="d-flex flex-column gap-1 align-items-end">
                                    <a href="index.php?route=movimentacoes&tipo=recolhimento&tecnico_id=<?php echo (int) ($item['tecnico_id'] ?? 0); ?>" class="btn btn-sm btn-outline-info">Recolher</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

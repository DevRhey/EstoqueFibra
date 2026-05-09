<?php
$consumoTecnico = $data['consumoTecnico'] ?? [];
$equipMaisUsados = $data['equipMaisUsados'] ?? [];
$estoqueAtual = $data['estoqueAtual'] ?? [];
$reposicao = $data['reposicao'] ?? [];
$cardsTecnicos = $data['cardsTecnicos'] ?? [];
$estoqueSeguroLabels = [
    'roteador' => 'Roteadores',
    'onu' => 'ONU',
    'conector_fibra' => 'Conectores',
    'conector_rj' => 'Conector RJ',
    'esticador' => 'Esticador',
];

usort($cardsTecnicos, static function (array $a, array $b): int {
    $reporA = (int) ($a['repor_total'] ?? 0);
    $reporB = (int) ($b['repor_total'] ?? 0);

    if ($reporA !== $reporB) {
        return $reporB <=> $reporA;
    }

    $saldoA = (int) ($a['saldo_total_mao'] ?? 0);
    $saldoB = (int) ($b['saldo_total_mao'] ?? 0);
    if ($saldoA !== $saldoB) {
        return $saldoA <=> $saldoB;
    }

    return strcmp((string) ($a['tecnico_nome'] ?? ''), (string) ($b['tecnico_nome'] ?? ''));
});

$totalTecnicos = count($cardsTecnicos);
$totalSaldoMao = 0;
$totalReposicaoPendente = 0;
$tecnicosComPendencia = 0;

foreach ($cardsTecnicos as $cardResumo) {
    $saldo = (int) ($cardResumo['saldo_total_mao'] ?? 0);
    $repor = (int) ($cardResumo['repor_total'] ?? 0);

    $totalSaldoMao += $saldo;
    $totalReposicaoPendente += $repor;

    if ($repor > 0) {
        $tecnicosComPendencia++;
    }
}
?>

<section class="mb-4 page-intro reveal">
    <h2 class="page-title">Relatorios</h2>
    <p class="page-subtitle">Analise consumo, uso dos equipamentos e sugestoes de reposicao.</p>
</section>

<div class="row g-3 mb-4 reveal">
    <div class="col-6 col-lg-3">
        <article class="card card-soft report-kpi-card h-100">
            <div class="card-body">
                <small class="report-kpi-label">Tecnicos monitorados</small>
                <h3 class="report-kpi-value mb-0"><?php echo $totalTecnicos; ?></h3>
            </div>
        </article>
    </div>
    <div class="col-6 col-lg-3">
        <article class="card card-soft report-kpi-card report-kpi-card-highlight h-100">
            <div class="card-body">
                <small class="report-kpi-label">Saldo total em mao</small>
                <h3 class="report-kpi-value mb-0"><?php echo $totalSaldoMao; ?></h3>
            </div>
        </article>
    </div>
    <div class="col-6 col-lg-3">
        <article class="card card-soft report-kpi-card report-kpi-card-warning h-100">
            <div class="card-body">
                <small class="report-kpi-label">Reposicao pendente</small>
                <h3 class="report-kpi-value mb-0"><?php echo $totalReposicaoPendente; ?></h3>
            </div>
        </article>
    </div>
    <div class="col-6 col-lg-3">
        <article class="card card-soft report-kpi-card h-100">
            <div class="card-body">
                <small class="report-kpi-label">Tecnicos com pendencia</small>
                <h3 class="report-kpi-value mb-0"><?php echo $tecnicosComPendencia; ?></h3>
            </div>
        </article>
    </div>
</div>

<div class="card card-soft reveal mb-4 report-toolbar-card">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-4">
                <label class="form-label small text-muted mb-1">Buscar tecnico nos cards</label>
                <input type="text" class="form-control js-report-card-search" placeholder="Digite o nome do tecnico">
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label small text-muted mb-1">Ordenar cards por</label>
                <select class="form-select js-report-sort-cards">
                    <option value="prioridade" selected>Maior reposicao pendente</option>
                    <option value="saldo_asc">Menor saldo em mao</option>
                    <option value="saldo_desc">Maior saldo em mao</option>
                    <option value="nome">Nome do tecnico (A-Z)</option>
                </select>
            </div>
            <div class="col-12 col-lg-3">
                <div class="form-check report-toolbar-check mt-lg-4">
                    <input class="form-check-input js-report-only-shortage" type="checkbox" id="report-only-shortage">
                    <label class="form-check-label" for="report-only-shortage">
                        Mostrar apenas tecnicos com reposicao pendente
                    </label>
                </div>
            </div>
            <div class="col-12 col-lg-2 d-grid gap-2">
                <button type="button" class="btn btn-outline-info js-report-export-cards">Exportar XLSX</button>
                <button type="button" class="btn btn-outline-secondary js-report-clear-filters">Limpar</button>
            </div>
            <div class="col-12">
                <small class="text-muted">Cards visiveis: <strong class="js-report-visible-count"><?php echo (int) count($cardsTecnicos); ?></strong></small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 reveal">
        <?php require __DIR__ . '/partials/tech-risk-legend.php'; ?>
    </div>
    <?php if (empty($cardsTecnicos)): ?>
        <div class="col-12 reveal">
            <div class="card card-soft">
                <div class="card-body text-center py-4">Nenhum tecnico cadastrado para gerar cards.</div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($cardsTecnicos as $card): ?>
            <?php
            $reporTotal = (int) ($card['repor_total'] ?? 0);
            $techRiskClass = $reporTotal === 0 ? 'tech-name-emphasis-ok' : ($reporTotal <= 3 ? 'tech-name-emphasis-warning' : 'tech-name-emphasis-critical');
            ?>
              <div class="col-12 col-xxl-6 reveal js-report-card"
                  data-tech-name="<?php echo strtolower(sanitize($card['tecnico_nome'])); ?>"
                  data-tech-label="<?php echo sanitize($card['tecnico_nome']); ?>"
                  data-has-shortage="<?php echo $reporTotal > 0 ? '1' : '0'; ?>"
                  data-repor-total="<?php echo (int) ($card['repor_total'] ?? 0); ?>"
                  data-saldo-total="<?php echo (int) ($card['saldo_total_mao'] ?? 0); ?>"
                  data-total-entrega="<?php echo (int) ($card['total_entrega'] ?? 0); ?>"
                  data-total-uso="<?php echo (int) ($card['total_uso'] ?? 0); ?>"
                  data-total-devolvido="<?php echo (int) ($card['total_devolvido'] ?? 0); ?>"
                  data-total-recolhido="<?php echo (int) ($card['total_recolhido'] ?? 0); ?>"
              >
                <div class="card card-soft h-100 report-tech-card">
                    <div class="card-header d-flex justify-content-between align-items-center gap-2">
                        <h5 class="mb-0"><span class="tech-name-emphasis <?php echo $techRiskClass; ?>"><?php echo sanitize($card['tecnico_nome']); ?></span></h5>
                        <span class="badge text-bg-primary">Saldo em mao: <?php echo (int) $card['saldo_total_mao']; ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 mb-3 report-tech-stats">
                            <div class="col-6 col-md-3"><small class="text-muted">Entregue</small><div><strong><?php echo (int) $card['total_entrega']; ?></strong></div></div>
                            <div class="col-6 col-md-3"><small class="text-muted">Usado</small><div><strong><?php echo (int) $card['total_uso']; ?></strong></div></div>
                            <div class="col-6 col-md-3"><small class="text-muted">Devolvido</small><div><strong><?php echo (int) $card['total_devolvido']; ?></strong></div></div>
                            <div class="col-6 col-md-3"><small class="text-muted">Recolhido</small><div><strong><?php echo (int) ($card['total_recolhido'] ?? 0); ?></strong></div></div>
                        </div>

                        <h6 class="mb-2">Equipamentos na mao</h6>
                        <?php if (empty($card['equipamentos_mao'])): ?>
                            <p class="text-muted mb-3">Nenhum item em aberto com este tecnico.</p>
                        <?php else: ?>
                            <div class="d-flex flex-wrap gap-2 mb-3 report-chip-cloud">
                                <?php foreach ($card['equipamentos_mao'] as $eq): ?>
                                    <span class="badge rounded-pill text-bg-secondary">
                                        <?php echo sanitize($eq['nome']); ?> (<?php echo sanitize($eq['tipo']); ?>): <?php echo (int) $eq['saldo_mao']; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <h6 class="mb-2">Estoque Seguro</h6>
                        <?php
                        $saldoCategoria = $card['saldo_por_categoria'] ?? [];
                        $saldoEfetivo = $card['saldo_por_categoria_efetivo'] ?? [];
                        $ontEmMao = (int) ($saldoCategoria['ont'] ?? 0);
                        ?>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge text-bg-success">Roteadores (efetivo): <?php echo (int) ($saldoEfetivo['roteador'] ?? 0); ?>/3</span>
                            <span class="badge text-bg-primary">ONU (efetivo): <?php echo (int) ($saldoEfetivo['onu'] ?? 0); ?>/2</span>
                            <span class="badge text-bg-secondary">ONT em mão: <?php echo $ontEmMao; ?></span>
                            <span class="badge text-bg-info text-dark">Conectores: <?php echo (int) ($card['saldo_por_categoria']['conector_fibra'] ?? 0); ?>/10</span>
                            <span class="badge text-bg-warning text-dark">Conector RJ: <?php echo (int) ($card['saldo_por_categoria']['conector_rj'] ?? 0); ?>/8</span>
                            <span class="badge text-bg-light text-dark">Esticador: <?php echo (int) ($card['saldo_por_categoria']['esticador'] ?? 0); ?>/8</span>
                        </div>
                        <?php if ($ontEmMao > 0): ?>
                            <p class="text-muted small mb-3">Cada ONT em mão substitui 1 ONU e 1 roteador no cálculo de reposição.</p>
                        <?php endif; ?>
                        <?php if (!empty($card['estoque_seguro_ok'])): ?>
                            <p class="text-success mb-3">Saldo seguro atendido.</p>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-1 mb-3 report-shortage-list">
                                <?php foreach (($card['reposicao_proximo_dia'] ?? []) as $repor): ?>
                                    <?php if ((int) $repor['faltante'] <= 0) { continue; } ?>
                                    <div class="dark-panel-subtle small p-2 rounded">
                                        <strong><?php echo sanitize($estoqueSeguroLabels[$repor['categoria']] ?? $repor['categoria']); ?></strong>
                                        | repor: <span class="text-danger fw-bold"><?php echo (int) $repor['faltante']; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <h6 class="mb-2">Usos recentes e local</h6>
                        <?php if (empty($card['usos_recentes'])): ?>
                            <p class="text-muted mb-3">Sem registros recentes de uso.</p>
                        <?php else: ?>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm align-middle mb-0 report-table-compact">
                                    <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Equipamento</th>
                                        <th>Qtd</th>
                                        <th>Onde</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($card['usos_recentes'] as $uso): ?>
                                        <tr>
                                            <td><?php echo date('d/m H:i', strtotime($uso['data_movimentacao'])); ?></td>
                                            <td><?php echo sanitize($uso['equipamento_nome']); ?> (<?php echo sanitize($uso['equipamento_tipo']); ?>)</td>
                                            <td><?php echo (int) $uso['quantidade']; ?></td>
                                            <td><?php echo !empty($uso['local_uso']) ? sanitize($uso['local_uso']) : '-'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <h6 class="mb-2">Repor no proximo dia</h6>
                        <?php if (empty($card['reposicao_proximo_dia'])): ?>
                            <p class="text-success mb-0">Nada a repor para iniciar o dia.</p>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-1">
                                <?php foreach ($card['reposicao_proximo_dia'] as $rep): ?>
                                    <?php if ((int) $rep['faltante'] <= 0) { continue; } ?>
                                    <div class="dark-panel-subtle small p-2 rounded">
                                        <strong><?php echo sanitize($estoqueSeguroLabels[$rep['categoria']] ?? $rep['categoria']); ?></strong>
                                        | necessário: <?php echo (int) $rep['necessario']; ?>
                                        | atual: <?php echo (int) $rep['atual']; ?>
                                        | repor: <span class="text-danger fw-bold"><?php echo (int) $rep['faltante']; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="alert alert-dark border d-none js-report-cards-empty reveal" role="status">
    Nenhum tecnico encontrado para os filtros selecionados.
</div>

<div class="row g-4">
    <div class="col-xl-6 reveal">
        <div class="card card-soft h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Consumo por Tecnico</h5>
                <span class="badge text-bg-secondary"><?php echo count($consumoTecnico); ?> itens</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 report-table">
                        <thead><tr><th>Tecnico</th><th>Total Consumido</th><th>Prioridade</th></tr></thead>
                        <tbody>
                        <?php foreach ($consumoTecnico as $row): ?>
                            <?php
                            $consumo = (int) ($row['total_consumido'] ?? 0);
                            $consumoPriorityClass = $consumo >= 40 ? 'text-bg-danger' : ($consumo >= 15 ? 'text-bg-warning' : 'text-bg-success');
                            $consumoPriorityLabel = $consumo >= 40 ? 'Alta' : ($consumo >= 15 ? 'Media' : 'Baixa');
                            ?>
                            <tr>
                                <td><?php echo sanitize($row['tecnico']); ?></td>
                                <td><?php echo $consumo; ?></td>
                                <td><span class="badge <?php echo $consumoPriorityClass; ?> report-priority-badge"><?php echo $consumoPriorityLabel; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6 reveal">
        <div class="card card-soft h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Equipamentos Mais Utilizados</h5>
                <span class="badge text-bg-secondary"><?php echo count($equipMaisUsados); ?> itens</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 report-table">
                        <thead><tr><th>Equipamento</th><th>Uso</th><th>Entrega</th><th>Prioridade</th></tr></thead>
                        <tbody>
                        <?php foreach ($equipMaisUsados as $row): ?>
                            <?php
                            $totalUso = (int) ($row['total_uso'] ?? 0);
                            $equipPriorityClass = $totalUso >= 50 ? 'text-bg-danger' : ($totalUso >= 20 ? 'text-bg-warning' : 'text-bg-secondary');
                            $equipPriorityLabel = $totalUso >= 50 ? 'Alta' : ($totalUso >= 20 ? 'Media' : 'Baixa');
                            ?>
                            <tr>
                                <td><?php echo sanitize($row['nome']); ?> (<?php echo sanitize($row['tipo']); ?>)</td>
                                <td><?php echo $totalUso; ?></td>
                                <td><?php echo (int) $row['total_entrega']; ?></td>
                                <td><span class="badge <?php echo $equipPriorityClass; ?> report-priority-badge"><?php echo $equipPriorityLabel; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6 reveal">
        <div class="card card-soft h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Estoque Atual</h5>
                <span class="badge text-bg-secondary"><?php echo count($estoqueAtual); ?> itens</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 report-table">
                        <thead><tr><th>Equipamento</th><th>Tipo</th><th>Estoque</th><th>Prioridade</th></tr></thead>
                        <tbody>
                        <?php foreach ($estoqueAtual as $row): ?>
                            <?php
                            $quantidadeAtual = (int) ($row['quantidade'] ?? 0);
                            $estoquePriorityClass = $quantidadeAtual < 5 ? 'text-bg-danger' : ($quantidadeAtual < 10 ? 'text-bg-warning' : 'text-bg-success');
                            $estoquePriorityLabel = $quantidadeAtual < 5 ? 'Critica' : ($quantidadeAtual < 10 ? 'Atencao' : 'Saudavel');
                            ?>
                            <tr>
                                <td><?php echo sanitize($row['nome']); ?></td>
                                <td><?php echo sanitize($row['tipo']); ?></td>
                                <td>
                                    <span class="badge <?php echo ($quantidadeAtual < 5) ? 'text-bg-danger' : 'text-bg-success'; ?>">
                                        <?php echo $quantidadeAtual; ?>
                                    </span>
                                </td>
                                <td><span class="badge <?php echo $estoquePriorityClass; ?> report-priority-badge"><?php echo $estoquePriorityLabel; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6 reveal">
        <div class="card card-soft h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Sugestao de Reposicao (7 dias)</h5>
                <span class="badge text-bg-secondary"><?php echo count($reposicao); ?> itens</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 report-table">
                        <thead><tr><th>Equipamento</th><th>Media Dia</th><th>Reposicao Sugerida</th><th>Prioridade</th></tr></thead>
                        <tbody>
                        <?php foreach ($reposicao as $row): ?>
                            <?php
                            $reposicaoSugestao = (int) ($row['sugestao_reposicao_7_dias'] ?? 0);
                            $reposicaoPriorityClass = $reposicaoSugestao >= 20 ? 'text-bg-danger' : ($reposicaoSugestao >= 8 ? 'text-bg-warning' : 'text-bg-secondary');
                            $reposicaoPriorityLabel = $reposicaoSugestao >= 20 ? 'Alta' : ($reposicaoSugestao >= 8 ? 'Media' : 'Baixa');
                            ?>
                            <tr>
                                <td><?php echo sanitize($row['nome']); ?> (<?php echo sanitize($row['tipo']); ?>)</td>
                                <td><?php echo (float) $row['media_consumo_dia']; ?></td>
                                <td><?php echo $reposicaoSugestao; ?></td>
                                <td><span class="badge <?php echo $reposicaoPriorityClass; ?> report-priority-badge"><?php echo $reposicaoPriorityLabel; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

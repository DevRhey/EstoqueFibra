<?php
$registros = $data['registros'] ?? [];
$tecnicos = $data['tecnicos'] ?? [];
$filters = $data['filters'] ?? [
    'date' => null,
    'tecnico_id' => 0,
    'q' => '',
];
$resumo = $data['resumo'] ?? [
    'registros' => 0,
    'quantidade_total' => 0,
    'tecnicos' => 0,
    'equipamentos' => 0,
];
?>

<section class="mb-4 page-intro reveal">
    <h2 class="page-title">Equipamentos com Defeito</h2>
    <p class="page-subtitle">Itens devolvidos com defeito saem da mao do tecnico e ficam nesta lista, sem retornar ao estoque normal.</p>
</section>

<div class="row g-3 mb-4 reveal">
    <div class="col-6 col-lg-3">
        <div class="card card-soft h-100 history-stat-card history-stat-card-total">
            <div class="card-body">
                <small class="text-muted d-block">Registros</small>
                <h3 class="mb-0"><?php echo (int) ($resumo['registros'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-soft h-100 history-stat-card history-stat-card-recolhimento-defeito">
            <div class="card-body">
                <small class="text-muted d-block">Quantidade total</small>
                <h3 class="mb-0"><?php echo (int) ($resumo['quantidade_total'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-soft h-100 history-stat-card history-stat-card-tecnicos">
            <div class="card-body">
                <small class="text-muted d-block">Tecnicos envolvidos</small>
                <h3 class="mb-0"><?php echo (int) ($resumo['tecnicos'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-soft h-100 history-stat-card history-stat-card-entrega">
            <div class="card-body">
                <small class="text-muted d-block">Equipamentos distintos</small>
                <h3 class="mb-0"><?php echo (int) ($resumo['equipamentos'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card card-soft reveal mb-4 sticky-date-filter">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <input type="hidden" name="route" value="equipamentos_defeito">

            <div class="col-12 col-lg-3">
                <label class="form-label mb-1">Data</label>
                <input type="date" name="date" class="form-control" value="<?php echo sanitize((string) ($filters['date'] ?? '')); ?>">
            </div>

            <div class="col-12 col-lg-3">
                <label class="form-label mb-1">Tecnico</label>
                <select name="tecnico_id" class="form-select">
                    <option value="0">Todos</option>
                    <?php foreach ($tecnicos as $tec): ?>
                        <?php $tecId = (int) ($tec['id'] ?? 0); ?>
                        <option value="<?php echo $tecId; ?>" <?php echo ((int) ($filters['tecnico_id'] ?? 0) === $tecId) ? 'selected' : ''; ?>>
                            <?php echo sanitize((string) ($tec['nome'] ?? 'Tecnico')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-lg-4">
                <label class="form-label mb-1">Busca</label>
                <input type="text" name="q" class="form-control" placeholder="Tecnico, equipamento ou observacoes" value="<?php echo sanitize((string) ($filters['q'] ?? '')); ?>">
            </div>

            <div class="col-12 col-lg-2 d-grid gap-2">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="index.php?route=equipamentos_defeito" class="btn btn-outline-secondary">Limpar</a>
            </div>
        </form>
    </div>
</div>

<div class="card card-soft reveal">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Lista de Defeitos</h5>
        <span class="badge text-bg-danger"><?php echo (int) count($registros); ?> registro(s)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th>Data</th>
                    <th>Tecnico</th>
                    <th>Equipamento</th>
                    <th>Tipo</th>
                    <th>Codigo barras</th>
                    <th>Serial</th>
                    <th>Qtd</th>
                    <th>Defeito</th>
                    <th>Observacoes</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($registros)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4">Nenhum equipamento com defeito encontrado para os filtros informados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($registros as $item): ?>
                        <tr>
                            <td><?php echo !empty($item['data_movimentacao']) ? date('d/m/Y H:i', strtotime((string) $item['data_movimentacao'])) : '-'; ?></td>
                            <td><?php echo sanitize((string) ($item['tecnico_nome'] ?? 'Sem tecnico')); ?></td>
                            <td><?php echo sanitize((string) ($item['equipamento_nome'] ?? 'Equipamento')); ?></td>
                            <td><?php echo sanitize((string) ($item['equipamento_tipo'] ?? '-')); ?></td>
                            <td><?php echo sanitize((string) ($item['equipamento_codigo_barras'] ?? '-')); ?></td>
                            <td><?php echo sanitize((string) ($item['serial_equipamento'] ?? '-')); ?></td>
                            <td><strong><?php echo (int) ($item['quantidade'] ?? 0); ?></strong></td>
                            <td><?php echo sanitize((string) ($item['motivo_defeito'] ?? '-')); ?></td>
                            <td><?php echo sanitize((string) ($item['observacoes'] ?? '-')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

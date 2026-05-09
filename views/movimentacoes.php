<?php
$movimentacoes = $data['movimentacoes'] ?? [];
$equipamentos = $data['equipamentos'] ?? [];
$tecnicos = $data['tecnicos'] ?? [];
$cardsTecnicos = $data['cardsTecnicos'] ?? [];
$alertasUsoTeste = $data['alertasUsoTeste'] ?? [];
$aparelhosComDefeito = $data['aparelhosComDefeito'] ?? [];
$recolhimentosSemLastro = $data['recolhimentosSemLastro'] ?? [];
$integrityIssues = $data['integrityIssues'] ?? [
    'estoque_negativo' => [],
    'saldo_mao_negativo' => [],
    'saldo_campo_global_negativo' => [],
    'total_issues' => 0,
];
$selectedDate = $data['selectedDate'] ?? date('Y-m-d');
$defectFilterDate = $data['defectFilterDate'] ?? $selectedDate;
$usageFilters = $data['usageFilters'] ?? [
    'apply' => false,
    'tecnico_id' => 0,
    'equipamento_tipo' => '',
    'start' => date('Y-m-d', strtotime('-6 days')),
    'end' => date('Y-m-d'),
];
$usageReport = $data['usageReport'] ?? [];
$usageSummary = $data['usageSummary'] ?? [
    'total_itens' => 0,
    'total_quantidade' => 0,
    'total_registros' => 0,
    'dias_periodo' => 0,
];

$equipamentosEmMaoPorTecnico = [];
$equipamentosEmCampoPorTecnico = [];
foreach ($cardsTecnicos as $card) {
    $equipamentosEmMaoPorTecnico[(int) $card['tecnico_id']] = $card['equipamentos_mao'] ?? [];
    $equipamentosEmCampoPorTecnico[(int) $card['tecnico_id']] = $card['equipamentos_campo'] ?? [];
}

$movimentacoesPorTecnico = [];
$resumoPorTecnico = [];
$contagemTipos = [
    'entrega' => 0,
    'uso' => 0,
    'uso_teste' => 0,
    'devolucao' => 0,
    'recolhimento' => 0,
    'recolhimento_defeito' => 0,
];
$movimentacoesHoje = 0;
$hoje = $selectedDate;
$tiposEquipamentoDisponiveis = [];

foreach ($equipamentos as $equipamentoTipoItem) {
    $tipoItem = trim((string) ($equipamentoTipoItem['tipo'] ?? ''));
    if ($tipoItem === '') {
        continue;
    }

    $tiposEquipamentoDisponiveis[$tipoItem] = true;
}

$tiposEquipamentoDisponiveis = array_keys($tiposEquipamentoDisponiveis);
sort($tiposEquipamentoDisponiveis);

foreach ($movimentacoes as $mov) {
    $tecnicoNome = $mov['tecnico_nome'] ?? 'Sem tecnico';
    $tipo = $mov['tipo'] ?? '';
    $dataMov = $mov['data_movimentacao'] ?? '';

    if (!isset($movimentacoesPorTecnico[$tecnicoNome])) {
        $movimentacoesPorTecnico[$tecnicoNome] = [];
    }

    if (!isset($resumoPorTecnico[$tecnicoNome])) {
        $resumoPorTecnico[$tecnicoNome] = [
            'entrega' => 0,
            'uso' => 0,
            'uso_teste' => 0,
            'devolucao' => 0,
            'recolhimento' => 0,
            'recolhimento_defeito' => 0,
            'total' => 0,
        ];
    }

    if (isset($contagemTipos[$tipo])) {
        $contagemTipos[$tipo]++;
        $resumoPorTecnico[$tecnicoNome][$tipo]++;
    }

    if ($dataMov !== '' && str_starts_with($dataMov, $hoje)) {
        $movimentacoesHoje++;
    }

    $resumoPorTecnico[$tecnicoNome]['total']++;

    $movimentacoesPorTecnico[$tecnicoNome][] = $mov;
}
?>

<section class="mb-4 page-intro reveal">
    <h2 class="page-title">Movimentacoes</h2>
    <p class="page-subtitle">Registre entregas, usos, uso em teste, devolucoes e recolhimentos com atualizacao automatica de estoque.</p>
</section>

<div class="card card-soft reveal mb-4 sticky-date-filter">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <input type="hidden" name="route" value="movimentacoes">
            <div class="col-12 col-md-4 col-lg-3">
                <label class="form-label mb-1">Data de Referencia</label>
                <input type="date" name="date" class="form-control" value="<?php echo sanitize($selectedDate); ?>">
            </div>
            <div class="col-12 col-md-8 col-lg-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Aplicar Data</button>
                <a href="index.php?route=movimentacoes" class="btn btn-outline-secondary">Hoje</a>
            </div>
            <div class="col-12 col-lg-5">
                <small class="text-muted">Exibindo movimentacoes do dia: <strong><?php echo date('d/m/Y', strtotime($selectedDate)); ?></strong></small>
            </div>
        </form>
    </div>
</div>

<div class="card card-soft reveal mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Consulta de Uso por Tecnico no Periodo</h5>
        <span class="badge text-bg-primary">Filtro personalizado</span>
    </div>
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <input type="hidden" name="route" value="movimentacoes">
            <input type="hidden" name="date" value="<?php echo sanitize($selectedDate); ?>">
            <input type="hidden" name="usage_apply" value="1">

            <div class="col-12 col-md-6 col-xl-3">
                <label class="form-label mb-1">Tecnico</label>
                <select name="usage_tecnico_id" class="form-select">
                    <option value="0">Todos os tecnicos</option>
                    <?php foreach ($tecnicos as $tec): ?>
                        <option
                            value="<?php echo (int) $tec['id']; ?>"
                            <?php echo (int) ($usageFilters['tecnico_id'] ?? 0) === (int) $tec['id'] ? 'selected' : ''; ?>
                        >
                            <?php echo sanitize((string) $tec['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label mb-1">Inicio</label>
                <input type="date" name="usage_start" class="form-control" value="<?php echo sanitize((string) ($usageFilters['start'] ?? '')); ?>" required>
            </div>

            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label mb-1">Fim</label>
                <input type="date" name="usage_end" class="form-control" value="<?php echo sanitize((string) ($usageFilters['end'] ?? '')); ?>" required>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <label class="form-label mb-1">Tipo de Equipamento</label>
                <select name="usage_equipment_type" class="form-select">
                    <option value="">Todos os tipos</option>
                    <?php foreach ($tiposEquipamentoDisponiveis as $tipoOption): ?>
                        <option value="<?php echo sanitize($tipoOption); ?>" <?php echo (string) ($usageFilters['equipamento_tipo'] ?? '') === $tipoOption ? 'selected' : ''; ?>>
                            <?php echo sanitize($tipoOption); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-6 col-xl-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Consultar</button>
                <a href="index.php?route=movimentacoes&amp;date=<?php echo urlencode((string) $selectedDate); ?>" class="btn btn-outline-secondary w-100">Limpar</a>
            </div>
        </form>

        <?php if (!empty($usageFilters['apply'])): ?>
            <hr class="my-3">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="badge text-bg-secondary">Periodo: <?php echo date('d/m/Y', strtotime((string) ($usageFilters['start'] ?? ''))); ?> ate <?php echo date('d/m/Y', strtotime((string) ($usageFilters['end'] ?? ''))); ?></span>
                <span class="badge text-bg-info text-dark">Dias: <?php echo (int) ($usageSummary['dias_periodo'] ?? 0); ?></span>
                <span class="badge text-bg-dark">Qtd total usada: <?php echo (int) ($usageSummary['total_quantidade'] ?? 0); ?></span>
                <span class="badge text-bg-primary">Registros base: <?php echo (int) ($usageSummary['total_registros'] ?? 0); ?></span>
            </div>

            <?php if (empty($usageReport)): ?>
                <div class="alert alert-dark border mb-0">Nenhum uso encontrado para os filtros informados.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Tecnico</th>
                            <th>Equipamento</th>
                            <th>Tipo</th>
                            <th>Qtd usada</th>
                            <th>Registros</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($usageReport as $usoItem): ?>
                            <tr>
                                <td><?php echo sanitize((string) ($usoItem['tecnico_nome'] ?? 'Sem tecnico')); ?></td>
                                <td><?php echo sanitize((string) ($usoItem['equipamento_nome'] ?? 'Equipamento')); ?></td>
                                <td><?php echo sanitize((string) ($usoItem['equipamento_tipo'] ?? 'indefinido')); ?></td>
                                <td><strong><?php echo (int) ($usoItem['total_usado'] ?? 0); ?></strong></td>
                                <td><?php echo (int) ($usoItem['total_registros'] ?? 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Botões de Operações Principais -->
<div class="row g-3 mb-4 reveal">
    <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
        <button class="btn btn-success w-100 h-100 p-4" data-bs-toggle="modal" data-bs-target="#modal-entrega">
            <div class="mb-2" style="font-size: 24px;">📦</div>
            <strong>Entregar</strong>
            <small class="d-block mt-1">Equipamento ao técnico</small>
        </button>
    </div>
    <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
        <button class="btn btn-warning w-100 h-100 p-4" data-bs-toggle="modal" data-bs-target="#modal-uso">
            <div class="mb-2" style="font-size: 24px;">🔧</div>
            <strong>Registrar Uso</strong>
            <small class="d-block mt-1">Uso no cliente</small>
        </button>
    </div>
    <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
        <button class="btn btn-dark w-100 h-100 p-4" data-bs-toggle="modal" data-bs-target="#modal-uso-teste">
            <div class="mb-2" style="font-size: 24px;">🧪</div>
            <strong>Uso em Teste</strong>
            <small class="d-block mt-1">Teste por 3 dias</small>
        </button>
    </div>
    <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
        <button class="btn btn-info w-100 h-100 p-4" data-bs-toggle="modal" data-bs-target="#modal-recolhimento">
            <div class="mb-2" style="font-size: 24px;">🔄</div>
            <strong>Recolher</strong>
            <small class="d-block mt-1">Entra direto no estoque geral</small>
        </button>
    </div>
    <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
        <button class="btn btn-danger w-100 h-100 p-4" data-bs-toggle="modal" data-bs-target="#modal-recolhimento-defeito">
            <div class="mb-2" style="font-size: 24px;">⚠️</div>
            <strong>Recolher c/ defeito</strong>
            <small class="d-block mt-1">Lista especial de defeitos</small>
        </button>
    </div>
    <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
        <button class="btn btn-primary w-100 h-100 p-4" data-bs-toggle="modal" data-bs-target="#modal-devolucao">
            <div class="mb-2" style="font-size: 24px;">↩️</div>
            <strong>Devolver</strong>
            <small class="d-block mt-1">Retorno ao estoque</small>
        </button>
    </div>
</div>

<!-- Modal: ENTREGA -->
<?php
    $return_route = '';
    $include_batch = true;
    require __DIR__ . '/partials/modal-entrega.php';
?>

<!-- Modal: USO -->
<?php
    $return_route = '';
    $require_local_uso = true;
    require __DIR__ . '/partials/modal-uso.php';
?>

<!-- Modal: USO EM TESTE -->
<?php
    $return_route = '';
    require __DIR__ . '/partials/modal-uso-teste.php';
?>

<!-- Modal: RECOLHIMENTO -->
<?php
    $return_route = '';
    $include_batch = true;
    require __DIR__ . '/partials/modal-recolhimento.php';
?>

<?php
    $return_route = '';
    $include_batch = true;
    require __DIR__ . '/partials/modal-recolhimento-defeito.php';
?>

<?php
    $return_route = '';
    $include_batch = true;
    require __DIR__ . '/partials/modal-devolucao.php';
?>

<!-- Modal: EDITAR USO -->
<?php require __DIR__ . '/partials/modal-editar-uso.php'; ?>

<!-- Seção: Importar Uso em Teste via Excel -->
<div class="card card-soft mb-4 reveal" id="import-uso-teste">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">Importar Equipamentos em Teste (Excel)</h5>
        <span class="badge text-bg-dark">Padrão: TecnicoID, CodigoBarras, Quantidade, Local, Observações</span>
    </div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data" class="needs-validation js-uso-teste-import-form" novalidate>
            <input type="hidden" name="action" value="movimentacao_importar_uso_teste">
            <input type="hidden" name="linhas_importacao_json" class="js-uso-teste-import-json">
            <input type="hidden" name="selected_date" value="<?php echo sanitize($selectedDate); ?>">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-7">
                    <label class="form-label fw-bold">Arquivo da planilha (Excel)</label>
                    <input type="file" name="planilha_uso_teste" class="form-control js-uso-teste-file" accept=".xlsx,.xls,.csv" required>
                    <div class="form-text">A leitura é feita no navegador. Depois confirme para gravar no banco de dados.</div>
                    <div class="invalid-feedback">Selecione uma planilha válida (.xlsx, .xls ou .csv).</div>
                </div>
                <div class="col-12 col-lg-5 d-grid">
                    <button type="button" class="btn btn-outline-dark js-uso-teste-parse">Ler planilha</button>
                </div>
            </div>

            <div class="alert alert-dark border mt-3 mb-0 d-none js-uso-teste-preview" role="status">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <strong>Pré-visualização da importação</strong>
                    <span class="badge text-bg-dark js-uso-teste-preview-count">0 linha(s)</span>
                </div>
                <div class="small js-uso-teste-preview-columns mb-2"></div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-2">
                        <thead>
                        <tr>
                            <th>TecnicoID</th>
                            <th>CodigoBarras</th>
                            <th>Quantidade</th>
                            <th>Local</th>
                            <th>Observações</th>
                        </tr>
                        </thead>
                        <tbody class="js-uso-teste-preview-body"></tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-dark">Confirmar Importação</button>
            </div>
        </form>
    </div>
</div>

<!-- Histórico de Movimentações -->
<?php if (!empty($alertasUsoTeste)): ?>
    <div class="card card-soft border-warning reveal mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-warning">Alertas de Uso em Teste</h6>
            <span class="badge text-bg-warning"><?php echo count($alertasUsoTeste); ?> pendentes</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php foreach ($alertasUsoTeste as $alerta): ?>
                    <div class="col-12 col-xl-6">
                        <div class="movement-note <?php echo ($alerta['status'] ?? '') === 'vencido' ? 'border border-danger' : ''; ?>">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <strong><?php echo sanitize($alerta['tecnico_nome']); ?></strong>
                                    <div class="small text-muted">
                                        <?php echo sanitize($alerta['equipamento_nome']); ?>
                                        <?php if (!empty($alerta['equipamento_tipo'])): ?>
                                            (<?php echo sanitize((string) $alerta['equipamento_tipo']); ?>)
                                        <?php endif; ?>
                                        | qtd <?php echo (int) $alerta['quantidade']; ?>
                                    </div>
                                </div>
                                <span class="badge <?php echo ($alerta['status'] ?? '') === 'vencido' ? 'text-bg-danger' : 'text-bg-warning'; ?>">
                                    <?php echo ($alerta['status'] ?? '') === 'vencido' ? 'Vencido' : 'Em andamento'; ?>
                                </span>
                            </div>
                            <div class="small mt-2">
                                <div><span class="text-muted">Inicio:</span> <?php echo date('d/m/Y H:i', strtotime($alerta['inicio_teste'])); ?></div>
                                <div><span class="text-muted">Vencimento:</span> <?php echo date('d/m/Y H:i', strtotime($alerta['vencimento_teste'])); ?></div>
                                <div><span class="text-muted">Dias restantes:</span> <?php echo (int) ($alerta['dias_restantes'] ?? 0); ?></div>
                                <div><span class="text-muted">Local:</span> <?php echo !empty($alerta['local_uso']) ? sanitize($alerta['local_uso']) : '-'; ?></div>
                                <?php if (!empty($alerta['equipamento_codigo_barras'])): ?>
                                    <div><span class="text-muted">Codigo de barras:</span> <?php echo sanitize((string) $alerta['equipamento_codigo_barras']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($alerta['historico_tratativa'])): ?>
                                    <div class="mt-2 movement-note mb-0"><?php echo nl2br(sanitize((string) $alerta['historico_tratativa'])); ?></div>
                                <?php elseif (!empty($alerta['observacoes'])): ?>
                                    <div class="mt-2 movement-note mb-0"><?php echo nl2br(sanitize((string) $alerta['observacoes'])); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($integrityIssues['total_issues'])): ?>
    <div class="card card-soft border-danger reveal mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-danger">Diagnostico de Integridade</h6>
            <span class="badge text-bg-danger"><?php echo (int) ($integrityIssues['total_issues'] ?? 0); ?> inconsistencias</span>
        </div>
        <div class="card-body">
            <div class="alert alert-danger mb-3 small">
                Foram encontradas inconsistencias de saldo. Recomenda-se ajustar historico de movimentacoes para evitar distorcao nos relatorios e no estoque tecnico.
            </div>

            <?php if (!empty($integrityIssues['estoque_negativo'])): ?>
                <h6 class="small text-danger">Estoque geral negativo</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Equipamento</th><th>Tipo</th><th>Saldo</th></tr></thead>
                        <tbody>
                        <?php foreach ($integrityIssues['estoque_negativo'] as $item): ?>
                            <tr>
                                <td><?php echo sanitize((string) ($item['nome'] ?? 'Equipamento')); ?></td>
                                <td><?php echo sanitize((string) ($item['tipo'] ?? '')); ?></td>
                                <td><strong><?php echo (int) ($item['quantidade'] ?? 0); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($integrityIssues['saldo_mao_negativo'])): ?>
                <h6 class="small text-danger">Saldo em mao negativo (por tecnico)</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Tecnico</th><th>Equipamento</th><th>Tipo</th><th>Saldo em mao</th></tr></thead>
                        <tbody>
                        <?php foreach ($integrityIssues['saldo_mao_negativo'] as $item): ?>
                            <tr>
                                <td><?php echo sanitize((string) ($item['tecnico_nome'] ?? 'Sem tecnico')); ?></td>
                                <td><?php echo sanitize((string) ($item['equipamento_nome'] ?? 'Equipamento')); ?></td>
                                <td><?php echo sanitize((string) ($item['equipamento_tipo'] ?? '')); ?></td>
                                <td><strong><?php echo (int) ($item['saldo_mao'] ?? 0); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($integrityIssues['saldo_campo_global_negativo'])): ?>
                <h6 class="small text-danger">Saldo em campo global negativo</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Equipamento</th><th>Tipo</th><th>Saldo em campo</th></tr></thead>
                        <tbody>
                        <?php foreach ($integrityIssues['saldo_campo_global_negativo'] as $item): ?>
                            <tr>
                                <td><?php echo sanitize((string) ($item['equipamento_nome'] ?? 'Equipamento')); ?></td>
                                <td><?php echo sanitize((string) ($item['equipamento_tipo'] ?? '')); ?></td>
                                <td><strong><?php echo (int) ($item['saldo_campo_global'] ?? 0); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($recolhimentosSemLastro)): ?>
    <div class="card card-soft border-warning reveal mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-warning">Recolhimentos para Conferencia</h6>
            <span class="badge text-bg-warning text-dark"><?php echo count($recolhimentosSemLastro); ?> sem lastro em campo</span>
        </div>
        <div class="card-body">
            <div class="alert alert-warning mb-3 small">
                Esses recolhimentos foram aceitos no estoque geral sem saldo em campo suficiente no historico. Revise os registros para reconciliar legado ou ajustar movimentacoes anteriores.
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tecnico</th>
                        <th>Equipamento</th>
                        <th>Qtd</th>
                        <th>Tipo</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recolhimentosSemLastro as $item): ?>
                        <tr>
                            <td><?php echo !empty($item['data_movimentacao']) ? date('d/m/Y H:i', strtotime((string) $item['data_movimentacao'])) : '-'; ?></td>
                            <td><?php echo sanitize((string) ($item['tecnico_nome'] ?? 'Sem tecnico')); ?></td>
                            <td>
                                <?php echo sanitize((string) ($item['equipamento_nome'] ?? 'Equipamento')); ?>
                                <small class="text-muted d-block"><?php echo sanitize((string) ($item['equipamento_tipo'] ?? '')); ?></small>
                            </td>
                            <td><?php echo (int) ($item['quantidade'] ?? 0); ?></td>
                            <td><?php echo strtoupper(sanitize((string) ($item['tipo'] ?? 'recolhimento'))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$totalAparelhosDefeito = 0;
foreach ($aparelhosComDefeito as $itemDefeitoTotal) {
    $totalAparelhosDefeito += (int) ($itemDefeitoTotal['quantidade'] ?? 0);
}
?>
<div class="card card-soft border-danger reveal mb-3" id="secao-com-defeito">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 text-danger">COM DEFEITO</h6>
        <span class="badge text-bg-danger"><?php echo count($aparelhosComDefeito); ?> registro(s) | Qtd total: <?php echo $totalAparelhosDefeito; ?></span>
    </div>
    <div class="card-body">
        <div class="alert alert-danger small mb-3">
            Itens recolhidos com defeito nao voltam para o estoque normal. Abaixo estao apenas os aparelhos recolhidos com defeito na data selecionada.
        </div>

        <div class="row g-2 mb-3">
            <div class="col-12 col-lg-5">
                <label class="form-label small text-muted mb-1">Data de exibicao</label>
                <form method="get" class="d-flex gap-2">
                    <input type="hidden" name="route" value="movimentacoes">
                    <input type="hidden" name="date" value="<?php echo sanitize($selectedDate); ?>">
                    <input type="date" name="defect_date" class="form-control" value="<?php echo sanitize((string) $defectFilterDate); ?>">
                    <button type="submit" class="btn btn-outline-danger">Filtrar</button>
                </form>
            </div>
            <div class="col-12 col-lg-5">
                <label class="form-label small text-muted mb-1">Filtrar por serial</label>
                <input type="text" class="form-control js-defect-serial-filter" placeholder="Digite o serial para buscar">
            </div>
        </div>

        <?php if (empty($aparelhosComDefeito)): ?>
            <div class="alert alert-dark border mb-0">Nenhum aparelho recolhido com defeito foi registrado em <?php echo date('d/m/Y', strtotime((string) $defectFilterDate)); ?>.</div>
        <?php else: ?>
            <div class="alert alert-dark border d-none js-defect-empty-filter mb-3">Nenhum registro com esse serial.</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Tecnico</th>
                        <th>Equipamento</th>
                        <th>Serial</th>
                        <th>Qtd</th>
                        <th>Motivo/Defeito</th>
                        <th>Data</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($aparelhosComDefeito as $itemDefeito): ?>
                        <tr class="js-defect-row" data-defect-serial="<?php echo strtolower(sanitize((string) ($itemDefeito['serial_equipamento'] ?? ''))); ?>">
                            <td><?php echo sanitize((string) ($itemDefeito['tecnico_nome'] ?? 'Sem tecnico')); ?></td>
                            <td>
                                <?php echo sanitize((string) ($itemDefeito['equipamento_nome'] ?? 'Equipamento')); ?>
                                <small class="text-muted d-block"><?php echo sanitize((string) ($itemDefeito['equipamento_tipo'] ?? '')); ?></small>
                            </td>
                            <td><?php echo sanitize((string) ($itemDefeito['serial_equipamento'] ?? '-')); ?></td>
                            <td><?php echo (int) ($itemDefeito['quantidade'] ?? 0); ?></td>
                            <td><?php echo sanitize((string) ($itemDefeito['motivo_defeito'] ?? '-')); ?></td>
                            <td><?php echo !empty($itemDefeito['data_movimentacao']) ? date('d/m/Y H:i', strtotime((string) $itemDefeito['data_movimentacao'])) : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mt-3 reveal">
    <div class="col-6 col-lg-3">
        <div class="card card-soft history-stat-card h-100">
            <div class="card-body">
                <small class="text-muted d-block">Total de Movimentações</small>
                <h3 class="mb-0"><?php echo count($movimentacoes); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-soft history-stat-card h-100">
            <div class="card-body">
                <small class="text-muted d-block">Movimentações na Data</small>
                <h3 class="mb-0"><?php echo $movimentacoesHoje; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-soft history-stat-card h-100">
            <div class="card-body">
                <small class="text-muted d-block">Recolhidos com Defeito</small>
                <h3 class="mb-0"><?php echo (int) ($contagemTipos['recolhimento_defeito'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-soft history-stat-card h-100">
            <div class="card-body">
                <small class="text-muted d-block">Usos Registrados</small>
                <h3 class="mb-0"><?php echo (int) ($contagemTipos['uso'] + $contagemTipos['uso_teste']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-soft history-stat-card h-100">
            <div class="card-body">
                <small class="text-muted d-block">Técnicos com Histórico</small>
                <h3 class="mb-0"><?php echo count($movimentacoesPorTecnico); ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card card-soft reveal mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Estoque Seguro por Técnico</h5>
        <span class="badge text-bg-secondary"><?php echo count($cardsTecnicos); ?> técnicos</span>
    </div>
    <div class="card-body">
        <?php if (empty($cardsTecnicos)): ?>
            <div class="text-center py-3">Nenhum técnico cadastrado para exibir estoque seguro.</div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($cardsTecnicos as $card): ?>
                    <?php
                    $saldoCategoria = $card['saldo_por_categoria'] ?? [];
                    $saldoEfetivo = $card['saldo_por_categoria_efetivo'] ?? [];
                    $ontEmMao = (int) ($saldoCategoria['ont'] ?? 0);
                    ?>
                    <div class="col-12 col-xl-6">
                        <div class="dark-panel-subtle rounded p-3 h-100">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                <strong><?php echo sanitize((string) ($card['tecnico_nome'] ?? 'Sem técnico')); ?></strong>
                                <span class="badge <?php echo !empty($card['estoque_seguro_ok']) ? 'text-bg-success' : 'text-bg-warning'; ?>">
                                    <?php echo !empty($card['estoque_seguro_ok']) ? 'OK' : 'Reposição pendente'; ?>
                                </span>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <span class="badge text-bg-success">Roteadores (efetivo): <?php echo (int) ($saldoEfetivo['roteador'] ?? 0); ?>/3</span>
                                <span class="badge text-bg-primary">ONU (efetivo): <?php echo (int) ($saldoEfetivo['onu'] ?? 0); ?>/2</span>
                                <span class="badge text-bg-secondary">ONT em mão: <?php echo $ontEmMao; ?></span>
                                <span class="badge text-bg-info text-dark">Conectores: <?php echo (int) ($saldoCategoria['conector_fibra'] ?? 0); ?>/10</span>
                                <span class="badge text-bg-warning text-dark">Conector RJ: <?php echo (int) ($saldoCategoria['conector_rj'] ?? 0); ?>/8</span>
                                <span class="badge text-bg-light text-dark">Esticador: <?php echo (int) ($saldoCategoria['esticador'] ?? 0); ?>/8</span>
                            </div>
                            <small class="text-muted d-block">Cada ONT em mão substitui 1 ONU e 1 roteador no cálculo de reposição.</small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4 mt-1 reveal">
    <div class="col-12">
        <div class="card card-soft h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Histórico Organizado por Técnico</h5>
                <span class="badge text-bg-primary"><?php echo count($movimentacoesPorTecnico); ?> técnicos</span>
            </div>
            <div class="card-body">
                <?php if (empty($movimentacoesPorTecnico)): ?>
                    <div class="text-center py-4">Nenhuma movimentação registrada.</div>
                <?php else: ?>
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-lg-5">
                            <label class="form-label small text-muted mb-1">Filtrar por técnico</label>
                            <input type="text" class="form-control js-history-tech-filter" placeholder="Digite o nome do técnico">
                        </div>
                        <div class="col-12 col-lg-4">
                            <label class="form-label small text-muted mb-1">Tipo de movimentação</label>
                            <select class="form-select js-history-type-filter">
                                <option value="">Todos os tipos</option>
                                <option value="entrega">Entrega</option>
                                <option value="uso">Uso</option>
                                <option value="uso_teste">Uso em teste</option>
                                <option value="devolucao">Devolução</option>
                                <option value="recolhimento">Recolhimento</option>
                                <option value="recolhimento_defeito">Recolhimento com defeito</option>
                            </select>
                        </div>
                        <div class="col-12 col-lg-3 d-flex align-items-end gap-2 history-actions-wrap">
                            <button type="button" class="btn btn-outline-secondary w-100 js-history-expand-all">Expandir todos</button>
                            <button type="button" class="btn btn-outline-secondary w-100 js-history-collapse-all">Fechar todos</button>
                        </div>
                    </div>

                    <div class="alert alert-dark border d-none js-history-empty-filter" role="status">
                        Nenhum tecnico encontrado para os filtros informados.
                    </div>

                    <div class="accordion" id="accordion-movimentacoes-tecnicos">
                        <?php $tecIndex = 0; ?>
                        <?php foreach ($movimentacoesPorTecnico as $tecnicoNome => $listaMovs): ?>
                            <?php
                            $headingId = 'heading-tecnico-' . $tecIndex;
                            $collapseId = 'collapse-tecnico-' . $tecIndex;
                            $resumo = $resumoPorTecnico[$tecnicoNome] ?? ['entrega' => 0, 'uso' => 0, 'uso_teste' => 0, 'devolucao' => 0, 'recolhimento' => 0, 'recolhimento_defeito' => 0, 'total' => 0];
                            ?>
                            <div class="accordion-item history-tech-item" data-tech-name="<?php echo strtolower(sanitize($tecnicoNome)); ?>">
                                <h2 class="accordion-header" id="<?php echo $headingId; ?>">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>" aria-expanded="false" aria-controls="<?php echo $collapseId; ?>">
                                        <div class="d-flex flex-wrap align-items-center gap-2 w-100 pe-3">
                                            <span class="fw-semibold me-2"><?php echo sanitize($tecnicoNome); ?></span>
                                            <span class="badge text-bg-secondary"><?php echo count($listaMovs); ?> movimentações</span>
                                            <span class="movement-pill movement-pill-entrega">E <?php echo (int) $resumo['entrega']; ?></span>
                                            <span class="movement-pill movement-pill-uso">U <?php echo (int) $resumo['uso']; ?></span>
                                            <span class="movement-pill movement-pill-teste">T <?php echo (int) $resumo['uso_teste']; ?></span>
                                            <span class="movement-pill movement-pill-devolucao">D <?php echo (int) $resumo['devolucao']; ?></span>
                                            <span class="movement-pill movement-pill-recolhimento">R <?php echo (int) $resumo['recolhimento']; ?></span>
                                            <span class="movement-pill movement-pill-recolhimento-defeito">RD <?php echo (int) $resumo['recolhimento_defeito']; ?></span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="<?php echo $collapseId; ?>" class="accordion-collapse collapse" aria-labelledby="<?php echo $headingId; ?>" data-bs-parent="#accordion-movimentacoes-tecnicos">
                                    <div class="accordion-body p-3">
                                        <?php
                                        $movsPorTipo = [
                                            'entrega' => [],
                                            'uso' => [],
                                            'uso_teste' => [],
                                            'devolucao' => [],
                                            'recolhimento' => [],
                                            'recolhimento_defeito' => [],
                                        ];

                                        foreach ($listaMovs as $mov) {
                                            $tipoMov = $mov['tipo'] ?? '';
                                            if (!isset($movsPorTipo[$tipoMov])) {
                                                continue;
                                            }

                                            $movsPorTipo[$tipoMov][] = $mov;
                                        }

                                        $rotulosTipo = [
                                            'entrega' => 'ENTREGAS',
                                            'uso' => 'USOS',
                                            'uso_teste' => 'USOS EM TESTE',
                                            'devolucao' => 'DEVOLUCOES',
                                            'recolhimento' => 'RECOLHIMENTOS',
                                            'recolhimento_defeito' => 'RECOLHIMENTOS COM DEFEITO',
                                        ];
                                        ?>

                                        <div class="accordion accordion-flush" id="accordion-tipos-<?php echo $tecIndex; ?>">
                                            <?php $tipoIndex = 0; ?>
                                            <?php foreach ($movsPorTipo as $tipoGrupo => $itensTipo): ?>
                                                <?php
                                                $headingTipoId = 'heading-tipo-' . $tecIndex . '-' . $tipoIndex;
                                                $collapseTipoId = 'collapse-tipo-' . $tecIndex . '-' . $tipoIndex;
                                                ?>
                                                <div class="accordion-item history-type-group" data-group-type="<?php echo $tipoGrupo; ?>">
                                                    <h2 class="accordion-header" id="<?php echo $headingTipoId; ?>">
                                                        <button class="accordion-button py-2 collapsed history-type-toggle history-type-<?php echo $tipoGrupo; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseTipoId; ?>" aria-expanded="false" aria-controls="<?php echo $collapseTipoId; ?>">
                                                            <span class="fw-semibold me-2"><?php echo $rotulosTipo[$tipoGrupo]; ?></span>
                                                            <span class="badge text-bg-secondary"><?php echo count($itensTipo); ?></span>
                                                        </button>
                                                    </h2>
                                                    <div id="<?php echo $collapseTipoId; ?>" class="accordion-collapse collapse" aria-labelledby="<?php echo $headingTipoId; ?>" data-bs-parent="#accordion-tipos-<?php echo $tecIndex; ?>">
                                                        <div class="accordion-body <?php echo empty($itensTipo) ? 'py-2' : ''; ?>">
                                                            <?php if (empty($itensTipo)): ?>
                                                                <small class="text-muted">Sem registros neste grupo.</small>
                                                            <?php else: ?>
                                                                <div class="row g-3">
                                                                    <?php foreach ($itensTipo as $mov): ?>
                                                                        <?php $tipoAtual = $mov['tipo'] ?? ''; ?>
                                                                        <div class="col-12 col-xl-6 history-mov-item" data-mov-type="<?php echo sanitize($tipoAtual); ?>">
                                                                            <div class="movement-card-lite">
                                                                                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                                                                    <span class="badge <?php echo ($tipoAtual === 'entrega' || $tipoAtual === 'devolucao' || $tipoAtual === 'recolhimento') ? 'text-bg-success' : (($tipoAtual === 'recolhimento_defeito') ? 'text-bg-danger' : 'text-bg-warning'); ?>">
                                                                                        <?php echo strtoupper(sanitize($tipoAtual)); ?>
                                                                                    </span>
                                                                                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($mov['data_movimentacao'])); ?></small>
                                                                                </div>
                                                                                <div class="mb-1">
                                                                                    <strong><?php echo sanitize($mov['equipamento_nome']); ?></strong>
                                                                                    <small class="text-muted">(<?php echo sanitize($mov['equipamento_tipo']); ?>)</small>
                                                                                </div>
                                                                                <div class="small text-muted mb-2">
                                                                                    Quantidade: <strong><?php echo (int) $mov['quantidade']; ?></strong>
                                                                                </div>
                                                                                <?php if ($tipoAtual === 'uso' || $tipoAtual === 'uso_teste'): ?>
                                                                                    <div class="small mb-2">
                                                                                        <span class="text-muted">Local:</span>
                                                                                        <strong><?php echo !empty($mov['local_uso']) ? sanitize($mov['local_uso']) : '-'; ?></strong>
                                                                                    </div>
                                                                                <?php endif; ?>
                                                                                <?php if (!empty($mov['observacoes'])): ?>
                                                                                    <div class="small movement-note"><?php echo sanitize($mov['observacoes']); ?></div>
                                                                                <?php endif; ?>
                                                                                <?php if ($tipoAtual === 'uso' || $tipoAtual === 'uso_teste'): ?>
                                                                                    <div class="mt-2 d-flex gap-2">
                                                                                        <button type="button" class="btn btn-sm btn-outline-info js-edit-uso-btn" 
                                                                                            data-mov-id="<?php echo (int) ($mov['id'] ?? 0); ?>"
                                                                                            data-tipo="<?php echo sanitize($tipoAtual); ?>"
                                                                                            data-quantidade="<?php echo (int) ($mov['quantidade'] ?? 0); ?>"
                                                                                            data-local="<?php echo sanitize($mov['local_uso'] ?? ''); ?>"
                                                                                            data-obs="<?php echo sanitize($mov['observacoes'] ?? ''); ?>"
                                                                                            data-tecnico-id="<?php echo (int) ($mov['tecnico_id'] ?? 0); ?>"
                                                                                            data-selected-date="<?php echo sanitize($selectedDate); ?>">
                                                                                            Editar
                                                                                        </button>
                                                                                        <form method="post" style="display: inline;" onsubmit="return confirm('Deseja realmente excluir este registro de uso?');">
                                                                                            <input type="hidden" name="action" value="movimentacao_delete_uso">
                                                                                            <input type="hidden" name="movimentacao_id" value="<?php echo (int) ($mov['id'] ?? 0); ?>">
                                                                                            <input type="hidden" name="tipo" value="<?php echo sanitize($tipoAtual); ?>">
                                                                                            <input type="hidden" name="selected_date" value="<?php echo sanitize($selectedDate); ?>">
                                                                                            <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                                                                                        </form>
                                                                                    </div>
                                                                                <?php elseif ($tipoAtual === 'entrega'): ?>
                                                                                    <form method="post" class="mt-2" onsubmit="return confirm('Deseja realmente excluir esta entrega? O estoque sera recomposto automaticamente.');">
                                                                                        <input type="hidden" name="action" value="movimentacao_delete_entrega">
                                                                                        <input type="hidden" name="movimentacao_id" value="<?php echo (int) ($mov['id'] ?? 0); ?>">
                                                                                        <input type="hidden" name="tipo" value="<?php echo sanitize($tipoAtual); ?>">
                                                                                        <input type="hidden" name="selected_date" value="<?php echo sanitize($selectedDate); ?>">
                                                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Excluir entrega</button>
                                                                                    </form>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php $tipoIndex++; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php $tecIndex++; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

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
    'fieldByTechnician' => $equipamentosEmCampoPorTecnico,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle edit uso button clicks
        const editBtns = document.querySelectorAll('.js-edit-uso-btn');
        const editModal = new bootstrap.Modal(document.getElementById('modal-editar-uso'));
        
        editBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                
                const movId = this.dataset.movId;
                const tipo = this.dataset.tipo;
                const quantidade = this.dataset.quantidade;
                const local = this.dataset.local;
                const obs = this.dataset.obs;
                const selectedDate = this.dataset.selectedDate;
                
                // Populate modal form
                document.querySelector('.js-edit-mov-id').value = movId || '';
                document.querySelector('.js-edit-mov-tipo').value = tipo || '';
                document.querySelector('.js-edit-selected-date').value = selectedDate || '';
                const quantidadeInput = document.querySelector('#modal-editar-uso input[name="quantidade"]');
                const localInput = document.querySelector('#modal-editar-uso input[name="local_uso"]');
                const obsInput = document.querySelector('#modal-editar-uso textarea[name="observacoes"]');
                
                if (quantidadeInput) quantidadeInput.value = quantidade || '';
                if (localInput) localInput.value = local || '';
                if (obsInput) obsInput.value = obs || '';
                
                // Show modal
                editModal.show();
            });
        });

        const defectSerialFilter = document.querySelector('.js-defect-serial-filter');
        const defectRows = Array.from(document.querySelectorAll('.js-defect-row'));
        const defectEmptyState = document.querySelector('.js-defect-empty-filter');

        if (defectSerialFilter && defectRows.length) {
            const applyDefectSerialFilter = function() {
                const term = (defectSerialFilter.value || '').toLowerCase().trim();
                let visibleCount = 0;

                defectRows.forEach(row => {
                    const serial = (row.getAttribute('data-defect-serial') || '').toLowerCase();
                    const visible = term === '' || serial.includes(term);
                    row.style.display = visible ? '' : 'none';

                    if (visible) {
                        visibleCount++;
                    }
                });

                if (defectEmptyState) {
                    defectEmptyState.classList.toggle('d-none', visibleCount > 0);
                }
            };

            defectSerialFilter.addEventListener('input', applyDefectSerialFilter);
            applyDefectSerialFilter();
        }

        // Validação de Batch para Formulários de Uso
        const batchForms = document.querySelectorAll('.js-batch-form');
        batchForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const tipoMovimento = form.querySelector('input[name="tipo"]').value;
                
                // Validar que há itens na lista para movimentos de uso/recolhimento
                if (['uso', 'uso_teste', 'recolhimento', 'recolhimento_defeito'].includes(tipoMovimento)) {
                    const itensJson = form.querySelector('.js-batch-json').value;

                    const equipamentoEl = form.querySelector('[name="equipamento_id"]');
                    const quantidadeEl = form.querySelector('[name="quantidade"]');
                    const localUsoEl = form.querySelector('[name="local_uso"]');

                    const equipamentoId = equipamentoEl ? parseInt(equipamentoEl.value || '0', 10) : 0;
                    const quantidade = quantidadeEl ? parseInt(quantidadeEl.value || '0', 10) : 0;
                    const localUso = localUsoEl ? (localUsoEl.value || '').trim() : '';

                    const temListaBatch = !!(itensJson && itensJson.trim() !== '');
                    const exigeLocal = tipoMovimento === 'uso' || tipoMovimento === 'uso_teste';
                    const temItemDiretoValido = equipamentoId > 0
                        && quantidade > 0
                        && (!exigeLocal || localUso !== '');

                    if (!temListaBatch && !temItemDiretoValido) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const tipoLabel = {
                            'uso': 'Uso',
                            'uso_teste': 'Uso em Teste',
                            'recolhimento': 'Recolhimento',
                            'recolhimento_defeito': 'Recolhimento com Defeito'
                        }[tipoMovimento] || 'Movimento';
                        
                        alert('⚠️ Dados incompletos para envio!\n\n' +
                              'Você pode enviar de 2 formas:\n' +
                              '1) Preencher Equipamento/Quantidade (e Local quando aplicável) e confirmar direto;\n' +
                              '2) Clicar em "+ Adicionar item" para montar a lista em lote.\n\n' +
                              'Preencha os dados e tente confirmar o ' + tipoLabel + ' novamente.');
                        return false;
                    }
                }
            });
        });
    });

</script>

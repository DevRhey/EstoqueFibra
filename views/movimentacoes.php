<?php
$movimentacoes = $data['movimentacoes'] ?? [];
$equipamentos = $data['equipamentos'] ?? [];
$tecnicos = $data['tecnicos'] ?? [];
$cardsTecnicos = $data['cardsTecnicos'] ?? [];
$alertasUsoTeste = $data['alertasUsoTeste'] ?? [];

$equipamentosEmMaoPorTecnico = [];
foreach ($cardsTecnicos as $card) {
    $equipamentosEmMaoPorTecnico[(int) $card['tecnico_id']] = $card['equipamentos_mao'] ?? [];
}

$movimentacoesPorTecnico = [];
$resumoPorTecnico = [];
$contagemTipos = [
    'entrega' => 0,
    'uso' => 0,
    'uso_teste' => 0,
    'devolucao' => 0,
    'recolhimento' => 0,
];
$movimentacoesHoje = 0;
$hoje = date('Y-m-d');

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
    <div class="col-12 col-sm-6 col-lg-6 col-xl-3">
        <button class="btn btn-info w-100 h-100 p-4" data-bs-toggle="modal" data-bs-target="#modal-recolhimento">
            <div class="mb-2" style="font-size: 24px;">🔄</div>
            <strong>Recolher</strong>
            <small class="d-block mt-1">Coleta de cliente</small>
        </button>
    </div>
    <div class="col-12 col-sm-6 col-lg-6 col-xl-3">
        <button class="btn btn-primary w-100 h-100 p-4" data-bs-toggle="modal" data-bs-target="#modal-devolucao">
            <div class="mb-2" style="font-size: 24px;">↩️</div>
            <strong>Devolver</strong>
            <small class="d-block mt-1">Retorno ao estoque</small>
        </button>
    </div>
</div>

<!-- Modal: ENTREGA -->
<div class="modal fade" id="modal-entrega" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Entregar Equipamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" class="needs-validation js-movement-form js-batch-form" novalidate>
                <input type="hidden" name="action" value="movimentacao_store">
                <input type="hidden" name="tipo" value="entrega">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Técnico</label>
                        <select name="tecnico_id" class="form-select js-entrega-tecnico" required>
                            <option value="">Selecione um técnico</option>
                            <?php foreach ($tecnicos as $tec): ?>
                                <option value="<?php echo (int) $tec['id']; ?>"><?php echo sanitize($tec['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Selecione um técnico.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Equipamento (de Estoque)</label>
                        <select name="equipamento_id" class="form-select js-entrega-equipamento" required>
                            <option value="">Selecione</option>
                            <?php foreach ($equipamentos as $eq): ?>
                                <option
                                    value="<?php echo (int) $eq['id']; ?>"
                                    data-label="<?php echo sanitize($eq['nome']); ?>"
                                    data-quantidade="<?php echo (int) $eq['quantidade']; ?>"
                                    data-codigo-barras="<?php echo sanitize((string) ($eq['codigo_barras'] ?? '')); ?>"
                                >
                                    <?php echo sanitize($eq['nome']); ?> (<?php echo sanitize($eq['tipo']); ?>) | Estoque: <?php echo (int) $eq['quantidade']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Selecione um equipamento.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Leitura por Código de Barras</label>
                        <input type="text" class="form-control js-scan-equip" placeholder="Bipe o código de barras do equipamento" autocomplete="off" inputmode="none">
                        <div class="form-text">Cada leitura adiciona 1 unidade na lista. O total aparece abaixo.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Quantidade</label>
                        <input type="number" name="quantidade" class="form-control" min="1" required>
                        <div class="invalid-feedback">Informe uma quantidade válida.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="2" maxlength="500" placeholder="Ex.: Cliente de origem / motivo da entrega"></textarea>
                    </div>
                    <div class="dark-panel-subtle border rounded p-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted">Lista de itens</small>
                            <button type="button" class="btn btn-sm btn-outline-success js-add-batch-item">Adicionar item</button>
                        </div>
                        <small class="text-muted js-batch-empty">Nenhum item adicionado.</small>
                        <div class="table-responsive d-none js-batch-table-wrap">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>Equipamento</th>
                                    <th>Qtd</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody class="js-batch-items"></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                            <span>Total lido</span>
                            <strong class="js-batch-total">0</strong>
                        </div>
                        <input type="hidden" name="itens_json" class="js-batch-json">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Confirmar Entrega</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: USO -->
<div class="modal fade" id="modal-uso" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">Registrar Uso no Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" class="needs-validation js-movement-form js-batch-form" novalidate>
                <input type="hidden" name="action" value="movimentacao_store">
                <input type="hidden" name="tipo" value="uso">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Técnico</label>
                        <select name="tecnico_id" class="form-select js-uso-tecnico" required>
                            <option value="">Selecione um técnico</option>
                            <?php foreach ($tecnicos as $tec): ?>
                                <option value="<?php echo (int) $tec['id']; ?>"><?php echo sanitize($tec['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Selecione um técnico.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Equipamento (em Mão)</label>
                        <select name="equipamento_id" class="form-select js-uso-equipamento" required>
                            <option value="">Primeiro escolha um técnico</option>
                        </select>
                        <div class="invalid-feedback">Selecione um equipamento.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Leitura por Código de Barras</label>
                        <input type="text" class="form-control js-scan-equip" placeholder="Bipe o código de barras do equipamento" autocomplete="off" inputmode="none">
                        <div class="form-text">Cada leitura adiciona 1 unidade na lista. O total aparece abaixo.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Local de Uso</label>
                        <input type="text" name="local_uso" class="form-control" maxlength="120" placeholder="Ex.: Bairro Centro / CTO Rua X" required>
                        <div class="invalid-feedback">Informe onde foi usado.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Quantidade</label>
                        <input type="number" name="quantidade" class="form-control" min="1" value="1" required>
                        <div class="invalid-feedback">Informe uma quantidade válida.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="2" maxlength="500" placeholder="Nome do cliente, detalhes da instalação etc."></textarea>
                    </div>
                    <div class="dark-panel-subtle border rounded p-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted">Lista de itens</small>
                            <button type="button" class="btn btn-sm btn-outline-warning js-add-batch-item">Adicionar item</button>
                        </div>
                        <small class="text-muted js-batch-empty">Nenhum item adicionado.</small>
                        <div class="table-responsive d-none js-batch-table-wrap">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>Equipamento</th>
                                    <th>Qtd</th>
                                    <th>Local</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody class="js-batch-items"></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                            <span>Total lido</span>
                            <strong class="js-batch-total">0</strong>
                        </div>
                        <input type="hidden" name="itens_json" class="js-batch-json">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning text-dark">Confirmar Uso</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: USO EM TESTE -->
<div class="modal fade" id="modal-uso-teste" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Registrar Uso em Teste (3 dias)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" class="needs-validation js-movement-form js-batch-form" novalidate>
                <input type="hidden" name="action" value="movimentacao_store">
                <input type="hidden" name="tipo" value="uso_teste">
                <div class="modal-body">
                    <div class="alert alert-secondary small mb-3">
                        O vencimento do teste é calculado em 3 dias. Se cair no domingo, vence na segunda-feira.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Técnico</label>
                        <select name="tecnico_id" class="form-select js-uso-teste-tecnico" required>
                            <option value="">Selecione um técnico</option>
                            <?php foreach ($tecnicos as $tec): ?>
                                <option value="<?php echo (int) $tec['id']; ?>"><?php echo sanitize($tec['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Selecione um técnico.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Equipamento (em Mão)</label>
                        <select name="equipamento_id" class="form-select js-uso-teste-equipamento" required>
                            <option value="">Primeiro escolha um técnico</option>
                        </select>
                        <div class="invalid-feedback">Selecione um equipamento.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Leitura por Código de Barras</label>
                        <input type="text" class="form-control js-scan-equip" placeholder="Bipe o código de barras do equipamento" autocomplete="off" inputmode="none">
                        <div class="form-text">Cada leitura adiciona 1 unidade na lista. O total aparece abaixo.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Local do Teste</label>
                        <input type="text" name="local_uso" class="form-control" maxlength="120" placeholder="Ex.: Casa do cliente Rua X" required>
                        <div class="invalid-feedback">Informe o local do teste.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Quantidade</label>
                        <input type="number" name="quantidade" class="form-control" min="1" value="1" required>
                        <div class="invalid-feedback">Informe uma quantidade válida.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="2" maxlength="500" placeholder="Nome do cliente, contato, observações do teste"></textarea>
                    </div>
                    <div class="dark-panel-subtle border rounded p-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted">Lista de itens</small>
                            <button type="button" class="btn btn-sm btn-outline-dark js-add-batch-item">Adicionar item</button>
                        </div>
                        <small class="text-muted js-batch-empty">Nenhum item adicionado.</small>
                        <div class="table-responsive d-none js-batch-table-wrap">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>Equipamento</th>
                                    <th>Qtd</th>
                                    <th>Local</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody class="js-batch-items"></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                            <span>Total lido</span>
                            <strong class="js-batch-total">0</strong>
                        </div>
                        <input type="hidden" name="itens_json" class="js-batch-json">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark">Iniciar Teste</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: RECOLHIMENTO -->
<div class="modal fade" id="modal-recolhimento" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Recolher de Cliente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" class="needs-validation js-movement-form js-batch-form" novalidate>
                <input type="hidden" name="action" value="movimentacao_store">
                <input type="hidden" name="tipo" value="recolhimento">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Técnico</label>
                        <select name="tecnico_id" class="form-select js-recolhimento-tecnico" required>
                            <option value="">Selecione um técnico</option>
                            <?php foreach ($tecnicos as $tec): ?>
                                <option value="<?php echo (int) $tec['id']; ?>"><?php echo sanitize($tec['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Selecione um técnico.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Equipamento Recolhido</label>
                        <select name="equipamento_id" class="form-select" required>
                            <option value="">Selecione</option>
                            <?php foreach ($equipamentos as $eq): ?>
                                <option value="<?php echo (int) $eq['id']; ?>" data-label="<?php echo sanitize($eq['nome']); ?>" data-codigo-barras="<?php echo sanitize((string) ($eq['codigo_barras'] ?? '')); ?>">
                                    <?php echo sanitize($eq['nome']); ?> (<?php echo sanitize($eq['tipo']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Selecione um equipamento.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Leitura por Código de Barras</label>
                        <input type="text" class="form-control js-scan-equip" placeholder="Bipe o código de barras do equipamento" autocomplete="off" inputmode="none">
                        <div class="form-text">Cada leitura adiciona 1 unidade na lista. O total aparece abaixo.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Quantidade</label>
                        <input type="number" name="quantidade" class="form-control" min="1" value="1" required>
                        <div class="invalid-feedback">Informe uma quantidade válida.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="2" maxlength="500" placeholder="Cliente / motivo da coleta / condições do equipamento"></textarea>
                    </div>
                    <div class="dark-panel-subtle border rounded p-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted">Lista de itens</small>
                            <button type="button" class="btn btn-sm btn-outline-info js-add-batch-item">Adicionar item</button>
                        </div>
                        <small class="text-muted js-batch-empty">Nenhum item adicionado.</small>
                        <div class="table-responsive d-none js-batch-table-wrap">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>Equipamento</th>
                                    <th>Qtd</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody class="js-batch-items"></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                            <span>Total lido</span>
                            <strong class="js-batch-total">0</strong>
                        </div>
                        <input type="hidden" name="itens_json" class="js-batch-json">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info">Confirmar Recolhimento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: DEVOLUÇÃO -->
<div class="modal fade" id="modal-devolucao" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Devolver ao Estoque</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" class="needs-validation js-movement-form js-batch-form" novalidate>
                <input type="hidden" name="action" value="movimentacao_store">
                <input type="hidden" name="tipo" value="devolucao">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Técnico</label>
                        <select name="tecnico_id" class="form-select js-devolucao-tecnico" required>
                            <option value="">Selecione um técnico</option>
                            <?php foreach ($tecnicos as $tec): ?>
                                <option value="<?php echo (int) $tec['id']; ?>"><?php echo sanitize($tec['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Selecione um técnico.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Equipamento (em Mão)</label>
                        <select name="equipamento_id" class="form-select js-devolucao-equipamento" required>
                            <option value="">Primeiro escolha um técnico</option>
                        </select>
                        <div class="invalid-feedback">Selecione um equipamento.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Leitura por Código de Barras</label>
                        <input type="text" class="form-control js-scan-equip" placeholder="Bipe o código de barras do equipamento" autocomplete="off" inputmode="none">
                        <div class="form-text">Cada leitura adiciona 1 unidade na lista. O total aparece abaixo.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Quantidade</label>
                        <input type="number" name="quantidade" class="form-control" min="1" value="1" required>
                        <div class="invalid-feedback">Informe uma quantidade válida.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="2" maxlength="500" placeholder="Motivo / condições / detalhes"></textarea>
                    </div>
                    <div class="dark-panel-subtle border rounded p-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted">Lista de itens</small>
                            <button type="button" class="btn btn-sm btn-outline-primary js-add-batch-item">Adicionar item</button>
                        </div>
                        <small class="text-muted js-batch-empty">Nenhum item adicionado.</small>
                        <div class="table-responsive d-none js-batch-table-wrap">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>Equipamento</th>
                                    <th>Qtd</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody class="js-batch-items"></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                            <span>Total lido</span>
                            <strong class="js-batch-total">0</strong>
                        </div>
                        <input type="hidden" name="itens_json" class="js-batch-json">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Confirmar Devolução</button>
                </div>
            </form>
        </div>
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
                                    <div class="small text-muted"><?php echo sanitize($alerta['equipamento_nome']); ?> | qtd <?php echo (int) $alerta['quantidade']; ?></div>
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
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

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
                <small class="text-muted d-block">Movimentações Hoje</small>
                <h3 class="mb-0"><?php echo $movimentacoesHoje; ?></h3>
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
                            $resumo = $resumoPorTecnico[$tecnicoNome] ?? ['entrega' => 0, 'uso' => 0, 'uso_teste' => 0, 'devolucao' => 0, 'recolhimento' => 0, 'total' => 0];
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
                                                                                    <span class="badge <?php echo ($tipoAtual === 'entrega' || $tipoAtual === 'devolucao' || $tipoAtual === 'recolhimento') ? 'text-bg-success' : 'text-bg-warning'; ?>">
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
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>

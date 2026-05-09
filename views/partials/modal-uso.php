<?php
$return_route = isset($return_route) ? (string) $return_route : '';
$require_local_uso = !empty($require_local_uso);
?>
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
                <?php if (!empty($return_route)): ?>
                    <input type="hidden" name="return_route" value="<?php echo sanitize($return_route); ?>">
                <?php endif; ?>
                <input type="hidden" name="selected_date" value="<?php echo sanitize($selectedDate); ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tecnico</label>
                        <select name="tecnico_id" class="form-select js-uso-tecnico" required>
                            <option value="">Selecione um tecnico</option>
                            <?php foreach ($tecnicos as $tec): ?>
                                <option value="<?php echo (int) $tec['id']; ?>"><?php echo sanitize($tec['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Selecione um tecnico.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Data da Movimentacao</label>
                        <input type="date" name="data_movimentacao" class="form-control" value="<?php echo sanitize($selectedDate); ?>" required>
                        <div class="invalid-feedback">Informe a data da movimentacao.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Equipamento (em Mao)</label>
                        <select name="equipamento_id" class="form-select js-uso-equipamento" required>
                            <option value="">Primeiro escolha um tecnico</option>
                        </select>
                        <div class="invalid-feedback">Selecione um equipamento.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Leitura por Codigo de Barras</label>
                        <input type="text" class="form-control js-scan-equip" placeholder="Bipe o codigo de barras do equipamento" autocomplete="off" inputmode="none">
                        <div class="form-text">Cada leitura adiciona 1 unidade na lista. O total aparece abaixo.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Quantidade</label>
                        <input type="number" name="quantidade" class="form-control" min="1" value="1" required>
                        <div class="invalid-feedback">Informe uma quantidade valida.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Local de Uso</label>
                        <input type="text" name="local_uso" class="form-control" maxlength="120" placeholder="Ex.: Bairro Centro / CTO Rua X"<?php echo $require_local_uso ? ' required' : ''; ?>>
                        <div class="invalid-feedback">Informe onde foi usado.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observacoes</label>
                        <textarea name="observacoes" class="form-control" rows="2" maxlength="500" placeholder="Nome do cliente, detalhes da instalacao etc."></textarea>
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
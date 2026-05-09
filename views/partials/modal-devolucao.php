<?php
$return_route = isset($return_route) ? (string) $return_route : '';
$include_batch = !empty($include_batch);
?>
<div class="modal fade" id="modal-devolucao" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Devolução ao Estoque</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" class="needs-validation js-movement-form js-batch-form" novalidate>
                <input type="hidden" name="action" value="movimentacao_store">
                <input type="hidden" name="tipo" value="devolucao">
                <?php if (!empty($return_route)): ?>
                    <input type="hidden" name="return_route" value="<?php echo sanitize($return_route); ?>">
                <?php endif; ?>
                <input type="hidden" name="selected_date" value="<?php echo sanitize($selectedDate); ?>">
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
                        <label class="form-label fw-bold">Data da Movimentação</label>
                        <input type="date" name="data_movimentacao" class="form-control" value="<?php echo sanitize($selectedDate); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Equipamento (devolvido)</label>
                        <select name="equipamento_id" class="form-select js-devolucao-equipamento" required>
                            <option value="">Primeiro escolha um técnico</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="2" maxlength="500"></textarea>
                    </div>

                    <?php if ($include_batch): ?>
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
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success text-white">Confirmar Devolução</button>
                </div>
            </form>
        </div>
    </div>
</div>

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
                <?php if (!empty($return_route)): ?>
                    <input type="hidden" name="return_route" value="<?php echo sanitize($return_route); ?>">
                <?php endif; ?>
                <input type="hidden" name="selected_date" value="<?php echo sanitize($selectedDate); ?>">
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
                        <label class="form-label fw-bold">Data da Movimentação</label>
                        <input type="date" name="data_movimentacao" class="form-control" value="<?php echo sanitize($selectedDate); ?>" required>
                        <div class="invalid-feedback">Informe a data da movimentação.</div>
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

                    <?php if (!empty($include_batch)): ?>
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
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Confirmar entrega</button>
                </div>
            </form>
        </div>
    </div>
</div>

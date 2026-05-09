<div class="modal fade" id="modal-editar-uso" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Editar Registro de Uso</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" class="needs-validation" novalidate id="form-editar-uso">
                <input type="hidden" name="action" value="movimentacao_update_uso">
                <input type="hidden" name="movimentacao_id" class="js-edit-mov-id" value="">
                <input type="hidden" name="tipo" class="js-edit-mov-tipo" value="">
                <input type="hidden" name="selected_date" class="js-edit-selected-date" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Quantidade</label>
                        <input type="number" name="quantidade" class="form-control" min="1" required>
                        <div class="invalid-feedback">Informe uma quantidade válida.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Local de Uso</label>
                        <input type="text" name="local_uso" class="form-control" maxlength="120" required>
                        <div class="invalid-feedback">Informe o local de uso.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="2" maxlength="500"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info">Atualizar Uso</button>
                </div>
            </form>
        </div>
    </div>
</div>

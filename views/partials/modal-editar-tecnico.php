<div class="modal fade" id="editTecnicoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Editar Tecnico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="tecnico_update">
                    <input type="hidden" name="id" id="edit-tecnico-id">
                    <div>
                        <label class="form-label">Nome do Tecnico</label>
                        <input type="text" name="nome" id="edit-tecnico-nome" class="form-control" required maxlength="120">
                        <div class="invalid-feedback">Informe o nome do tecnico.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alteracoes</button>
                </div>
            </form>
        </div>
    </div>
</div>

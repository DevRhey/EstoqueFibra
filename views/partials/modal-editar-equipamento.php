<div class="modal fade" id="editarEquipamentoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Editar Equipamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="equipamento_update">
                    <input type="hidden" name="id" id="edit-equip-id">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" id="edit-equip-nome" class="form-control" required>
                        <div class="invalid-feedback">Campo obrigatório.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" id="edit-equip-tipo" class="form-select" required>
                            <option value="">Selecione</option>
                            <option value="roteador">Roteador</option>
                            <option value="onu">ONU</option>
                            <option value="ont">ONT</option>
                            <option value="conector_fibra">Conector de Fibra</option>
                            <option value="insumos">Insumos</option>
                        </select>
                        <div class="invalid-feedback">Campo obrigatório.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Código de Barras</label>
                        <input type="text" name="codigo_barras" id="edit-equip-codigo" class="form-control" maxlength="64" placeholder="Leia com o scanner ou digite manualmente">
                        <div class="form-text">Opcional. Deve ser único para cada equipamento.</div>
                    </div>
                    <div>
                        <label class="form-label">Quantidade</label>
                        <input type="number" name="quantidade" id="edit-equip-quantidade" class="form-control" required min="0">
                        <div class="invalid-feedback">Campo obrigatório.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

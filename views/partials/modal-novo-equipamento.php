<div class="modal fade" id="novoEquipamentoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" class="needs-validation js-movement-form" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Cadastrar Novo Equipamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="equipamento_store">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" class="form-control" required maxlength="120">
                        <div class="invalid-feedback">Informe o nome.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select" required>
                            <option value="">Selecione</option>
                            <option value="roteador">Roteador</option>
                            <option value="onu">ONU</option>
                            <option value="ont">ONT</option>
                            <option value="conector_fibra">Conector de Fibra</option>
                            <option value="insumos">Insumos</option>
                        </select>
                        <div class="invalid-feedback">Selecione o tipo.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Código de Barras</label>
                        <input type="text" name="codigo_barras" class="form-control" maxlength="64" placeholder="Leia com o scanner ou digite manualmente">
                        <div class="form-text">Opcional. Deve ser único para cada equipamento.</div>
                    </div>
                    <div>
                        <label class="form-label">Quantidade Inicial</label>
                        <input type="number" name="quantidade" class="form-control" required min="0">
                        <div class="invalid-feedback">Informe uma quantidade válida.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

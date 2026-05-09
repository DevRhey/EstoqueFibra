<div class="modal fade" id="ajusteMaoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Ajustar Saldo em Mao</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="movimentacao_ajuste_mao">
                    <input type="hidden" name="tecnico_id" id="ajuste-mao-tecnico-id">
                    <input type="hidden" name="equipamento_id" id="ajuste-mao-equipamento-id">
                    <input type="hidden" name="selected_date" value="<?php echo sanitize($selectedDate); ?>">

                    <div class="mb-3">
                        <label class="form-label">Tecnico</label>
                        <input type="text" id="ajuste-mao-tecnico-nome" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Equipamento</label>
                        <input type="text" id="ajuste-mao-equipamento-nome" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Saldo Atual em Mao</label>
                        <input type="number" id="ajuste-mao-saldo-atual" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Novo Saldo em Mao</label>
                        <input type="number" name="novo_saldo_mao" id="ajuste-mao-saldo-novo" class="form-control" min="0" required>
                        <div class="invalid-feedback">Informe um saldo valido (0 ou maior).</div>
                    </div>
                    <div>
                        <label class="form-label">Observacoes</label>
                        <textarea name="observacoes" class="form-control" rows="2" maxlength="500" placeholder="Motivo do ajuste manual"></textarea>
                    </div>
                    <small class="text-muted d-block mt-2">O sistema registrara automaticamente uma movimentacao de entrega ou devolucao para ajustar o saldo em mao.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Ajuste</button>
                </div>
            </form>
        </div>
    </div>
</div>

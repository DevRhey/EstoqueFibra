<?php $equipamentos = $data['equipamentos'] ?? []; ?>

<section class="mb-4 page-intro reveal">
    <h2 class="page-title">Equipamentos</h2>
    <p class="page-subtitle">Cadastre e controle o estoque de materiais utilizados em campo.</p>
</section>

<div class="row g-4">
    <div class="col-lg-4 reveal">
        <div class="card card-soft h-100">
            <div class="card-header">
                <h5 class="mb-0">Novo Equipamento</h5>
            </div>
            <div class="card-body">
                <form method="post" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="equipamento_store">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" class="form-control" required maxlength="120">
                        <div class="invalid-feedback">Informe o nome do equipamento.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select" required>
                            <option value="">Selecione</option>
                            <option value="roteador">Roteador</option>
                            <option value="onu">ONU</option>
                            <option value="ont">ONT</option>
                            <option value="conector_rj">Conector RJ</option>
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
                    <div class="mb-3">
                        <label class="form-label">Quantidade Inicial</label>
                        <input type="number" name="quantidade" class="form-control" required min="0">
                        <div class="invalid-feedback">Informe uma quantidade valida.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Cadastrar Equipamento</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8 reveal">
        <div class="card card-soft h-100">
            <div class="card-header">
                <h5 class="mb-0">Estoque Atual</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Código de Barras</th>
                            <th>Quantidade</th>
                            <th class="text-end">Acoes</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($equipamentos)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">Nenhum equipamento cadastrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($equipamentos as $item): ?>
                                <tr>
                                    <td><?php echo sanitize($item['nome']); ?></td>
                                    <td><?php echo sanitize($item['tipo']); ?></td>
                                    <td><?php echo !empty($item['codigo_barras']) ? sanitize($item['codigo_barras']) : '-'; ?></td>
                                    <td>
                                        <span class="badge <?php echo ((int) $item['quantidade'] < 5) ? 'text-bg-danger' : 'text-bg-success'; ?>">
                                            <?php echo (int) $item['quantidade']; ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button
                                            class="btn btn-sm btn-outline-primary me-2 btn-edit-equip"
                                            data-id="<?php echo (int) $item['id']; ?>"
                                            data-nome="<?php echo sanitize($item['nome']); ?>"
                                            data-tipo="<?php echo sanitize($item['tipo']); ?>"
                                            data-codigo-barras="<?php echo sanitize((string) ($item['codigo_barras'] ?? '')); ?>"
                                            data-quantidade="<?php echo (int) $item['quantidade']; ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editEquipamentoModal"
                                        >Editar</button>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Deseja excluir este equipamento?');">
                                            <input type="hidden" name="action" value="equipamento_delete">
                                            <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editEquipamentoModal" tabindex="-1" aria-hidden="true">
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
                            <option value="conector_rj">Conector RJ</option>
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
                        <input type="number" min="0" name="quantidade" id="edit-equip-quantidade" class="form-control" required>
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

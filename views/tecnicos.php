<?php
$tecnicos = $data['tecnicos'] ?? [];
?>

<section class="mb-4 page-intro reveal">
    <h2 class="page-title">Tecnicos</h2>
    <p class="page-subtitle">Gerencie os colaboradores de campo e acompanhe seu historico de uso.</p>
</section>

<div class="row g-4 mb-4">
    <div class="col-lg-4 reveal">
        <div class="card card-soft h-100">
            <div class="card-header">
                <h5 class="mb-0">Novo Tecnico</h5>
            </div>
            <div class="card-body">
                <form method="post" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="tecnico_store">
                    <div class="mb-3">
                        <label class="form-label">Nome do Tecnico</label>
                        <input type="text" name="nome" class="form-control" required maxlength="120">
                        <div class="invalid-feedback">Informe o nome do tecnico.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Cadastrar Tecnico</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8 reveal">
        <div class="card card-soft h-100">
            <div class="card-header">
                <h5 class="mb-0">Lista de Tecnicos</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Cadastro</th>
                            <th class="text-end">Historico</th>
                            <th class="text-end">Acoes</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($tecnicos)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">Nenhum tecnico cadastrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tecnicos as $tec): ?>
                                <tr>
                                    <td><?php echo sanitize($tec['nome']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($tec['created_at'])); ?></td>
                                    <td class="text-end">
                                        <a href="index.php?route=tecnico_historico&tecnico_id=<?php echo (int) $tec['id']; ?>" class="btn btn-sm btn-outline-primary">Historico</a>
                                    </td>
                                    <td class="text-end">
                                        <form method="post" class="d-inline" onsubmit="return confirm('Deseja excluir este tecnico?');">
                                            <input type="hidden" name="action" value="tecnico_delete">
                                            <input type="hidden" name="id" value="<?php echo (int) $tec['id']; ?>">
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

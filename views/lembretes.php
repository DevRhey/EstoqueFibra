<?php
$lembretes = $data['lembretes'] ?? [];
$selectedDate = $data['selectedDate'] ?? date('Y-m-d');
$selectedStatus = $data['selectedStatus'] ?? '';
$resumo = $data['resumo'] ?? [
    'total' => 0,
    'pendentes' => 0,
    'abertos' => 0,
    'lidos' => 0,
    'resolvidos' => 0,
    'urgentes' => 0,
];
?>

<section class="mb-4 page-intro reveal">
    <h2 class="page-title">Lembretes</h2>
    <p class="page-subtitle">Registre lembretes manuais para a operação. O alerta permanece ativo até marcar como resolvido.</p>
</section>

<div class="card card-soft reveal mb-4">
    <div class="card-header d-flex justify-content-between align-items-center gap-2">
        <h5 class="mb-0">Novo Lembrete</h5>
        <span class="badge text-bg-info">Manual</span>
    </div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <input type="hidden" name="action" value="lembrete_store">
            <input type="hidden" name="selected_date" value="<?php echo sanitize($selectedDate); ?>">
            <div class="col-12 col-lg-6">
                <label class="form-label">Titulo</label>
                <input type="text" name="titulo" class="form-control" maxlength="180" required placeholder="Ex.: Ligar para cliente para confirmar visita">
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label">Categoria</label>
                <input type="text" name="categoria" class="form-control" maxlength="60" placeholder="Ex.: operacional, cliente, financeiro" value="operacional">
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label">Nivel</label>
                <select name="nivel" class="form-select">
                    <option value="info">Info</option>
                    <option value="warning">Aviso</option>
                    <option value="danger">Urgente</option>
                    <option value="success">Concluido</option>
                </select>
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label">Data do lembrete</label>
                <input type="date" name="data_referencia" class="form-control" value="<?php echo sanitize($selectedDate); ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Mensagem</label>
                <textarea name="mensagem" class="form-control" rows="3" maxlength="500" required placeholder="Descreva o lembrete com o detalhe que for necessario."></textarea>
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Salvar Lembrete</button>
            </div>
        </form>
    </div>
</div>

<div class="card card-soft reveal mb-4 sticky-date-filter">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <input type="hidden" name="route" value="lembretes">
            <div class="col-12 col-md-4 col-lg-3">
                <label class="form-label mb-1">Data de Referencia</label>
                <input type="date" name="date" class="form-control" value="<?php echo sanitize($selectedDate); ?>">
            </div>
            <div class="col-12 col-md-4 col-lg-3">
                <label class="form-label mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value=""<?php echo $selectedStatus === '' ? ' selected' : ''; ?>>Todos</option>
                    <option value="aberto"<?php echo $selectedStatus === 'aberto' ? ' selected' : ''; ?>>Abertos</option>
                    <option value="lido"<?php echo $selectedStatus === 'lido' ? ' selected' : ''; ?>>Lidos</option>
                    <option value="resolvido"<?php echo $selectedStatus === 'resolvido' ? ' selected' : ''; ?>>Resolvidos</option>
                </select>
            </div>
            <div class="col-12 col-md-4 col-lg-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                <a href="index.php?route=lembretes" class="btn btn-outline-secondary">Limpar</a>
            </div>
            <div class="col-12 col-lg-3">
                <small class="text-muted">Atualizado com lembretes do dia: <strong><?php echo date('d/m/Y', strtotime($selectedDate)); ?></strong></small>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4 reveal">
    <div class="col-6 col-lg-3">
        <div class="card card-soft h-100 history-stat-card history-stat-card-total">
            <div class="card-body">
                <small class="text-muted d-block">Total</small>
                <h3 class="mb-0"><?php echo (int) ($resumo['total'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-soft h-100 history-stat-card history-stat-card-uso">
            <div class="card-body">
                <small class="text-muted d-block">Pendentes</small>
                <h3 class="mb-0"><?php echo (int) ($resumo['pendentes'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-soft h-100 history-stat-card history-stat-card-entrega">
            <div class="card-body">
                <small class="text-muted d-block">Lidos</small>
                <h3 class="mb-0"><?php echo (int) ($resumo['lidos'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-soft h-100 history-stat-card history-stat-card-recolhimento">
            <div class="card-body">
                <small class="text-muted d-block">Urgentes</small>
                <h3 class="mb-0"><?php echo (int) ($resumo['urgentes'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card card-soft reveal">
    <div class="card-header d-flex justify-content-between align-items-center gap-2">
        <h5 class="mb-0">Mensagens e Lembretes</h5>
        <span class="badge text-bg-info"><?php echo count($lembretes); ?> registro(s)</span>
    </div>
    <div class="card-body">
        <?php if (empty($lembretes)): ?>
            <div class="alert alert-secondary mb-0">Nenhum lembrete encontrado para os filtros atuais.</div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($lembretes as $lembrete): ?>
                    <?php
                    $lembreteId = (int) ($lembrete['id'] ?? 0);
                    $nivel = (string) ($lembrete['nivel'] ?? 'info');
                    $status = (string) ($lembrete['status'] ?? 'aberto');
                    $nivelClass = match ($nivel) {
                        'danger' => 'text-bg-danger',
                        'warning' => 'text-bg-warning',
                        'success' => 'text-bg-success',
                        default => 'text-bg-info',
                    };
                    $statusClass = match ($status) {
                        'resolvido' => 'text-bg-success',
                        'lido' => 'text-bg-secondary',
                        default => 'text-bg-warning',
                    };
                    ?>
                    <div class="col-12 col-xl-6">
                        <div class="card card-soft h-100 border <?php echo $nivel === 'danger' ? 'border-danger' : 'border-secondary'; ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div>
                                        <span class="badge <?php echo $nivelClass; ?> mb-2"><?php echo sanitize(ucfirst($nivel)); ?></span>
                                        <span class="badge <?php echo $statusClass; ?> mb-2 ms-1"><?php echo sanitize(ucfirst($status)); ?></span>
                                        <h5 class="mb-1"><?php echo sanitize($lembrete['titulo'] ?? 'Lembrete'); ?></h5>
                                        <div class="small text-muted"><?php echo sanitize($lembrete['categoria'] ?? 'geral'); ?></div>
                                    </div>
                                    <small class="text-muted text-nowrap"><?php echo date('d/m/Y H:i', strtotime((string) ($lembrete['created_at'] ?? 'now'))); ?></small>
                                </div>

                                <p class="mb-3"><?php echo sanitize($lembrete['mensagem'] ?? ''); ?></p>

                                <div class="small text-muted mb-3">
                                    <?php if (!empty($lembrete['tecnico_nome'])): ?>
                                        <div>Técnico: <strong><?php echo sanitize($lembrete['tecnico_nome']); ?></strong></div>
                                    <?php endif; ?>
                                    <?php if (!empty($lembrete['equipamento_nome'])): ?>
                                        <div>Equipamento: <strong><?php echo sanitize($lembrete['equipamento_nome']); ?></strong></div>
                                    <?php endif; ?>
                                    <?php if (!empty($lembrete['data_referencia'])): ?>
                                        <div>Data de referência: <strong><?php echo date('d/m/Y', strtotime((string) $lembrete['data_referencia'])); ?></strong></div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="collapse" data-bs-target="#edit-lembrete-<?php echo $lembreteId; ?>" aria-expanded="false" aria-controls="edit-lembrete-<?php echo $lembreteId; ?>">Editar</button>
                                    <?php if ($status !== 'resolvido'): ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="lembrete_marcar_lido">
                                            <input type="hidden" name="id" value="<?php echo $lembreteId; ?>">
                                            <input type="hidden" name="selected_date" value="<?php echo sanitize($selectedDate); ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Marcar como lido</button>
                                        </form>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Marcar este lembrete como resolvido?');">
                                            <input type="hidden" name="action" value="lembrete_resolver">
                                            <input type="hidden" name="id" value="<?php echo $lembreteId; ?>">
                                            <input type="hidden" name="selected_date" value="<?php echo sanitize($selectedDate); ?>">
                                            <button type="submit" class="btn btn-sm btn-success">Resolver</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge text-bg-success align-self-center">Concluído</span>
                                    <?php endif; ?>
                                </div>

                                <div class="collapse mt-3" id="edit-lembrete-<?php echo $lembreteId; ?>">
                                    <div class="dark-panel-subtle rounded p-3">
                                        <form method="post" class="row g-2">
                                            <input type="hidden" name="action" value="lembrete_update">
                                            <input type="hidden" name="id" value="<?php echo $lembreteId; ?>">
                                            <input type="hidden" name="selected_date" value="<?php echo sanitize($selectedDate); ?>">

                                            <div class="col-12 col-lg-6">
                                                <label class="form-label mb-1">Titulo</label>
                                                <input type="text" name="titulo" class="form-control form-control-sm" maxlength="180" value="<?php echo sanitize((string) ($lembrete['titulo'] ?? '')); ?>" required>
                                            </div>
                                            <div class="col-12 col-lg-3">
                                                <label class="form-label mb-1">Categoria</label>
                                                <input type="text" name="categoria" class="form-control form-control-sm" maxlength="60" value="<?php echo sanitize((string) ($lembrete['categoria'] ?? '')); ?>">
                                            </div>
                                            <div class="col-12 col-lg-3">
                                                <label class="form-label mb-1">Nivel</label>
                                                <select name="nivel" class="form-select form-select-sm">
                                                    <option value="info"<?php echo $nivel === 'info' ? ' selected' : ''; ?>>Info</option>
                                                    <option value="warning"<?php echo $nivel === 'warning' ? ' selected' : ''; ?>>Aviso</option>
                                                    <option value="danger"<?php echo $nivel === 'danger' ? ' selected' : ''; ?>>Urgente</option>
                                                    <option value="success"<?php echo $nivel === 'success' ? ' selected' : ''; ?>>Concluido</option>
                                                </select>
                                            </div>
                                            <div class="col-12 col-lg-4">
                                                <label class="form-label mb-1">Data de referencia</label>
                                                <input type="date" name="data_referencia" class="form-control form-control-sm" value="<?php echo sanitize((string) ($lembrete['data_referencia'] ?? $selectedDate)); ?>">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label mb-1">Mensagem</label>
                                                <textarea name="mensagem" class="form-control form-control-sm" rows="2" maxlength="500" required><?php echo sanitize((string) ($lembrete['mensagem'] ?? '')); ?></textarea>
                                            </div>
                                            <div class="col-12 d-flex justify-content-end">
                                                <button type="submit" class="btn btn-sm btn-info">Salvar alterações</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
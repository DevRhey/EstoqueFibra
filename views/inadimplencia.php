<?php
$registros = $data['registros'] ?? [];
$resumo = $data['resumo'] ?? [];
$filters = $data['filters'] ?? [];
$statusOptions = $data['statusOptions'] ?? ['AGUARDANDO', 'AGENDADO', 'EM CONTATO', 'RECOLHIDO', 'SEM CONTATO', 'NAO RECOLHER'];
$routePlanning = $data['routePlanning'] ?? [
    'mode' => 'bairro',
    'googleAvailable' => false,
    'usedGoogle' => false,
    'maxPerGroup' => 8,
    'onlyCity' => true,
    'targetCityLabel' => 'Simoes Filho/BA',
    'totalCandidates' => 0,
    'excludedOutOfCity' => 0,
    'warnings' => [],
    'groups' => [],
];
?>

<section class="mb-4 page-intro reveal">
    <h2 class="page-title">Inadimplencia e Recolhimento</h2>
    <p class="page-subtitle">Importe a planilha de inadimplencia e gerencie os recolhimentos pendentes por status, prazo e historico da tentativa.</p>
</section>

<div class="card card-soft reveal mb-3">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-2 section-shortcuts">
            <a class="btn btn-sm btn-outline-secondary" href="#inad-importacao">Importacao</a>
            <a class="btn btn-sm btn-outline-secondary" href="#inad-filtros">Filtros e rota</a>
            <a class="btn btn-sm btn-outline-secondary" href="#inad-lista">Lista de registros</a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4 reveal">
    <div class="col-6 col-lg-2">
        <div class="card card-soft h-100">
            <div class="card-body">
                <small class="text-muted d-block">Total</small>
                <h3 class="mb-0"><?php echo (int) ($resumo['total'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card card-soft h-100">
            <div class="card-body">
                <small class="text-muted d-block">Aguardando</small>
                <h3 class="mb-0 text-warning"><?php echo (int) ($resumo['aguardando'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card card-soft h-100">
            <div class="card-body">
                <small class="text-muted d-block">Agendado</small>
                <h3 class="mb-0 text-info"><?php echo (int) ($resumo['agendado'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card card-soft h-100">
            <div class="card-body">
                <small class="text-muted d-block">Recolhido</small>
                <h3 class="mb-0 text-success"><?php echo (int) ($resumo['recolhido'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card card-soft h-100">
            <div class="card-body">
                <small class="text-muted d-block">Sem Contato</small>
                <h3 class="mb-0 text-danger"><?php echo (int) ($resumo['sem_contato'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card card-soft h-100">
            <div class="card-body">
                <small class="text-muted d-block">Prazos vencidos</small>
                <h3 class="mb-0 text-danger"><?php echo (int) ($resumo['vencidos'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card card-soft mb-4 reveal" id="inad-importacao">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">Importar Planilha</h5>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#inad-import-body" aria-expanded="false" aria-controls="inad-import-body" data-label-expand="Mostrar" data-label-collapse="Ocultar">Mostrar</button>
    </div>
    <div id="inad-import-body" class="collapse">
    <div class="card-body">
        <div class="mb-3 small text-muted">Padrao: Titular, Equipamento, Contato, Endereco, Prazo, Status, Tentativas</div>
        <form method="post" enctype="multipart/form-data" class="needs-validation js-inadimplencia-import-form" novalidate>
            <input type="hidden" name="action" value="inadimplencia_importar_planilha">
            <input type="hidden" name="linhas_importacao_json" class="js-inadimplencia-import-json">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-7">
                    <label class="form-label fw-bold">Arquivo da planilha</label>
                    <input type="file" name="planilha_inadimplencia" class="form-control js-inadimplencia-file" accept=".xlsx,.xls,.csv" required>
                    <div class="form-text">A leitura e feita no navegador. Depois confirme para gravar no banco.</div>
                    <div class="invalid-feedback">Selecione uma planilha valida (.xlsx, .xls ou .csv).</div>
                </div>
                <div class="col-12 col-lg-3">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" value="1" id="substituir-base" name="substituir_base">
                        <label class="form-check-label" for="substituir-base">
                            Substituir base atual
                        </label>
                    </div>
                    <small class="text-muted">Marcado: limpa os registros antigos e importa somente o novo arquivo.</small>
                </div>
                <div class="col-12 col-lg-2 d-grid">
                    <button type="button" class="btn btn-outline-info js-inadimplencia-parse">Ler planilha</button>
                </div>
            </div>

            <div class="alert alert-dark border mt-3 mb-0 d-none js-inadimplencia-preview" role="status">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <strong>Pre-visualizacao da importacao</strong>
                    <span class="badge text-bg-info js-inadimplencia-preview-count">0 linha(s)</span>
                </div>
                <div class="small js-inadimplencia-preview-columns mb-2"></div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-2">
                        <thead>
                        <tr>
                            <th>Titular</th>
                            <th>Equipamento</th>
                            <th>Contato</th>
                            <th>Endereco</th>
                            <th>Prazo</th>
                            <th>Status</th>
                            <th>Tentativas</th>
                        </tr>
                        </thead>
                        <tbody class="js-inadimplencia-preview-body"></tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-primary">Confirmar Importacao</button>
            </div>
        </form>
        <form method="post" class="mt-3" onsubmit="return confirm('Isso vai apagar todos os registros atuais da base. Deseja continuar?');">
            <input type="hidden" name="action" value="inadimplencia_limpar_base">
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-outline-danger">Limpar base atual</button>
                <small class="text-muted align-self-center">Use isto para zerar a base antes de importar uma nova planilha.</small>
            </div>
        </form>
    </div>
    </div>
</div>

<div class="card card-soft reveal mb-4" id="inad-filtros">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Filtros e Planejamento de Rota</h5>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#inad-filter-body" aria-expanded="true" aria-controls="inad-filter-body" data-label-expand="Mostrar" data-label-collapse="Ocultar">Ocultar</button>
    </div>
    <div id="inad-filter-body" class="collapse show">
    <div class="card-body border-bottom">
        <form method="get" class="row g-3 align-items-end">
            <input type="hidden" name="route" value="inadimplencia">
            <div class="col-12 col-lg-4">
                <label class="form-label">Buscar</label>
                <input type="text" name="q" class="form-control" placeholder="Titular, contato, endereco, tentativa" value="<?php echo sanitize((string) ($filters['query'] ?? '')); ?>">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($statusOptions as $status): ?>
                        <option value="<?php echo sanitize($status); ?>" <?php echo strtoupper((string) ($filters['status'] ?? '')) === $status ? 'selected' : ''; ?>>
                            <?php echo sanitize($status); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Prazo de</label>
                <input type="date" name="prazo_de" class="form-control" value="<?php echo sanitize((string) ($filters['prazo_de'] ?? '')); ?>">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Prazo ate</label>
                <input type="date" name="prazo_ate" class="form-control" value="<?php echo sanitize((string) ($filters['prazo_ate'] ?? '')); ?>">
            </div>
            <div class="col-6 col-lg-2 d-grid gap-2">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="index.php?route=inadimplencia" class="btn btn-outline-secondary">Limpar</a>
            </div>
        </form>

        <hr class="my-4">

        <form method="get" class="row g-3 align-items-end">
            <input type="hidden" name="route" value="inadimplencia">
            <input type="hidden" name="q" value="<?php echo sanitize((string) ($filters['query'] ?? '')); ?>">
            <input type="hidden" name="status" value="<?php echo sanitize((string) ($filters['status'] ?? '')); ?>">
            <input type="hidden" name="prazo_de" value="<?php echo sanitize((string) ($filters['prazo_de'] ?? '')); ?>">
            <input type="hidden" name="prazo_ate" value="<?php echo sanitize((string) ($filters['prazo_ate'] ?? '')); ?>">

            <div class="col-12">
                <h6 class="mb-1">Lista de recolhimento otimizada</h6>
                <p class="text-muted small mb-0">Agrupe por bairro ou por geolocalizacao (Google) para priorizar visitas proximas em <?php echo sanitize((string) ($routePlanning['targetCityLabel'] ?? 'Simoes Filho/BA')); ?>.</p>
            </div>

            <div class="col-12 col-lg-3">
                <label class="form-label">Modo de agrupamento</label>
                <select name="rota_modo" class="form-select">
                    <option value="bairro" <?php echo (($routePlanning['mode'] ?? 'bairro') === 'bairro') ? 'selected' : ''; ?>>Por bairro/endereco</option>
                    <option value="geo" <?php echo (($routePlanning['mode'] ?? 'bairro') === 'geo') ? 'selected' : ''; ?>>Geolocalizacao (Google Maps)</option>
                </select>
            </div>

            <div class="col-6 col-lg-2">
                <label class="form-label">Maximo por grupo</label>
                <input type="number" min="3" max="20" class="form-control" name="rota_limite_grupo" value="<?php echo (int) ($routePlanning['maxPerGroup'] ?? 8); ?>">
            </div>

            <div class="col-12 col-lg-3">
                <div class="form-check mt-lg-4 pt-lg-2">
                    <input class="form-check-input" type="checkbox" value="1" id="rota-so-cidade" name="rota_so_cidade" <?php echo !empty($routePlanning['onlyCity']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="rota-so-cidade">Somente enderecos de Simoes Filho/BA</label>
                </div>
            </div>

            <div class="col-6 col-lg-2 d-grid">
                <button type="submit" class="btn btn-outline-primary">Gerar lista</button>
            </div>

            <div class="col-6 col-lg-2 d-grid">
                <a href="index.php?route=inadimplencia" class="btn btn-outline-secondary">Resetar</a>
            </div>
        </form>

        <div class="mt-3 d-flex flex-wrap gap-2">
            <span class="badge text-bg-dark">Candidatos: <?php echo (int) ($routePlanning['totalCandidates'] ?? 0); ?></span>
            <span class="badge text-bg-secondary">Excluidos fora da cidade: <?php echo (int) ($routePlanning['excludedOutOfCity'] ?? 0); ?></span>
            <?php if (!empty($routePlanning['usedGoogle'])): ?>
                <span class="badge text-bg-success">Google geocoding ativo</span>
            <?php elseif (!empty($routePlanning['googleAvailable'])): ?>
                <span class="badge text-bg-info">Google disponivel (modo bairro em uso)</span>
            <?php else: ?>
                <span class="badge text-bg-warning text-dark">Google nao configurado</span>
            <?php endif; ?>
        </div>

        <?php foreach (($routePlanning['warnings'] ?? []) as $warning): ?>
            <div class="alert alert-warning mt-3 mb-0 py-2"><?php echo sanitize((string) $warning); ?></div>
        <?php endforeach; ?>

        <div class="mt-4">
            <?php $groups = $routePlanning['groups'] ?? []; ?>
            <?php if (empty($groups)): ?>
                <div class="alert alert-light border mb-0">Nenhum grupo de recolhimento foi identificado com os filtros atuais.</div>
            <?php else: ?>
                <div class="accordion" id="accordion-rota-coleta">
                    <?php foreach ($groups as $index => $group): ?>
                        <?php $collapseId = 'rota-group-' . (int) $index; ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading-<?php echo $collapseId; ?>">
                                <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="<?php echo $collapseId; ?>">
                                    <span class="me-3 fw-bold">Grupo <?php echo (int) ($group['sequence'] ?? ($index + 1)); ?></span>
                                    <span class="me-3"><?php echo sanitize((string) ($group['label'] ?? 'Rota')); ?></span>
                                    <span class="badge text-bg-primary ms-auto"><?php echo (int) ($group['size'] ?? 0); ?> coleta(s)</span>
                                </button>
                            </h2>
                            <div id="<?php echo $collapseId; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" aria-labelledby="heading-<?php echo $collapseId; ?>" data-bs-parent="#accordion-rota-coleta">
                                <div class="accordion-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped mb-0 align-middle">
                                            <thead>
                                            <tr>
                                                <th>Titular</th>
                                                <th>Contato</th>
                                                <th>Bairro</th>
                                                <th>Endereco</th>
                                                <th>Status</th>
                                                <th>Prazo</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach (($group['items'] ?? []) as $item): ?>
                                                <tr>
                                                    <td><?php echo sanitize((string) ($item['titular'] ?? '')); ?></td>
                                                    <td><?php echo sanitize((string) ($item['contato'] ?? '')); ?></td>
                                                    <td><?php echo sanitize((string) ($item['bairro'] ?? '')); ?></td>
                                                    <td><?php echo sanitize((string) ($item['endereco'] ?? '')); ?></td>
                                                    <td><?php echo sanitize((string) ($item['status'] ?? '')); ?></td>
                                                    <td>
                                                        <?php if (!empty($item['prazo'])): ?>
                                                            <?php echo date('d/m/Y', strtotime((string) $item['prazo'])); ?>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>

    <div class="card-body p-0" id="inad-lista">
        <div class="p-3 border-bottom">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-6">
                    <label class="form-label small text-muted mb-1">Busca rapida na lista atual</label>
                    <input type="text" class="form-control js-inad-table-search" placeholder="Filtrar por titular, contato, endereco ou status">
                </div>
                <div class="col-12 col-lg-6">
                    <small class="text-muted">Registros visiveis: <strong class="js-inad-visible-count"><?php echo count($registros); ?></strong></small>
                </div>
            </div>
            <div class="alert alert-dark border d-none mt-3 mb-0 js-inad-empty">Nenhum registro encontrado para a busca rapida.</div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead>
                <tr>
                    <th>Titular</th>
                    <th>Equipamento</th>
                    <th>Contato</th>
                    <th>Endereco</th>
                    <th>Prazo</th>
                    <th>Status</th>
                    <th>Tentativas</th>
                    <th>Obs.</th>
                    <th style="width: 220px;">Acoes</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($registros)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4">Nenhum registro encontrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($registros as $item): ?>
                        <?php
                        $status = strtoupper((string) ($item['status'] ?? 'AGUARDANDO'));
                        $badgeClass = 'text-bg-secondary';
                        if ($status === 'AGUARDANDO') {
                            $badgeClass = 'text-bg-warning';
                        } elseif ($status === 'AGENDADO' || $status === 'EM CONTATO') {
                            $badgeClass = 'text-bg-info';
                        } elseif ($status === 'RECOLHIDO') {
                            $badgeClass = 'text-bg-success';
                        } elseif ($status === 'SEM CONTATO') {
                            $badgeClass = 'text-bg-danger';
                        }
                        ?>
                        <tr class="js-inad-row"
                            data-inad-text="<?php echo strtolower(sanitize((string) (($item['titular'] ?? '') . ' ' . ($item['contato'] ?? '') . ' ' . ($item['endereco'] ?? '') . ' ' . ($item['status'] ?? '')))); ?>">
                            <td><?php echo sanitize((string) ($item['titular'] ?? '')); ?></td>
                            <td><?php echo sanitize((string) ($item['equipamento'] ?? '')); ?></td>
                            <td><?php echo sanitize((string) ($item['contato'] ?? '')); ?></td>
                            <td><?php echo sanitize((string) ($item['endereco'] ?? '')); ?></td>
                            <td>
                                <?php if (!empty($item['prazo'])): ?>
                                    <?php echo date('d/m/Y', strtotime((string) $item['prazo'])); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?php echo $badgeClass; ?>"><?php echo sanitize($status); ?></span></td>
                            <td class="small" style="white-space: pre-line;"><?php echo nl2br(sanitize((string) ($item['tentativa_1'] ?? ''))); ?></td>
                            <td class="small"><?php echo sanitize((string) ($item['observacoes'] ?? '')); ?></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-info js-open-inadimplencia-edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modal-editar-inadimplencia"
                                        data-id="<?php echo (int) ($item['id'] ?? 0); ?>"
                                        data-titular="<?php echo sanitize((string) ($item['titular'] ?? '')); ?>"
                                        data-equipamento="<?php echo sanitize((string) ($item['equipamento'] ?? '')); ?>"
                                        data-contato="<?php echo sanitize((string) ($item['contato'] ?? '')); ?>"
                                        data-endereco="<?php echo sanitize((string) ($item['endereco'] ?? '')); ?>"
                                        data-prazo="<?php echo sanitize((string) ($item['prazo'] ?? '')); ?>"
                                        data-status="<?php echo sanitize((string) ($item['status'] ?? 'AGUARDANDO')); ?>"
                                        data-tentativa="<?php echo sanitize((string) ($item['tentativa_1'] ?? '')); ?>"
                                        data-observacoes="<?php echo sanitize((string) ($item['observacoes'] ?? '')); ?>"
                                    >
                                        Editar
                                    </button>

                                    <form method="post" onsubmit="return confirm('Remover este registro?');">
                                        <input type="hidden" name="action" value="inadimplencia_excluir">
                                        <input type="hidden" name="id" value="<?php echo (int) ($item['id'] ?? 0); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-editar-inadimplencia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-dark">
                <h5 class="modal-title">Atualizar registro de inadimplencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="inadimplencia_atualizar">
                <input type="hidden" name="id" id="inad-edit-id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Titular</label>
                            <input type="text" name="titular" id="inad-edit-titular" class="form-control" required>
                            <div class="invalid-feedback">Informe o titular.</div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Equipamento</label>
                            <input type="text" name="equipamento" id="inad-edit-equipamento" class="form-control" required>
                            <div class="invalid-feedback">Informe o equipamento.</div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Contato</label>
                            <input type="text" name="contato" id="inad-edit-contato" class="form-control" maxlength="80">
                        </div>
                        <div class="col-12 col-lg-8">
                            <label class="form-label">Endereco</label>
                            <input type="text" name="endereco" id="inad-edit-endereco" class="form-control" maxlength="255">
                        </div>
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Prazo</label>
                            <input type="date" name="prazo" id="inad-edit-prazo" class="form-control">
                        </div>
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Status</label>
                            <select name="status" id="inad-edit-status" class="form-select" required>
                                <?php foreach ($statusOptions as $status): ?>
                                    <option value="<?php echo sanitize($status); ?>"><?php echo sanitize($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-lg-12">
                            <label class="form-label">Tentativas registradas</label>
                            <textarea name="tentativa_1" id="inad-edit-tentativa" class="form-control" rows="4" maxlength="1500"></textarea>
                            <div class="form-text">Cada nova tentativa pode ser adicionada abaixo sem apagar o historico acima.</div>
                        </div>
                        <div class="col-12 col-lg-12">
                            <label class="form-label">Nova tentativa</label>
                            <textarea name="nova_tentativa" id="inad-edit-nova-tentativa" class="form-control" rows="2" maxlength="1500" placeholder="Descreva a tentativa realizada"></textarea>
                            <div class="d-flex justify-content-end mt-2">
                                <button type="button" class="btn btn-outline-primary btn-sm js-add-inadimplencia-attempt">Registrar tentativa</button>
                            </div>
                            <div class="form-text">Ao clicar em registrar, a tentativa entra no historico com data e hora.</div>
                        </div>
                        <div class="col-12 col-lg-12">
                            <label class="form-label">Observacoes internas</label>
                            <textarea name="observacoes" id="inad-edit-observacoes" class="form-control" rows="2" maxlength="500"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info">Salvar alteracoes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$config = $data['config'] ?? [
    'prazo_reposicao_dias' => 3,
];

$itens = $data['itens'] ?? [];
$alertasCompra = $data['alertas_compra'] ?? [];
$equipamentos = $data['equipamentos'] ?? [];
$resumo = $data['resumo'] ?? [
    'total_alertas' => 0,
    'prazo_reposicao_dias' => (int) ($config['prazo_reposicao_dias'] ?? 3),
];

$whatsappNumber = '5571999429671';
?>

<section class="mb-4 page-intro reveal">
    <h2 class="page-title">Apoio Compra</h2>
    <p class="page-subtitle">Cada equipamento e tratado de forma independente para calcular estoque minimo, momento de compra e quantidade sugerida.</p>
</section>

<form method="post" class="needs-validation" novalidate>
    <input type="hidden" name="action" value="apoio_compra_save">

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-5 reveal">
            <div class="card card-soft h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Configuracao Geral</h5>
                    <span class="badge text-bg-info">Lead time: <?php echo (int) ($config['prazo_reposicao_dias'] ?? 3); ?> dias</span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Prazo medio de reposicao (dias)</label>
                        <input type="number" min="1" name="prazo_reposicao_dias" class="form-control" required value="<?php echo (int) ($config['prazo_reposicao_dias'] ?? 3); ?>">
                        <div class="invalid-feedback">Informe um prazo valido.</div>
                    </div>

                    <div class="small text-muted mb-3">
                        O alerta de compra usa a formula:<br>
                        <strong>estoque minimo = consumo diario do item x prazo de reposicao</strong><br>
                        A compra sugerida e calculada para cobrir <strong><?php echo (int) ($resumo['cobertura_compra_dias'] ?? 30); ?> dias</strong>.
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Salvar configuracoes</button>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7 reveal">
            <div class="row g-3">
                <div class="col-6 col-lg-4">
                    <div class="card card-soft h-100">
                        <div class="card-body">
                            <small class="text-muted d-block">Alertas de compra</small>
                            <h3 class="mb-0"><?php echo (int) ($resumo['total_alertas'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-4">
                    <div class="card card-soft h-100">
                        <div class="card-body">
                            <small class="text-muted d-block">Itens monitorados</small>
                            <h3 class="mb-0"><?php echo (int) ($resumo['total_itens'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="card card-soft h-100">
                        <div class="card-body">
                            <small class="text-muted d-block">Prazo de reposicao</small>
                            <h3 class="mb-0"><?php echo (int) ($resumo['prazo_reposicao_dias'] ?? 0); ?> dias</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-soft mt-3 h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Alertas imediatos de compra</h5>
                    <span class="badge text-bg-warning"><?php echo count($alertasCompra); ?> itens</span>
                </div>
                <div class="card-body">
                    <?php if (empty($alertasCompra)): ?>
                        <p class="text-success mb-0">Estoque em nivel seguro para os itens com consumo configurado.</p>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($alertasCompra as $alerta): ?>
                                <div class="dark-panel-subtle p-2 rounded small">
                                    <strong><?php echo sanitize($alerta['label'] ?? '-'); ?></strong>
                                    (<?php echo sanitize($alerta['tipo'] ?? '-'); ?>)
                                    | estoque atual: <strong><?php echo (int) ($alerta['estoque_atual'] ?? 0); ?></strong>
                                    | minimo: <strong><?php echo (int) ($alerta['estoque_minimo'] ?? 0); ?></strong>
                                    | comprar (<?php echo (int) ($alerta['cobertura_compra_dias'] ?? 30); ?> dias): <strong class="text-danger"><?php echo (int) ($alerta['quantidade_compra_sugerida'] ?? 0); ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
                            <button type="button" class="btn btn-success js-purchase-send-whatsapp" data-whatsapp-number="<?php echo sanitize($whatsappNumber); ?>">
                                Gerar WhatsApp com selecionados
                            </button>
                            <small class="text-muted">Marque na tabela abaixo os equipamentos que devem entrar na mensagem para <?php echo sanitize($whatsappNumber); ?>.</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Compras Personalizada -->
    <div class="card card-soft reveal">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Lista de Compras Personalizada</h5>
            <span class="badge text-bg-info">Itens: <span class="js-purchase-list-count">0</span></span>
        </div>
        <div class="card-body border-bottom">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-5">
                    <label class="form-label small text-muted mb-1">Selecione ou descreva o item</label>
                    <select class="form-select js-purchase-new-item-select" id="purchase-new-item-select">
                        <option value="">-- Escolher equipamento ou digitar --</option>
                        <?php foreach ($equipamentos as $equip): ?>
                            <?php
                                $equipId = (int) ($equip['id'] ?? 0);
                                $equipNome = sanitize($equip['nome'] ?? 'Item');
                                $equipTipo = sanitize($equip['tipo'] ?? '');
                            ?>
                            <option value="<?php echo $equipId; ?>" data-equip-name="<?php echo $equipNome; ?>" data-equip-type="<?php echo $equipTipo; ?>">
                                <?php echo $equipNome; ?> <?php if ($equipTipo): ?>(<?php echo $equipTipo; ?>)<?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="custom">+ Digitar descrição customizada</option>
                    </select>
                    <input type="text" class="form-control js-purchase-new-item-custom d-none mt-2" placeholder="Digite a descrição do item" id="purchase-new-item-custom">
                </div>
                <div class="col-12 col-lg-3">
                    <label class="form-label small text-muted mb-1">Quantidade</label>
                    <input type="number" min="1" value="1" class="form-control js-purchase-new-item-qty" id="purchase-new-item-qty">
                </div>
                <div class="col-12 col-lg-2">
                    <button type="button" class="btn btn-primary w-100 js-purchase-add-item">Adicionar</button>
                </div>
            </div>
        </div>

        <div class="card-body" id="purchase-list-container">
            <?php if (true): // Always show empty state initially ?>
                <p class="text-muted text-center py-3 mb-0" id="purchase-empty-message">Nenhum item na lista. Adicione itens acima para criar sua lista de compras.</p>
            <?php endif; ?>
            <div id="purchase-list-items" class="d-none">
                <div class="d-flex flex-column gap-2" id="purchase-list-items-container"></div>
                <hr class="my-3">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <button type="button" class="btn btn-success btn-sm js-purchase-list-whatsapp" data-whatsapp-number="<?php echo sanitize($whatsappNumber); ?>">
                        <span>Enviar via WhatsApp</span>
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm js-purchase-list-print">
                        Baixar PDF
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm js-purchase-list-clear">
                        Limpar lista
                    </button>
                    <small class="text-muted">Total de itens: <strong class="js-purchase-list-total-items">0</strong></small>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-soft reveal">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Planejamento por Equipamento</h5>
            <button type="submit" class="btn btn-sm btn-primary">Salvar consumos por item</button>
        </div>
        <div class="card-body border-bottom">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-5">
                    <label class="form-label small text-muted mb-1">Buscar equipamento</label>
                    <input type="text" class="form-control js-purchase-item-search" placeholder="Digite o nome do equipamento">
                </div>
                <div class="col-12 col-lg-4">
                    <div class="form-check mt-lg-4">
                        <input class="form-check-input js-purchase-only-alert" type="checkbox" id="purchase-only-alert">
                        <label class="form-check-label" for="purchase-only-alert">
                            Mostrar apenas itens com alerta de compra
                        </label>
                    </div>
                </div>
                <div class="col-12 col-lg-3 d-grid gap-2">
                    <button type="button" class="btn btn-outline-secondary js-purchase-clear-filters">Limpar filtros</button>
                    <small class="text-muted">Itens visiveis: <strong class="js-purchase-visible-count"><?php echo (int) count($itens); ?></strong></small>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Enviar</th>
                        <th>Equipamento</th>
                        <th>Tipo</th>
                        <th>Consumo Diario</th>
                        <th>Estoque Atual</th>
                        <th>Estoque Minimo (<?php echo (int) ($config['prazo_reposicao_dias'] ?? 3); ?> dias)</th>
                        <th>Estoque Objetivo (<?php echo (int) ($resumo['cobertura_compra_dias'] ?? 30); ?> dias)</th>
                        <th>Dias de Cobertura</th>
                        <th>Quando Comprar</th>
                        <th>Qtd Compra Sugerida (<?php echo (int) ($resumo['cobertura_compra_dias'] ?? 30); ?> dias)</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($itens)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">Sem dados para calculo.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($itens as $item): ?>
                            <?php
                            $comprarAgora = !empty($item['deve_comprar_agora']);
                            $diasCobertura = $item['dias_cobertura'];
                            $diasAteCompra = $item['dias_ate_ponto_compra'];
                            $equipamentoId = (int) ($item['equipamento_id'] ?? 0);
                            $itemLabel = (string) ($item['label'] ?? '');
                            $itemTipo = (string) ($item['tipo'] ?? '');
                            $qtdSugerida = (int) ($item['quantidade_compra_sugerida'] ?? 0);
                            $estoqueAtual = (int) ($item['estoque_atual'] ?? 0);
                            $estoqueMinimo = (int) ($item['estoque_minimo'] ?? 0);
                            $estoqueObjetivo = (int) ($item['estoque_objetivo_compra'] ?? 0);
                            ?>
                            <tr
                                class="js-purchase-row"
                                data-item-name="<?php echo strtolower(sanitize($itemLabel)); ?>"
                                data-item-type="<?php echo strtolower(sanitize($itemTipo)); ?>"
                                data-item-label="<?php echo sanitize($itemLabel); ?>"
                                data-item-type-label="<?php echo sanitize($itemTipo); ?>"
                                data-alert="<?php echo $comprarAgora ? '1' : '0'; ?>"
                                data-buy-qty="<?php echo $qtdSugerida; ?>"
                                data-stock-current="<?php echo $estoqueAtual; ?>"
                                data-stock-min="<?php echo $estoqueMinimo; ?>"
                            >
                                <td>
                                    <input type="checkbox" class="form-check-input js-purchase-whatsapp-item" <?php echo $comprarAgora ? 'checked' : ''; ?>>
                                </td>
                                <td><strong><?php echo sanitize($itemLabel !== '' ? $itemLabel : '-'); ?></strong></td>
                                <td><?php echo sanitize($itemTipo !== '' ? $itemTipo : '-'); ?></td>
                                <td style="max-width: 140px;">
                                    <input
                                        type="number"
                                        min="0"
                                        name="consumo_item[<?php echo $equipamentoId; ?>]"
                                        class="form-control form-control-sm"
                                        value="<?php echo (int) ($item['consumo_dia'] ?? 0); ?>"
                                    >
                                </td>
                                <td>
                                    <span class="badge <?php echo $comprarAgora ? 'text-bg-danger' : 'text-bg-success'; ?>">
                                        <?php echo $estoqueAtual; ?>
                                    </span>
                                </td>
                                <td><?php echo $estoqueMinimo; ?></td>
                                <td><?php echo $estoqueObjetivo; ?></td>
                                <td>
                                    <?php if ($diasCobertura === null): ?>
                                        -
                                    <?php else: ?>
                                        <?php echo number_format((float) $diasCobertura, 1, ',', '.'); ?> dias
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($comprarAgora): ?>
                                        <span class="badge text-bg-danger">Comprar agora</span>
                                    <?php elseif ($diasAteCompra === null): ?>
                                        <span class="text-muted">Sem previsao</span>
                                    <?php else: ?>
                                        Em <?php echo (int) $diasAteCompra; ?> dia(s)
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo $qtdSugerida; ?></strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="alert alert-dark border d-none mt-3 js-purchase-empty" role="status">
        Nenhum equipamento encontrado para os filtros informados.
    </div>
</form>

<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="/controle-estoque-fibra/assets/js/purchase-list.js"></script>

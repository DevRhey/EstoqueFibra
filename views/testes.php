<?php
$itens = $data['itens'] ?? [];
$resumo = $data['resumo'] ?? [
    'total' => 0,
    'vencidos' => 0,
    'vence_hoje' => 0,
    'proximos_3_dias' => 0,
    'em_andamento' => 0,
];
$porTecnico = $data['porTecnico'] ?? [];
$selectedDate = (string) ($data['selectedDate'] ?? date('Y-m-d'));
$automation = $data['automation'] ?? [];
$automationTestesResumo = $automation['testes']['resumo'] ?? [
    'critica' => 0,
    'alta' => 0,
    'media' => 0,
    'baixa' => 0,
];
$automationReposicaoResumo = $automation['reposicao']['resumo'] ?? [
    'tecnicos_com_pendencia' => 0,
    'pendente_total' => 0,
];
?>

<section class="mb-4 page-intro reveal">
    <h2 class="page-title">Equipamentos em Teste</h2>
    <p class="page-subtitle">Monitore todos os testes ativos com alertas de vencimento, previsoes e acoes operacionais.</p>
</section>

<div class="card card-soft reveal mb-4 border-info">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Verificacao IA (Heuristica)</h5>
        <span class="badge text-bg-info">Ativa</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-6 col-lg-3">
                <small class="text-muted d-block">Prioridade Critica</small>
                <strong class="text-danger"><?php echo (int) ($automationTestesResumo['critica'] ?? 0); ?></strong>
            </div>
            <div class="col-6 col-lg-3">
                <small class="text-muted d-block">Prioridade Alta</small>
                <strong class="text-warning"><?php echo (int) ($automationTestesResumo['alta'] ?? 0); ?></strong>
            </div>
            <div class="col-6 col-lg-3">
                <small class="text-muted d-block">Prioridade Media</small>
                <strong class="text-info"><?php echo (int) ($automationTestesResumo['media'] ?? 0); ?></strong>
            </div>
            <div class="col-6 col-lg-3">
                <small class="text-muted d-block">Reposicao Pendente Global</small>
                <strong><?php echo (int) ($automationReposicaoResumo['pendente_total'] ?? 0); ?></strong>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('modal-teste-historico');
        if (!modal) {
            return;
        }

        const title = modal.querySelector('.js-teste-historico-title');
        const subtitle = modal.querySelector('.js-teste-historico-subtitle');
        const historyList = modal.querySelector('.js-teste-historico-list');
        const historyId = modal.querySelector('.js-teste-historico-id');

        const openModal = function (button) {
            if (!button || !title || !subtitle || !historyList || !historyId) {
                return;
            }

            const tecnico = button.getAttribute('data-tecnico') || '-';
            const equipamento = button.getAttribute('data-equipamento') || '-';
            const historico = button.getAttribute('data-historico') || '';
            const movId = button.getAttribute('data-movimentacao-id') || '';

            title.textContent = tecnico + ' - ' + equipamento;
            subtitle.textContent = 'Registro #' + movId;
            historyId.value = movId;
            historyList.textContent = historico.trim() || 'Sem historico registrado.';

            modal.classList.add('show');
            modal.style.display = 'block';
            modal.setAttribute('aria-modal', 'true');
            modal.removeAttribute('aria-hidden');
            document.body.classList.add('modal-open');

            let backdrop = document.querySelector('.js-teste-historico-backdrop');
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show js-teste-historico-backdrop';
                document.body.appendChild(backdrop);
            }
        };

        const closeModal = function () {
            modal.classList.remove('show');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            modal.removeAttribute('aria-modal');
            document.body.classList.remove('modal-open');

            const backdrop = document.querySelector('.js-teste-historico-backdrop');
            if (backdrop && backdrop.parentNode) {
                backdrop.parentNode.removeChild(backdrop);
            }
        };

        document.querySelectorAll('.js-open-teste-historico').forEach(function (button) {
            button.addEventListener('click', function () {
                openModal(button);
            });
        });

        modal.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function (button) {
            button.addEventListener('click', closeModal);
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    });
</script>

<div class="row g-3 mb-4 reveal">
    <div class="col-6 col-lg-2">
        <div class="card card-soft history-stat-card h-100">
            <div class="card-body">
                <small class="text-muted d-block">Total em Teste</small>
                <h3 class="mb-0"><?php echo (int) ($resumo['total'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card card-soft history-stat-card h-100 border-danger">
            <div class="card-body">
                <small class="text-muted d-block">Vencidos</small>
                <h3 class="mb-0 text-danger"><?php echo (int) ($resumo['vencidos'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card card-soft history-stat-card h-100 border-warning">
            <div class="card-body">
                <small class="text-muted d-block">Vencem Hoje</small>
                <h3 class="mb-0 text-warning"><?php echo (int) ($resumo['vence_hoje'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-soft history-stat-card h-100 border-info">
            <div class="card-body">
                <small class="text-muted d-block">Vencem em ate 3 Dias</small>
                <h3 class="mb-0 text-info"><?php echo (int) ($resumo['proximos_3_dias'] ?? 0); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-3">
        <div class="card card-soft h-100">
            <div class="card-body">
                <small class="text-muted d-block mb-2">Testes por Tecnico</small>
                <?php if (empty($porTecnico)): ?>
                    <small class="text-muted">Sem tecnicos com teste ativo.</small>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($porTecnico as $tecnicoNome => $total): ?>
                            <span class="badge text-bg-secondary"><?php echo sanitize($tecnicoNome); ?>: <?php echo (int) $total; ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card card-soft reveal">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">Lista Completa de Testes Ativos</h5>
        <div class="d-flex align-items-center gap-2">
            <a href="index.php?route=movimentacoes#import-uso-teste" class="btn btn-sm btn-outline-info">Importar planilha</a>
            <span class="badge text-bg-primary js-testes-visible-count"><?php echo count($itens); ?> registro(s)</span>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($itens)): ?>
            <div class="text-center py-5 text-muted">Nenhum equipamento em teste no momento.</div>
        <?php else: ?>
            <div class="p-3 border-bottom">
                <div class="row g-3">
                    <div class="col-12 col-lg-5">
                        <label class="form-label small text-muted mb-1">Filtrar por tecnico</label>
                        <input type="text" class="form-control js-testes-tech-filter" placeholder="Digite o nome do tecnico">
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label small text-muted mb-1">Status</label>
                        <select class="form-select js-testes-status-filter">
                            <option value="">Todos</option>
                            <option value="vencido">Vencido</option>
                            <option value="vence_hoje">Vence hoje</option>
                            <option value="proximo_vencimento">Proximo vencimento</option>
                            <option value="em_andamento">Em andamento</option>
                        </select>
                    </div>
                    <div class="col-12 col-lg-3 d-flex align-items-end">
                        <div class="d-flex gap-2 w-100">
                            <button type="button" class="btn btn-outline-secondary w-100 js-testes-clear-filters">Limpar filtros</button>
                            <button type="button" class="btn btn-outline-primary w-100 js-testes-toggle-order" data-order="asc">Urgencia ↑</button>
                        </div>
                    </div>
                </div>
                <div class="alert alert-dark border d-none mt-3 mb-0 js-testes-empty-filter" role="status">
                    Nenhum teste encontrado para os filtros informados.
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Tecnico</th>
                        <th>Equipamento</th>
                        <th>Qtd</th>
                        <th>Inicio</th>
                        <th>Vencimento</th>
                        <th>Dias</th>
                        <th>Status</th>
                        <th>IA</th>
                        <th>Local</th>
                        <th>Observacoes</th>
                        <th>Previsao</th>
                        <th>Acao IA</th>
                        <th class="text-end">Acoes</th>
                    </tr>
                    </thead>
                    <tbody class="js-testes-rows">
                    <?php foreach ($itens as $item): ?>
                        <tr class="js-teste-row" data-tech-name="<?php echo strtolower(sanitize($item['tecnico_nome'] ?? '')); ?>" data-status="<?php echo sanitize($item['faixa'] ?? 'em_andamento'); ?>" data-due-ts="<?php echo !empty($item['vencimento_teste']) ? (int) strtotime($item['vencimento_teste']) : 0; ?>">
                            <td><strong><?php echo sanitize($item['tecnico_nome'] ?? '-'); ?></strong></td>
                            <td><?php echo sanitize($item['equipamento_nome'] ?? '-'); ?></td>
                            <td><?php echo (int) ($item['quantidade'] ?? 0); ?></td>
                            <td><?php echo !empty($item['inicio_teste']) ? date('d/m/Y H:i', strtotime($item['inicio_teste'])) : '-'; ?></td>
                            <td><?php echo !empty($item['vencimento_teste']) ? date('d/m/Y H:i', strtotime($item['vencimento_teste'])) : '-'; ?></td>
                            <td><?php echo (int) ($item['dias_restantes'] ?? 0); ?></td>
                            <td>
                                <span class="badge <?php echo sanitize($item['badge_class'] ?? 'text-bg-secondary'); ?>">
                                    <?php
                                    echo match ($item['faixa'] ?? '') {
                                        'vencido' => 'VENCIDO',
                                        'vence_hoje' => 'VENCE HOJE',
                                        'proximo_vencimento' => 'PROXIMO',
                                        default => 'EM ANDAMENTO',
                                    };
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $aiPrioridade = (string) ($item['ai_prioridade'] ?? 'baixa');
                                $aiBadgeClass = match ($aiPrioridade) {
                                    'critica' => 'text-bg-danger',
                                    'alta' => 'text-bg-warning',
                                    'media' => 'text-bg-info',
                                    default => 'text-bg-secondary',
                                };
                                ?>
                                <span class="badge <?php echo $aiBadgeClass; ?>">
                                    <?php echo strtoupper(sanitize($aiPrioridade)); ?>
                                </span>
                                <div class="small text-muted">Score: <?php echo (int) ($item['ai_score'] ?? 0); ?></div>
                            </td>
                            <td><?php echo !empty($item['local_uso']) ? sanitize($item['local_uso']) : '-'; ?></td>
                            <td><?php echo !empty($item['observacoes']) ? sanitize($item['observacoes']) : '-'; ?></td>
                            <td><small><?php echo sanitize($item['previsao'] ?? '-'); ?></small></td>
                            <td><small><?php echo sanitize((string) ($item['ai_acao'] ?? 'Monitorar diariamente.')); ?></small></td>
                            <td class="text-end">
                                <div class="d-flex flex-column gap-1 align-items-end">
                                    <button type="button"
                                        class="btn btn-sm btn-outline-secondary js-open-teste-historico"
                                        data-movimentacao-id="<?php echo (int) ($item['movimentacao_id'] ?? 0); ?>"
                                        data-tecnico="<?php echo sanitize((string) ($item['tecnico_nome'] ?? '-')); ?>"
                                        data-equipamento="<?php echo sanitize((string) ($item['equipamento_nome'] ?? '-')); ?>"
                                        data-historico="<?php echo sanitize((string) ($item['historico_tratativa'] ?? '')); ?>"
                                    >Tentativas</button>
                                    <form method="post" onsubmit="return confirm('Confirmar que este equipamento ficou com o cliente e deve ser marcado como uso?');">
                                        <input type="hidden" name="action" value="movimentacao_converter_teste_uso">
                                        <input type="hidden" name="movimentacao_id" value="<?php echo (int) ($item['movimentacao_id'] ?? 0); ?>">
                                        <input type="hidden" name="local_uso" value="<?php echo sanitize((string) ($item['local_uso'] ?? '')); ?>">
                                        <input type="hidden" name="observacoes" value="<?php echo sanitize((string) ($item['observacoes'] ?? '')); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-warning">Definir uso</button>
                                    </form>
                                    <a href="index.php?route=movimentacoes&tipo=recolhimento&tecnico_id=<?php echo (int) ($item['tecnico_id'] ?? 0); ?>" class="btn btn-sm btn-outline-info">Recolher</a>
                                    <a href="index.php?route=movimentacoes&tipo=recolhimento_defeito&tecnico_id=<?php echo (int) ($item['tecnico_id'] ?? 0); ?>" class="btn btn-sm btn-outline-danger">Com defeito</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modal-teste-historico" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <div>
                    <h5 class="modal-title mb-0">Historico de Tentativas</h5>
                    <small class="d-block js-teste-historico-subtitle">Acompanhe as tratativas do teste</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="movimentacao_adicionar_tentativa_teste">
                <input type="hidden" name="movimentacao_id" class="js-teste-historico-id" value="">
                <input type="hidden" name="selected_date" value="<?php echo sanitize($selectedDate); ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <div class="small text-muted">Registro</div>
                        <strong class="js-teste-historico-title">-</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Historico atual</label>
                        <div class="border rounded p-3 bg-dark-subtle js-teste-historico-list" style="white-space: pre-line; min-height: 120px;"></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold">Tipo da tentativa</label>
                            <select name="tipo_tentativa" class="form-select">
                                <option value="geral">Geral</option>
                                <option value="recolhimento">Tentativa de recolhimento</option>
                                <option value="definir_uso">Tentativa de definir como uso</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-8">
                            <label class="form-label fw-bold">Nova tratativa</label>
                            <textarea name="nova_tentativa" class="form-control" rows="4" maxlength="1500" placeholder="Descreva o contato, retorno do cliente, agendamento ou qualquer tratativa realizada" required></textarea>
                            <div class="invalid-feedback">Informe a tentativa ou tratativa.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between flex-wrap gap-2">
                    <small class="text-muted">As tentativas ficam registradas sem apagar o histórico anterior.</small>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="submit" class="btn btn-secondary">Adicionar tentativa</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

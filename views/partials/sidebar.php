<aside class="col-12 col-lg-3 col-xl-2 p-0 sidebar-wrap">
    <div class="sidebar h-100 p-3 p-lg-4">
        <div class="brand mb-4">
            <div class="brand-badge">FTTH</div>
            <h1 class="brand-title">Estoque Provedor</h1>
            <p class="brand-subtitle">Gestao de materiais de campo</p>
        </div>

        <nav class="nav flex-column gap-2">
            <a class="nav-link sidebar-link <?php echo $currentRoute === 'dashboard' ? 'active' : ''; ?>" href="index.php?route=dashboard">Dashboard</a>
            <a class="nav-link sidebar-link <?php echo $currentRoute === 'movimentacoes' ? 'active' : ''; ?>" href="index.php?route=movimentacoes">Movimentacoes</a>
            <a class="nav-link sidebar-link <?php echo $currentRoute === 'tecnicos' ? 'active' : ''; ?>" href="index.php?route=tecnicos">Tecnicos</a>
            <a class="nav-link sidebar-link <?php echo $currentRoute === 'lembretes' ? 'active' : ''; ?>" href="index.php?route=lembretes">
                Lembretes
                <?php if (!empty($lembretesResumo['pendentes'] ?? 0)): ?>
                    <span class="badge text-bg-warning ms-2"><?php echo (int) $lembretesResumo['pendentes']; ?></span>
                <?php endif; ?>
            </a>
            <a class="nav-link sidebar-link <?php echo $currentRoute === 'testes' ? 'active' : ''; ?>" href="index.php?route=testes">Equipamentos em Teste</a>
            <a class="nav-link sidebar-link <?php echo $currentRoute === 'relatorios' ? 'active' : ''; ?>" href="index.php?route=relatorios">Relatorios + Cards</a>
            <a class="nav-link sidebar-link <?php echo $currentRoute === 'apoio_compra' ? 'active' : ''; ?>" href="index.php?route=apoio_compra">Apoio Compra</a>
            <a class="nav-link sidebar-link <?php echo $currentRoute === 'inadimplencia' ? 'active' : ''; ?>" href="index.php?route=inadimplencia">Inadimplencia</a>
        </nav>

        <?php if (is_array($automationAlerts)): ?>
            <?php
            $reposicaoResumo = $automationAlerts['reposicao']['resumo'] ?? [];
            $testesResumo = $automationAlerts['testes']['resumo'] ?? [];
            $reposicaoItens = $automationAlerts['reposicao']['itens'] ?? [];
            $testesItens = $automationAlerts['testes']['itens'] ?? [];
            $temAlertas = !empty($reposicaoItens) || !empty($testesItens);
            ?>
            <div class="mt-4 p-3 sidebar-ai-panel">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="fw-bold">Automacao IA (heuristica)</small>
                    <span class="badge text-bg-info"><?php echo (int) ($automationAlerts['resumo']['total_alertas'] ?? 0); ?></span>
                </div>
                <div class="small mb-2">
                    <div>Reposicao pendente: <strong><?php echo (int) ($reposicaoResumo['pendente_total'] ?? 0); ?></strong></div>
                    <div>Testes criticos: <strong><?php echo (int) ($testesResumo['critica'] ?? 0); ?></strong></div>
                </div>

                <?php if ($temAlertas): ?>
                    <div class="small d-flex flex-column gap-2 mb-3 sidebar-ai-list">
                        <?php foreach (array_slice($reposicaoItens, 0, 2) as $item): ?>
                            <a class="sidebar-ai-item" href="index.php?route=movimentacoes&tipo=entrega&tecnico_id=<?php echo (int) ($item['tecnico_id'] ?? 0); ?>">
                                <span class="badge text-bg-warning sidebar-ai-pill">Reposicao</span>
                                <span><?php echo sanitize((string) ($item['tecnico_nome'] ?? 'Tecnico')); ?></span>
                            </a>
                        <?php endforeach; ?>
                        <?php foreach (array_slice($testesItens, 0, 2) as $item): ?>
                            <a class="sidebar-ai-item" href="index.php?route=testes">
                                <span class="badge text-bg-danger sidebar-ai-pill">Teste</span>
                                <span><?php echo sanitize((string) ($item['tecnico_nome'] ?? 'Tecnico')); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="d-flex gap-2">
                    <a href="index.php?route=testes" class="btn btn-sm btn-outline-info w-100 sidebar-ai-action">Ver testes</a>
                    <a href="index.php?route=relatorios" class="btn btn-sm btn-outline-light w-100 sidebar-ai-action">Ver cards</a>
                </div>
            </div>
        <?php endif; ?>

        <div class="sidebar-footer mt-4">
            <small>Ambiente local XAMPP</small>
            <div>http://localhost/controle-estoque-fibra</div>
        </div>
    </div>
</aside>

<main class="col-12 col-lg-9 col-xl-10 p-3 p-md-4 p-xl-5 content-wrap">
    <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo sanitize($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

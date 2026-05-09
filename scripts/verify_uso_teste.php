<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Tecnico.php';
require_once __DIR__ . '/../models/Equipamento.php';
require_once __DIR__ . '/../models/Movimentacao.php';

function println($s) { echo $s . PHP_EOL; }

$tecModel = new Tecnico();
$eqModel = new Equipamento();
$movModel = new Movimentacao();

try {
    // Ensure equipamento exists
    $equipName = 'AUTOTEST_EQUIP';
    $equipTipo = 'insumos';
    $existing = null;
    foreach ($eqModel->all() as $e) {
        if (trim((string)$e['nome']) === $equipName) { $existing = $e; break; }
    }

    if ($existing) {
        $equipId = (int)$existing['id'];
        println("Using existing equipamento id={$equipId}");
    } else {
        $created = $eqModel->create($equipName, $equipTipo, 10, null);
        if (!$created) throw new RuntimeException('Falha ao criar equipamento teste');
        // find it
        $found = null;
        foreach ($eqModel->all() as $e) { if (trim((string)$e['nome']) === $equipName) { $found = $e; break; } }
        if (!$found) throw new RuntimeException('Nao conseguiu localizar equipamento apos criacao');
        $equipId = (int)$found['id'];
        println("Created equipamento id={$equipId}");
    }

    // Ensure tecnico exists
    $tecName = 'AUTOTEST_TEC';
    $foundTec = null;
    foreach ($tecModel->all() as $t) { if (trim((string)$t['nome']) === $tecName) { $foundTec = $t; break; } }
    if ($foundTec) {
        $tecId = (int)$foundTec['id'];
        println("Using existing tecnico id={$tecId}");
    } else {
        $tecModel->create($tecName);
        $found = null;
        foreach ($tecModel->all() as $t) { if (trim((string)$t['nome']) === $tecName) { $found = $t; break; } }
        if (!$found) throw new RuntimeException('Falha ao criar tecnico teste');
        $tecId = (int)$found['id'];
        println("Created tecnico id={$tecId}");
    }

    // Create entrega to give one unit in hand
    $now = date('Y-m-d H:i:s');
    $movModel->create($tecId, $equipId, 1, 'entrega', null, 'Autotest entrega', $now);
    println("Entrega registrada: tecnico={$tecId} equipamento={$equipId} quantidade=1");

    // Create uso_teste
    $movModel->create($tecId, $equipId, 1, 'uso_teste', 'Local Autotest', 'Autotest uso_teste', $now);
    println("Uso em teste registrado: tecnico={$tecId} equipamento={$equipId} quantidade=1");

    // Fetch alertas
    $alerts = $movModel->reportAlertasUsoTeste();
    println("Total alertas uso_teste: " . count($alerts));
    foreach ($alerts as $a) {
        if ((int)$a['tecnico_id'] === $tecId && (int)$a['equipamento_id'] === $equipId) {
            println("Found alert for our test: mov_id={$a['movimentacao_id']} inicio={$a['inicio_teste']} vencimento={$a['vencimento_teste']} dias_restantes={$a['dias_restantes']}");
        }
    }

    // Try convertTestToUsage: ensure no later movimentacao; attempt convert
    $our = null;
    foreach ($alerts as $a) { if ((int)$a['tecnico_id'] === $tecId && (int)$a['equipamento_id'] === $equipId) { $our = $a; break; } }
    if ($our) {
        $movId = (int)$our['movimentacao_id'];
        $converted = $movModel->convertTestToUsage($movId, 'Local Autotest Convertido', 'Autotest convert');
        println('convertTestToUsage result: ' . ($converted ? 'converted' : 'no-op'));
    } else {
        println('No matching alert found to test conversion.');
    }

    println('Done.');
} catch (Throwable $e) {
    println('ERROR: ' . $e->getMessage());
    exit(1);
}

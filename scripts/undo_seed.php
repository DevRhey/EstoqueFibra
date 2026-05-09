<?php
/**
 * Undo seed: remove records created by scripts/seed_database.php
 * Use with caution: php scripts/undo_seed.php
 */
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$pdo = $db->getConnection();

echo "Iniciando undo do seed...\n";

try {
    $pdo->beginTransaction();

    // Remove inadimplencia origin 'seed'
    $stmt = $pdo->prepare("DELETE FROM inadimplencia_recolhimentos WHERE origem_arquivo = :orig");
    $stmt->execute(['orig' => 'seed']);
    echo "Inadimplencia removida: " . $stmt->rowCount() . "\n";

    // Remove lembretes seed by key prefix
    $stmt = $pdo->prepare("DELETE FROM lembretes WHERE lembrete_key LIKE 'seed_%'");
    $stmt->execute();
    echo "Lembretes seed removidos: " . $stmt->rowCount() . "\n";

    // Remove movimentacoes with observacoes containing 'Seed:' (best-effort)
    $stmt = $pdo->prepare("DELETE FROM movimentacoes WHERE observacoes LIKE :obs");
    $stmt->execute(['obs' => '%Seed:%']);
    echo "Movimentacoes seed removidas (por observacoes): " . $stmt->rowCount() . "\n";

    // Remove equipamentos created by seed names
    $seedEquipNames = ['Roteador X100','ONU Z200','Conector Fibra P10','ONT M50','Esticador E1'];
    $in = implode(',', array_fill(0, count($seedEquipNames), '?'));
    $stmt = $pdo->prepare("DELETE FROM equipamentos WHERE nome IN ($in)");
    $stmt->execute($seedEquipNames);
    echo "Equipamentos seed removidos: " . $stmt->rowCount() . "\n";

    // Remove tecnicos created by seed names
    $seedTecNames = ['Joao Silva','Maria Oliveira','Carlos Pereira','Ana Souza'];
    $in = implode(',', array_fill(0, count($seedTecNames), '?'));
    $stmt = $pdo->prepare("DELETE FROM tecnicos WHERE nome IN ($in)");
    $stmt->execute($seedTecNames);
    echo "Tecnicos seed removidos: " . $stmt->rowCount() . "\n";

    $pdo->commit();
    echo "Undo seed concluido.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "Erro no undo seed: " . $e->getMessage() . "\n";
    exit(1);
}

return 0;

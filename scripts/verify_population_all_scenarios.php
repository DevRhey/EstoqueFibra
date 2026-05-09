<?php
require_once __DIR__ . '/../config/database.php';

function out(string $line): void
{
    echo $line . PHP_EOL;
}

$conn = (new Database())->getConnection();

$enum = $conn->query("SHOW COLUMNS FROM movimentacoes LIKE 'tipo'")->fetch(PDO::FETCH_ASSOC);
$autTec = (int) $conn->query("SELECT COUNT(*) FROM tecnicos WHERE nome LIKE 'AUTOSCEN %'")->fetchColumn();
$autEq = (int) $conn->query("SELECT COUNT(*) FROM equipamentos WHERE nome LIKE 'AUTOSCEN %'")->fetchColumn();
$mov = $conn
    ->query("SELECT tipo, COUNT(*) total FROM movimentacoes WHERE observacoes LIKE '%AUTOSCEN%' GROUP BY tipo ORDER BY tipo")
    ->fetchAll(PDO::FETCH_ASSOC);
$lem = $conn
    ->query("SELECT status, COUNT(*) total FROM lembretes WHERE lembrete_key LIKE 'autoscen_%' GROUP BY status ORDER BY status")
    ->fetchAll(PDO::FETCH_ASSOC);
$ina = $conn
    ->query("SELECT status, COUNT(*) total FROM inadimplencia_recolhimentos WHERE origem_arquivo = 'autoscen' GROUP BY status ORDER BY status")
    ->fetchAll(PDO::FETCH_ASSOC);

out('enum_movimentacoes=' . (string) ($enum['Type'] ?? 'desconhecido'));
out('autoscen_tecnicos=' . $autTec);
out('autoscen_equipamentos=' . $autEq);
out('movimentacoes_por_tipo=' . json_encode($mov, JSON_UNESCAPED_UNICODE));
out('lembretes_por_status=' . json_encode($lem, JSON_UNESCAPED_UNICODE));
out('inadimplencia_por_status=' . json_encode($ina, JSON_UNESCAPED_UNICODE));

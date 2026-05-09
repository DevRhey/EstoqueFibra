<?php
/**
 * Seeder básico para popular o sistema com dados de exemplo.
 * Execute: php scripts/seed_database.php
 */
require_once __DIR__ . '/../config/database.php';

function now($offsetDays = 0)
{
    if ($offsetDays === 0) return date('Y-m-d H:i:s');
    return date('Y-m-d H:i:s', strtotime("${offsetDays} days"));
}

$db = new Database();
$pdo = $db->getConnection();

echo "Iniciando seed da base...\n";

try {
    $pdo->beginTransaction();

    // Tecnicos
    $tecnicos = [
        'Joao Silva',
        'Maria Oliveira',
        'Carlos Pereira',
        'Ana Souza'
    ];

    $tecIds = [];
    $stmtFindTec = $pdo->prepare('SELECT id FROM tecnicos WHERE nome = :nome LIMIT 1');
    $stmtInsertTec = $pdo->prepare('INSERT INTO tecnicos (nome) VALUES (:nome)');
    foreach ($tecnicos as $nome) {
        $stmtFindTec->execute(['nome' => $nome]);
        $row = $stmtFindTec->fetch();
        if ($row) {
            $tecIds[] = (int) $row['id'];
            continue;
        }
        $stmtInsertTec->execute(['nome' => $nome]);
        $tecIds[] = (int) $pdo->lastInsertId();
    }

    echo "Técnicos inseridos/confirmados: " . count($tecIds) . "\n";

    // Equipamentos
    $equipamentos = [
        ['nome' => 'Roteador X100', 'tipo' => 'roteador', 'quantidade' => 30, 'codigo_barras' => 'RTX100-0001'],
        ['nome' => 'ONU Z200', 'tipo' => 'onu', 'quantidade' => 40, 'codigo_barras' => 'ONUZ200-0001'],
        ['nome' => 'Conector Fibra P10', 'tipo' => 'conector_fibra', 'quantidade' => 500, 'codigo_barras' => null],
        ['nome' => 'ONT M50', 'tipo' => 'ont', 'quantidade' => 10, 'codigo_barras' => 'ONTM50-0001'],
        ['nome' => 'Esticador E1', 'tipo' => 'insumos', 'quantidade' => 50, 'codigo_barras' => null],
    ];

    $equipIds = [];
    $stmtFindEq = $pdo->prepare('SELECT id FROM equipamentos WHERE nome = :nome LIMIT 1');
    $stmtInsertEq = $pdo->prepare('INSERT INTO equipamentos (nome, tipo, quantidade, codigo_barras) VALUES (:nome, :tipo, :quantidade, :codigo_barras)');
    foreach ($equipamentos as $eq) {
        $stmtFindEq->execute(['nome' => $eq['nome']]);
        $row = $stmtFindEq->fetch();
        if ($row) {
            $equipIds[] = (int) $row['id'];
            continue;
        }
        $stmtInsertEq->execute([
            'nome' => $eq['nome'],
            'tipo' => $eq['tipo'],
            'quantidade' => $eq['quantidade'],
            'codigo_barras' => $eq['codigo_barras'],
        ]);
        $equipIds[] = (int) $pdo->lastInsertId();
    }

    echo "Equipamentos inseridos/confirmados: " . count($equipIds) . "\n";

    // Movimentacoes de exemplo
    $stmtInsertMov = $pdo->prepare(
        'INSERT INTO movimentacoes (tecnico_id, equipamento_id, quantidade, tipo, local_uso, observacoes, data_movimentacao, equipamento_nome_snapshot, equipamento_tipo_snapshot, equipamento_codigo_barras_snapshot)
         VALUES (:tecnico_id, :equipamento_id, :quantidade, :tipo, :local_uso, :observacoes, :data_movimentacao, :equip_nome_snap, :equip_tipo_snap, :equip_codigo_snap)'
    );

    // Criar algumas movimentações: entregas e usos
    $pairs = [
        ['tec' => $tecIds[0], 'eq' => $equipIds[0]],
        ['tec' => $tecIds[1], 'eq' => $equipIds[1]],
        ['tec' => $tecIds[2], 'eq' => $equipIds[2]],
    ];

    foreach ($pairs as $i => $p) {
        // entrega
        $stmtInsertMov->execute([
            'tecnico_id' => $p['tec'],
            'equipamento_id' => $p['eq'],
            'quantidade' => 3 + $i,
            'tipo' => 'entrega',
            'local_uso' => null,
            'observacoes' => 'Seed: entrega inicial',
            'data_movimentacao' => now(-2 + $i),
            'equip_nome_snap' => $equipamentos[$i]['nome'] ?? null,
            'equip_tipo_snap' => $equipamentos[$i]['tipo'] ?? null,
            'equip_codigo_snap' => $equipamentos[$i]['codigo_barras'] ?? null,
        ]);

        // uso
        $stmtInsertMov->execute([
            'tecnico_id' => $p['tec'],
            'equipamento_id' => $p['eq'],
            'quantidade' => 1,
            'tipo' => 'uso',
            'local_uso' => 'Cliente seed - Rua A',
            'observacoes' => 'Seed: uso em cliente',
            'data_movimentacao' => now(-1 + $i),
            'equip_nome_snap' => $equipamentos[$i]['nome'] ?? null,
            'equip_tipo_snap' => $equipamentos[$i]['tipo'] ?? null,
            'equip_codigo_snap' => $equipamentos[$i]['codigo_barras'] ?? null,
        ]);

        // uso_teste
        $stmtInsertMov->execute([
            'tecnico_id' => $p['tec'],
            'equipamento_id' => $p['eq'],
            'quantidade' => 1,
            'tipo' => 'uso_teste',
            'local_uso' => 'Teste seed - Cliente B',
            'observacoes' => 'Seed: uso em teste',
            'data_movimentacao' => now($i),
            'equip_nome_snap' => $equipamentos[$i]['nome'] ?? null,
            'equip_tipo_snap' => $equipamentos[$i]['tipo'] ?? null,
            'equip_codigo_snap' => $equipamentos[$i]['codigo_barras'] ?? null,
        ]);
    }

    echo "Movimentações seed inseridas.\n";

    // Lembretes
    $stmtInsertL = $pdo->prepare('INSERT INTO lembretes (lembrete_key, categoria, titulo, mensagem, nivel, data_referencia, auto_gerado, status) VALUES (:lembrete_key, :categoria, :titulo, :mensagem, :nivel, :data_referencia, 0, :status)');
    $lembretes = [
        ['titulo' => 'Verificar estoque Roteador', 'mensagem' => 'Estoque abaixo do minimo para roteadores.', 'nivel' => 'warning', 'categoria' => 'estoque'],
        ['titulo' => 'Contato cliente X', 'mensagem' => 'Ligar para confirmar recolhimento agendado.', 'nivel' => 'info', 'categoria' => 'operacional'],
    ];
    foreach ($lembretes as $l) {
        $stmtInsertL->execute([
            'lembrete_key' => uniqid('seed_', true),
            'categoria' => $l['categoria'],
            'titulo' => $l['titulo'],
            'mensagem' => $l['mensagem'],
            'nivel' => $l['nivel'],
            'data_referencia' => date('Y-m-d'),
            'status' => 'aberto',
        ]);
    }

    echo "Lembretes seed inseridos.\n";

    // Inadimplencia exemplo
    $stmtInsInad = $pdo->prepare('INSERT INTO inadimplencia_recolhimentos (titular, equipamento, contato, endereco, prazo, status, tentativa_1, observacoes, origem_arquivo, last_import_at) VALUES (:titular, :equipamento, :contato, :endereco, :prazo, :status, :tentativa_1, :observacoes, :origem_arquivo, NOW())');
    $stmtInsInad->execute([
        'titular' => 'Assinante Seed',
        'equipamento' => 'ONU Z200',
        'contato' => '71999990000',
        'endereco' => 'Rua das Flores, Bairro Centro, Simoes Filho - BA',
        'prazo' => date('Y-m-d', strtotime('+7 days')),
        'status' => 'AGUARDANDO',
        'tentativa_1' => 'Importado via seed',
        'observacoes' => 'Registro de teste',
        'origem_arquivo' => 'seed',
    ]);

    echo "Inadimplência seed inserida.\n";

    $pdo->commit();

    echo "SEED concluído com sucesso.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "Erro durante seed: " . $e->getMessage() . "\n";
    exit(1);
}

// Resumo final
echo "Resumo: tecnicos=" . count($tecIds) . " equipamentos=" . count($equipIds) . "\n";

return 0;

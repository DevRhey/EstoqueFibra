<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Equipamento.php';
require_once __DIR__ . '/../models/Tecnico.php';
require_once __DIR__ . '/../models/Movimentacao.php';

const AUTOSCEN_TAG = '[AUTOSCEN]';
const AUTOSCEN_SOURCE = 'autoscen';

function println(string $message): void
{
    echo $message . PHP_EOL;
}

function hasTable(PDO $conn, string $table): bool
{
    $stmt = $conn->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table');
    $stmt->execute(['table' => $table]);
    return (int) $stmt->fetchColumn() > 0;
}

function hasColumn(PDO $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column');
    $stmt->execute([
        'table' => $table,
        'column' => $column,
    ]);
    return (int) $stmt->fetchColumn() > 0;
}

function findEquipamentoByName(array $equipamentos, string $name): ?array
{
    foreach ($equipamentos as $item) {
        if (trim((string) ($item['nome'] ?? '')) === $name) {
            return $item;
        }
    }

    return null;
}

function findTecnicoByName(array $tecnicos, string $name): ?array
{
    foreach ($tecnicos as $item) {
        if (trim((string) ($item['nome'] ?? '')) === $name) {
            return $item;
        }
    }

    return null;
}

function ensureEquipamento(Equipamento $equipamentoModel, string $nome, string $tipo, int $quantidade, ?string $codigo): int
{
    $existing = findEquipamentoByName($equipamentoModel->all(), $nome);
    if ($existing !== null) {
        $equipamentoModel->update((int) $existing['id'], $nome, $tipo, $quantidade, $codigo);
        return (int) $existing['id'];
    }

    $equipamentoModel->create($nome, $tipo, $quantidade, $codigo);
    $fresh = findEquipamentoByName($equipamentoModel->all(), $nome);
    if ($fresh === null) {
        throw new RuntimeException('Falha ao criar equipamento: ' . $nome);
    }

    return (int) $fresh['id'];
}

function ensureTecnico(Tecnico $tecnicoModel, string $nome): int
{
    $existing = findTecnicoByName($tecnicoModel->all(), $nome);
    if ($existing !== null) {
        return (int) $existing['id'];
    }

    $tecnicoModel->create($nome);
    $fresh = findTecnicoByName($tecnicoModel->all(), $nome);
    if ($fresh === null) {
        throw new RuntimeException('Falha ao criar tecnico: ' . $nome);
    }

    return (int) $fresh['id'];
}

function movementDate(string $date, string $time): string
{
    return $date . ' ' . $time;
}

$database = new Database();
$conn = $database->getConnection();
$equipamentoModel = new Equipamento();
$tecnicoModel = new Tecnico();
$movimentacaoModel = new Movimentacao();

$today = new DateTimeImmutable('today');
$d0 = $today->format('Y-m-d');
$d1 = $today->modify('-1 day')->format('Y-m-d');
$d2 = $today->modify('-2 days')->format('Y-m-d');
$d3 = $today->modify('-3 days')->format('Y-m-d');
$d4 = $today->modify('-4 days')->format('Y-m-d');
$d7 = $today->modify('-7 days')->format('Y-m-d');
$d10 = $today->modify('-10 days')->format('Y-m-d');

println('Iniciando populacao completa de cenarios...');

try {
    println('Removendo dados anteriores da tag AUTOSCEN...');
    if (hasTable($conn, 'lembretes')) {
        $stmtDelLembretes = $conn->prepare("DELETE FROM lembretes WHERE lembrete_key LIKE 'autoscen_%' OR titulo LIKE :tag");
        $stmtDelLembretes->execute(['tag' => AUTOSCEN_TAG . '%']);
    }

    if (hasTable($conn, 'inadimplencia_recolhimentos')) {
        $stmtDelInad = $conn->prepare('DELETE FROM inadimplencia_recolhimentos WHERE origem_arquivo = :source OR titular LIKE :tag');
        $stmtDelInad->execute([
            'source' => AUTOSCEN_SOURCE,
            'tag' => AUTOSCEN_TAG . '%',
        ]);
    }

    $stmtTecIds = $conn->prepare("SELECT id FROM tecnicos WHERE nome LIKE 'AUTOSCEN %'");
    $stmtTecIds->execute();
    $tecnicoIdsToClean = array_map('intval', $stmtTecIds->fetchAll(PDO::FETCH_COLUMN));

    $stmtEqIds = $conn->prepare("SELECT id FROM equipamentos WHERE nome LIKE 'AUTOSCEN %'");
    $stmtEqIds->execute();
    $equipIdsToClean = array_map('intval', $stmtEqIds->fetchAll(PDO::FETCH_COLUMN));

    if (!empty($tecnicoIdsToClean) || !empty($equipIdsToClean)) {
        $conditions = ["observacoes LIKE '" . AUTOSCEN_TAG . "%'"];
        $params = [];

        if (!empty($tecnicoIdsToClean)) {
            $placeholders = [];
            foreach ($tecnicoIdsToClean as $index => $id) {
                $key = 'tec_' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $id;
            }
            $conditions[] = 'tecnico_id IN (' . implode(', ', $placeholders) . ')';
        }

        if (!empty($equipIdsToClean)) {
            $placeholders = [];
            foreach ($equipIdsToClean as $index => $id) {
                $key = 'eq_' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $id;
            }
            $conditions[] = 'equipamento_id IN (' . implode(', ', $placeholders) . ')';
        }

        $sqlDeleteMov = 'DELETE FROM movimentacoes WHERE ' . implode(' OR ', $conditions);
        $stmtDeleteMov = $conn->prepare($sqlDeleteMov);
        $stmtDeleteMov->execute($params);
    }

    if (!empty($tecnicoIdsToClean)) {
        $placeholders = [];
        $params = [];
        foreach ($tecnicoIdsToClean as $index => $id) {
            $key = 'tec_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
        }
        $stmtDeleteTec = $conn->prepare('DELETE FROM tecnicos WHERE id IN (' . implode(', ', $placeholders) . ')');
        $stmtDeleteTec->execute($params);
    }

    if (!empty($equipIdsToClean)) {
        $placeholders = [];
        $params = [];
        foreach ($equipIdsToClean as $index => $id) {
            $key = 'eq_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
        }
        $stmtDeleteEq = $conn->prepare('DELETE FROM equipamentos WHERE id IN (' . implode(', ', $placeholders) . ')');
        $stmtDeleteEq->execute($params);
    }

    println('Criando equipamentos de cenario...');
    $equipamentos = [
        'roteador' => ensureEquipamento($equipamentoModel, 'AUTOSCEN Roteador AX', 'roteador', 220, 'AUTOSCEN-ROTEADOR-001'),
        'onu' => ensureEquipamento($equipamentoModel, 'AUTOSCEN ONU ZX', 'onu', 220, 'AUTOSCEN-ONU-001'),
        'ont' => ensureEquipamento($equipamentoModel, 'AUTOSCEN ONT Flex', 'ont', 160, 'AUTOSCEN-ONT-001'),
        'conector_fibra' => ensureEquipamento($equipamentoModel, 'AUTOSCEN Conector Fibra SC', 'conector_fibra', 1200, 'AUTOSCEN-CF-001'),
        'conector_rj' => ensureEquipamento($equipamentoModel, 'AUTOSCEN Conector RJ45 Cat6', 'conector_rj', 950, 'AUTOSCEN-RJ-001'),
        'esticador' => ensureEquipamento($equipamentoModel, 'AUTOSCEN Esticador FTTX', 'insumos', 420, 'AUTOSCEN-EST-001'),
    ];

    println('Criando tecnicos de cenario...');
    $tecnicos = [
        'tec_01' => ensureTecnico($tecnicoModel, 'AUTOSCEN TEC 01 - Joao'),
        'tec_02' => ensureTecnico($tecnicoModel, 'AUTOSCEN TEC 02 - Maria'),
        'tec_03' => ensureTecnico($tecnicoModel, 'AUTOSCEN TEC 03 - Carlos'),
        'tec_04' => ensureTecnico($tecnicoModel, 'AUTOSCEN TEC 04 - Ana'),
    ];

    println('Gerando movimentacoes cobrindo todos os tipos...');
    $movimentacaoModel->create($tecnicos['tec_01'], $equipamentos['roteador'], 20, 'entrega', null, AUTOSCEN_TAG . ' entrega inicial tec01 roteador', movementDate($d10, '08:30:00'));
    $movimentacaoModel->create($tecnicos['tec_01'], $equipamentos['roteador'], 6, 'uso', 'Bairro Centro CTO-01', AUTOSCEN_TAG . ' uso cliente premium', movementDate($d7, '10:15:00'));
    $movimentacaoModel->create($tecnicos['tec_01'], $equipamentos['roteador'], 4, 'uso_teste', 'Bairro Pitanguinha', AUTOSCEN_TAG . ' teste aguardando retorno', movementDate($d4, '09:00:00'));
    $movimentacaoModel->create($tecnicos['tec_01'], $equipamentos['roteador'], 3, 'devolucao', null, AUTOSCEN_TAG . ' devolucao por troca de plano', movementDate($d3, '15:25:00'));
    $movimentacaoModel->create($tecnicos['tec_01'], $equipamentos['roteador'], 2, 'recolhimento', null, AUTOSCEN_TAG . ' recolhimento normal em campo', movementDate($d2, '11:40:00'));
    $movimentacaoModel->create($tecnicos['tec_01'], $equipamentos['roteador'], 1, 'recolhimento_defeito', null, AUTOSCEN_TAG . ' Serial: AX-9911 | Defeito: Sem sinal no PON', movementDate($d1, '16:50:00'));

    $movimentacaoModel->create($tecnicos['tec_02'], $equipamentos['onu'], 18, 'entrega', null, AUTOSCEN_TAG . ' entrega inicial tec02 onu', movementDate($d7, '08:15:00'));
    $movimentacaoModel->create($tecnicos['tec_02'], $equipamentos['onu'], 7, 'uso', 'Bairro Centro Sul', AUTOSCEN_TAG . ' uso em instalacao residencial', movementDate($d4, '13:10:00'));
    $movimentacaoModel->create($tecnicos['tec_02'], $equipamentos['onu'], 3, 'uso_teste', 'Bairro Cia 1', AUTOSCEN_TAG . ' teste em monitoramento', movementDate($d2, '14:35:00'));
    $movimentacaoModel->create($tecnicos['tec_02'], $equipamentos['onu'], 2, 'devolucao', null, AUTOSCEN_TAG . ' devolucao por equipamento substituido', movementDate($d1, '10:00:00'));

    $movimentacaoModel->create($tecnicos['tec_03'], $equipamentos['conector_fibra'], 80, 'entrega', null, AUTOSCEN_TAG . ' entrega de insumos fibra', movementDate($d7, '07:45:00'));
    $movimentacaoModel->create($tecnicos['tec_03'], $equipamentos['conector_fibra'], 32, 'uso', 'Cia Aeroporto', AUTOSCEN_TAG . ' uso massivo em mutirao', movementDate($d3, '17:20:00'));
    $movimentacaoModel->create($tecnicos['tec_03'], $equipamentos['conector_fibra'], 20, 'devolucao', null, AUTOSCEN_TAG . ' devolucao de excedente', movementDate($d1, '08:05:00'));
    $movimentacaoModel->create($tecnicos['tec_03'], $equipamentos['conector_fibra'], 3, 'recolhimento_defeito', null, AUTOSCEN_TAG . ' Serial: SC-5566 | Defeito: Quebra no terminal', movementDate($d0, '09:55:00'));

    $movimentacaoModel->create($tecnicos['tec_04'], $equipamentos['conector_rj'], 60, 'entrega', null, AUTOSCEN_TAG . ' entrega conectores rj', movementDate($d4, '09:05:00'));
    $movimentacaoModel->create($tecnicos['tec_04'], $equipamentos['conector_rj'], 15, 'uso', 'Bairro Goes Calmon', AUTOSCEN_TAG . ' uso em atendimentos comerciais', movementDate($d2, '12:30:00'));
    $movimentacaoModel->create($tecnicos['tec_04'], $equipamentos['esticador'], 25, 'entrega', null, AUTOSCEN_TAG . ' entrega esticadores', movementDate($d4, '09:20:00'));
    $movimentacaoModel->create($tecnicos['tec_04'], $equipamentos['esticador'], 11, 'uso', 'Bairro Vida Nova', AUTOSCEN_TAG . ' uso esticador em posteamento', movementDate($d1, '13:45:00'));
    $movimentacaoModel->create($tecnicos['tec_04'], $equipamentos['esticador'], 4, 'devolucao', null, AUTOSCEN_TAG . ' devolucao esticador apos manutencao', movementDate($d0, '07:30:00'));

    if (hasTable($conn, 'lembretes')) {
        println('Criando lembretes com todos os niveis e status...');
        $stmtInsertLembrete = $conn->prepare(
            'INSERT INTO lembretes
            (lembrete_key, categoria, titulo, mensagem, nivel, tecnico_id, equipamento_id, data_referencia, auto_gerado, status, lido_em, resolvido_em)
            VALUES
            (:lembrete_key, :categoria, :titulo, :mensagem, :nivel, :tecnico_id, :equipamento_id, :data_referencia, 0, :status, :lido_em, :resolvido_em)'
        );

        $lembretes = [
            [
                'key' => 'autoscen_open_warning',
                'categoria' => 'operacional',
                'titulo' => AUTOSCEN_TAG . ' Verificar pendencia de recolhimento',
                'mensagem' => 'Cliente com equipamento em teste ha mais de 3 dias.',
                'nivel' => 'warning',
                'tecnico_id' => $tecnicos['tec_01'],
                'equipamento_id' => $equipamentos['roteador'],
                'data' => $d0,
                'status' => 'aberto',
                'lido_em' => null,
                'resolvido_em' => null,
            ],
            [
                'key' => 'autoscen_read_info',
                'categoria' => 'estoque',
                'titulo' => AUTOSCEN_TAG . ' Conferir saldo de conectores',
                'mensagem' => 'Revisar saldo de conectores apos mutirao do fim de semana.',
                'nivel' => 'info',
                'tecnico_id' => $tecnicos['tec_03'],
                'equipamento_id' => $equipamentos['conector_fibra'],
                'data' => $d1,
                'status' => 'lido',
                'lido_em' => movementDate($d0, '10:00:00'),
                'resolvido_em' => null,
            ],
            [
                'key' => 'autoscen_resolved_success',
                'categoria' => 'cliente',
                'titulo' => AUTOSCEN_TAG . ' Cliente regularizado',
                'mensagem' => 'Equipamento devolvido e pendencia encerrada.',
                'nivel' => 'success',
                'tecnico_id' => $tecnicos['tec_02'],
                'equipamento_id' => $equipamentos['onu'],
                'data' => $d2,
                'status' => 'resolvido',
                'lido_em' => movementDate($d1, '11:10:00'),
                'resolvido_em' => movementDate($d0, '08:20:00'),
            ],
            [
                'key' => 'autoscen_open_danger',
                'categoria' => 'defeito',
                'titulo' => AUTOSCEN_TAG . ' Equipamento com defeito recorrente',
                'mensagem' => 'Avaliar lote com aumento de falhas em campo.',
                'nivel' => 'danger',
                'tecnico_id' => $tecnicos['tec_04'],
                'equipamento_id' => $equipamentos['conector_rj'],
                'data' => $d0,
                'status' => 'aberto',
                'lido_em' => null,
                'resolvido_em' => null,
            ],
        ];

        foreach ($lembretes as $item) {
            $stmtInsertLembrete->execute([
                'lembrete_key' => $item['key'],
                'categoria' => $item['categoria'],
                'titulo' => $item['titulo'],
                'mensagem' => $item['mensagem'],
                'nivel' => $item['nivel'],
                'tecnico_id' => $item['tecnico_id'],
                'equipamento_id' => $item['equipamento_id'],
                'data_referencia' => $item['data'],
                'status' => $item['status'],
                'lido_em' => $item['lido_em'],
                'resolvido_em' => $item['resolvido_em'],
            ]);
        }
    }

    if (hasTable($conn, 'inadimplencia_recolhimentos')) {
        println('Criando inadimplencia com todos os status...');
        $stmtInsertInad = $conn->prepare(
            'INSERT INTO inadimplencia_recolhimentos
            (titular, equipamento, contato, endereco, prazo, status, tentativa_1, observacoes, origem_arquivo, last_import_at)
            VALUES
            (:titular, :equipamento, :contato, :endereco, :prazo, :status, :tentativa_1, :observacoes, :origem_arquivo, NOW())'
        );

        $inadimplencias = [
            [AUTOSCEN_TAG . ' Cliente A', 'AUTOSCEN ONU ZX', '71911110001', 'Rua Um, Bairro Centro, Simoes Filho - BA', $today->modify('+2 days')->format('Y-m-d'), 'AGUARDANDO', 'Primeiro contato pendente', 'Rota 1'],
            [AUTOSCEN_TAG . ' Cliente B', 'AUTOSCEN Roteador AX', '71911110002', 'Avenida Dois, Bairro Cia, Simoes Filho - BA', $today->modify('+1 day')->format('Y-m-d'), 'AGENDADO', 'Visita marcada para amanha', 'Rota 2'],
            [AUTOSCEN_TAG . ' Cliente C', 'AUTOSCEN Conector RJ45 Cat6', '71911110003', 'Rua Tres, Bairro Vida Nova, Simoes Filho - BA', $today->modify('-1 day')->format('Y-m-d'), 'EM CONTATO', 'Cliente pediu retorno no periodo da tarde', 'Rota 3'],
            [AUTOSCEN_TAG . ' Cliente D', 'AUTOSCEN ONT Flex', '71911110004', 'Rua Quatro, Bairro Goes Calmon, Simoes Filho - BA', $today->modify('-3 days')->format('Y-m-d'), 'SEM CONTATO', 'Sem sucesso em 3 ligacoes', 'Sem retorno'],
            [AUTOSCEN_TAG . ' Cliente E', 'AUTOSCEN Esticador FTTX', '71911110005', 'Rua Cinco, Bairro Centro, Salvador - BA', $today->modify('+5 days')->format('Y-m-d'), 'NAO RECOLHER', 'Cliente sem equipamento no local', 'Fora de rota alvo'],
            [AUTOSCEN_TAG . ' Cliente F', 'AUTOSCEN ONU ZX', '71911110006', 'Rua Seis, Bairro Cia 2, Simoes Filho - BA', $today->modify('-2 days')->format('Y-m-d'), 'RECOLHIDO', 'Equipamento devolvido em loja', 'Concluido'],
        ];

        foreach ($inadimplencias as $item) {
            $stmtInsertInad->execute([
                'titular' => $item[0],
                'equipamento' => $item[1],
                'contato' => $item[2],
                'endereco' => $item[3],
                'prazo' => $item[4],
                'status' => $item[5],
                'tentativa_1' => $item[6],
                'observacoes' => $item[7],
                'origem_arquivo' => AUTOSCEN_SOURCE,
            ]);
        }
    }

    if (hasTable($conn, 'apoio_compra_config')) {
        println('Atualizando configuracao de apoio a compra...');
        $stmtConfig = $conn->prepare(
            'INSERT INTO apoio_compra_config
            (id, consumo_roteador_dia, consumo_onu_dia, consumo_conector_dia, prazo_reposicao_dias, updated_at)
            VALUES (1, :roteador, :onu, :conector, :prazo, NOW())
            ON DUPLICATE KEY UPDATE
                consumo_roteador_dia = VALUES(consumo_roteador_dia),
                consumo_onu_dia = VALUES(consumo_onu_dia),
                consumo_conector_dia = VALUES(consumo_conector_dia),
                prazo_reposicao_dias = VALUES(prazo_reposicao_dias),
                updated_at = NOW()'
        );

        $stmtConfig->execute([
            'roteador' => 9,
            'onu' => 8,
            'conector' => 25,
            'prazo' => 4,
        ]);
    }

    if (hasTable($conn, 'apoio_compra_item_config')) {
        $stmtItem = $conn->prepare(
            'INSERT INTO apoio_compra_item_config (equipamento_id, consumo_dia, updated_at)
             VALUES (:equipamento_id, :consumo_dia, NOW())
             ON DUPLICATE KEY UPDATE
                consumo_dia = VALUES(consumo_dia),
                updated_at = NOW()'
        );

        $consumoPorItem = [
            $equipamentos['roteador'] => 9,
            $equipamentos['onu'] => 8,
            $equipamentos['ont'] => 3,
            $equipamentos['conector_fibra'] => 25,
            $equipamentos['conector_rj'] => 18,
            $equipamentos['esticador'] => 10,
        ];

        foreach ($consumoPorItem as $equipamentoId => $consumoDia) {
            $stmtItem->execute([
                'equipamento_id' => $equipamentoId,
                'consumo_dia' => $consumoDia,
            ]);
        }
    }

    println('Populacao concluida com sucesso.');
    println('Tecnicos criados: ' . count($tecnicos));
    println('Equipamentos criados: ' . count($equipamentos));
    println('Movimentacoes geradas com cenarios de entrega, uso, uso_teste, devolucao, recolhimento e recolhimento_defeito.');
    println('Lembretes, inadimplencia e apoio a compra atualizados (quando tabelas existem).');
    exit(0);
} catch (Throwable $e) {
    println('Erro na populacao: ' . $e->getMessage());
    exit(1);
}

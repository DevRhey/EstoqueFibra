<?php
require_once __DIR__ . '/../config/database.php';

class Movimentacao
{
    private PDO $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create(int $tecnicoId, int $equipamentoId, int $quantidade, string $tipo, ?string $localUso = null, ?string $observacoes = null): bool
    {
        $this->conn->beginTransaction();

        try {
            $this->createInternal($tecnicoId, $equipamentoId, $quantidade, $tipo, $localUso, $observacoes);
            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function createBatch(int $tecnicoId, string $tipo, array $itens): int
    {
        if (empty($itens)) {
            throw new InvalidArgumentException('Nenhum item informado para o lote.');
        }

        $this->conn->beginTransaction();

        try {
            $processados = 0;

            foreach ($itens as $index => $item) {
                if (!is_array($item)) {
                    throw new RuntimeException('Item de lote invalido na posicao ' . ($index + 1) . '.');
                }

                $equipamentoId = (int) ($item['equipamento_id'] ?? 0);
                $quantidade = (int) ($item['quantidade'] ?? 0);
                $localUso = isset($item['local_uso']) ? trim((string) $item['local_uso']) : '';
                $observacoes = isset($item['observacoes']) ? trim((string) $item['observacoes']) : '';

                if ($equipamentoId <= 0 || $quantidade <= 0) {
                    throw new RuntimeException('Item de lote invalido na posicao ' . ($index + 1) . '.');
                }

                $this->createInternal(
                    $tecnicoId,
                    $equipamentoId,
                    $quantidade,
                    $tipo,
                    $localUso !== '' ? $localUso : null,
                    $observacoes !== '' ? $observacoes : null
                );

                $processados++;
            }

            $this->conn->commit();
            return $processados;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    private function createInternal(int $tecnicoId, int $equipamentoId, int $quantidade, string $tipo, ?string $localUso = null, ?string $observacoes = null): void
    {
        $tipo = $this->normalizeMovementType($tipo);

        if (!in_array($tipo, ['entrega', 'uso', 'uso_teste', 'devolucao', 'recolhimento'], true)) {
            throw new InvalidArgumentException('Tipo de movimentacao invalido.');
        }

        if (($tipo === 'uso' || $tipo === 'uso_teste') && ($localUso === null || trim($localUso) === '')) {
            throw new InvalidArgumentException('Informe o local de uso para movimentacoes do tipo uso.');
        }

        $selectCodigoBarras = $this->hasEquipamentoColumn('codigo_barras') ? ', codigo_barras' : '';
        $stmtEquip = $this->conn->prepare('SELECT quantidade, nome, tipo' . $selectCodigoBarras . ' FROM equipamentos WHERE id = :id FOR UPDATE');
        $stmtEquip->execute(['id' => $equipamentoId]);
        $equipamento = $stmtEquip->fetch();

        if (!$equipamento) {
            throw new RuntimeException('Equipamento nao encontrado.');
        }

        $estoqueAtual = (int) $equipamento['quantidade'];
        $deltaEstoque = $this->calculateStockDelta($tipo, $quantidade);

        if (($estoqueAtual + $deltaEstoque) < 0) {
            throw new RuntimeException('Saida maior que o estoque disponivel.');
        }

        if ($tipo === 'uso' || $tipo === 'uso_teste' || $tipo === 'devolucao') {
            $saldoNaMao = $this->getSaldoNaMao($tecnicoId, $equipamentoId);

            if ($saldoNaMao < $quantidade) {
                throw new RuntimeException('O equipamento selecionado nao esta em mao deste tecnico ou a quantidade e maior que o saldo disponivel.');
            }
        }

        // Construir INSERT dinamicamente baseado na existência das colunas opcionais
        $columns = ['tecnico_id', 'equipamento_id', 'quantidade', 'tipo', 'data_movimentacao'];
        $placeholders = [':tecnico_id', ':equipamento_id', ':quantidade', ':tipo', ':data_movimentacao'];
        $bindParams = [
            ':tecnico_id' => $tecnicoId,
            ':equipamento_id' => $equipamentoId,
            ':quantidade' => $quantidade,
            ':tipo' => $tipo,
            ':data_movimentacao' => date('Y-m-d H:i:s'),
        ];

        if ($this->hasColumn('local_uso')) {
            $columns[] = 'local_uso';
            $placeholders[] = ':local_uso';
            $bindParams[':local_uso'] = ($tipo === 'uso' || $tipo === 'uso_teste') ? $localUso : null;
        }

        if ($this->hasColumn('observacoes')) {
            $columns[] = 'observacoes';
            $placeholders[] = ':observacoes';
            $bindParams[':observacoes'] = $observacoes !== null && trim($observacoes) !== '' ? $observacoes : null;
        }

        if ($this->hasColumn('equipamento_nome_snapshot')) {
            $columns[] = 'equipamento_nome_snapshot';
            $placeholders[] = ':equipamento_nome_snapshot';
            $bindParams[':equipamento_nome_snapshot'] = (string) ($equipamento['nome'] ?? '');
        }

        if ($this->hasColumn('equipamento_tipo_snapshot')) {
            $columns[] = 'equipamento_tipo_snapshot';
            $placeholders[] = ':equipamento_tipo_snapshot';
            $bindParams[':equipamento_tipo_snapshot'] = (string) ($equipamento['tipo'] ?? '');
        }

        if ($this->hasColumn('equipamento_codigo_barras_snapshot')) {
            $columns[] = 'equipamento_codigo_barras_snapshot';
            $placeholders[] = ':equipamento_codigo_barras_snapshot';
            $bindParams[':equipamento_codigo_barras_snapshot'] = isset($equipamento['codigo_barras'])
                ? (string) ($equipamento['codigo_barras'] ?? '')
                : null;
        }

        $sqlInsert = 'INSERT INTO movimentacoes (' . implode(', ', $columns) . ')
                     VALUES (' . implode(', ', $placeholders) . ')';

        $stmtMov = $this->conn->prepare($sqlInsert);
        $stmtMov->execute($bindParams);

        $stmtUpdate = $this->conn->prepare(
            'UPDATE equipamentos SET quantidade = quantidade + :delta WHERE id = :id'
        );
        $stmtUpdate->execute([
            'delta' => $deltaEstoque,
            'id' => $equipamentoId,
        ]);
    }

    private function hasColumn(string $columnName): bool
    {
        try {
            $this->conn->query('SELECT m.' . $columnName . ' FROM movimentacoes m LIMIT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    private function hasEquipamentoColumn(string $columnName): bool
    {
        try {
            $this->conn->query('SELECT e.' . $columnName . ' FROM equipamentos e LIMIT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    private function normalizeMovementType(string $tipo): string
    {
        $tipo = strtolower(trim($tipo));

        return match ($tipo) {
            'saida' => 'entrega',
            'entrada' => 'devolucao',
            default => $tipo,
        };
    }

    private function getSaldoNaMao(int $tecnicoId, int $equipamentoId): int
    {
        $stmt = $this->conn->prepare(
            "SELECT COALESCE(SUM(CASE
                    WHEN tipo IN ('entrega', 'saida') THEN quantidade
                    WHEN tipo IN ('uso', 'uso_teste', 'devolucao', 'entrada') THEN -quantidade
                    WHEN tipo = 'recolhimento' THEN 0
                    ELSE 0
                END), 0) AS saldo_mao
             FROM movimentacoes
             WHERE tecnico_id = :tecnico_id AND equipamento_id = :equipamento_id"
        );
        $stmt->execute([
            'tecnico_id' => $tecnicoId,
            'equipamento_id' => $equipamentoId,
        ]);

        return (int) ($stmt->fetch()['saldo_mao'] ?? 0);
    }

    private function calculateStockDelta(string $tipo, int $quantidade): int
    {
        $tipo = $this->normalizeMovementType($tipo);

        if ($tipo === 'entrega') {
            return -$quantidade;
        }

        if ($tipo === 'recolhimento') {
            return $quantidade;
        }

        if ($tipo === 'uso' || $tipo === 'uso_teste') {
            return 0;
        }

        if ($tipo === 'devolucao') {
            return $quantidade;
        }

        return 0;
    }

    public function allWithRelations(?string $selectedDate = null): array
    {
        $selectedDate = $this->normalizeDate($selectedDate);
        $selectLocal = $this->hasColumn('local_uso') ? 'm.local_uso,' : "'' AS local_uso,";
        $selectObservacoes = $this->hasColumn('observacoes') ? 'm.observacoes,' : "'' AS observacoes,";
        $equipamentoNomeSelect = $this->hasColumn('equipamento_nome_snapshot')
            ? "COALESCE(e.nome, m.equipamento_nome_snapshot, 'Equipamento removido') AS equipamento_nome,"
            : "COALESCE(e.nome, 'Equipamento removido') AS equipamento_nome,";
        $equipamentoTipoSelect = $this->hasColumn('equipamento_tipo_snapshot')
            ? "COALESCE(e.tipo, m.equipamento_tipo_snapshot, 'indefinido') AS equipamento_tipo"
            : "COALESCE(e.tipo, 'indefinido') AS equipamento_tipo";

        $where = '';
        $params = [];
        if ($selectedDate !== null) {
            $where = 'WHERE DATE(m.data_movimentacao) = :selected_date';
            $params['selected_date'] = $selectedDate;
        }

        $sql = "SELECT m.id,
                       m.tecnico_id,
                       m.quantidade,
                       CASE
                           WHEN m.tipo = 'saida' THEN 'entrega'
                           WHEN m.tipo = 'entrada' THEN 'devolucao'
                           ELSE m.tipo
                       END AS tipo,
                       {$selectLocal}
                   {$selectObservacoes}
                       m.data_movimentacao,
                       COALESCE(t.nome, 'Tecnico removido') AS tecnico_nome,
                      {$equipamentoNomeSelect}
                      {$equipamentoTipoSelect}
                FROM movimentacoes m
                LEFT JOIN tecnicos t ON t.id = m.tecnico_id
                  LEFT JOIN equipamentos e ON e.id = m.equipamento_id
                {$where}
                ORDER BY m.data_movimentacao DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function reportConsumoPorTecnico(): array
    {
        $sql = "SELECT t.nome AS tecnico,
                       SUM(CASE WHEN m.tipo IN ('uso', 'uso_teste') THEN m.quantidade ELSE 0 END) AS total_consumido
                FROM tecnicos t
                LEFT JOIN movimentacoes m ON m.tecnico_id = t.id
                GROUP BY t.id, t.nome
                ORDER BY total_consumido DESC, t.nome ASC";

        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }

    public function reportEquipamentosMaisUsados(): array
    {
         $sql = "SELECT e.nome,
                  e.tipo,
                                    SUM(CASE WHEN m.tipo IN ('uso', 'uso_teste') THEN m.quantidade ELSE 0 END) AS total_uso,
                  SUM(CASE WHEN m.tipo IN ('entrega', 'saida') THEN m.quantidade ELSE 0 END) AS total_entrega
                FROM equipamentos e
                LEFT JOIN movimentacoes m ON m.equipamento_id = e.id
                GROUP BY e.id, e.nome, e.tipo
              ORDER BY total_uso DESC, total_entrega DESC, e.nome ASC";

        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }

    public function reportEstoqueAtual(): array
    {
        $sql = 'SELECT id, nome, tipo, quantidade FROM equipamentos ORDER BY nome ASC';
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }

    public function reportReposicaoDiaria(): array
    {
        $sql = "SELECT e.nome,
                       e.tipo,
                       e.quantidade AS estoque_atual,
                       ROUND(AVG(d.consumo_diario), 2) AS media_consumo_dia,
                       CASE
                           WHEN ROUND(AVG(d.consumo_diario), 2) > 0
                           THEN CEIL(ROUND(AVG(d.consumo_diario), 2) * 7)
                           ELSE 0
                       END AS sugestao_reposicao_7_dias
                FROM equipamentos e
                LEFT JOIN (
                    SELECT equipamento_id,
                           DATE(data_movimentacao) AS dia,
                              SUM(CASE WHEN tipo IN ('uso', 'uso_teste') THEN quantidade ELSE 0 END) AS consumo_diario
                    FROM movimentacoes
                    GROUP BY equipamento_id, DATE(data_movimentacao)
                ) d ON d.equipamento_id = e.id
                GROUP BY e.id, e.nome, e.tipo, e.quantidade
                ORDER BY sugestao_reposicao_7_dias DESC, e.nome ASC";

        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }

    public function reportCardsTecnicos(?string $selectedDate = null): array
    {
        $selectedDate = $this->normalizeDate($selectedDate);
        $isFilteredByDate = $selectedDate !== null;
        $dailyDate = $isFilteredByDate ? $selectedDate : date('Y-m-d');
        $joinDateFilter = $isFilteredByDate ? ' AND DATE(m.data_movimentacao) = :selected_date' : '';

        $tecnicoSql =
            "SELECT t.id,
                    t.nome,
                    SUM(CASE WHEN m.tipo IN ('entrega', 'saida') THEN m.quantidade ELSE 0 END) AS total_entrega,
                    SUM(CASE WHEN m.tipo IN ('uso', 'uso_teste') THEN m.quantidade ELSE 0 END) AS total_uso,
                    SUM(CASE WHEN m.tipo = 'uso_teste' THEN m.quantidade ELSE 0 END) AS total_uso_teste,
                    SUM(CASE WHEN m.tipo IN ('devolucao', 'entrada') THEN m.quantidade ELSE 0 END) AS total_devolvido,
                    SUM(CASE WHEN m.tipo = 'recolhimento' THEN m.quantidade ELSE 0 END) AS total_recolhido
             FROM tecnicos t
               LEFT JOIN movimentacoes m ON m.tecnico_id = t.id{$joinDateFilter}
             GROUP BY t.id, t.nome
             ORDER BY t.nome ASC";

        $tecnicoStmt = $this->conn->prepare($tecnicoSql);
           $tecnicoStmt->execute($isFilteredByDate ? ['selected_date' => $dailyDate] : []);
        $tecnicos = $tecnicoStmt->fetchAll();

        $cards = [];
        foreach ($tecnicos as $tec) {
            $cards[(int) $tec['id']] = [
                'tecnico_id' => (int) $tec['id'],
                'tecnico_nome' => $tec['nome'],
                'total_entrega' => (int) $tec['total_entrega'],
                'total_uso' => (int) $tec['total_uso'],
                'total_uso_teste' => (int) ($tec['total_uso_teste'] ?? 0),
                'total_devolvido' => (int) $tec['total_devolvido'],
                'total_recolhido' => (int) ($tec['total_recolhido'] ?? 0),
                'saldo_total_mao' => 0,
                'equipamentos_mao' => [],
                'usos_recentes' => [],
                'estoque_seguro' => $this->getSafeStockTargets(),
                'saldo_por_categoria' => [
                    'roteador' => 0,
                    'onu' => 0,
                    'conector_fibra' => 0,
                ],
                'reposicao_proximo_dia' => [],
                'repor_total' => 0,
                'estoque_seguro_ok' => true,
            ];
        }

        $selectEquipamentoNomeSaldo = $this->hasColumn('equipamento_nome_snapshot')
            ? "MAX(COALESCE(e.nome, m.equipamento_nome_snapshot, 'Equipamento removido')) AS equipamento_nome,"
            : "MAX(COALESCE(e.nome, 'Equipamento removido')) AS equipamento_nome,";
        $selectEquipamentoTipoSaldo = $this->hasColumn('equipamento_tipo_snapshot')
            ? "MAX(COALESCE(e.tipo, m.equipamento_tipo_snapshot, 'indefinido')) AS equipamento_tipo,"
            : "MAX(COALESCE(e.tipo, 'indefinido')) AS equipamento_tipo,";
        $selectCodigoBarrasSaldo = $this->hasColumn('equipamento_codigo_barras_snapshot')
            ? "MAX(COALESCE(e.codigo_barras, m.equipamento_codigo_barras_snapshot)) AS codigo_barras,"
            : ($this->hasEquipamentoColumn('codigo_barras')
                ? 'MAX(e.codigo_barras) AS codigo_barras,'
                : 'NULL AS codigo_barras,');

        $saldoSql = "SELECT m.tecnico_id,
                            m.equipamento_id,
                            {$selectEquipamentoNomeSaldo}
                            {$selectEquipamentoTipoSaldo}
                            {$selectCodigoBarrasSaldo}
                            SUM(CASE
                                    WHEN m.tipo IN ('entrega', 'saida') THEN m.quantidade
                                    WHEN m.tipo IN ('uso', 'uso_teste', 'devolucao', 'entrada') THEN -m.quantidade
                                    WHEN m.tipo = 'recolhimento' THEN 0
                                    ELSE 0
                                END) AS saldo_mao
                     FROM movimentacoes m
                     LEFT JOIN equipamentos e ON e.id = m.equipamento_id
                             " . ($isFilteredByDate ? "WHERE DATE(m.data_movimentacao) <= :selected_date" : '') . "
                     GROUP BY m.tecnico_id, m.equipamento_id";

        $saldoStmt = $this->conn->prepare($saldoSql);
                $saldoStmt->execute($isFilteredByDate ? ['selected_date' => $dailyDate] : []);
        $saldos = $saldoStmt->fetchAll();

        $saldoMap = [];
        $equipInfoMap = [];
        foreach ($saldos as $row) {
            $tecnicoId = (int) $row['tecnico_id'];
            if (!isset($cards[$tecnicoId])) {
                continue;
            }

            $equipamentoId = (int) $row['equipamento_id'];
            $saldo = max(0, (int) $row['saldo_mao']);
            $saldoMap[$tecnicoId][$equipamentoId] = $saldo;
            $equipInfoMap[$tecnicoId][$equipamentoId] = [
                'nome' => (string) ($row['equipamento_nome'] ?? 'Equipamento removido'),
                'tipo' => (string) ($row['equipamento_tipo'] ?? 'indefinido'),
                'codigo_barras' => $row['codigo_barras'] ?? null,
            ];

            if ($saldo > 0) {
                $cards[$tecnicoId]['equipamentos_mao'][] = [
                    'equipamento_id' => $equipamentoId,
                    'nome' => (string) ($row['equipamento_nome'] ?? 'Equipamento removido'),
                    'tipo' => (string) ($row['equipamento_tipo'] ?? 'indefinido'),
                    'codigo_barras' => $row['codigo_barras'] ?? null,
                    'saldo_mao' => $saldo,
                ];
                $cards[$tecnicoId]['saldo_total_mao'] += $saldo;
            }
        }

        foreach ($cards as $tecnicoId => $card) {
            $safeSummary = $this->calculateSafeStockSummary($card['equipamentos_mao']);
            $cards[$tecnicoId]['saldo_por_categoria'] = $safeSummary['saldo_por_categoria'];
            $cards[$tecnicoId]['reposicao_proximo_dia'] = $safeSummary['repor'];
            $cards[$tecnicoId]['repor_total'] = $safeSummary['repor_total'];
            $cards[$tecnicoId]['estoque_seguro_ok'] = $safeSummary['repor_total'] === 0;
        }

        $usoHojeStmt = $this->conn->prepare(
            "SELECT tecnico_id,
                    equipamento_id,
                    SUM(quantidade) AS uso_hoje
             FROM movimentacoes
             WHERE tipo IN ('uso', 'uso_teste') AND DATE(data_movimentacao) = :usage_date
             GROUP BY tecnico_id, equipamento_id"
        );

        $usoHojeStmt->execute(['usage_date' => $dailyDate]);

        foreach ($usoHojeStmt->fetchAll() as $usoRow) {
            $tecnicoId = (int) $usoRow['tecnico_id'];
            $equipamentoId = (int) $usoRow['equipamento_id'];
            $usoHoje = (int) $usoRow['uso_hoje'];

            if (!isset($cards[$tecnicoId])) {
                continue;
            }

            $saldoAtual = $saldoMap[$tecnicoId][$equipamentoId] ?? 0;

            if ($usoHoje > 0) {
                $eq = $equipInfoMap[$tecnicoId][$equipamentoId] ?? null;

                if ($eq) {
                    $cards[$tecnicoId]['previsao_reposicao_amanha'][] = [
                        'equipamento_id' => $equipamentoId,
                        'nome' => (string) ($eq['nome'] ?? 'Equipamento removido'),
                        'tipo' => (string) ($eq['tipo'] ?? 'indefinido'),
                        'uso_hoje' => $usoHoje,
                        'saldo_mao' => $saldoAtual,
                        'sugestao' => max(0, $usoHoje - $saldoAtual),
                    ];
                }
            }
        }

        $selectLocal = $this->hasColumn('local_uso') ? 'm.local_uso,' : "'' AS local_uso,";
        $selectObservacoes = $this->hasColumn('observacoes') ? 'm.observacoes,' : "'' AS observacoes,";
        $selectEquipamentoNomeUso = $this->hasColumn('equipamento_nome_snapshot')
            ? "COALESCE(e.nome, m.equipamento_nome_snapshot, 'Equipamento removido') AS equipamento_nome,"
            : "COALESCE(e.nome, 'Equipamento removido') AS equipamento_nome,";
        $selectEquipamentoTipoUso = $this->hasColumn('equipamento_tipo_snapshot')
            ? "COALESCE(e.tipo, m.equipamento_tipo_snapshot, 'indefinido') AS equipamento_tipo,"
            : "COALESCE(e.tipo, 'indefinido') AS equipamento_tipo,";
        $usosWhereDate = $isFilteredByDate ? ' AND DATE(m.data_movimentacao) = :selected_date' : '';
        $usosRecentesStmt = $this->conn->prepare(
            "SELECT m.tecnico_id,
                    {$selectEquipamentoNomeUso}
                    {$selectEquipamentoTipoUso}
                    m.quantidade,
                    $selectLocal
                $selectObservacoes
                    m.data_movimentacao
             FROM movimentacoes m
             LEFT JOIN equipamentos e ON e.id = m.equipamento_id
                   WHERE m.tipo IN ('uso', 'uso_teste'){$usosWhereDate}
             ORDER BY m.data_movimentacao DESC"
        );

          $usosRecentesStmt->execute($isFilteredByDate ? ['selected_date' => $dailyDate] : []);

        $limitePorTecnico = [];
        foreach ($usosRecentesStmt->fetchAll() as $uso) {
            $tecnicoId = (int) $uso['tecnico_id'];
            if (!isset($cards[$tecnicoId])) {
                continue;
            }

            $limitePorTecnico[$tecnicoId] = $limitePorTecnico[$tecnicoId] ?? 0;
            if ($limitePorTecnico[$tecnicoId] >= 5) {
                continue;
            }

            $cards[$tecnicoId]['usos_recentes'][] = [
                'equipamento_nome' => $uso['equipamento_nome'],
                'equipamento_tipo' => $uso['equipamento_tipo'],
                'quantidade' => (int) $uso['quantidade'],
                'local_uso' => $uso['local_uso'],
                'observacoes' => $uso['observacoes'],
                'data_movimentacao' => $uso['data_movimentacao'],
            ];

            $limitePorTecnico[$tecnicoId]++;
        }

        return array_values($cards);
    }

    public function reportAlertasUsoTeste(): array
    {
        $selectLocal = $this->hasColumn('local_uso') ? 'm.local_uso,' : "'' AS local_uso,";
        $selectObservacoes = $this->hasColumn('observacoes') ? 'm.observacoes,' : "'' AS observacoes,";
                $selectEquipamentoNome = $this->hasColumn('equipamento_nome_snapshot')
                        ? "COALESCE(e.nome, m.equipamento_nome_snapshot, 'Equipamento removido') AS equipamento_nome,"
                        : "COALESCE(e.nome, 'Equipamento removido') AS equipamento_nome,";

        $sql = "SELECT m.id,
                       m.tecnico_id,
                  COALESCE(t.nome, 'Tecnico removido') AS tecnico_nome,
                       m.equipamento_id,
                                             {$selectEquipamentoNome}
                       m.quantidade,
                       {$selectLocal}
                       {$selectObservacoes}
                       m.data_movimentacao
                FROM movimentacoes m
              LEFT JOIN tecnicos t ON t.id = m.tecnico_id
                                LEFT JOIN equipamentos e ON e.id = m.equipamento_id
                WHERE m.tipo = 'uso_teste'
                ORDER BY m.data_movimentacao DESC";

        $tests = $this->conn->query($sql)->fetchAll();
        $alertas = [];
        $now = new DateTimeImmutable('now');

        foreach ($tests as $test) {
            $stmtResolucao = $this->conn->prepare(
                "SELECT id
                 FROM movimentacoes
                 WHERE tecnico_id = :tecnico_id
                   AND equipamento_id = :equipamento_id
                   AND data_movimentacao > :data_movimentacao
                   AND tipo IN ('uso', 'uso_teste', 'devolucao', 'recolhimento')
                 ORDER BY data_movimentacao DESC
                 LIMIT 1"
            );
            $stmtResolucao->execute([
                'tecnico_id' => (int) $test['tecnico_id'],
                'equipamento_id' => (int) $test['equipamento_id'],
                'data_movimentacao' => $test['data_movimentacao'],
            ]);

            if ($stmtResolucao->fetch()) {
                continue;
            }

            $inicioTeste = new DateTimeImmutable($test['data_movimentacao']);
            $vencimento = $this->calculateTesteDueDate($inicioTeste);
            $diasRestantes = (int) $now->diff($vencimento)->format('%r%a');

            $alertas[] = [
                'movimentacao_id' => (int) $test['id'],
                'tecnico_id' => (int) $test['tecnico_id'],
                'tecnico_nome' => $test['tecnico_nome'],
                'equipamento_id' => (int) $test['equipamento_id'],
                'equipamento_nome' => $test['equipamento_nome'],
                'quantidade' => (int) $test['quantidade'],
                'local_uso' => $test['local_uso'],
                'observacoes' => $test['observacoes'],
                'inicio_teste' => $inicioTeste->format('Y-m-d H:i:s'),
                'vencimento_teste' => $vencimento->format('Y-m-d H:i:s'),
                'dias_restantes' => $diasRestantes,
                'status' => $diasRestantes < 0 ? 'vencido' : 'no_prazo',
            ];
        }

        usort($alertas, static function (array $a, array $b): int {
            return strcmp($a['vencimento_teste'], $b['vencimento_teste']);
        });

        return $alertas;
    }

    private function calculateTesteDueDate(DateTimeImmutable $inicioTeste): DateTimeImmutable
    {
        $vencimento = $inicioTeste->modify('+3 days');

        // Domingo nao conta como dia de vencimento: empurra para segunda.
        if ($vencimento->format('w') === '0') {
            $vencimento = $vencimento->modify('+1 day');
        }

        return $vencimento;
    }

    private function getSafeStockTargets(): array
    {
        return [
            'roteador' => 3,
            'onu' => 2,
            'conector_fibra' => 10,
        ];
    }

    private function normalizeEquipmentType(string $tipo): string
    {
        $tipo = strtolower(trim($tipo));

        return match ($tipo) {
            'router', 'roteador' => 'roteador',
            'onu' => 'onu',
            'ont' => 'ont',
            'conector', 'conector_fibra', 'fibra', 'conector de fibra' => 'conector_fibra',
            default => $tipo,
        };
    }

    private function calculateSafeStockSummary(array $equipamentosMao): array
    {
        $targets = $this->getSafeStockTargets();
        $saldoPorCategoria = [
            'roteador' => 0,
            'onu' => 0,
            'conector_fibra' => 0,
        ];

        foreach ($equipamentosMao as $item) {
            $tipo = $this->normalizeEquipmentType((string) ($item['tipo'] ?? ''));
            $saldo = max(0, (int) ($item['saldo_mao'] ?? 0));

            if ($saldo <= 0) {
                continue;
            }

            if ($tipo === 'roteador') {
                $saldoPorCategoria['roteador'] += $saldo;
                continue;
            }

            if ($tipo === 'onu') {
                $saldoPorCategoria['onu'] += $saldo;
                continue;
            }

            if ($tipo === 'ont') {
                $saldoPorCategoria['roteador'] += $saldo;
                $saldoPorCategoria['onu'] += $saldo;
                continue;
            }

            if ($tipo === 'conector_fibra') {
                $saldoPorCategoria['conector_fibra'] += $saldo;
            }
        }

        $repor = [];
        $reporTotal = 0;
        foreach ($targets as $categoria => $necessario) {
            $atual = $saldoPorCategoria[$categoria] ?? 0;
            $faltante = max(0, $necessario - $atual);

            $repor[] = [
                'categoria' => $categoria,
                'necessario' => $necessario,
                'atual' => $atual,
                'faltante' => $faltante,
            ];

            $reporTotal += $faltante;
        }

        return [
            'saldo_por_categoria' => $saldoPorCategoria,
            'repor' => $repor,
            'repor_total' => $reporTotal,
        ];
    }

    private function normalizeDate(?string $date): ?string
    {
        if ($date === null) {
            return null;
        }

        $date = trim($date);
        if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $date));
        if (!checkdate($month, $day, $year)) {
            return null;
        }

        return $date;
    }

    private function calculateDelta(string $tipo, int $quantidade): int
    {
        return $this->calculateStockDelta($tipo, $quantidade);
    }
}

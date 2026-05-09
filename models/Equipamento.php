<?php
require_once __DIR__ . '/../config/database.php';

class Equipamento
{
    private PDO $conn;
    private ?bool $hasCodigoBarrasColumn = null;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function all(): array
    {
        $stmt = $this->conn->query(
            "SELECT *
             FROM equipamentos
             ORDER BY
                CASE tipo
                    WHEN 'roteador' THEN 1
                    WHEN 'onu' THEN 2
                    WHEN 'ont' THEN 3
                    WHEN 'conector_rj' THEN 4
                    WHEN 'conector_fibra' THEN 5
                    WHEN 'insumos' THEN 6
                    ELSE 99
                END,
                nome ASC"
        );
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->conn->prepare('SELECT * FROM equipamentos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();
        return $data ?: null;
    }

    public function create(string $nome, string $tipo, int $quantidade, ?string $codigoBarras = null): bool
    {
        $codigoBarras = $codigoBarras !== null ? trim($codigoBarras) : null;
        if ($codigoBarras === '') {
            $codigoBarras = null;
        }

        if ($this->supportsCodigoBarras()) {
            $stmt = $this->conn->prepare(
                'INSERT INTO equipamentos (nome, tipo, quantidade, codigo_barras)
                 VALUES (:nome, :tipo, :quantidade, :codigo_barras)'
            );

            return $stmt->execute([
                'nome' => $nome,
                'tipo' => $tipo,
                'quantidade' => $quantidade,
                'codigo_barras' => $codigoBarras,
            ]);
        }

        $stmt = $this->conn->prepare(
            'INSERT INTO equipamentos (nome, tipo, quantidade) VALUES (:nome, :tipo, :quantidade)'
        );

        return $stmt->execute([
            'nome' => $nome,
            'tipo' => $tipo,
            'quantidade' => $quantidade,
        ]);
    }

    public function update(int $id, string $nome, string $tipo, int $quantidade, ?string $codigoBarras = null): bool
    {
        $codigoBarras = $codigoBarras !== null ? trim($codigoBarras) : null;
        if ($codigoBarras === '') {
            $codigoBarras = null;
        }

        if ($this->supportsCodigoBarras()) {
            $stmt = $this->conn->prepare(
                'UPDATE equipamentos
                 SET nome = :nome, tipo = :tipo, quantidade = :quantidade, codigo_barras = :codigo_barras
                 WHERE id = :id'
            );

            return $stmt->execute([
                'id' => $id,
                'nome' => $nome,
                'tipo' => $tipo,
                'quantidade' => $quantidade,
                'codigo_barras' => $codigoBarras,
            ]);
        }

        $stmt = $this->conn->prepare(
            'UPDATE equipamentos SET nome = :nome, tipo = :tipo, quantidade = :quantidade WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $id,
            'nome' => $nome,
            'tipo' => $tipo,
            'quantidade' => $quantidade,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare('DELETE FROM equipamentos WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function handBalanceByTechnician(int $equipamentoId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT m.tecnico_id,
                    COALESCE(t.nome, 'Tecnico removido') AS tecnico_nome,
                    SUM(CASE
                            WHEN m.tipo IN ('entrega', 'saida') THEN m.quantidade
                            WHEN m.tipo IN ('uso', 'uso_teste', 'devolucao', 'entrada') THEN -m.quantidade
                            WHEN m.tipo IN ('recolhimento', 'recolhimento_defeito') THEN 0
                            ELSE 0
                        END) AS saldo_mao
             FROM movimentacoes m
             LEFT JOIN tecnicos t ON t.id = m.tecnico_id
             WHERE m.equipamento_id = :equipamento_id
             GROUP BY m.tecnico_id, t.nome
             HAVING saldo_mao > 0
             ORDER BY saldo_mao DESC, tecnico_nome ASC"
        );

        $stmt->execute(['equipamento_id' => $equipamentoId]);
        return $stmt->fetchAll();
    }

    public function adjustStock(int $equipamentoId, int $delta): bool
    {
        $sql = 'UPDATE equipamentos
                SET quantidade = quantidade + :delta
                WHERE id = :id AND (quantidade + :delta) >= 0';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'delta' => $delta,
            'id' => $equipamentoId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function getConnection(): PDO
    {
        return $this->conn;
    }

    private function supportsCodigoBarras(): bool
    {
        if ($this->hasCodigoBarrasColumn !== null) {
            return $this->hasCodigoBarrasColumn;
        }

        try {
            $this->conn->query('SELECT codigo_barras FROM equipamentos LIMIT 1');
            $this->hasCodigoBarrasColumn = true;
        } catch (PDOException $e) {
            $this->hasCodigoBarrasColumn = false;
        }

        return $this->hasCodigoBarrasColumn;
    }
}

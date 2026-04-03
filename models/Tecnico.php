<?php
require_once __DIR__ . '/../config/database.php';

class Tecnico
{
    private PDO $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function all(): array
    {
        $stmt = $this->conn->query('SELECT * FROM tecnicos ORDER BY nome ASC');
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->conn->prepare('SELECT * FROM tecnicos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();
        return $data ?: null;
    }

    public function create(string $nome): bool
    {
        $stmt = $this->conn->prepare('INSERT INTO tecnicos (nome) VALUES (:nome)');
        return $stmt->execute(['nome' => $nome]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare('DELETE FROM tecnicos WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function countMovements(int $tecnicoId): int
    {
        $stmt = $this->conn->prepare('SELECT COUNT(*) FROM movimentacoes WHERE tecnico_id = :tecnico_id');
        $stmt->execute(['tecnico_id' => $tecnicoId]);

        return (int) $stmt->fetchColumn();
    }

    public function movementHistory(int $tecnicoId): array
    {
        $localUsoSelect = $this->hasLocalUsoColumn() ? 'm.local_uso,' : "'' AS local_uso,";
        $observacoesSelect = $this->hasColumn('observacoes') ? 'm.observacoes,' : "'' AS observacoes,";
        $equipamentoNomeSelect = $this->hasColumn('equipamento_nome_snapshot')
            ? "COALESCE(e.nome, m.equipamento_nome_snapshot, 'Equipamento removido') AS equipamento_nome,"
            : "COALESCE(e.nome, 'Equipamento removido') AS equipamento_nome,";
        $equipamentoTipoSelect = $this->hasColumn('equipamento_tipo_snapshot')
            ? "COALESCE(e.tipo, m.equipamento_tipo_snapshot, 'indefinido') AS equipamento_tipo"
            : "COALESCE(e.tipo, 'indefinido') AS equipamento_tipo";

        $sql = "SELECT m.id,
                       m.equipamento_id,
                       CASE
                           WHEN m.tipo = 'saida' THEN 'entrega'
                           WHEN m.tipo = 'entrada' THEN 'devolucao'
                           ELSE m.tipo
                       END AS tipo,
                       m.quantidade,
                       {$localUsoSelect}
                       {$observacoesSelect}
                       m.data_movimentacao,
                      {$equipamentoNomeSelect}
                      {$equipamentoTipoSelect}
                FROM movimentacoes m
                  LEFT JOIN equipamentos e ON e.id = m.equipamento_id
                WHERE m.tecnico_id = :tecnico_id
                ORDER BY m.data_movimentacao DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['tecnico_id' => $tecnicoId]);

        return $stmt->fetchAll();
    }

    private function hasLocalUsoColumn(): bool
    {
        return $this->hasColumn('local_uso');
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
}

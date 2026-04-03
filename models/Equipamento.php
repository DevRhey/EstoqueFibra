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
        $stmt = $this->conn->query('SELECT * FROM equipamentos ORDER BY nome ASC');
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

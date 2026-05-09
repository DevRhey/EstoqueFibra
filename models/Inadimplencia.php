<?php
require_once __DIR__ . '/../config/database.php';

class Inadimplencia
{
    private PDO $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->ensureTableExists();
    }

    private function ensureTableExists(): void
    {
        $this->conn->exec(<<<SQL
CREATE TABLE IF NOT EXISTS inadimplencia_recolhimentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titular VARCHAR(180) NOT NULL,
    equipamento VARCHAR(180) NOT NULL,
    contato VARCHAR(80) NULL,
    endereco VARCHAR(255) NULL,
    prazo DATE NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'AGUARDANDO',
    tentativa_1 TEXT NULL,
    observacoes VARCHAR(500) NULL,
    origem_arquivo VARCHAR(180) NULL,
    last_import_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_inadimplencia_status (status),
    INDEX idx_inadimplencia_prazo (prazo),
    INDEX idx_inadimplencia_titular (titular)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function list(array $filters = []): array
    {
        $where = [];
        $params = [];

        $query = trim((string) ($filters['query'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $prazoDe = trim((string) ($filters['prazo_de'] ?? ''));
        $prazoAte = trim((string) ($filters['prazo_ate'] ?? ''));

        if ($query !== '') {
            $where[] = '(titular LIKE :query_titular OR equipamento LIKE :query_equipamento OR contato LIKE :query_contato OR endereco LIKE :query_endereco OR tentativa_1 LIKE :query_tentativa)';
            $queryLike = '%' . $query . '%';
            $params['query_titular'] = $queryLike;
            $params['query_equipamento'] = $queryLike;
            $params['query_contato'] = $queryLike;
            $params['query_endereco'] = $queryLike;
            $params['query_tentativa'] = $queryLike;
        }

        if ($status !== '') {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        if ($prazoDe !== '') {
            $where[] = 'prazo >= :prazo_de';
            $params['prazo_de'] = $prazoDe;
        }

        if ($prazoAte !== '') {
            $where[] = 'prazo <= :prazo_ate';
            $params['prazo_ate'] = $prazoAte;
        }

        $sql = 'SELECT * FROM inadimplencia_recolhimentos';
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= " ORDER BY
            CASE
                WHEN status = 'AGUARDANDO' THEN 1
                WHEN status = 'AGENDADO' THEN 2
                WHEN status = 'EM CONTATO' THEN 3
                WHEN status = 'RECOLHIDO' THEN 4
                WHEN status = 'SEM CONTATO' THEN 5
                WHEN status = 'NAO RECOLHER' THEN 6
                ELSE 99
            END,
            prazo IS NULL,
            prazo ASC,
            titular ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function statusOptions(): array
    {
        return [
            'AGUARDANDO',
            'AGENDADO',
            'EM CONTATO',
            'RECOLHIDO',
            'SEM CONTATO',
            'NAO RECOLHER',
        ];
    }

    public function summary(array $filters = []): array
    {
        $rows = $this->list($filters);
        $summary = [
            'total' => count($rows),
            'aguardando' => 0,
            'agendado' => 0,
            'em_contato' => 0,
            'recolhido' => 0,
            'sem_contato' => 0,
            'nao_recolher' => 0,
            'vencidos' => 0,
        ];

        $today = date('Y-m-d');

        foreach ($rows as $row) {
            $status = strtoupper(trim((string) ($row['status'] ?? '')));
            if ($status === 'AGUARDANDO') {
                $summary['aguardando']++;
            } elseif ($status === 'AGENDADO') {
                $summary['agendado']++;
            } elseif ($status === 'EM CONTATO') {
                $summary['em_contato']++;
            } elseif ($status === 'RECOLHIDO') {
                $summary['recolhido']++;
            } elseif ($status === 'SEM CONTATO') {
                $summary['sem_contato']++;
            } elseif ($status === 'NAO RECOLHER') {
                $summary['nao_recolher']++;
            }

            $prazo = (string) ($row['prazo'] ?? '');
            if ($prazo !== '' && $prazo < $today && $status !== 'RECOLHIDO' && $status !== 'NAO RECOLHER') {
                $summary['vencidos']++;
            }
        }

        return $summary;
    }

    public function importRows(array $rows, bool $replaceAll = false, ?string $sourceFile = null): int
    {
        if (empty($rows)) {
            return 0;
        }

        $this->conn->beginTransaction();

        try {
            if ($replaceAll) {
                $this->conn->exec('DELETE FROM inadimplencia_recolhimentos');
            }

            $stmt = $this->conn->prepare(
                'INSERT INTO inadimplencia_recolhimentos
                (titular, equipamento, contato, endereco, prazo, status, tentativa_1, observacoes, origem_arquivo, last_import_at)
                VALUES
                (:titular, :equipamento, :contato, :endereco, :prazo, :status, :tentativa_1, :observacoes, :origem_arquivo, NOW())'
            );

            $count = 0;

            foreach ($rows as $row) {
                $titular = trim((string) ($row['titular'] ?? ''));
                $equipamento = trim((string) ($row['equipamento'] ?? ''));

                if ($titular === '' && $equipamento === '') {
                    continue;
                }

                if ($titular === '') {
                    $titular = 'Sem titular informado';
                }

                if ($equipamento === '') {
                    $equipamento = 'Sem equipamento informado';
                }

                $status = strtoupper(trim((string) ($row['status'] ?? 'AGUARDANDO')));
                if (!in_array($status, $this->statusOptions(), true)) {
                    $status = 'AGUARDANDO';
                }

                $prazoDate = $this->normalizePrazo((string) ($row['prazo'] ?? ''));

                $stmt->execute([
                    'titular' => mb_substr($titular, 0, 180),
                    'equipamento' => mb_substr($equipamento, 0, 180),
                    'contato' => $this->nullableSubstring((string) ($row['contato'] ?? ''), 80),
                    'endereco' => $this->nullableSubstring((string) ($row['endereco'] ?? ''), 255),
                    'prazo' => $prazoDate,
                    'status' => $status,
                    'tentativa_1' => $this->nullableSubstring((string) ($row['tentativa_1'] ?? ''), 1500),
                    'observacoes' => $this->nullableSubstring((string) ($row['observacoes'] ?? ''), 500),
                    'origem_arquivo' => $this->nullableSubstring((string) ($sourceFile ?? ''), 180),
                ]);

                $count++;
            }

            $this->conn->commit();

            return $count;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function clearAll(): void
    {
        $this->conn->exec('TRUNCATE TABLE inadimplencia_recolhimentos');
    }

    public function update(int $id, array $data): bool
    {
        $status = strtoupper(trim((string) ($data['status'] ?? 'AGUARDANDO')));
        if (!in_array($status, $this->statusOptions(), true)) {
            $status = 'AGUARDANDO';
        }

        $stmt = $this->conn->prepare(
            'UPDATE inadimplencia_recolhimentos
             SET titular = :titular,
                 equipamento = :equipamento,
                 contato = :contato,
                 endereco = :endereco,
                 prazo = :prazo,
                 status = :status,
                 tentativa_1 = :tentativa_1,
                 observacoes = :observacoes
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'titular' => mb_substr(trim((string) ($data['titular'] ?? '')), 0, 180),
            'equipamento' => mb_substr(trim((string) ($data['equipamento'] ?? '')), 0, 180),
            'contato' => $this->nullableSubstring((string) ($data['contato'] ?? ''), 80),
            'endereco' => $this->nullableSubstring((string) ($data['endereco'] ?? ''), 255),
            'prazo' => $this->normalizePrazo((string) ($data['prazo'] ?? '')),
            'status' => $status,
            'tentativa_1' => $this->nullableSubstring((string) ($data['tentativa_1'] ?? ''), 1500),
            'observacoes' => $this->nullableSubstring((string) ($data['observacoes'] ?? ''), 500),
        ]);

        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare('DELETE FROM inadimplencia_recolhimentos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    private function nullableSubstring(string $value, int $max): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, $max);
    }

    private function normalizePrazo(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?$/', $value, $matches)) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];
            $year = isset($matches[3]) ? (int) $matches[3] : (int) date('Y');

            if ($year < 100) {
                $year += 2000;
            }

            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        $normalized = strtolower($value);
        $normalized = strtr($normalized, [
            'jan' => '01',
            'fev' => '02',
            'mar' => '03',
            'abr' => '04',
            'mai' => '05',
            'jun' => '06',
            'jul' => '07',
            'ago' => '08',
            'set' => '09',
            'out' => '10',
            'nov' => '11',
            'dez' => '12',
        ]);

        if (preg_match('/^(\d{1,2})\/(\d{2})$/', $normalized, $matches)) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];
            $year = (int) date('Y');

            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        return null;
    }
}

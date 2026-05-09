<?php
require_once __DIR__ . '/../config/database.php';

class Lembrete
{
    private PDO $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->ensureTableExists();
    }

    public function create(array $data): bool
    {
        $titulo = trim((string) ($data['titulo'] ?? ''));
        $mensagem = trim((string) ($data['mensagem'] ?? ''));
        $categoria = trim((string) ($data['categoria'] ?? 'geral'));
        $nivel = $this->normalizeLevel((string) ($data['nivel'] ?? 'info'));
        $dataReferencia = $this->normalizeDate((string) ($data['data_referencia'] ?? '')) ?? date('Y-m-d');

        if ($titulo === '' || $mensagem === '') {
            throw new InvalidArgumentException('Titulo e mensagem sao obrigatorios.');
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO lembretes
                (lembrete_key, categoria, titulo, mensagem, nivel, data_referencia, auto_gerado, status)
             VALUES
                (:lembrete_key, :categoria, :titulo, :mensagem, :nivel, :data_referencia, 0, 'aberto')"
        );

        return $stmt->execute([
            'lembrete_key' => uniqid('manual_', true),
            'categoria' => $categoria !== '' ? $categoria : 'geral',
            'titulo' => $titulo,
            'mensagem' => $mensagem,
            'nivel' => $nivel,
            'data_referencia' => $dataReferencia,
        ]);
    }

    public function update(int $id, array $data): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID do lembrete invalido.');
        }

        $titulo = trim((string) ($data['titulo'] ?? ''));
        $mensagem = trim((string) ($data['mensagem'] ?? ''));
        $categoria = trim((string) ($data['categoria'] ?? 'geral'));
        $nivel = $this->normalizeLevel((string) ($data['nivel'] ?? 'info'));
        $dataReferencia = $this->normalizeDate((string) ($data['data_referencia'] ?? '')) ?? date('Y-m-d');

        if ($titulo === '' || $mensagem === '') {
            throw new InvalidArgumentException('Titulo e mensagem sao obrigatorios.');
        }

        $stmt = $this->conn->prepare(
            "UPDATE lembretes
             SET categoria = :categoria,
                 titulo = :titulo,
                 mensagem = :mensagem,
                 nivel = :nivel,
                 data_referencia = :data_referencia,
                 updated_at = NOW()
             WHERE id = :id"
        );

        $stmt->execute([
            'categoria' => $categoria !== '' ? $categoria : 'geral',
            'titulo' => $titulo,
            'mensagem' => $mensagem,
            'nivel' => $nivel,
            'data_referencia' => $dataReferencia,
            'id' => $id,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function summary(): array
    {
        $stmt = $this->conn->query("SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status <> 'resolvido' THEN 1 ELSE 0 END) AS pendentes,
                SUM(CASE WHEN status = 'aberto' THEN 1 ELSE 0 END) AS abertos,
                SUM(CASE WHEN status = 'lido' THEN 1 ELSE 0 END) AS lidos,
                SUM(CASE WHEN status = 'resolvido' THEN 1 ELSE 0 END) AS resolvidos,
                SUM(CASE WHEN nivel = 'danger' AND status <> 'resolvido' THEN 1 ELSE 0 END) AS urgentes
            FROM lembretes");

        $row = $stmt->fetch() ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'pendentes' => (int) ($row['pendentes'] ?? 0),
            'abertos' => (int) ($row['abertos'] ?? 0),
            'lidos' => (int) ($row['lidos'] ?? 0),
            'resolvidos' => (int) ($row['resolvidos'] ?? 0),
            'urgentes' => (int) ($row['urgentes'] ?? 0),
        ];
    }

    public function listAll(?string $status = null, ?string $selectedDate = null): array
    {
        $sql = "SELECT * FROM lembretes";
        $conditions = [];
        $params = [];

        if ($status !== null && in_array($status, ['aberto', 'lido', 'resolvido'], true)) {
            $conditions[] = 'status = :status';
            $params['status'] = $status;
        } else {
            // Default view keeps alerting pending reminders until user resolves them.
            $conditions[] = "status <> 'resolvido'";
        }

        $date = $this->normalizeDate($selectedDate);
        if ($date !== null) {
            $conditions[] = 'data_referencia = :data_referencia';
            $params['data_referencia'] = $date;
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY FIELD(status, "aberto", "lido", "resolvido"), FIELD(nivel, "danger", "warning", "info", "success"), data_referencia ASC, created_at DESC';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function markAsRead(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $stmt = $this->conn->prepare("UPDATE lembretes SET status = 'lido', lido_em = COALESCE(lido_em, NOW()) WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function resolve(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $stmt = $this->conn->prepare("UPDATE lembretes SET status = 'resolvido', resolvido_em = NOW() WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    private function ensureTableExists(): void
    {
        $this->conn->exec(
            "CREATE TABLE IF NOT EXISTS lembretes (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                lembrete_key VARCHAR(190) NOT NULL,
                categoria VARCHAR(60) NOT NULL,
                titulo VARCHAR(180) NOT NULL,
                mensagem VARCHAR(500) NOT NULL,
                nivel ENUM('info', 'warning', 'danger', 'success') NOT NULL DEFAULT 'info',
                tecnico_id INT UNSIGNED NULL,
                equipamento_id INT UNSIGNED NULL,
                movimentacao_id INT UNSIGNED NULL,
                data_referencia DATE NULL,
                auto_gerado TINYINT(1) NOT NULL DEFAULT 1,
                status ENUM('aberto', 'lido', 'resolvido') NOT NULL DEFAULT 'aberto',
                lido_em DATETIME NULL,
                resolvido_em DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_lembretes_key (lembrete_key),
                KEY idx_lembretes_status (status),
                KEY idx_lembretes_nivel (nivel),
                KEY idx_lembretes_data (data_referencia),
                KEY idx_lembretes_tecnico (tecnico_id),
                KEY idx_lembretes_movimentacao (movimentacao_id)
            ) ENGINE=InnoDB"
        );
    }

    private function normalizeLevel(string $level): string
    {
        return match (strtolower(trim($level))) {
            'danger', 'warning', 'success', 'info' => strtolower(trim($level)),
            default => 'info',
        };
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
}
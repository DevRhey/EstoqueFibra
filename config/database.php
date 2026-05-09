<?php
class Database
{
    private string $host;
    private string $dbName;
    private string $username;
    private string $password;
    private string $charset;

    public function __construct()
    {
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->dbName = getenv('DB_NAME') ?: 'controle_estoque_fibra';
        $this->username = getenv('DB_USER') ?: getenv('DB_USERNAME') ?: 'root';
        $this->password = getenv('DB_PASS') ?: getenv('DB_PASSWORD') ?: '';
        $this->charset = getenv('DB_CHARSET') ?: 'utf8mb4';
    }

    public function getConnection(): PDO
    {
        $dsn = "mysql:host={$this->host};dbname={$this->dbName};charset={$this->charset}";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        return new PDO($dsn, $this->username, $this->password, $options);
    }
}

CREATE DATABASE IF NOT EXISTS controle_estoque_fibra
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE controle_estoque_fibra;

CREATE TABLE IF NOT EXISTS equipamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    tipo VARCHAR(80) NOT NULL,
    codigo_barras VARCHAR(64) NULL,
    quantidade INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_equip_quantidade_nonnegative CHECK (quantidade >= 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tecnicos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS movimentacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tecnico_id INT UNSIGNED NULL,
    equipamento_id INT UNSIGNED NULL,
    quantidade INT NOT NULL,
    tipo ENUM('entrada', 'saida', 'uso', 'uso_teste', 'recolhimento', 'entrega', 'devolucao') NOT NULL,
    local_uso VARCHAR(120) NULL,
    observacoes VARCHAR(500) NULL,
    equipamento_nome_snapshot VARCHAR(120) NULL,
    equipamento_tipo_snapshot VARCHAR(80) NULL,
    equipamento_codigo_barras_snapshot VARCHAR(64) NULL,
    data_movimentacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mov_tecnico FOREIGN KEY (tecnico_id)
        REFERENCES tecnicos(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_mov_equipamento FOREIGN KEY (equipamento_id)
        REFERENCES equipamentos(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT chk_mov_quantidade_positive CHECK (quantidade > 0)
) ENGINE=InnoDB;

CREATE INDEX idx_equipamentos_nome ON equipamentos (nome);
CREATE UNIQUE INDEX idx_equipamentos_codigo_barras ON equipamentos (codigo_barras);
CREATE INDEX idx_tecnicos_nome ON tecnicos (nome);
CREATE INDEX idx_mov_tecnico_data ON movimentacoes (tecnico_id, data_movimentacao);
CREATE INDEX idx_mov_equipamento_data ON movimentacoes (equipamento_id, data_movimentacao);
CREATE INDEX idx_mov_tipo_data ON movimentacoes (tipo, data_movimentacao);
CREATE INDEX idx_mov_local_uso ON movimentacoes (local_uso);
CREATE INDEX idx_mov_observacoes ON movimentacoes (observacoes);

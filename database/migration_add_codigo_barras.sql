USE controle_estoque_fibra;

ALTER TABLE equipamentos
    ADD COLUMN IF NOT EXISTS codigo_barras VARCHAR(64) NULL AFTER tipo;

SET @idx_exists = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'equipamentos'
      AND index_name = 'idx_equipamentos_codigo_barras'
);

SET @sql = IF(
    @idx_exists = 0,
    'CREATE UNIQUE INDEX idx_equipamentos_codigo_barras ON equipamentos (codigo_barras)',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

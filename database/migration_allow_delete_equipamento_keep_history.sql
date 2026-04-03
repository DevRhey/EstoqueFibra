USE controle_estoque_fibra;

-- Preserva historico de movimentacoes ao excluir equipamentos.
-- Mantem nome/tipo/codigo de barras em colunas snapshot para exibicao posterior.
ALTER TABLE movimentacoes
ADD COLUMN IF NOT EXISTS equipamento_nome_snapshot VARCHAR(120) NULL AFTER observacoes,
ADD COLUMN IF NOT EXISTS equipamento_tipo_snapshot VARCHAR(80) NULL AFTER equipamento_nome_snapshot,
ADD COLUMN IF NOT EXISTS equipamento_codigo_barras_snapshot VARCHAR(64) NULL AFTER equipamento_tipo_snapshot;

UPDATE movimentacoes m
LEFT JOIN equipamentos e ON e.id = m.equipamento_id
SET m.equipamento_nome_snapshot = COALESCE(m.equipamento_nome_snapshot, e.nome),
    m.equipamento_tipo_snapshot = COALESCE(m.equipamento_tipo_snapshot, e.tipo),
    m.equipamento_codigo_barras_snapshot = COALESCE(m.equipamento_codigo_barras_snapshot, e.codigo_barras)
WHERE e.id IS NOT NULL;

ALTER TABLE movimentacoes
DROP FOREIGN KEY fk_mov_equipamento;

ALTER TABLE movimentacoes
MODIFY equipamento_id INT UNSIGNED NULL;

ALTER TABLE movimentacoes
ADD CONSTRAINT fk_mov_equipamento FOREIGN KEY (equipamento_id)
    REFERENCES equipamentos(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL;

SELECT 'Migracao aplicada: exclusao de equipamento com historico preservado.' AS mensagem;

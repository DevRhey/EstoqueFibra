USE controle_estoque_fibra;

ALTER TABLE movimentacoes
    ADD COLUMN IF NOT EXISTS local_uso VARCHAR(120) NULL AFTER tipo;

ALTER TABLE movimentacoes
    ADD COLUMN observacoes VARCHAR(500) NULL AFTER local_uso;

CREATE INDEX idx_mov_observacoes ON movimentacoes (observacoes);

SELECT 'Migracao de observacoes concluida com sucesso!' AS mensagem;
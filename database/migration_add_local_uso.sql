USE controle_estoque_fibra;

ALTER TABLE movimentacoes
    ADD COLUMN local_uso VARCHAR(120) NULL AFTER tipo;

CREATE INDEX idx_mov_local_uso ON movimentacoes (local_uso);

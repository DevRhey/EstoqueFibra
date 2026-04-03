USE controle_estoque_fibra;

-- Adicionar coluna local_uso se ela não existir
ALTER TABLE movimentacoes
ADD COLUMN local_uso VARCHAR(120) NULL AFTER tipo;

-- Adicionar índice para local_uso
CREATE INDEX idx_mov_local_uso ON movimentacoes (local_uso);

-- Confirmar que a migração foi executada
SELECT 'Migração concluída com sucesso!' as mensagem;

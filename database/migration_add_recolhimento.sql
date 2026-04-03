USE controle_estoque_fibra;

ALTER TABLE movimentacoes
    MODIFY COLUMN tipo ENUM('entrada', 'saida', 'uso', 'recolhimento') NOT NULL;

SELECT 'Migração de recolhimento concluída com sucesso!' AS mensagem;
ALTER TABLE movimentacoes
    MODIFY COLUMN tipo ENUM('entrada', 'saida', 'uso', 'uso_teste', 'recolhimento', 'entrega', 'devolucao') NOT NULL;

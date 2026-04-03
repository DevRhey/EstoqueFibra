USE controle_estoque_fibra;

-- Permite excluir tecnicos sem apagar movimentacoes historicas.
-- As movimentacoes existentes permanecem e passam a apontar tecnico_id = NULL.
ALTER TABLE movimentacoes
DROP FOREIGN KEY fk_mov_tecnico;

ALTER TABLE movimentacoes
MODIFY tecnico_id INT UNSIGNED NULL;

ALTER TABLE movimentacoes
ADD CONSTRAINT fk_mov_tecnico FOREIGN KEY (tecnico_id)
    REFERENCES tecnicos(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL;

SELECT 'Migracao aplicada: exclusao de tecnico com historico preservado.' AS mensagem;

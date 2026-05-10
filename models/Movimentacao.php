<?php
require_once __DIR__ . '/../config/database.php';

class Movimentacao
{
    private PDO $conn;
    private array $snapshotEquipmentCache = [];

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create(?int $tecnicoId, int $equipamentoId, int $quantidade, string $tipo, ?string $localUso = null, ?string $observacoes = null, ?string $dataMovimentacao = null): void
    {
        if ($equipamentoId <= 0 || $quantidade <= 0) {
            throw new InvalidArgumentException('Dados invalidos para registrar movimentacao.');
        }

        $this->conn->beginTransaction();

        try {
            $this->createInternal($tecnicoId, $equipamentoId, $quantidade, $tipo, $localUso, $observacoes, $dataMovimentacao);
            $this->conn->commit();
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            throw $e;
        }
    }

    public function createBatch(?int $tecnicoId, string $tipo, array $itens, ?string $dataMovimentacao = null): int
    {
        if (empty($itens)) {
            throw new InvalidArgumentException('Nenhum item informado para o lote.');
        }

        $this->conn->beginTransaction();

        try {
            $processados = 0;

            foreach ($itens as $index => $item) {
                if (!is_array($item)) {
                    throw new InvalidArgumentException('Item de lote invalido na posicao ' . ($index + 1) . '.');
                }

                $equipamentoId = (int) ($item['equipamento_id'] ?? 0);
                $quantidade = (int) ($item['quantidade'] ?? 0);
                $localUso = isset($item['local_uso']) ? (string) $item['local_uso'] : null;
                $observacoes = isset($item['observacoes']) ? (string) $item['observacoes'] : null;

                if ($equipamentoId <= 0 || $quantidade <= 0) {
                    throw new InvalidArgumentException('Item de lote invalido na posicao ' . ($index + 1) . '.');
                }

                $this->createInternal(
                    $tecnicoId,
                    $equipamentoId,
                    $quantidade,
                    $tipo,
                    $localUso,
                    $observacoes,
                    $dataMovimentacao
                );
                $processados++;
            }

            $this->conn->commit();
            return $processados;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            throw $e;
        }
    }

    public function adjustHandBalance(int $tecnicoId, int $equipamentoId, int $novoSaldo, ?string $observacoes = null): array
    {
        if ($tecnicoId <= 0 || $equipamentoId <= 0 || $novoSaldo < 0) {
            throw new InvalidArgumentException('Parametros invalidos para ajuste de saldo em mao.');
        }

        $saldoAtual = $this->getSaldoNaMao($tecnicoId, $equipamentoId);
        $delta = $novoSaldo - $saldoAtual;

        if ($delta === 0) {
            return [
                'alterado' => false,
                'saldo_anterior' => $saldoAtual,
                'saldo_novo' => $novoSaldo,
                'quantidade_ajustada' => 0,
                'tipo_movimentacao' => null,
            ];
        }

        $tipo = $delta > 0 ? 'entrega' : 'devolucao';
        $quantidade = abs($delta);
        $obsPrefixo = 'Ajuste manual de saldo em mao.';
        $obsFinal = $obsPrefixo;

        if ($observacoes !== null && trim($observacoes) !== '') {
            $obsFinal .= ' ' . trim($observacoes);
        }

        $this->create($tecnicoId, $equipamentoId, $quantidade, $tipo, null, $obsFinal);

        return [
            'alterado' => true,
            'saldo_anterior' => $saldoAtual,
            'saldo_novo' => $novoSaldo,
            'quantidade_ajustada' => $quantidade,
            'tipo_movimentacao' => $tipo,
        ];
    }

    public function deleteUsageMovement(int $movimentacaoId, string $tipo): bool
    {
        if ($movimentacaoId <= 0 || !in_array($tipo, ['uso', 'uso_teste'], true)) {
            throw new InvalidArgumentException('Dados invalidos para excluir movimentacao de uso.');
        }

        $this->conn->beginTransaction();

        try {
            $stmtSelect = $this->conn->prepare(
                'SELECT id, tipo, quantidade, tecnico_id, equipamento_id, data_movimentacao
                 FROM movimentacoes
                 WHERE id = :id
                 FOR UPDATE'
            );
            $stmtSelect->execute(['id' => $movimentacaoId]);
            $mov = $stmtSelect->fetch();

            if (!$mov) {
                throw new RuntimeException('Movimentacao nao encontrada.');
            }

            $tipoAtual = (string) ($mov['tipo'] ?? '');
            if (!in_array($tipoAtual, ['uso', 'uso_teste'], true)) {
                throw new RuntimeException('Somente movimentacoes de uso podem ser excluidas por esta acao.');
            }

            if ($tipoAtual !== $tipo) {
                throw new RuntimeException('Tipo de movimentacao nao confere para exclusao.');
            }

            $quantidadeAtual = (int) ($mov['quantidade'] ?? 0);
            $tecnicoId = (int) ($mov['tecnico_id'] ?? 0);
            $equipamentoId = (int) ($mov['equipamento_id'] ?? 0);
            if ($quantidadeAtual <= 0 || $tecnicoId <= 0 || $equipamentoId <= 0) {
                throw new RuntimeException('Nao foi possivel validar este uso para exclusao com seguranca.');
            }

            $saldoCampoAtual = $this->getSaldoEmCampo($tecnicoId, $equipamentoId);
            if ($saldoCampoAtual < $quantidadeAtual) {
                throw new RuntimeException('Nao e possivel excluir este uso, pois isso deixaria recolhimentos maiores que o total usado em campo.');
            }

            $stmtDelete = $this->conn->prepare('DELETE FROM movimentacoes WHERE id = :id');
            $stmtDelete->execute(['id' => $movimentacaoId]);

            $this->conn->commit();
            return $stmtDelete->rowCount() > 0;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function deleteDeliveryMovement(int $movimentacaoId, string $tipo): bool
    {
        if ($movimentacaoId <= 0 || !in_array($tipo, ['entrega', 'saida'], true)) {
            throw new InvalidArgumentException('Dados invalidos para excluir movimentacao de entrega.');
        }

        $this->conn->beginTransaction();

        try {
            $stmtSelect = $this->conn->prepare(
                'SELECT id, tipo, tecnico_id, equipamento_id, quantidade, data_movimentacao
                 FROM movimentacoes
                 WHERE id = :id
                 FOR UPDATE'
            );
            $stmtSelect->execute(['id' => $movimentacaoId]);
            $mov = $stmtSelect->fetch();

            if (!$mov) {
                throw new RuntimeException('Movimentacao nao encontrada.');
            }

            $tipoAtual = $this->normalizeMovementType((string) ($mov['tipo'] ?? ''));
            if ($tipoAtual !== 'entrega') {
                throw new RuntimeException('Somente movimentacoes de entrega podem ser excluidas por esta acao.');
            }

            if ($this->normalizeMovementType($tipo) !== 'entrega') {
                throw new RuntimeException('Tipo de movimentacao nao confere para exclusao.');
            }

            $tecnicoId = (int) ($mov['tecnico_id'] ?? 0);
            $equipamentoId = (int) ($mov['equipamento_id'] ?? 0);
            $quantidade = (int) ($mov['quantidade'] ?? 0);
            $dataMovimentacao = (string) ($mov['data_movimentacao'] ?? '');

            if ($tecnicoId <= 0 || $equipamentoId <= 0 || $quantidade <= 0) {
                throw new RuntimeException('Nao foi possivel excluir esta entrega porque o registro historico esta incompleto.');
            }

            if ($dataMovimentacao !== '') {
                $stmtPosteriores = $this->conn->prepare(
                    "SELECT COUNT(*) AS total
                     FROM movimentacoes
                     WHERE tecnico_id = :tecnico_id
                       AND equipamento_id = :equipamento_id
                       AND (
                            data_movimentacao > :data_movimentacao
                            OR (data_movimentacao = :data_movimentacao_equal AND id > :id)
                       )
                       AND tipo NOT IN ('entrega', 'saida')"
                );
                $stmtPosteriores->execute([
                    'tecnico_id' => $tecnicoId,
                    'equipamento_id' => $equipamentoId,
                    'data_movimentacao' => $dataMovimentacao,
                    'data_movimentacao_equal' => $dataMovimentacao,
                    'id' => $movimentacaoId,
                ]);

                $temMovimentacaoPosterior = (int) $stmtPosteriores->fetchColumn() > 0;

                if ($temMovimentacaoPosterior) {
                    throw new RuntimeException('Nao e possivel excluir esta entrega, pois ha movimentacoes posteriores de uso/devolucao/recolhimento para este equipamento.');
                }
            }

            $saldoAtual = $this->getSaldoNaMao($tecnicoId, $equipamentoId);
            if ($saldoAtual < $quantidade) {
                throw new RuntimeException('Nao e possivel excluir esta entrega, pois parte dos itens ja foi usada ou devolvida.');
            }

            $stmtUpdate = $this->conn->prepare(
                'UPDATE equipamentos SET quantidade = quantidade + :quantidade WHERE id = :equipamento_id'
            );
            $stmtUpdate->execute([
                'quantidade' => $quantidade,
                'equipamento_id' => $equipamentoId,
            ]);

            if ($stmtUpdate->rowCount() === 0) {
                throw new RuntimeException('Equipamento nao encontrado para recompor o estoque.');
            }

            $stmtDelete = $this->conn->prepare('DELETE FROM movimentacoes WHERE id = :id');
            $stmtDelete->execute(['id' => $movimentacaoId]);

            $this->conn->commit();
            return $stmtDelete->rowCount() > 0;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function getMovementById(int $movimentacaoId): ?array
    {
        if ($movimentacaoId <= 0) {
            throw new InvalidArgumentException('ID de movimentacao invalido.');
        }

        $selectLocal = $this->hasColumn('local_uso') ? 'm.local_uso,' : "'' AS local_uso,";
        $selectObservacoes = $this->hasColumn('observacoes') ? 'm.observacoes,' : "'' AS observacoes,";
        $selectHistorico = $this->hasColumn('historico_tratativa') ? 'm.historico_tratativa,' : "'' AS historico_tratativa,";

        $sql = "SELECT m.id,
                       m.tecnico_id,
                       m.equipamento_id,
                       m.quantidade,
                       m.tipo,
                       {$selectLocal}
                       {$selectObservacoes}
                      {$selectHistorico}
                       m.data_movimentacao
                FROM movimentacoes m
                WHERE m.id = :id
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $movimentacaoId]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function updateUsageMovement(int $movimentacaoId, string $tipo, int $novaQuantidade, string $localUso, ?string $observacoes = null): bool
    {
        if ($movimentacaoId <= 0 || !in_array($tipo, ['uso', 'uso_teste'], true)) {
            throw new InvalidArgumentException('Dados invalidos para atualizar movimentacao de uso.');
        }

        if ($novaQuantidade <= 0) {
            throw new InvalidArgumentException('A quantidade deve ser maior que zero.');
        }

        if (trim($localUso) === '') {
            throw new InvalidArgumentException('Informe o local de uso.');
        }

        $this->conn->beginTransaction();

        try {
            $stmtSelect = $this->conn->prepare('SELECT id, tipo, quantidade, tecnico_id, equipamento_id FROM movimentacoes WHERE id = :id FOR UPDATE');
            $stmtSelect->execute(['id' => $movimentacaoId]);
            $mov = $stmtSelect->fetch();

            if (!$mov) {
                throw new RuntimeException('Movimentacao nao encontrada.');
            }

            $tipoAtual = (string) ($mov['tipo'] ?? '');
            if (!in_array($tipoAtual, ['uso', 'uso_teste'], true)) {
                throw new RuntimeException('Somente movimentacoes de uso podem ser editadas por esta acao.');
            }

            if ($tipoAtual !== $tipo) {
                throw new RuntimeException('Tipo de movimentacao nao confere para atualizacao.');
            }

            $quantidadeAtual = (int) ($mov['quantidade'] ?? 0);
            $tecnicoId = (int) ($mov['tecnico_id'] ?? 0);
            $equipamentoId = (int) ($mov['equipamento_id'] ?? 0);

            if ($quantidadeAtual <= 0 || $tecnicoId <= 0 || $equipamentoId <= 0) {
                throw new RuntimeException('Nao foi possivel validar o saldo da movimentacao para atualizacao.');
            }

            $saldoAtual = $this->getSaldoNaMao($tecnicoId, $equipamentoId);
            $limiteDisponivel = $saldoAtual + $quantidadeAtual;

            if ($novaQuantidade > $limiteDisponivel) {
                throw new RuntimeException('Quantidade informada maior que o saldo em mao disponivel para este equipamento.');
            }

            $stmtUpdate = $this->conn->prepare(
                'UPDATE movimentacoes SET quantidade = :quantidade, local_uso = :local_uso, observacoes = :observacoes WHERE id = :id'
            );
            $stmtUpdate->execute([
                'quantidade' => $novaQuantidade,
                'local_uso' => trim($localUso),
                'observacoes' => $observacoes !== null && trim($observacoes) !== '' ? trim($observacoes) : null,
                'id' => $movimentacaoId,
            ]);

            $this->conn->commit();
            return $stmtUpdate->rowCount() > 0;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function convertTestToUsage(int $movimentacaoId, ?string $localUso = null, ?string $observacoes = null): bool
    {
        if ($movimentacaoId <= 0) {
            throw new InvalidArgumentException('ID de movimentacao invalido para conversao de teste.');
        }

        $hasLocalUso = $this->hasColumn('local_uso');
        $hasObservacoes = $this->hasColumn('observacoes');
        $hasHistorico = $this->hasColumn('historico_tratativa');

        $selectLocal = $hasLocalUso ? ', local_uso' : '';
        $selectObservacoes = $hasObservacoes ? ', observacoes' : '';

        $this->conn->beginTransaction();

        try {
            $selectHistorico = $hasHistorico ? ', historico_tratativa' : '';

            $stmtSelect = $this->conn->prepare(
                'SELECT id, tipo, tecnico_id, equipamento_id, data_movimentacao' . $selectLocal . $selectObservacoes . $selectHistorico . '
                 FROM movimentacoes
                 WHERE id = :id
                 FOR UPDATE'
            );
            $stmtSelect->execute(['id' => $movimentacaoId]);
            $mov = $stmtSelect->fetch();

            if (!$mov) {
                throw new RuntimeException('Movimentacao de teste nao encontrada.');
            }

            if ((string) ($mov['tipo'] ?? '') !== 'uso_teste') {
                throw new RuntimeException('Apenas registros de uso em teste podem ser definidos como uso.');
            }

            $tecnicoId = (int) ($mov['tecnico_id'] ?? 0);
            $equipamentoId = (int) ($mov['equipamento_id'] ?? 0);
            $dataMovimentacao = (string) ($mov['data_movimentacao'] ?? '');

            $stmtResolucao = $this->conn->prepare(
                "SELECT id
                 FROM movimentacoes
                 WHERE tecnico_id = :tecnico_id
                   AND equipamento_id = :equipamento_id
                   AND data_movimentacao > :data_movimentacao
                   AND tipo IN ('uso', 'uso_teste', 'devolucao', 'recolhimento', 'recolhimento_defeito')
                 ORDER BY data_movimentacao DESC
                 LIMIT 1"
            );
            $stmtResolucao->execute([
                'tecnico_id' => $tecnicoId,
                'equipamento_id' => $equipamentoId,
                'data_movimentacao' => $dataMovimentacao,
            ]);

            if ($stmtResolucao->fetch()) {
                throw new RuntimeException('Este teste ja possui movimentacao posterior e nao pode ser convertido.');
            }

            $localFinal = trim((string) ($localUso ?? ($mov['local_uso'] ?? '')));
            if ($hasLocalUso && $localFinal === '') {
                throw new RuntimeException('Informe o local de uso para definir o uso no cliente.');
            }

            $campos = ['tipo = :tipo'];
            $params = [
                'tipo' => 'uso',
                'id' => $movimentacaoId,
            ];

            if ($hasLocalUso) {
                $campos[] = 'local_uso = :local_uso';
                $params['local_uso'] = $localFinal;
            }

            if ($hasObservacoes) {
                $observacoesFinal = trim((string) ($observacoes ?? ($mov['observacoes'] ?? '')));
                $campos[] = 'observacoes = :observacoes';
                $params['observacoes'] = $observacoesFinal !== '' ? $observacoesFinal : null;
            }

            if ($hasHistorico) {
                $campos[] = 'historico_tratativa = :historico_tratativa';
                $params['historico_tratativa'] = $this->appendHistoryEntry(
                    (string) ($mov['historico_tratativa'] ?? ''),
                    'Definido como uso no cliente.'
                );
            }

            $stmtUpdate = $this->conn->prepare(
                'UPDATE movimentacoes
                 SET ' . implode(', ', $campos) . '
                 WHERE id = :id'
            );
            $stmtUpdate->execute($params);

            $this->conn->commit();
            return $stmtUpdate->rowCount() > 0;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function appendTestAttemptHistory(int $movimentacaoId, string $categoria, string $descricao): bool
    {
        if ($movimentacaoId <= 0) {
            throw new InvalidArgumentException('ID de movimentacao invalido para historico de teste.');
        }

        if (!$this->hasColumn('historico_tratativa')) {
            throw new RuntimeException('Aplique a migracao do historico de teste antes de registrar tentativas.');
        }

        $categoria = trim($categoria);
        $descricao = trim($descricao);
        if ($descricao === '') {
            throw new InvalidArgumentException('Informe a tentativa ou tratativa a ser registrada.');
        }

        $this->conn->beginTransaction();

        try {
            $stmtSelect = $this->conn->prepare(
                'SELECT id, tipo, historico_tratativa
                 FROM movimentacoes
                 WHERE id = :id
                 FOR UPDATE'
            );
            $stmtSelect->execute(['id' => $movimentacaoId]);
            $mov = $stmtSelect->fetch();

            if (!$mov) {
                throw new RuntimeException('Movimentacao de teste nao encontrada.');
            }

            if ((string) ($mov['tipo'] ?? '') !== 'uso_teste') {
                throw new RuntimeException('Apenas testes ativos podem receber novas tentativas.');
            }

            $prefixo = match ($categoria) {
                'recolhimento' => 'Tentativa de recolhimento',
                'definir_uso' => 'Tentativa de definir como uso',
                default => 'Tratativa',
            };

            $stmtUpdate = $this->conn->prepare(
                'UPDATE movimentacoes
                 SET historico_tratativa = :historico_tratativa
                 WHERE id = :id'
            );
            $stmtUpdate->execute([
                'historico_tratativa' => $this->appendHistoryEntry((string) ($mov['historico_tratativa'] ?? ''), $prefixo . ': ' . $descricao),
                'id' => $movimentacaoId,
            ]);

            $this->conn->commit();
            return $stmtUpdate->rowCount() > 0;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            throw $e;
        }
    }

    private function createInternal(?int $tecnicoId, int $equipamentoId, int $quantidade, string $tipo, ?string $localUso = null, ?string $observacoes = null, ?string $dataMovimentacao = null): void
    {
        $tipo = $this->normalizeMovementType($tipo);

        $dateTimeMovimentacao = $this->resolveMovementDateTime($dataMovimentacao);

        if (!in_array($tipo, ['entrega', 'uso', 'uso_teste', 'devolucao', 'recolhimento', 'recolhimento_defeito'], true)) {
            throw new InvalidArgumentException('Tipo de movimentacao invalido.');
        }

        $tecnicoObrigatorio = in_array($tipo, ['entrega', 'uso', 'uso_teste', 'devolucao', 'recolhimento', 'recolhimento_defeito'], true);
        if ($tecnicoObrigatorio && (($tecnicoId ?? 0) <= 0)) {
            throw new InvalidArgumentException('Tecnico obrigatorio para este tipo de movimentacao.');
        }

        if (($tipo === 'uso' || $tipo === 'uso_teste') && ($localUso === null || trim($localUso) === '')) {
            throw new InvalidArgumentException('Informe o local de uso para movimentacoes do tipo uso.');
        }

        $selectCodigoBarras = $this->hasEquipamentoColumn('codigo_barras') ? ', codigo_barras' : '';
        $stmtEquip = $this->conn->prepare('SELECT quantidade, nome, tipo' . $selectCodigoBarras . ' FROM equipamentos WHERE id = :id FOR UPDATE');
        $stmtEquip->execute(['id' => $equipamentoId]);
        $equipamento = $stmtEquip->fetch();

        if (!$equipamento) {
            throw new RuntimeException('Equipamento nao encontrado.');
        }

        $estoqueAtual = (int) $equipamento['quantidade'];
        $deltaEstoque = $this->calculateStockDelta($tipo, $quantidade);

        if ($tipo === 'recolhimento_defeito' && $deltaEstoque !== 0) {
            throw new RuntimeException('Recolhimento com defeito nao pode alterar o estoque normal.');
        }

        if (($estoqueAtual + $deltaEstoque) < 0) {
            throw new RuntimeException('Saida maior que o estoque disponivel.');
        }

        if ($tipo === 'uso' || $tipo === 'uso_teste' || $tipo === 'devolucao' || $tipo === 'recolhimento_defeito') {
            $saldoNaMao = $this->getSaldoNaMao($tecnicoId, $equipamentoId);

            if ($saldoNaMao < $quantidade) {
                throw new RuntimeException('O equipamento selecionado nao esta em mao deste tecnico ou a quantidade e maior que o saldo disponivel.');
            }
        }

        if ($tipo === 'recolhimento') {
            // Recolhimento representa retirada do cliente para a mao do tecnico.
            // Nao deve retornar direto ao estoque geral, apenas baixar o saldo em campo.
            $saldoEmCampoGlobal = $this->getSaldoEmCampoGlobal($equipamentoId);
            if ($saldoEmCampoGlobal < $quantidade) {
                $diferenca = $quantidade - $saldoEmCampoGlobal;
                $observacoes = $this->appendSystemObservation(
                    $observacoes,
                    sprintf('[SEM_LASTRO_CAMPO: saldo_global=%d, recolhido=%d, diferenca=%d]', $saldoEmCampoGlobal, $quantidade, $diferenca)
                );
            }
        }

        // Construir INSERT dinamicamente baseado na existência das colunas opcionais
        $columns = ['tecnico_id', 'equipamento_id', 'quantidade', 'tipo', 'data_movimentacao'];
        $placeholders = [':tecnico_id', ':equipamento_id', ':quantidade', ':tipo', ':data_movimentacao'];
        $bindParams = [
            ':tecnico_id' => (($tecnicoId ?? 0) > 0) ? $tecnicoId : null,
            ':equipamento_id' => $equipamentoId,
            ':quantidade' => $quantidade,
            ':tipo' => $tipo,
            ':data_movimentacao' => $dateTimeMovimentacao,
        ];

        if ($this->hasColumn('local_uso')) {
            $columns[] = 'local_uso';
            $placeholders[] = ':local_uso';
            $bindParams[':local_uso'] = ($tipo === 'uso' || $tipo === 'uso_teste') ? $localUso : null;
        }

        if ($this->hasColumn('observacoes')) {
            $columns[] = 'observacoes';
            $placeholders[] = ':observacoes';
            $bindParams[':observacoes'] = $observacoes !== null && trim($observacoes) !== '' ? $observacoes : null;
        }

        if ($this->hasColumn('historico_tratativa')) {
            $columns[] = 'historico_tratativa';
            $placeholders[] = ':historico_tratativa';
            $bindParams[':historico_tratativa'] = $tipo === 'uso_teste'
                ? $this->appendHistoryEntry('', 'Teste iniciado.')
                : null;
        }

        if ($this->hasColumn('equipamento_nome_snapshot')) {
            $columns[] = 'equipamento_nome_snapshot';
            $placeholders[] = ':equipamento_nome_snapshot';
            $bindParams[':equipamento_nome_snapshot'] = (string) ($equipamento['nome'] ?? '');
        }

        if ($this->hasColumn('equipamento_tipo_snapshot')) {
            $columns[] = 'equipamento_tipo_snapshot';
            $placeholders[] = ':equipamento_tipo_snapshot';
            $bindParams[':equipamento_tipo_snapshot'] = (string) ($equipamento['tipo'] ?? '');
        }

        if ($this->hasColumn('equipamento_codigo_barras_snapshot')) {
            $columns[] = 'equipamento_codigo_barras_snapshot';
            $placeholders[] = ':equipamento_codigo_barras_snapshot';
            $bindParams[':equipamento_codigo_barras_snapshot'] = isset($equipamento['codigo_barras'])
                ? (string) ($equipamento['codigo_barras'] ?? '')
                : null;
        }

        $sqlInsert = 'INSERT INTO movimentacoes (' . implode(', ', $columns) . ')
                     VALUES (' . implode(', ', $placeholders) . ')';

        $stmtMov = $this->conn->prepare($sqlInsert);
        $stmtMov->execute($bindParams);

        $stmtUpdate = $this->conn->prepare(
            'UPDATE equipamentos SET quantidade = quantidade + :delta WHERE id = :id'
        );
        $stmtUpdate->execute([
            'delta' => $deltaEstoque,
            'id' => $equipamentoId,
        ]);
    }

    private function resolveMovementDateTime(?string $date): string
    {
        if ($date === null) {
            return date('Y-m-d H:i:s');
        }

        $date = trim($date);
        if ($date === '') {
            return date('Y-m-d H:i:s');
        }

        if (preg_match('/^(\d{4}-\d{2}-\d{2})\s(\d{2}):(\d{2}):(\d{2})$/', $date, $matches) === 1) {
            [$year, $month, $day] = array_map('intval', explode('-', $matches[1]));
            $hour = (int) $matches[2];
            $minute = (int) $matches[3];
            $second = (int) $matches[4];

            if (!checkdate($month, $day, $year)) {
                throw new InvalidArgumentException('Data de movimentacao invalida.');
            }

            if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59 || $second < 0 || $second > 59) {
                throw new InvalidArgumentException('Hora de movimentacao invalida.');
            }

            return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new InvalidArgumentException('Data de movimentacao invalida.');
        }

        [$year, $month, $day] = array_map('intval', explode('-', $date));
        if (!checkdate($month, $day, $year)) {
            throw new InvalidArgumentException('Data de movimentacao invalida.');
        }

        return $date . ' ' . date('H:i:s');
    }

    private function hasColumn(string $columnName): bool
    {
        try {
            $this->conn->query('SELECT m.' . $columnName . ' FROM movimentacoes m LIMIT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    private function hasEquipamentoColumn(string $columnName): bool
    {
        try {
            $this->conn->query('SELECT e.' . $columnName . ' FROM equipamentos e LIMIT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    private function normalizeMovementType(string $tipo): string
    {
        $tipo = strtolower(trim($tipo));

        return match ($tipo) {
            'saida' => 'entrega',
            'entrada' => 'devolucao',
            default => $tipo,
        };
    }

    private function getSaldoNaMao(int $tecnicoId, int $equipamentoId): int
    {
        $stmt = $this->conn->prepare(
            "SELECT COALESCE(SUM(CASE
                    WHEN tipo IN ('entrega', 'saida') THEN quantidade
                    WHEN tipo = 'recolhimento' THEN quantidade
                    WHEN tipo IN ('uso', 'uso_teste', 'devolucao', 'entrada', 'recolhimento_defeito') THEN -quantidade
                    ELSE 0
                END), 0) AS saldo_mao
             FROM movimentacoes
             WHERE tecnico_id = :tecnico_id AND equipamento_id = :equipamento_id"
        );
        $stmt->execute([
            'tecnico_id' => $tecnicoId,
            'equipamento_id' => $equipamentoId,
        ]);

        $saldoDireto = (int) ($stmt->fetch()['saldo_mao'] ?? 0);

        $snapshotSaldo = 0;
        if ($this->hasColumn('equipamento_nome_snapshot') && $this->hasColumn('equipamento_tipo_snapshot')) {
            $selectCodigoBarras = $this->hasEquipamentoColumn('codigo_barras') ? ', codigo_barras' : '';
            $stmtEquip = $this->conn->prepare('SELECT nome, tipo' . $selectCodigoBarras . ' FROM equipamentos WHERE id = :id');
            $stmtEquip->execute(['id' => $equipamentoId]);
            $equipamento = $stmtEquip->fetch();

            if ($equipamento) {
                $equipNome = trim((string) ($equipamento['nome'] ?? ''));
                $equipTipo = trim((string) ($equipamento['tipo'] ?? ''));
                $equipCodigo = trim((string) ($equipamento['codigo_barras'] ?? ''));

                if ($equipNome !== '' && $equipTipo !== '') {
                    $matchByCode = $equipCodigo !== '' && $this->hasColumn('equipamento_codigo_barras_snapshot');
                    $snapshotWhere = $matchByCode
                        ? 'm.equipamento_codigo_barras_snapshot = :equip_codigo'
                        : 'm.equipamento_nome_snapshot = :equip_nome AND m.equipamento_tipo_snapshot = :equip_tipo';

                    $snapshotSql = "SELECT COALESCE(SUM(CASE
                                            WHEN m.tipo IN ('entrega', 'saida') THEN m.quantidade
                                            WHEN m.tipo = 'recolhimento' THEN m.quantidade
                                            WHEN m.tipo IN ('uso', 'uso_teste', 'devolucao', 'entrada', 'recolhimento_defeito') THEN -m.quantidade
                                            ELSE 0
                                        END), 0) AS saldo_mao
                                    FROM movimentacoes m
                                    WHERE m.tecnico_id = :tecnico_id
                                      AND m.equipamento_id IS NULL
                                      AND ({$snapshotWhere})";

                    $snapshotStmt = $this->conn->prepare($snapshotSql);
                    $snapshotParams = [
                        'tecnico_id' => $tecnicoId,
                    ];

                    if ($matchByCode) {
                        $snapshotParams['equip_codigo'] = $equipCodigo;
                    } else {
                        $snapshotParams['equip_nome'] = $equipNome;
                        $snapshotParams['equip_tipo'] = $equipTipo;
                    }

                    $snapshotStmt->execute($snapshotParams);
                    $snapshotSaldo = (int) ($snapshotStmt->fetch()['saldo_mao'] ?? 0);
                }
            }
        }

        return $saldoDireto + $snapshotSaldo;
    }

    private function getSaldoEmCampo(int $tecnicoId, int $equipamentoId): int
    {
        $stmt = $this->conn->prepare(
            "SELECT COALESCE(SUM(CASE
                    WHEN tipo IN ('uso', 'uso_teste') THEN quantidade
                    WHEN tipo = 'recolhimento' THEN -quantidade
                    ELSE 0
                END), 0) AS saldo_campo
             FROM movimentacoes
             WHERE tecnico_id = :tecnico_id AND equipamento_id = :equipamento_id"
        );
        $stmt->execute([
            'tecnico_id' => $tecnicoId,
            'equipamento_id' => $equipamentoId,
        ]);

        return (int) ($stmt->fetch()['saldo_campo'] ?? 0);
    }

    private function getSaldoEmCampoGlobal(int $equipamentoId): int
    {
        $stmt = $this->conn->prepare(
            "SELECT COALESCE(SUM(CASE
                    WHEN tipo IN ('uso', 'uso_teste') THEN quantidade
                    WHEN tipo = 'recolhimento' THEN -quantidade
                    ELSE 0
                END), 0) AS saldo_campo
             FROM movimentacoes
             WHERE equipamento_id = :equipamento_id"
        );
        $stmt->execute([
            'equipamento_id' => $equipamentoId,
        ]);

        return (int) ($stmt->fetch()['saldo_campo'] ?? 0);
    }

    private function resolveEquipmentIdFromSnapshot(string $nome, string $tipo, ?string $codigoBarras): int
    {
        $cacheKey = strtolower(trim($nome)) . '|' . strtolower(trim($tipo)) . '|' . strtolower(trim((string) $codigoBarras));
        if (array_key_exists($cacheKey, $this->snapshotEquipmentCache)) {
            return $this->snapshotEquipmentCache[$cacheKey];
        }

        $nome = trim($nome);
        $tipo = trim($tipo);
        $codigo = trim((string) $codigoBarras);

        if ($nome === '' || $tipo === '') {
            $this->snapshotEquipmentCache[$cacheKey] = 0;
            return 0;
        }

        if ($codigo !== '' && $this->hasEquipamentoColumn('codigo_barras')) {
            $stmtCodigo = $this->conn->prepare('SELECT id FROM equipamentos WHERE codigo_barras = :codigo LIMIT 1');
            $stmtCodigo->execute(['codigo' => $codigo]);
            $foundByCode = (int) ($stmtCodigo->fetch()['id'] ?? 0);
            if ($foundByCode > 0) {
                $this->snapshotEquipmentCache[$cacheKey] = $foundByCode;
                return $foundByCode;
            }
        }

        $stmtNomeTipo = $this->conn->prepare('SELECT id FROM equipamentos WHERE nome = :nome AND tipo = :tipo ORDER BY id DESC');
        $stmtNomeTipo->execute([
            'nome' => $nome,
            'tipo' => $tipo,
        ]);

        $candidates = $stmtNomeTipo->fetchAll();
        $found = count($candidates) === 1 ? (int) ($candidates[0]['id'] ?? 0) : 0;
        $this->snapshotEquipmentCache[$cacheKey] = $found;
        return $found;
    }

    private function calculateStockDelta(string $tipo, int $quantidade): int
    {
        $tipo = $this->normalizeMovementType($tipo);

        if ($tipo === 'entrega') {
            return -$quantidade;
        }

        if ($tipo === 'recolhimento') {
            return 0;
        }

        if ($tipo === 'recolhimento_defeito') {
            return 0;
        }

        if ($tipo === 'uso' || $tipo === 'uso_teste') {
            return 0;
        }

        if ($tipo === 'devolucao') {
            return $quantidade;
        }

        return 0;
    }

    private function appendSystemObservation(?string $observacoes, string $systemNote): ?string
    {
        $systemNote = trim($systemNote);
        if ($systemNote === '') {
            return $observacoes;
        }

        $base = trim((string) ($observacoes ?? ''));
        if ($base !== '' && strpos($base, $systemNote) !== false) {
            return $base;
        }

        if ($base === '') {
            return $systemNote;
        }

        return $base . ' | ' . $systemNote;
    }

    private function appendHistoryEntry(string $history, string $entry): string
    {
        $entry = trim($entry);
        if ($entry === '') {
            return trim($history);
        }

        $stamp = '[' . date('d/m/Y H:i') . '] ' . $entry;
        $history = trim($history);

        if ($history === '') {
            return $stamp;
        }

        return $history . PHP_EOL . PHP_EOL . $stamp;
    }

    public function reportRecolhimentosSemLastro(?string $selectedDate = null): array
    {
        if (!$this->hasColumn('observacoes')) {
            return [];
        }

        $selectedDate = $this->normalizeDate($selectedDate);
        $selectEquipamentoNome = $this->hasColumn('equipamento_nome_snapshot')
            ? "COALESCE(e.nome, m.equipamento_nome_snapshot, 'Equipamento removido') AS equipamento_nome,"
            : "COALESCE(e.nome, 'Equipamento removido') AS equipamento_nome,";
        $selectEquipamentoTipo = $this->hasColumn('equipamento_tipo_snapshot')
            ? "COALESCE(e.tipo, m.equipamento_tipo_snapshot, 'indefinido') AS equipamento_tipo"
            : "COALESCE(e.tipo, 'indefinido') AS equipamento_tipo";

        $where = "m.tipo = 'recolhimento'
                  AND m.observacoes LIKE :marker";
        $params = ['marker' => '%[SEM_LASTRO_CAMPO:%'];

        if ($selectedDate !== null) {
            $where .= ' AND DATE(m.data_movimentacao) = :selected_date';
            $params['selected_date'] = $selectedDate;
        }

        $sql = "SELECT m.id,
                       m.tecnico_id,
                       COALESCE(t.nome, 'Sem tecnico') AS tecnico_nome,
                       m.quantidade,
                       m.tipo,
                       m.observacoes,
                       m.data_movimentacao,
                       {$selectEquipamentoNome}
                       {$selectEquipamentoTipo}
                FROM movimentacoes m
                LEFT JOIN tecnicos t ON t.id = m.tecnico_id
                LEFT JOIN equipamentos e ON e.id = m.equipamento_id
                WHERE {$where}
                ORDER BY m.data_movimentacao DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function reportMovementIntegrityIssues(?string $selectedDate = null): array
    {
        $selectedDate = $this->normalizeDate($selectedDate);

        $negativosEstoque = $this->conn
            ->query("SELECT id, nome, tipo, quantidade
                     FROM equipamentos
                     WHERE quantidade < 0
                     ORDER BY quantidade ASC, nome ASC")
            ->fetchAll();

        $filtroDataMov = $selectedDate !== null ? ' AND DATE(m.data_movimentacao) <= :selected_date' : '';
        $paramsMov = $selectedDate !== null ? ['selected_date' => $selectedDate] : [];

        $sqlSaldoMaoNegativo = "SELECT
                    m.tecnico_id,
                    COALESCE(t.nome, 'Sem tecnico') AS tecnico_nome,
                    m.equipamento_id,
                    COALESCE(e.nome, MAX(m.equipamento_nome_snapshot), 'Equipamento removido') AS equipamento_nome,
                    COALESCE(e.tipo, MAX(m.equipamento_tipo_snapshot), 'indefinido') AS equipamento_tipo,
                    SUM(CASE
                        WHEN m.tipo IN ('entrega', 'saida') THEN m.quantidade
                        WHEN m.tipo = 'recolhimento' THEN m.quantidade
                        WHEN m.tipo IN ('uso', 'uso_teste', 'devolucao', 'entrada', 'recolhimento_defeito') THEN -m.quantidade
                        ELSE 0
                    END) AS saldo_mao
                FROM movimentacoes m
                LEFT JOIN tecnicos t ON t.id = m.tecnico_id
                LEFT JOIN equipamentos e ON e.id = m.equipamento_id
                WHERE m.tecnico_id IS NOT NULL {$filtroDataMov}
                GROUP BY m.tecnico_id, tecnico_nome, m.equipamento_id
                HAVING saldo_mao < 0
                ORDER BY saldo_mao ASC, tecnico_nome ASC";

        $stmtSaldoMaoNegativo = $this->conn->prepare($sqlSaldoMaoNegativo);
        $stmtSaldoMaoNegativo->execute($paramsMov);
        $negativosMao = $stmtSaldoMaoNegativo->fetchAll();

        $campoRecolhimentoExpr = "-m.quantidade";
        if ($this->hasColumn('observacoes')) {
            $campoRecolhimentoExpr = "CASE
                        WHEN LOWER(COALESCE(m.observacoes, '')) LIKE '%[sem_lastro_campo:%' THEN 0
                        ELSE -m.quantidade
                    END";
        }

        $sqlSaldoCampoGlobalNegativo = "SELECT
                    m.equipamento_id,
                    COALESCE(e.nome, MAX(m.equipamento_nome_snapshot), 'Equipamento removido') AS equipamento_nome,
                    COALESCE(e.tipo, MAX(m.equipamento_tipo_snapshot), 'indefinido') AS equipamento_tipo,
                    SUM(CASE
                        WHEN m.tipo IN ('uso', 'uso_teste') THEN m.quantidade
                        WHEN m.tipo = 'recolhimento' THEN {$campoRecolhimentoExpr}
                        ELSE 0
                    END) AS saldo_campo_global
                FROM movimentacoes m
                LEFT JOIN equipamentos e ON e.id = m.equipamento_id
                WHERE 1=1 {$filtroDataMov}
                GROUP BY m.equipamento_id
                HAVING saldo_campo_global < 0
                ORDER BY saldo_campo_global ASC, equipamento_nome ASC";

        $stmtSaldoCampoGlobalNegativo = $this->conn->prepare($sqlSaldoCampoGlobalNegativo);
        $stmtSaldoCampoGlobalNegativo->execute($paramsMov);
        $negativosCampoGlobal = $stmtSaldoCampoGlobalNegativo->fetchAll();

        return [
            'estoque_negativo' => $negativosEstoque,
            'saldo_mao_negativo' => $negativosMao,
            'saldo_campo_global_negativo' => $negativosCampoGlobal,
            'total_issues' => count($negativosEstoque) + count($negativosMao) + count($negativosCampoGlobal),
        ];
    }

    public function allWithRelations(?string $selectedDate = null): array
    {
        $selectedDate = $this->normalizeDate($selectedDate);
        $selectLocal = $this->hasColumn('local_uso') ? 'm.local_uso,' : "'' AS local_uso,";
        $selectObservacoes = $this->hasColumn('observacoes') ? 'm.observacoes,' : "'' AS observacoes,";
        $equipamentoNomeSelect = $this->hasColumn('equipamento_nome_snapshot')
            ? "COALESCE(e.nome, m.equipamento_nome_snapshot, 'Equipamento removido') AS equipamento_nome,"
            : "COALESCE(e.nome, 'Equipamento removido') AS equipamento_nome,";
        $equipamentoTipoSelect = $this->hasColumn('equipamento_tipo_snapshot')
            ? "COALESCE(e.tipo, m.equipamento_tipo_snapshot, 'indefinido') AS equipamento_tipo"
            : "COALESCE(e.tipo, 'indefinido') AS equipamento_tipo";

        $where = '';
        $params = [];
        if ($selectedDate !== null) {
            $where = 'WHERE DATE(m.data_movimentacao) = :selected_date';
            $params['selected_date'] = $selectedDate;
        }

        $sql = "SELECT m.id,
                       m.tecnico_id,
                       m.quantidade,
                       CASE
                           WHEN m.tipo = 'saida' THEN 'entrega'
                           WHEN m.tipo = 'entrada' THEN 'devolucao'
                           ELSE m.tipo
                       END AS tipo,
                       {$selectLocal}
                   {$selectObservacoes}
                       m.data_movimentacao,
                       COALESCE(t.nome, 'Tecnico removido') AS tecnico_nome,
                      {$equipamentoNomeSelect}
                      {$equipamentoTipoSelect}
                FROM movimentacoes m
                LEFT JOIN tecnicos t ON t.id = m.tecnico_id
                  LEFT JOIN equipamentos e ON e.id = m.equipamento_id
                {$where}
                ORDER BY m.data_movimentacao DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function reportConsumoPorTecnico(): array
    {
        $sql = "SELECT t.nome AS tecnico,
                       SUM(CASE WHEN m.tipo IN ('uso', 'uso_teste') THEN m.quantidade ELSE 0 END) AS total_consumido
                FROM tecnicos t
                LEFT JOIN movimentacoes m ON m.tecnico_id = t.id
                GROUP BY t.id, t.nome
                ORDER BY total_consumido DESC, t.nome ASC";

        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }

    public function reportUsoPorTecnicoPeriodo(?int $tecnicoId, string $dataInicio, string $dataFim, ?string $equipamentoTipo = null): array
    {
        $dataInicio = trim($dataInicio);
        $dataFim = trim($dataFim);

        if (
            preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataInicio) !== 1
            || preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFim) !== 1
        ) {
            throw new InvalidArgumentException('Periodo invalido para consulta de uso por tecnico.');
        }

        $equipamentoNomeExpr = $this->hasColumn('equipamento_nome_snapshot')
            ? "COALESCE(e.nome, m.equipamento_nome_snapshot, 'Equipamento removido')"
            : "COALESCE(e.nome, 'Equipamento removido')";
        $equipamentoTipoExpr = $this->hasColumn('equipamento_tipo_snapshot')
            ? "COALESCE(e.tipo, m.equipamento_tipo_snapshot, 'indefinido')"
            : "COALESCE(e.tipo, 'indefinido')";

        $where = [
            "m.tipo IN ('uso', 'uso_teste')",
            'DATE(m.data_movimentacao) BETWEEN :data_inicio AND :data_fim',
        ];
        $params = [
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
        ];

        if ($tecnicoId !== null && $tecnicoId > 0) {
            $where[] = 'm.tecnico_id = :tecnico_id';
            $params['tecnico_id'] = $tecnicoId;
        }

        $equipamentoTipo = $equipamentoTipo !== null ? trim($equipamentoTipo) : null;
        if ($equipamentoTipo !== null && $equipamentoTipo !== '') {
            $where[] = 'LOWER(' . $equipamentoTipoExpr . ') = :equipamento_tipo';
            $params['equipamento_tipo'] = strtolower($equipamentoTipo);
        }

        $sql = "SELECT
                    m.tecnico_id,
                    COALESCE(t.nome, 'Tecnico removido') AS tecnico_nome,
                    {$equipamentoNomeExpr} AS equipamento_nome,
                    {$equipamentoTipoExpr} AS equipamento_tipo,
                    SUM(m.quantidade) AS total_usado,
                    COUNT(*) AS total_registros
                FROM movimentacoes m
                LEFT JOIN tecnicos t ON t.id = m.tecnico_id
                LEFT JOIN equipamentos e ON e.id = m.equipamento_id
                WHERE " . implode(' AND ', $where) . "
                GROUP BY m.tecnico_id, tecnico_nome, equipamento_nome, equipamento_tipo
                ORDER BY tecnico_nome ASC, total_usado DESC, equipamento_nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function reportEquipamentosMaisUsados(): array
    {
         $sql = "SELECT e.nome,
                  e.tipo,
                                    SUM(CASE WHEN m.tipo IN ('uso', 'uso_teste') THEN m.quantidade ELSE 0 END) AS total_uso,
                  SUM(CASE WHEN m.tipo IN ('entrega', 'saida') THEN m.quantidade ELSE 0 END) AS total_entrega
                FROM equipamentos e
                LEFT JOIN movimentacoes m ON m.equipamento_id = e.id
                GROUP BY e.id, e.nome, e.tipo
              ORDER BY total_uso DESC, total_entrega DESC, e.nome ASC";

        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }

    public function reportEstoqueAtual(): array
    {
        $sql = "SELECT id, nome, tipo, quantidade
                FROM equipamentos
                ORDER BY
                    CASE tipo
                        WHEN 'roteador' THEN 1
                        WHEN 'onu' THEN 2
                        WHEN 'ont' THEN 3
                        WHEN 'conector_fibra' THEN 4
                        WHEN 'insumos' THEN 5
                        ELSE 99
                    END,
                    nome ASC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }

    public function reportReposicaoDiaria(): array
    {
        $sql = "SELECT e.nome,
                       e.tipo,
                       e.quantidade AS estoque_atual,
                       ROUND(AVG(d.consumo_diario), 2) AS media_consumo_dia,
                       CASE
                           WHEN ROUND(AVG(d.consumo_diario), 2) > 0
                           THEN CEIL(ROUND(AVG(d.consumo_diario), 2) * 7)
                           ELSE 0
                       END AS sugestao_reposicao_7_dias
                FROM equipamentos e
                LEFT JOIN (
                    SELECT equipamento_id,
                           DATE(data_movimentacao) AS dia,
                              SUM(CASE WHEN tipo IN ('uso', 'uso_teste') THEN quantidade ELSE 0 END) AS consumo_diario
                    FROM movimentacoes
                    GROUP BY equipamento_id, DATE(data_movimentacao)
                ) d ON d.equipamento_id = e.id
                GROUP BY e.id, e.nome, e.tipo, e.quantidade
                ORDER BY sugestao_reposicao_7_dias DESC, e.nome ASC";

        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }

    public function getPurchaseSupportData(): array
    {
        $config = $this->getPurchaseSupportConfig();
        $equipamentos = $this->getEquipamentosForPurchaseSupport();
        $consumoPorItem = $this->getPurchaseSupportItemConfigMap();

        $prazoReposicao = max(1, (int) ($config['prazo_reposicao_dias'] ?? 1));
        $coberturaCompraDias = 30;
        $itens = [];
        $alertasCompra = [];

        foreach ($equipamentos as $equipamento) {
            $equipamentoId = (int) ($equipamento['id'] ?? 0);
            if ($equipamentoId <= 0) {
                continue;
            }

            $consumoDia = max(0, (int) ($consumoPorItem[$equipamentoId] ?? 0));
            $estoqueAtual = max(0, (int) ($equipamento['quantidade'] ?? 0));
            $estoqueMinimo = $consumoDia * $prazoReposicao;
            $estoqueObjetivoCompra = $consumoDia * $coberturaCompraDias;
            $pontoCompra = $estoqueMinimo;
            $deveComprarAgora = $consumoDia > 0 && $estoqueAtual <= $pontoCompra;
            $quantidadeCompraSugerida = max(0, $estoqueObjetivoCompra - $estoqueAtual);
            $diasCobertura = $consumoDia > 0 ? round($estoqueAtual / $consumoDia, 1) : null;

            $diasAtePontoCompra = null;
            if ($consumoDia > 0 && $estoqueAtual > $pontoCompra) {
                $diasAtePontoCompra = (int) floor(($estoqueAtual - $pontoCompra) / $consumoDia);
            }

            $item = [
                'equipamento_id' => $equipamentoId,
                'tipo' => (string) ($equipamento['tipo'] ?? 'indefinido'),
                'label' => (string) ($equipamento['nome'] ?? 'Equipamento'),
                'consumo_dia' => $consumoDia,
                'estoque_atual' => $estoqueAtual,
                'prazo_reposicao_dias' => $prazoReposicao,
                'estoque_minimo' => $estoqueMinimo,
                'estoque_objetivo_compra' => $estoqueObjetivoCompra,
                'cobertura_compra_dias' => $coberturaCompraDias,
                'ponto_compra' => $pontoCompra,
                'dias_cobertura' => $diasCobertura,
                'deve_comprar_agora' => $deveComprarAgora,
                'dias_ate_ponto_compra' => $diasAtePontoCompra,
                'quantidade_compra_sugerida' => $quantidadeCompraSugerida,
            ];

            $itens[] = $item;
            if ($deveComprarAgora) {
                $alertasCompra[] = $item;
            }
        }

        return [
            'config' => $config,
            'itens' => $itens,
            'alertas_compra' => $alertasCompra,
            'equipamentos' => $equipamentos,
            'resumo' => [
                'total_alertas' => count($alertasCompra),
                'total_itens' => count($itens),
                'prazo_reposicao_dias' => $prazoReposicao,
                'cobertura_compra_dias' => $coberturaCompraDias,
            ],
        ];
    }

    public function getPurchaseSupportConfig(): array
    {
        $this->ensurePurchaseSupportTable();

        $stmt = $this->conn->query('SELECT * FROM apoio_compra_config WHERE id = 1 LIMIT 1');
        $row = $stmt->fetch();

        if (!$row) {
            $default = [
                'consumo_roteador_dia' => 6,
                'consumo_onu_dia' => 6,
                'consumo_conector_dia' => 12,
                'prazo_reposicao_dias' => 3,
            ];

            $stmtInsert = $this->conn->prepare(
                'INSERT INTO apoio_compra_config (
                    id,
                    consumo_roteador_dia,
                    consumo_onu_dia,
                    consumo_conector_dia,
                    prazo_reposicao_dias,
                    updated_at
                ) VALUES (
                    1,
                    :consumo_roteador_dia,
                    :consumo_onu_dia,
                    :consumo_conector_dia,
                    :prazo_reposicao_dias,
                    CURRENT_TIMESTAMP
                )
                ON DUPLICATE KEY UPDATE
                    consumo_roteador_dia = VALUES(consumo_roteador_dia),
                    consumo_onu_dia = VALUES(consumo_onu_dia),
                    consumo_conector_dia = VALUES(consumo_conector_dia),
                    prazo_reposicao_dias = VALUES(prazo_reposicao_dias),
                    updated_at = CURRENT_TIMESTAMP'
            );

            $stmtInsert->execute([
                'consumo_roteador_dia' => $default['consumo_roteador_dia'],
                'consumo_onu_dia' => $default['consumo_onu_dia'],
                'consumo_conector_dia' => $default['consumo_conector_dia'],
                'prazo_reposicao_dias' => $default['prazo_reposicao_dias'],
            ]);

            return $default;
        }

        return [
            'consumo_roteador_dia' => max(0, (int) ($row['consumo_roteador_dia'] ?? 0)),
            'consumo_onu_dia' => max(0, (int) ($row['consumo_onu_dia'] ?? 0)),
            'consumo_conector_dia' => max(0, (int) ($row['consumo_conector_dia'] ?? 0)),
            'prazo_reposicao_dias' => max(1, (int) ($row['prazo_reposicao_dias'] ?? 1)),
        ];
    }

    public function savePurchaseSupportConfig(array $config, array $consumoItem = []): void
    {
        $this->ensurePurchaseSupportTable();
        $this->ensurePurchaseSupportItemTable();

        $atual = $this->getPurchaseSupportConfig();

        $stmt = $this->conn->prepare(
            'INSERT INTO apoio_compra_config (
                id,
                consumo_roteador_dia,
                consumo_onu_dia,
                consumo_conector_dia,
                prazo_reposicao_dias,
                updated_at
            ) VALUES (
                1,
                :consumo_roteador_dia,
                :consumo_onu_dia,
                :consumo_conector_dia,
                :prazo_reposicao_dias,
                CURRENT_TIMESTAMP
            )
            ON DUPLICATE KEY UPDATE
                consumo_roteador_dia = VALUES(consumo_roteador_dia),
                consumo_onu_dia = VALUES(consumo_onu_dia),
                consumo_conector_dia = VALUES(consumo_conector_dia),
                prazo_reposicao_dias = VALUES(prazo_reposicao_dias),
                updated_at = CURRENT_TIMESTAMP'
        );

        $stmt->execute([
            'consumo_roteador_dia' => max(0, (int) ($config['consumo_roteador_dia'] ?? $atual['consumo_roteador_dia'] ?? 0)),
            'consumo_onu_dia' => max(0, (int) ($config['consumo_onu_dia'] ?? $atual['consumo_onu_dia'] ?? 0)),
            'consumo_conector_dia' => max(0, (int) ($config['consumo_conector_dia'] ?? $atual['consumo_conector_dia'] ?? 0)),
            'prazo_reposicao_dias' => max(1, (int) ($config['prazo_reposicao_dias'] ?? $atual['prazo_reposicao_dias'] ?? 1)),
        ]);

        $stmtItem = $this->conn->prepare(
            'INSERT INTO apoio_compra_item_config (equipamento_id, consumo_dia, updated_at)
             VALUES (:equipamento_id, :consumo_dia, CURRENT_TIMESTAMP)
             ON DUPLICATE KEY UPDATE
                consumo_dia = VALUES(consumo_dia),
                updated_at = CURRENT_TIMESTAMP'
        );

        foreach ($consumoItem as $equipamentoId => $consumoDia) {
            $equipamentoId = (int) $equipamentoId;
            if ($equipamentoId <= 0) {
                continue;
            }

            $stmtItem->execute([
                'equipamento_id' => $equipamentoId,
                'consumo_dia' => max(0, (int) $consumoDia),
            ]);
        }
    }

    public function reportCardsTecnicos(?string $selectedDate = null): array
    {
        $selectedDate = $this->normalizeDate($selectedDate);
        $isFilteredByDate = $selectedDate !== null;
        $dailyDate = $isFilteredByDate ? $selectedDate : date('Y-m-d');
        $joinDateFilter = $isFilteredByDate ? ' AND DATE(m.data_movimentacao) = :selected_date' : '';

        $tecnicoSql =
            "SELECT t.id,
                    t.nome,
                    SUM(CASE WHEN m.tipo IN ('entrega', 'saida') THEN m.quantidade ELSE 0 END) AS total_entrega,
                    SUM(CASE WHEN m.tipo IN ('uso', 'uso_teste') THEN m.quantidade ELSE 0 END) AS total_uso,
                    SUM(CASE WHEN m.tipo = 'uso_teste' THEN m.quantidade ELSE 0 END) AS total_uso_teste,
                    SUM(CASE WHEN m.tipo IN ('devolucao', 'entrada') THEN m.quantidade ELSE 0 END) AS total_devolvido,
                    SUM(CASE WHEN m.tipo IN ('recolhimento', 'recolhimento_defeito') THEN m.quantidade ELSE 0 END) AS total_recolhido
             FROM tecnicos t
               LEFT JOIN movimentacoes m ON m.tecnico_id = t.id{$joinDateFilter}
             GROUP BY t.id, t.nome
             ORDER BY t.nome ASC";

        $tecnicoStmt = $this->conn->prepare($tecnicoSql);
           $tecnicoStmt->execute($isFilteredByDate ? ['selected_date' => $dailyDate] : []);
        $tecnicos = $tecnicoStmt->fetchAll();

        $cards = [];
        foreach ($tecnicos as $tec) {
            $cards[(int) $tec['id']] = [
                'tecnico_id' => (int) $tec['id'],
                'tecnico_nome' => $tec['nome'],
                'total_entrega' => (int) $tec['total_entrega'],
                'total_uso' => (int) $tec['total_uso'],
                'total_uso_teste' => (int) ($tec['total_uso_teste'] ?? 0),
                'total_devolvido' => (int) $tec['total_devolvido'],
                'total_recolhido' => (int) ($tec['total_recolhido'] ?? 0),
                'saldo_total_mao' => 0,
                'saldo_total_campo' => 0,
                'equipamentos_mao' => [],
                'equipamentos_campo' => [],
                'usos_recentes' => [],
                'estoque_seguro' => $this->getSafeStockTargets(),
                'saldo_por_categoria' => [
                    'roteador' => 0,
                    'onu' => 0,
                    'conector_fibra' => 0,
                    'conector_rj' => 0,
                    'esticador' => 0,
                ],
                'reposicao_proximo_dia' => [],
                'repor_total' => 0,
                'estoque_seguro_ok' => true,
            ];
        }

        $selectEquipamentoNomeSaldo = $this->hasColumn('equipamento_nome_snapshot')
            ? "MAX(COALESCE(e.nome, m.equipamento_nome_snapshot, 'Equipamento removido')) AS equipamento_nome,"
            : "MAX(COALESCE(e.nome, 'Equipamento removido')) AS equipamento_nome,";
        $selectEquipamentoTipoSaldo = $this->hasColumn('equipamento_tipo_snapshot')
            ? "MAX(COALESCE(e.tipo, m.equipamento_tipo_snapshot, 'indefinido')) AS equipamento_tipo,"
            : "MAX(COALESCE(e.tipo, 'indefinido')) AS equipamento_tipo,";
        $selectCodigoBarrasSaldo = $this->hasColumn('equipamento_codigo_barras_snapshot')
            ? "MAX(COALESCE(e.codigo_barras, m.equipamento_codigo_barras_snapshot)) AS codigo_barras,"
            : ($this->hasEquipamentoColumn('codigo_barras')
                ? 'MAX(e.codigo_barras) AS codigo_barras,'
                : 'NULL AS codigo_barras,');

        $groupBySaldo = ['m.tecnico_id', 'm.equipamento_id'];
        if ($this->hasColumn('equipamento_nome_snapshot')) {
            $groupBySaldo[] = 'm.equipamento_nome_snapshot';
        }
        if ($this->hasColumn('equipamento_tipo_snapshot')) {
            $groupBySaldo[] = 'm.equipamento_tipo_snapshot';
        }
        if ($this->hasColumn('equipamento_codigo_barras_snapshot')) {
            $groupBySaldo[] = 'm.equipamento_codigo_barras_snapshot';
        }

        $saldoSql = "SELECT m.tecnico_id,
                            m.equipamento_id,
                            {$selectEquipamentoNomeSaldo}
                            {$selectEquipamentoTipoSaldo}
                            {$selectCodigoBarrasSaldo}
                            SUM(CASE
                                    WHEN m.tipo IN ('entrega', 'saida') THEN m.quantidade
                                    WHEN m.tipo = 'recolhimento' THEN m.quantidade
                                    WHEN m.tipo IN ('uso', 'uso_teste', 'devolucao', 'entrada', 'recolhimento_defeito') THEN -m.quantidade
                                    ELSE 0
                                END) AS saldo_mao
                     FROM movimentacoes m
                     LEFT JOIN equipamentos e ON e.id = m.equipamento_id
                             " . ($isFilteredByDate ? "WHERE DATE(m.data_movimentacao) <= :selected_date" : '') . "
                         GROUP BY " . implode(', ', $groupBySaldo);

        $saldoStmt = $this->conn->prepare($saldoSql);
                $saldoStmt->execute($isFilteredByDate ? ['selected_date' => $dailyDate] : []);
        $saldos = $saldoStmt->fetchAll();

        $saldoMap = [];
        $equipInfoMap = [];
        $aggregatedByEquipment = [];
        foreach ($saldos as $row) {
            $tecnicoId = (int) $row['tecnico_id'];
            if (!isset($cards[$tecnicoId])) {
                continue;
            }

            $equipamentoId = (int) $row['equipamento_id'];
            $equipamentoNome = (string) ($row['equipamento_nome'] ?? 'Equipamento removido');
            $equipamentoTipo = (string) ($row['equipamento_tipo'] ?? 'indefinido');
            $codigoBarras = $row['codigo_barras'] ?? null;
            $saldoBruto = (int) ($row['saldo_mao'] ?? 0);

            if ($equipamentoId <= 0) {
                $equipamentoId = $this->resolveEquipmentIdFromSnapshot($equipamentoNome, $equipamentoTipo, is_scalar($codigoBarras) ? (string) $codigoBarras : null);
            }

            if ($equipamentoId <= 0) {
                continue;
            }

            if (!isset($aggregatedByEquipment[$tecnicoId][$equipamentoId])) {
                $aggregatedByEquipment[$tecnicoId][$equipamentoId] = [
                    'nome' => $equipamentoNome,
                    'tipo' => $equipamentoTipo,
                    'codigo_barras' => $codigoBarras,
                    'saldo_mao' => 0,
                ];
            }

            $aggregatedByEquipment[$tecnicoId][$equipamentoId]['saldo_mao'] += $saldoBruto;

            if (
                ($aggregatedByEquipment[$tecnicoId][$equipamentoId]['codigo_barras'] === null || $aggregatedByEquipment[$tecnicoId][$equipamentoId]['codigo_barras'] === '')
                && $codigoBarras !== null
                && $codigoBarras !== ''
            ) {
                $aggregatedByEquipment[$tecnicoId][$equipamentoId]['codigo_barras'] = $codigoBarras;
            }
        }

        foreach ($aggregatedByEquipment as $tecnicoId => $equipamentosDoTecnico) {
            foreach ($equipamentosDoTecnico as $equipamentoId => $agregado) {
                $saldo = max(0, (int) ($agregado['saldo_mao'] ?? 0));
                $saldoMap[$tecnicoId][$equipamentoId] = $saldo;
                $equipInfoMap[$tecnicoId][$equipamentoId] = [
                    'nome' => (string) ($agregado['nome'] ?? 'Equipamento removido'),
                    'tipo' => (string) ($agregado['tipo'] ?? 'indefinido'),
                    'codigo_barras' => $agregado['codigo_barras'] ?? null,
                ];

                if ($saldo > 0) {
                    $cards[$tecnicoId]['equipamentos_mao'][] = [
                        'equipamento_id' => $equipamentoId,
                        'nome' => (string) ($agregado['nome'] ?? 'Equipamento removido'),
                        'tipo' => (string) ($agregado['tipo'] ?? 'indefinido'),
                        'codigo_barras' => $agregado['codigo_barras'] ?? null,
                        'saldo_mao' => $saldo,
                    ];
                    $cards[$tecnicoId]['saldo_total_mao'] += $saldo;
                }
            }
        }

        $campoSql = "SELECT m.tecnico_id,
                            m.equipamento_id,
                            {$selectEquipamentoNomeSaldo}
                            {$selectEquipamentoTipoSaldo}
                            {$selectCodigoBarrasSaldo}
                            SUM(CASE
                                    WHEN m.tipo IN ('uso', 'uso_teste') THEN m.quantidade
                                    WHEN m.tipo = 'recolhimento' THEN -m.quantidade
                                    ELSE 0
                                END) AS saldo_campo
                     FROM movimentacoes m
                     LEFT JOIN equipamentos e ON e.id = m.equipamento_id
                             " . ($isFilteredByDate ? "WHERE DATE(m.data_movimentacao) <= :selected_date" : '') . "
                         GROUP BY " . implode(', ', $groupBySaldo);

        $campoStmt = $this->conn->prepare($campoSql);
        $campoStmt->execute($isFilteredByDate ? ['selected_date' => $dailyDate] : []);

        $campoAggregatedByEquipment = [];
        foreach ($campoStmt->fetchAll() as $row) {
            $tecnicoId = (int) $row['tecnico_id'];
            if (!isset($cards[$tecnicoId])) {
                continue;
            }

            $equipamentoId = (int) $row['equipamento_id'];
            $equipamentoNome = (string) ($row['equipamento_nome'] ?? 'Equipamento removido');
            $equipamentoTipo = (string) ($row['equipamento_tipo'] ?? 'indefinido');
            $codigoBarras = $row['codigo_barras'] ?? null;
            $saldoBrutoCampo = (int) ($row['saldo_campo'] ?? 0);

            if ($equipamentoId <= 0) {
                $equipamentoId = $this->resolveEquipmentIdFromSnapshot($equipamentoNome, $equipamentoTipo, is_scalar($codigoBarras) ? (string) $codigoBarras : null);
            }

            if ($equipamentoId <= 0) {
                continue;
            }

            if (!isset($campoAggregatedByEquipment[$tecnicoId][$equipamentoId])) {
                $campoAggregatedByEquipment[$tecnicoId][$equipamentoId] = [
                    'nome' => $equipamentoNome,
                    'tipo' => $equipamentoTipo,
                    'codigo_barras' => $codigoBarras,
                    'saldo_campo' => 0,
                ];
            }

            $campoAggregatedByEquipment[$tecnicoId][$equipamentoId]['saldo_campo'] += $saldoBrutoCampo;

            if (
                ($campoAggregatedByEquipment[$tecnicoId][$equipamentoId]['codigo_barras'] === null || $campoAggregatedByEquipment[$tecnicoId][$equipamentoId]['codigo_barras'] === '')
                && $codigoBarras !== null
                && $codigoBarras !== ''
            ) {
                $campoAggregatedByEquipment[$tecnicoId][$equipamentoId]['codigo_barras'] = $codigoBarras;
            }
        }

        foreach ($campoAggregatedByEquipment as $tecnicoId => $equipamentosDoTecnico) {
            foreach ($equipamentosDoTecnico as $equipamentoId => $agregado) {
                $saldoCampo = max(0, (int) ($agregado['saldo_campo'] ?? 0));
                if ($saldoCampo <= 0) {
                    continue;
                }

                $cards[$tecnicoId]['equipamentos_campo'][] = [
                    'equipamento_id' => $equipamentoId,
                    'nome' => (string) ($agregado['nome'] ?? 'Equipamento removido'),
                    'tipo' => (string) ($agregado['tipo'] ?? 'indefinido'),
                    'codigo_barras' => $agregado['codigo_barras'] ?? null,
                    'saldo_campo' => $saldoCampo,
                ];
                $cards[$tecnicoId]['saldo_total_campo'] += $saldoCampo;
            }
        }

        foreach ($cards as $tecnicoId => $card) {
            $safeSummary = $this->calculateSafeStockSummary($card['equipamentos_mao']);
            $cards[$tecnicoId]['saldo_por_categoria'] = $safeSummary['saldo_por_categoria'];
            $cards[$tecnicoId]['saldo_por_categoria_efetivo'] = $safeSummary['saldo_por_categoria_efetivo'];
            $cards[$tecnicoId]['reposicao_proximo_dia'] = $safeSummary['repor'];
            $cards[$tecnicoId]['repor_total'] = $safeSummary['repor_total'];
            $cards[$tecnicoId]['estoque_seguro_ok'] = $safeSummary['repor_total'] === 0;
        }

        $usoHojeStmt = $this->conn->prepare(
            "SELECT tecnico_id,
                    equipamento_id,
                    SUM(quantidade) AS uso_hoje
             FROM movimentacoes
             WHERE tipo IN ('uso', 'uso_teste') AND DATE(data_movimentacao) = :usage_date
             GROUP BY tecnico_id, equipamento_id"
        );

        $usoHojeStmt->execute(['usage_date' => $dailyDate]);

        foreach ($usoHojeStmt->fetchAll() as $usoRow) {
            $tecnicoId = (int) $usoRow['tecnico_id'];
            $equipamentoId = (int) $usoRow['equipamento_id'];
            $usoHoje = (int) $usoRow['uso_hoje'];

            if (!isset($cards[$tecnicoId])) {
                continue;
            }

            $saldoAtual = $saldoMap[$tecnicoId][$equipamentoId] ?? 0;

            if ($usoHoje > 0) {
                $eq = $equipInfoMap[$tecnicoId][$equipamentoId] ?? null;

                if ($eq) {
                    $cards[$tecnicoId]['previsao_reposicao_amanha'][] = [
                        'equipamento_id' => $equipamentoId,
                        'nome' => (string) ($eq['nome'] ?? 'Equipamento removido'),
                        'tipo' => (string) ($eq['tipo'] ?? 'indefinido'),
                        'uso_hoje' => $usoHoje,
                        'saldo_mao' => $saldoAtual,
                    ];
                }
            }
        }

        $selectLocal = $this->hasColumn('local_uso') ? 'm.local_uso,' : "'' AS local_uso,";
        $selectObservacoes = $this->hasColumn('observacoes') ? 'm.observacoes,' : "'' AS observacoes,";
        $selectEquipamentoNomeUso = $this->hasColumn('equipamento_nome_snapshot')
            ? "COALESCE(e.nome, m.equipamento_nome_snapshot, 'Equipamento removido') AS equipamento_nome,"
            : "COALESCE(e.nome, 'Equipamento removido') AS equipamento_nome,";
        $selectEquipamentoTipoUso = $this->hasColumn('equipamento_tipo_snapshot')
            ? "COALESCE(e.tipo, m.equipamento_tipo_snapshot, 'indefinido') AS equipamento_tipo,"
            : "COALESCE(e.tipo, 'indefinido') AS equipamento_tipo,";
        $usosWhereDate = $isFilteredByDate ? ' AND DATE(m.data_movimentacao) = :selected_date' : '';
        $usosRecentesStmt = $this->conn->prepare(
            "SELECT m.tecnico_id,
                    {$selectEquipamentoNomeUso}
                    {$selectEquipamentoTipoUso}
                    m.quantidade,
                    $selectLocal
                $selectObservacoes
                    m.data_movimentacao
             FROM movimentacoes m
             LEFT JOIN equipamentos e ON e.id = m.equipamento_id
                   WHERE m.tipo IN ('uso', 'uso_teste'){$usosWhereDate}
             ORDER BY m.data_movimentacao DESC"
        );

        $usosRecentesStmt->execute($isFilteredByDate ? ['selected_date' => $dailyDate] : []);

        $limitePorTecnico = [];
        $limiteUsosRecentes = $isFilteredByDate ? null : 5;
        foreach ($usosRecentesStmt->fetchAll() as $uso) {
            $tecnicoId = (int) $uso['tecnico_id'];
            if (!isset($cards[$tecnicoId])) {
                continue;
            }

            $limitePorTecnico[$tecnicoId] = $limitePorTecnico[$tecnicoId] ?? 0;
            if ($limiteUsosRecentes !== null && $limitePorTecnico[$tecnicoId] >= $limiteUsosRecentes) {
                continue;
            }

            $cards[$tecnicoId]['usos_recentes'][] = [
                'equipamento_nome' => $uso['equipamento_nome'],
                'equipamento_tipo' => $uso['equipamento_tipo'],
                'quantidade' => (int) $uso['quantidade'],
                'local_uso' => $uso['local_uso'],
                'observacoes' => $uso['observacoes'],
                'data_movimentacao' => $uso['data_movimentacao'],
            ];

            $limitePorTecnico[$tecnicoId]++;
        }

        return array_values($cards);
    }

        public function reportAlertasUsoTeste(?string $selectedDate = null): array
    {
            $selectLocal = $this->hasColumn('local_uso') ? 'm.local_uso,' : "'' AS local_uso,";
            $selectObservacoes = $this->hasColumn('observacoes') ? 'm.observacoes,' : "'' AS observacoes,";
            $selectHistorico = $this->hasColumn('historico_tratativa') ? 'm.historico_tratativa,' : "'' AS historico_tratativa,";
                $selectEquipamentoNome = $this->hasColumn('equipamento_nome_snapshot')
                        ? "COALESCE(e.nome, m.equipamento_nome_snapshot, 'Equipamento removido') AS equipamento_nome,"
                        : "COALESCE(e.nome, 'Equipamento removido') AS equipamento_nome,";
            $selectEquipamentoTipo = $this->hasColumn('equipamento_tipo_snapshot')
                ? "COALESCE(e.tipo, m.equipamento_tipo_snapshot, 'indefinido') AS equipamento_tipo,"
                : "COALESCE(e.tipo, 'indefinido') AS equipamento_tipo,";
                $selectCodigoBarras = $this->hasColumn('equipamento_codigo_barras_snapshot')
                        ? "COALESCE(e.codigo_barras, m.equipamento_codigo_barras_snapshot) AS equipamento_codigo_barras,"
                        : ($this->hasEquipamentoColumn('codigo_barras') ? 'e.codigo_barras AS equipamento_codigo_barras,' : "NULL AS equipamento_codigo_barras,");

            $date = $this->normalizeDate($selectedDate);
            $whereDate = $date !== null ? ' AND DATE(m.data_movimentacao) = :selected_date' : '';

                $sql = "SELECT m.id,
                                             m.tecnico_id,
                                    COALESCE(t.nome, 'Tecnico removido') AS tecnico_nome,
                                             m.equipamento_id,
                                             {$selectEquipamentoNome}
                             {$selectEquipamentoTipo}
                                             {$selectCodigoBarras}
                                             m.quantidade,
                                             {$selectLocal}
                                             {$selectObservacoes}
                                             {$selectHistorico}
                                             m.data_movimentacao
                                FROM movimentacoes m
                            LEFT JOIN tecnicos t ON t.id = m.tecnico_id
                            LEFT JOIN equipamentos e ON e.id = m.equipamento_id
                    WHERE m.tipo = 'uso_teste'{$whereDate}
                                ORDER BY m.data_movimentacao DESC";

        $stmtTests = $this->conn->prepare($sql);
        $stmtTests->execute($date !== null ? ['selected_date' => $date] : []);
        $tests = $stmtTests->fetchAll();
        $alertas = [];
        $now = new DateTimeImmutable('now');

        foreach ($tests as $test) {
            $stmtResolucao = null;
            $tecnicoIdParam = (int) $test['tecnico_id'];
            $equipamentoIdParam = (int) ($test['equipamento_id'] ?? 0);
            $equipCodigoParam = is_scalar($test['equipamento_codigo_barras']) ? trim((string) ($test['equipamento_codigo_barras'] ?? '')) : '';

            // Build resolution query: if equipamento_id present use it, otherwise try to match by codigo_barras (snapshot) or by nome+tipo snapshot
            if ($equipamentoIdParam > 0) {
                $stmtResolucao = $this->conn->prepare(
                    "SELECT id
                     FROM movimentacoes
                     WHERE tecnico_id = :tecnico_id
                       AND equipamento_id = :equipamento_id
                       AND data_movimentacao > :data_movimentacao
                                             AND tipo IN ('uso', 'devolucao', 'recolhimento', 'recolhimento_defeito')
                     ORDER BY data_movimentacao DESC
                     LIMIT 1"
                );
                $stmtResolucao->execute([
                    'tecnico_id' => $tecnicoIdParam,
                    'equipamento_id' => $equipamentoIdParam,
                    'data_movimentacao' => $test['data_movimentacao'],
                ]);
            } else {
                // try codigo de barras snapshot match when available
                if ($equipCodigoParam !== '' && $this->hasColumn('equipamento_codigo_barras_snapshot')) {
                    $stmtResolucao = $this->conn->prepare(
                        "SELECT id
                         FROM movimentacoes
                         WHERE tecnico_id = :tecnico_id
                           AND equipamento_codigo_barras_snapshot = :equip_codigo
                           AND data_movimentacao > :data_movimentacao
                                                     AND tipo IN ('uso', 'devolucao', 'recolhimento', 'recolhimento_defeito')
                         ORDER BY data_movimentacao DESC
                         LIMIT 1"
                    );
                    $stmtResolucao->execute([
                        'tecnico_id' => $tecnicoIdParam,
                        'equip_codigo' => $equipCodigoParam,
                        'data_movimentacao' => $test['data_movimentacao'],
                    ]);
                } else {
                    // fallback: match by equipamento_nome_snapshot + equipamento_tipo_snapshot when available
                    if ($this->hasColumn('equipamento_nome_snapshot') && $this->hasColumn('equipamento_tipo_snapshot')) {
                        $stmtResolucao = $this->conn->prepare(
                            "SELECT id
                             FROM movimentacoes
                             WHERE tecnico_id = :tecnico_id
                               AND equipamento_id IS NULL
                               AND equipamento_nome_snapshot = :equip_nome
                               AND equipamento_tipo_snapshot = :equip_tipo
                               AND data_movimentacao > :data_movimentacao
                                                                                                                         AND tipo IN ('devolucao', 'recolhimento', 'recolhimento_defeito')
                             ORDER BY data_movimentacao DESC
                             LIMIT 1"
                        );
                        $stmtResolucao->execute([
                            'tecnico_id' => $tecnicoIdParam,
                            'equip_nome' => $test['equipamento_nome'],
                            'equip_tipo' => $test['equipamento_tipo'] ?? 'indefinido',
                            'data_movimentacao' => $test['data_movimentacao'],
                        ]);
                    }
                }
            }

            if ($stmtResolucao !== null && $stmtResolucao->fetch()) {
                continue;
            }

            $inicioTeste = new DateTimeImmutable($test['data_movimentacao']);
            $vencimento = $this->calculateTesteDueDate($inicioTeste);
            $diasRestantes = (int) $now->diff($vencimento)->format('%r%a');

            $alertas[] = [
                'movimentacao_id' => (int) $test['id'],
                'tecnico_id' => (int) $test['tecnico_id'],
                'tecnico_nome' => $test['tecnico_nome'],
                'equipamento_id' => (int) $test['equipamento_id'],
                'equipamento_nome' => $test['equipamento_nome'],
                'equipamento_tipo' => $test['equipamento_tipo'] ?? 'indefinido',
                'equipamento_codigo_barras' => $test['equipamento_codigo_barras'] ?? null,
                'quantidade' => (int) $test['quantidade'],
                'local_uso' => $test['local_uso'],
                'observacoes' => $test['observacoes'],
                'historico_tratativa' => $test['historico_tratativa'],
                'inicio_teste' => $inicioTeste->format('Y-m-d H:i:s'),
                'vencimento_teste' => $vencimento->format('Y-m-d H:i:s'),
                'dias_restantes' => $diasRestantes,
                'status' => $diasRestantes < 0 ? 'vencido' : 'no_prazo',
            ];
        }

        usort($alertas, static function (array $a, array $b): int {
            return strcmp($a['vencimento_teste'], $b['vencimento_teste']);
        });

        return $alertas;
    }

    private function calculateTesteDueDate(DateTimeImmutable $inicioTeste): DateTimeImmutable
    {
        $vencimento = $inicioTeste->modify('+3 days');

        // Domingo nao conta como dia de vencimento: empurra para segunda.
        if ($vencimento->format('w') === '0') {
            $vencimento = $vencimento->modify('+1 day');
        }

        return $vencimento;
    }

    private function getSafeStockTargets(): array
    {
        return [
            'roteador' => 3,
            'onu' => 2,
            'conector_fibra' => 10,
            'conector_rj' => 8,
            'esticador' => 8,
        ];
    }

    private function normalizeEquipmentType(string $tipo, string $nome = ''): string
    {
        $tipo = strtolower(trim($tipo));
        $nome = strtolower(trim($nome));

        if ($tipo === 'insumos') {
            if (strpos($nome, 'esticador') !== false) {
                return 'esticador';
            }

            if (strpos($nome, 'rj') !== false || strpos($nome, 'rj45') !== false) {
                return 'conector_rj';
            }
        }

        return match ($tipo) {
            'router', 'roteador' => 'roteador',
            'onu' => 'onu',
            'ont' => 'ont',
            'conector', 'conector_fibra', 'fibra', 'conector de fibra' => 'conector_fibra',
            'conector_rj', 'rj', 'rj45', 'conector de rj', 'conector rj' => 'conector_rj',
            'esticador', 'esticadores' => 'esticador',
            default => $tipo,
        };
    }

    private function calculateSafeStockSummary(array $equipamentosMao): array
    {
        $targets = $this->getSafeStockTargets();
        $saldoPorCategoria = [
            'roteador' => 0,
            'onu' => 0,
            'ont' => 0,
            'conector_fibra' => 0,
            'conector_rj' => 0,
            'esticador' => 0,
        ];

        foreach ($equipamentosMao as $item) {
            $tipo = $this->normalizeEquipmentType((string) ($item['tipo'] ?? ''), (string) ($item['nome'] ?? ''));
            $saldo = max(0, (int) ($item['saldo_mao'] ?? 0));

            if ($saldo <= 0) {
                continue;
            }

            if ($tipo === 'roteador') {
                $saldoPorCategoria['roteador'] += $saldo;
                continue;
            }

            if ($tipo === 'onu') {
                $saldoPorCategoria['onu'] += $saldo;
                continue;
            }

            if ($tipo === 'ont') {
                $saldoPorCategoria['ont'] += $saldo;
                continue;
            }

            if ($tipo === 'conector_fibra') {
                $saldoPorCategoria['conector_fibra'] += $saldo;
                continue;
            }

            if ($tipo === 'conector_rj') {
                $saldoPorCategoria['conector_rj'] += $saldo;
                continue;
            }

            if ($tipo === 'esticador') {
                $saldoPorCategoria['esticador'] += $saldo;
            }
        }

        // ONT conta como substituto de 1 ONU e 1 roteador para estoque seguro.
        $saldoEfetivoPorCategoria = [
            'roteador' => $saldoPorCategoria['roteador'] + $saldoPorCategoria['ont'],
            'onu' => $saldoPorCategoria['onu'] + $saldoPorCategoria['ont'],
            'conector_fibra' => $saldoPorCategoria['conector_fibra'],
            'conector_rj' => $saldoPorCategoria['conector_rj'],
            'esticador' => $saldoPorCategoria['esticador'],
        ];

        $repor = [];
        $reporTotal = 0;
        foreach ($targets as $categoria => $necessario) {
            $atual = $saldoEfetivoPorCategoria[$categoria] ?? 0;
            $faltante = max(0, $necessario - $atual);

            $repor[] = [
                'categoria' => $categoria,
                'necessario' => $necessario,
                'atual' => $atual,
                'faltante' => $faltante,
            ];

            $reporTotal += $faltante;
        }

        return [
            'saldo_por_categoria' => $saldoPorCategoria,
            'saldo_por_categoria_efetivo' => $saldoEfetivoPorCategoria,
            'repor' => $repor,
            'repor_total' => $reporTotal,
        ];
    }

    private function normalizeDate(?string $date): ?string
    {
        if ($date === null) {
            return null;
        }

        $date = trim($date);
        if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $date));
        if (!checkdate($month, $day, $year)) {
            return null;
        }

        return $date;
    }

    private function calculateDelta(string $tipo, int $quantidade): int
    {
        return $this->calculateStockDelta($tipo, $quantidade);
    }

    private function getEquipamentosForPurchaseSupport(): array
    {
        $stmt = $this->conn->query(
            "SELECT id, nome, tipo, quantidade
             FROM equipamentos
             ORDER BY
                CASE tipo
                    WHEN 'roteador' THEN 1
                    WHEN 'onu' THEN 2
                    WHEN 'ont' THEN 3
                    WHEN 'conector_fibra' THEN 4
                    WHEN 'insumos' THEN 5
                    ELSE 99
                END,
                nome ASC"
        );

        return $stmt->fetchAll();
    }

    private function getPurchaseSupportItemConfigMap(): array
    {
        $this->ensurePurchaseSupportItemTable();
        $stmt = $this->conn->query('SELECT equipamento_id, consumo_dia FROM apoio_compra_item_config');
        $map = [];

        foreach ($stmt->fetchAll() as $row) {
            $equipamentoId = (int) ($row['equipamento_id'] ?? 0);
            if ($equipamentoId <= 0) {
                continue;
            }

            $map[$equipamentoId] = max(0, (int) ($row['consumo_dia'] ?? 0));
        }

        return $map;
    }

    public function reportEquipamentosComDefeito(?string $selectedDate = null, ?int $tecnicoId = null, ?string $query = null): array
    {
        $selectedDate = $this->normalizeDate($selectedDate);
        $tecnicoId = $tecnicoId !== null && $tecnicoId > 0 ? $tecnicoId : null;
        $query = trim((string) ($query ?? ''));

        $selectObservacoes = $this->hasColumn('observacoes') ? 'm.observacoes,' : "'' AS observacoes,";
        $selectEquipamentoNome = $this->hasColumn('equipamento_nome_snapshot')
            ? "COALESCE(e.nome, m.equipamento_nome_snapshot, 'Equipamento removido') AS equipamento_nome,"
            : "COALESCE(e.nome, 'Equipamento removido') AS equipamento_nome,";
        $selectEquipamentoTipo = $this->hasColumn('equipamento_tipo_snapshot')
            ? "COALESCE(e.tipo, m.equipamento_tipo_snapshot, 'indefinido') AS equipamento_tipo,"
            : "COALESCE(e.tipo, 'indefinido') AS equipamento_tipo,";
        $selectCodigoBarras = $this->hasColumn('equipamento_codigo_barras_snapshot')
            ? "COALESCE(e.codigo_barras, m.equipamento_codigo_barras_snapshot) AS equipamento_codigo_barras"
            : ($this->hasEquipamentoColumn('codigo_barras') ? 'e.codigo_barras AS equipamento_codigo_barras' : 'NULL AS equipamento_codigo_barras');

        $where = ["m.tipo = 'recolhimento_defeito'"];
        $params = [];

        if ($selectedDate !== null) {
            $where[] = 'DATE(m.data_movimentacao) = :selected_date';
            $params['selected_date'] = $selectedDate;
        }

        if ($tecnicoId !== null) {
            $where[] = 'm.tecnico_id = :tecnico_id';
            $params['tecnico_id'] = $tecnicoId;
        }

        if ($query !== '') {
            $where[] = '(COALESCE(t.nome, "") LIKE :query OR COALESCE(e.nome, m.equipamento_nome_snapshot, "") LIKE :query OR COALESCE(m.observacoes, "") LIKE :query)';
            $params['query'] = '%' . $query . '%';
        }

        $sql = "SELECT m.id,
                       m.tecnico_id,
                       COALESCE(t.nome, 'Sem tecnico') AS tecnico_nome,
                       m.equipamento_id,
                       {$selectEquipamentoNome}
                       {$selectEquipamentoTipo}
                       {$selectObservacoes}
                       {$selectCodigoBarras},
                       m.quantidade,
                       m.data_movimentacao
                FROM movimentacoes m
                LEFT JOIN tecnicos t ON t.id = m.tecnico_id
                LEFT JOIN equipamentos e ON e.id = m.equipamento_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY m.data_movimentacao DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $obs = (string) ($row['observacoes'] ?? '');
            $row['motivo_defeito'] = $this->extractDefectReason($obs);
            $row['serial_equipamento'] = $this->extractDefectSerial($obs);
        }
        unset($row);

        return $rows;
    }

    private function extractDefectReason(string $observacoes): string
    {
        $observacoes = trim($observacoes);
        if ($observacoes === '') {
            return '-';
        }

        if (preg_match('/Defeito:\s*([^|]+)/i', $observacoes, $matches) === 1) {
            $motivo = trim((string) ($matches[1] ?? ''));
            if ($motivo !== '') {
                return $motivo;
            }
        }

        return $observacoes;
    }

    private function extractDefectSerial(string $observacoes): string
    {
        $observacoes = trim($observacoes);
        if ($observacoes === '') {
            return '-';
        }

        if (preg_match('/Serial:\s*([^|]+)/i', $observacoes, $matches) === 1) {
            $serial = trim((string) ($matches[1] ?? ''));
            if ($serial !== '') {
                return $serial;
            }
        }

        return '-';
    }

    private function ensurePurchaseSupportTable(): void
    {
        $this->conn->exec(
            'CREATE TABLE IF NOT EXISTS apoio_compra_config (
                id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
                consumo_roteador_dia INT UNSIGNED NOT NULL DEFAULT 6,
                consumo_onu_dia INT UNSIGNED NOT NULL DEFAULT 6,
                consumo_conector_dia INT UNSIGNED NOT NULL DEFAULT 12,
                prazo_reposicao_dias INT UNSIGNED NOT NULL DEFAULT 3,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB'
        );
    }

    private function ensurePurchaseSupportItemTable(): void
    {
        $this->conn->exec(
            'CREATE TABLE IF NOT EXISTS apoio_compra_item_config (
                equipamento_id INT UNSIGNED NOT NULL PRIMARY KEY,
                consumo_dia INT UNSIGNED NOT NULL DEFAULT 0,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_apoio_compra_item_equip FOREIGN KEY (equipamento_id)
                    REFERENCES equipamentos(id)
                    ON UPDATE CASCADE
                    ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );
    }
}

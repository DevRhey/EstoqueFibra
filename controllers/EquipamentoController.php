<?php
require_once __DIR__ . '/../models/Equipamento.php';
require_once __DIR__ . '/../config/helpers.php';

class EquipamentoController
{
    private Equipamento $model;

    public function __construct()
    {
        $this->model = new Equipamento();
    }

    public function index(): array
    {
        return $this->model->all();
    }

    public function store(array $data): void
    {
        $nome = sanitizeInput($data['nome'] ?? '');
        $tipo = sanitizeInput($data['tipo'] ?? '');
        $quantidade = (int) ($data['quantidade'] ?? 0);
        $codigoBarras = sanitizeInput((string) ($data['codigo_barras'] ?? ''));
        $codigoBarras = trim($codigoBarras) !== '' ? trim($codigoBarras) : null;

        if ($nome === '' || $tipo === '' || $quantidade < 0) {
            setFlash('danger', 'Preencha os campos corretamente para cadastrar o equipamento.');
            redirect('equipamentos');
        }

        if ($codigoBarras !== null && strlen($codigoBarras) > 64) {
            setFlash('danger', 'Codigo de barras muito longo. Use ate 64 caracteres.');
            redirect('equipamentos');
        }

        try {
            $this->model->create($nome, $tipo, $quantidade, $codigoBarras);
            setFlash('success', 'Equipamento cadastrado com sucesso.');
        } catch (Throwable $e) {
            setFlash('danger', 'Falha ao cadastrar equipamento. Verifique se o codigo de barras ja esta em uso.');
        }
        redirect('equipamentos');
    }

    public function update(array $data): void
    {
        $id = (int) ($data['id'] ?? 0);
        $nome = sanitizeInput($data['nome'] ?? '');
        $tipo = sanitizeInput($data['tipo'] ?? '');
        $quantidade = (int) ($data['quantidade'] ?? 0);
        $codigoBarras = sanitizeInput((string) ($data['codigo_barras'] ?? ''));
        $codigoBarras = trim($codigoBarras) !== '' ? trim($codigoBarras) : null;

        if ($id <= 0 || $nome === '' || $tipo === '' || $quantidade < 0) {
            setFlash('danger', 'Dados invalidos para atualizar equipamento.');
            redirect('equipamentos');
        }

        if ($codigoBarras !== null && strlen($codigoBarras) > 64) {
            setFlash('danger', 'Codigo de barras muito longo. Use ate 64 caracteres.');
            redirect('equipamentos');
        }

        try {
            $this->model->update($id, $nome, $tipo, $quantidade, $codigoBarras);
            setFlash('success', 'Equipamento atualizado com sucesso.');
        } catch (Throwable $e) {
            setFlash('danger', 'Falha ao atualizar equipamento. Verifique se o codigo de barras ja esta em uso.');
        }
        redirect('equipamentos');
    }

    public function destroy(array $data): void
    {
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            setFlash('danger', 'Equipamento invalido para exclusao.');
            redirect('equipamentos');
        }

        $saldosEmMao = $this->model->handBalanceByTechnician($id);
        if (!empty($saldosEmMao)) {
            $nomes = [];
            foreach (array_slice($saldosEmMao, 0, 4) as $saldo) {
                $nome = trim((string) ($saldo['tecnico_nome'] ?? 'Tecnico'));
                $qtd = (int) ($saldo['saldo_mao'] ?? 0);
                $nomes[] = $nome . ' (' . $qtd . ')';
            }

            $detalhes = implode(', ', $nomes);
            setFlash('danger', 'Nao e possivel excluir este equipamento porque ele ainda esta em mao de tecnicos: ' . $detalhes . '. Registre devolucao/uso antes da exclusao para evitar inconsistencias.');
            redirect('equipamentos');
        }

        try {
            $this->model->delete($id);
            setFlash('success', 'Equipamento excluido com sucesso.');
        } catch (PDOException $e) {
            if (($e->getCode() ?? '') === '23000') {
                setFlash('danger', 'Nao foi possivel excluir. Aplique a migracao de exclusao com historico para equipamentos e tente novamente.');
            } else {
                setFlash('danger', 'Falha ao excluir equipamento. Tente novamente.');
            }
        } catch (Throwable $e) {
            setFlash('danger', 'Falha ao excluir equipamento. Tente novamente.');
        }

        redirect('equipamentos');
    }
}

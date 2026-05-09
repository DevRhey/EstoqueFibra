<?php
require_once __DIR__ . '/../models/Movimentacao.php';
require_once __DIR__ . '/../models/Tecnico.php';
require_once __DIR__ . '/../config/helpers.php';

class TecnicoController
{
    private Tecnico $model;
    private Movimentacao $movimentacaoModel;

    public function __construct()
    {
        $this->model = new Tecnico();
        $this->movimentacaoModel = new Movimentacao();
    }

    public function index(?string $selectedDate = null): array
    {
        $selectedDate = $this->normalizeDate($selectedDate) ?? date('Y-m-d');
        $movimentacoesDia = $this->movimentacaoModel->allWithRelations($selectedDate);
        $cardsTecnicos = $this->movimentacaoModel->reportCardsTecnicos($selectedDate);

        $resumoDia = [
            'total' => count($movimentacoesDia),
            'entrega' => 0,
            'uso' => 0,
            'uso_teste' => 0,
            'devolucao' => 0,
            'recolhimento' => 0,
            'recolhimento_defeito' => 0,
            'tecnicos_ativos' => 0,
        ];

        $tecnicosAtivos = [];
        foreach ($movimentacoesDia as $mov) {
            $tipo = $mov['tipo'] ?? '';

            if (isset($resumoDia[$tipo])) {
                $resumoDia[$tipo]++;
            }

            $tecnicoNome = trim((string) ($mov['tecnico_nome'] ?? ''));
            if ($tecnicoNome !== '') {
                $tecnicosAtivos[$tecnicoNome] = true;
            }
        }
        $resumoDia['tecnicos_ativos'] = count($tecnicosAtivos);

        return [
            'tecnicos' => $this->model->all(),
            'selectedDate' => $selectedDate,
            'cardsTecnicos' => $cardsTecnicos,
            'resumoDia' => $resumoDia,
            'automation' => null,
        ];
    }

    public function store(array $data): void
    {
        $nome = sanitizeInput($data['nome'] ?? '');

        if ($nome === '') {
            setFlash('danger', 'Informe o nome do tecnico.');
            redirect('tecnicos');
        }

        $this->model->create($nome);
        setFlash('success', 'Tecnico cadastrado com sucesso.');
        redirect('tecnicos');
    }

    public function update(array $data): void
    {
        $id = (int) ($data['id'] ?? 0);
        $nome = sanitizeInput($data['nome'] ?? '');

        if ($id <= 0 || $nome === '') {
            setFlash('danger', 'Dados invalidos para atualizar tecnico.');
            redirect('tecnicos');
        }

        $this->model->update($id, $nome);
        setFlash('success', 'Tecnico atualizado com sucesso.');
        redirect('tecnicos');
    }

    public function destroy(array $data): void
    {
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            setFlash('danger', 'Tecnico invalido para exclusao.');
            redirect('tecnicos');
        }

        try {
            $this->model->delete($id);
            setFlash('success', 'Tecnico excluido com sucesso.');
        } catch (Throwable $e) {
            setFlash('danger', 'Falha ao excluir tecnico. Aplique a migracao de exclusao com historico para permitir a remocao sem perder registros.');
        }

        redirect('tecnicos');
    }

    public function history(int $tecnicoId): array
    {
        if ($tecnicoId <= 0) {
            return [];
        }

        return $this->model->movementHistory($tecnicoId);
    }

    public function historyPage(int $tecnicoId, ?string $selectedDate = null): array
    {
        if ($tecnicoId <= 0) {
            return [
                'tecnico' => null,
                'historico' => [],
                'selectedDate' => $selectedDate ?? date('Y-m-d'),
            ];
        }

        $tecnico = $this->model->find($tecnicoId);

        return [
            'tecnico' => $tecnico,
            'historico' => $tecnico ? $this->model->movementHistory($tecnicoId) : [],
            'selectedDate' => $selectedDate ?? date('Y-m-d'),
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
}

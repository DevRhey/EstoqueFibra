<?php
require_once __DIR__ . '/../models/Tecnico.php';
require_once __DIR__ . '/../config/helpers.php';

class TecnicoController
{
    private Tecnico $model;

    public function __construct()
    {
        $this->model = new Tecnico();
    }

    public function index(): array
    {
        return $this->model->all();
    }

    public function store(array $data): void
    {
        $nome = sanitize($data['nome'] ?? '');

        if ($nome === '') {
            setFlash('danger', 'Informe o nome do tecnico.');
            redirect('tecnicos');
        }

        $this->model->create($nome);
        setFlash('success', 'Tecnico cadastrado com sucesso.');
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

    public function historyPage(int $tecnicoId): array
    {
        if ($tecnicoId <= 0) {
            return [
                'tecnico' => null,
                'historico' => [],
            ];
        }

        $tecnico = $this->model->find($tecnicoId);

        return [
            'tecnico' => $tecnico,
            'historico' => $tecnico ? $this->model->movementHistory($tecnicoId) : [],
        ];
    }
}

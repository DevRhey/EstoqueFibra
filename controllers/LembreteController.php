<?php
require_once __DIR__ . '/../models/Lembrete.php';
require_once __DIR__ . '/../config/helpers.php';

class LembreteController
{
    private Lembrete $model;

    public function __construct()
    {
        $this->model = new Lembrete();
    }

    public function index(?string $selectedDate = null, ?string $status = null): array
    {
        $selectedDate = $this->normalizeDate($selectedDate) ?? date('Y-m-d');
        $items = $this->model->listAll($status, $selectedDate);

        return [
            'selectedDate' => $selectedDate,
            'selectedStatus' => in_array($status, ['aberto', 'lido', 'resolvido'], true) ? $status : '',
            'lembretes' => $items,
            'resumo' => $this->model->summary(),
        ];
    }

    public function summary(): array
    {
        return $this->model->summary();
    }

    public function store(array $data): void
    {
        try {
            $this->model->create($data);
            setFlash('success', 'Lembrete criado com sucesso.');
        } catch (Throwable $e) {
            setFlash('danger', 'Falha ao criar lembrete: ' . $e->getMessage());
        }

        $this->redirectBack($this->normalizeDate((string) ($data['selected_date'] ?? '')));
    }

    public function update(array $data): void
    {
        $id = (int) ($data['id'] ?? 0);
        $returnDate = $this->normalizeDate((string) ($data['selected_date'] ?? ''));

        if ($id <= 0) {
            setFlash('danger', 'Lembrete invalido para edicao.');
            $this->redirectBack($returnDate);
        }

        try {
            $this->model->update($id, $data);
            setFlash('success', 'Lembrete atualizado com sucesso.');
        } catch (Throwable $e) {
            setFlash('danger', 'Falha ao atualizar lembrete: ' . $e->getMessage());
        }

        $this->redirectBack($returnDate);
    }

    public function markRead(array $data): void
    {
        $id = (int) ($data['id'] ?? 0);
        $returnDate = $this->normalizeDate((string) ($data['selected_date'] ?? ''));

        if ($id <= 0) {
            setFlash('danger', 'Lembrete invalido.');
            $this->redirectBack($returnDate);
        }

        $this->model->markAsRead($id);
        setFlash('success', 'Lembrete marcado como lido.');
        $this->redirectBack($returnDate);
    }

    public function resolve(array $data): void
    {
        $id = (int) ($data['id'] ?? 0);
        $returnDate = $this->normalizeDate((string) ($data['selected_date'] ?? ''));

        if ($id <= 0) {
            setFlash('danger', 'Lembrete invalido.');
            $this->redirectBack($returnDate);
        }

        $this->model->resolve($id);
        setFlash('success', 'Lembrete resolvido.');
        $this->redirectBack($returnDate);
    }

    private function redirectBack(?string $selectedDate): void
    {
        if ($selectedDate !== null) {
            header('Location: index.php?route=lembretes&date=' . urlencode($selectedDate));
            exit;
        }

        redirect('lembretes');
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
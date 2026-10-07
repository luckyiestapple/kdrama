<?php

namespace App\Controllers\Api;

use App\Models\KdramaModel;
use CodeIgniter\RESTful\ResourceController;

/**
 * RESTful API untuk tabel `kdramas`.
 *
 * ResourceController sudah menyediakan:
 *   respond(), respondCreated(), respondDeleted(),
 *   failNotFound(), failValidationErrors(), failResourceExists(),
 *   failServerError(), paginate()
 */
class Kdramas extends ResourceController
{
    /**
     * Dipakai ResourceController untuk meng-instansiasi model ke $this->model.
     */
    protected $modelName = KdramaModel::class;

    /**
     * API selalu balas JSON, tidak perlu content negotiation.
     */
    protected $format = 'json';

    /**
     * Batas jumlah data per halaman.
     */
    private const MAX_PER_PAGE = 50;

    /**
     * GET /api/kdramas
     *
     * Mendukung filter lewat query string, contoh:
     *   /api/kdramas?year_of_release=2021
     *   /api/kdramas?original_network=tvN&content_rating=15
     *   /api/kdramas?page=2&per_page=5
     */
    public function index()
    {
        $builder = $this->model->orderByRank();

        $filters = $this->collectFilters();

        // Filter dengan LIKE, tapi hanya untuk kolom teks.
        $textFilters = ['original_network', 'content_rating', 'director'];

        foreach ($filters as $column => $value) {
            if (in_array($column, $textFilters, true)) {
                $builder->like($column, $value);
            } else {
                $builder->where($column, $value);
            }
        }

        return $this->paginate(resource: $builder, perPage: $this->perPage());
    }

    /**
     * GET /api/kdramas/search/{keyword}
     *
     * Mencari di beberapa kolom sekaligus.
     */
    public function search($keyword = null)
    {
        $keyword = trim((string) $keyword);

        if ($keyword === '') {
            return $this->failValidationErrors([
                'keyword' => 'Kata kunci pencarian tidak boleh kosong.',
            ]);
        }

        $builder = $this->model->groupStart()
            ->like('name', $keyword)
            ->orLike('director', $keyword)
            ->orLike('screenwriter', $keyword)
            ->orLike('cast_members', $keyword)
            ->orLike('genre', $keyword)
            ->orLike('tags', $keyword)
            ->orLike('original_network', $keyword)
            ->groupEnd()
            ->orderByRank();

        return $this->paginate(resource: $builder, perPage: $this->perPage());
    }

    /**
     * GET /api/kdramas/{id}
     */
    public function show($id = null)
    {
        $kdrama = $this->model->find($id);

        if ($kdrama === null) {
            return $this->failNotFound("Kdrama dengan id {$id} tidak ditemukan.");
        }

        return $this->respond($kdrama);
    }

    /**
     * POST /api/kdramas
     */
    public function create()
    {
        $data = $this->payload();

        if ($data === null) {
            return $this->failValidationErrors([
                'body' => 'Body request wajib diisi.',
            ]);
        }

        // `name` dicek di sini, bukan pakai rule `required` di model,
        // supaya PATCH parsial tetap bisa jalan.
        $name = isset($data['name']) ? trim((string) $data['name']) : '';

        if ($name === '') {
            return $this->failValidationErrors([
                'name' => 'Nama kdrama wajib diisi.',
            ]);
        }

        $data['name'] = $name;

        if ($this->nameExists($name)) {
            return $this->failResourceExists(
                "Kdrama dengan nama \"{$name}\" sudah ada."
            );
        }

        $id = $this->model->insert($data, true);

        if ($id === false) {
            return $this->validationOrServerError($this->model->errors());
        }

        return $this->respondCreated($this->model->find($id));
    }

    /**
     * PUT|PATCH /api/kdramas/{id}
     */
    public function update($id = null)
    {
        if ($this->model->find($id) === null) {
            return $this->failNotFound("Kdrama dengan id {$id} tidak ditemukan.");
        }

        $data = $this->payload();

        if ($data === null) {
            return $this->failValidationErrors([
                'body' => 'Body request wajib diisi.',
            ]);
        }

        if (isset($data['name']) && $this->nameExists($data['name'], $id)) {
            return $this->failResourceExists(
                "Kdrama dengan nama \"{$data['name']}\" sudah dipakai drama lain."
            );
        }

        if (! $this->model->update($id, $data)) {
            return $this->validationOrServerError($this->model->errors());
        }

        return $this->respond($this->model->find($id));
    }

    /**
     * DELETE /api/kdramas/{id}
     *
     * Soft delete: baris tetap ada, deleted_at diisi.
     */
    public function delete($id = null)
    {
        if ($this->model->find($id) === null) {
            return $this->failNotFound("Kdrama dengan id {$id} tidak ditemukan.");
        }

        if (! $this->model->delete($id)) {
            return $this->validationOrServerError($this->model->errors());
        }

        return $this->respondDeleted(['id' => (int) $id]);
    }

    // -----------------------------------------------------------------
    // Helper
    // -----------------------------------------------------------------

    /**
     * Ambil body request, JSON dulu, lalu fallback ke form-encoded.
     *
     * Dipisah ke method supaya create() dan update() tidak duplikat.
     *
     * @return array<string, mixed>|null
     */
    private function payload(): ?array
    {
        $json = $this->request->getJSON(true);

        if (is_array($json) && $json !== []) {
            return $json;
        }

        $post = $this->request->getPost();

        if (is_array($post) && $post !== []) {
            return $post;
        }

        return null;
    }

    /**
     * Kumpulkan query string yang boleh jadi filter.
     *
     * Query param yang tidak ada di $filterable diabaikan, bukan error,
     * supaya client tidak perlu menebak nama parameter.
     *
     * @return array<string, string>
     */
    private function collectFilters(): array
    {
        $filters = [];

        foreach ($this->model->filterable as $column) {
            $value = $this->request->getGet($column);

            if ($value !== null && $value !== '') {
                $filters[$column] = (string) $value;
            }
        }

        return $filters;
    }

    /**
     * Jumlah data per halaman, dibatasi MAX_PER_PAGE.
     */
    private function perPage(): int
    {
        $requested = (int) ($this->request->getGet('per_page') ?? 20);

        if ($requested < 1) {
            return 20;
        }

        return min($requested, self::MAX_PER_PAGE);
    }

    /**
     * Cek nama kdrama sudah dipakai, dengan kecuali id tertentu.
     *
     * Pakai instance model BARU, bukan $this->model, supaya kondisi
     * where di sini tidak ikut terbawa ke query update/delete berikutnya.
     */
    private function nameExists(string $name, $exceptId = null): bool
    {
        $query = new KdramaModel();

        if ($exceptId !== null) {
            $query->where('id !=', $exceptId);
        }

        return $query->where('name', $name)->first() !== null;
    }

    /**
     * Pisahkan error validasi (400) dari error database (500).
     */
    private function validationOrServerError(array $errors)
    {
        if (isset($errors['database'])) {
            return $this->failServerError($errors['database']);
        }

        return $this->failValidationErrors($errors);
    }
}

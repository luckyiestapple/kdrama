<?php

namespace App\Controllers\Api;

use App\Models\KdramaModel;
use CodeIgniter\RESTful\ResourceController;

/**
 * REST API untuk tabel `kdramas`.
 *
 * Mengikuti app/README_KDRAMAS.md bagian 9 & 10.
 *
 * ResourceController menyediakan response helper:
 *   respond(), respondCreated(), failNotFound(),
 *   failValidationErrors()
 *
 * Semua response sukses memakai bentuk yang sama:
 *   { "status": <kode>, "message": "<pesan>", "data": ... }
 * index() menambah "total" sesuai jumlah baris.
 */
class Kdramas extends ResourceController
{
    /**
     * ResourceController memakai ini untuk mengisi $this->model.
     */
    protected $modelName = KdramaModel::class;

    /**
     * API selalu balas JSON, jadi tidak perlu content negotiation.
     */
    protected $format = 'json';

    /**
     * GET /api/kdramas
     */
    public function index()
    {
        $data = $this->model->orderBy('id', 'ASC')->findAll();

        return $this->respond([
            'status'  => 200,
            'message' => 'Daftar K-drama',
            'total'   => count($data),
            'data'    => $data,
        ]);
    }

    /**
     * GET /api/kdramas/{id}
     */
    public function show($id = null)
    {
        $data = $this->model->find($id);

        if (! $data) {
            return $this->failNotFound("K-drama dengan id {$id} tidak ditemukan.");
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Detail K-drama',
            'data'    => $data,
        ]);
    }

    /**
     * POST /api/kdramas
     */
    public function create()
    {
        $data = $this->payload();

        if ($data === null) {
            return $this->failValidationErrors([
                'body' => 'Body request wajib diisi dan harus berupa JSON yang valid.',
            ]);
        }

        // `name` dicek di sini, bukan pakai rule `required` di model,
        // supaya PATCH/PUT sebagian data tetap bisa jalan.
        $name = isset($data['name']) ? trim((string) $data['name']) : '';

        if ($name === '') {
            return $this->failValidationErrors([
                'name' => 'Nama kdrama wajib diisi.',
            ]);
        }

        $data['name'] = $name;

        $id = $this->model->insert($data, true);

        if ($id === false) {
            return $this->failValidationErrors($this->model->errors());
        }

        return $this->respondCreated([
            'status'  => 201,
            'message' => 'K-drama berhasil ditambahkan',
            'data'    => $this->model->find($id),
        ]);
    }

    /**
     * PUT /api/kdramas/{id}  - ganti data
     * PATCH /api/kdramas/{id} - ubah sebagian data
     */
    public function update($id = null)
    {
        $existing = $this->model->find($id);

        if (! $existing) {
            return $this->failNotFound("K-drama dengan id {$id} tidak ditemukan.");
        }

        $data = $this->payload();

        if ($data === null) {
            return $this->failValidationErrors([
                'body' => 'Body request wajib diisi dan harus berupa JSON yang valid.',
            ]);
        }

        if (isset($data['name'])) {
            $data['name'] = trim((string) $data['name']);

            if ($data['name'] === '') {
                return $this->failValidationErrors([
                    'name' => 'Nama kdrama tidak boleh kosong.',
                ]);
            }
        }

        if (! $this->model->update($id, $data)) {
            return $this->failValidationErrors($this->model->errors());
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'K-drama berhasil diperbarui',
            'data'    => $this->model->find($id),
        ]);
    }

    /**
     * DELETE /api/kdramas/{id}
     *
     * Hard delete, karena tabel `kdramas` tidak punya kolom deleted_at.
     */
    public function delete($id = null)
    {
        $existing = $this->model->find($id);

        if (! $existing) {
            return $this->failNotFound("K-drama dengan id {$id} tidak ditemukan.");
        }

        if (! $this->model->delete($id)) {
            return $this->failServerError("K-drama dengan id {$id} gagal dihapus.");
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'K-drama berhasil dihapus',
            'data'    => $existing,
        ]);
    }

    /**
     * Ambil body request.
     *
     * getJSON(true) mengembalikan array asosiatif, dan null kalau body
     * kosong atau JSON-nya rusak. getPost() dipakai sebagai fallback
     * supaya POST dari form HTML / Postman form-data tetap jalan.
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
}

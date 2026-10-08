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
     * Kolom yang dicari pakai LIKE pada endpoint /search.
     * Hanya kolom teks. Kolom angka/date tidak termasuk.
     */
    private const SEARCHABLE = [
        'name',
        'slug',
        'director',
        'screenwriter',
        'cast_members',
        'genre',
        'tags',
        'original_network',
        'synopsis',
    ];

    /**
     * Filter tepat (bukan LIKE) yang boleh dipakai di /api/kdramas.
     * Contoh: /api/kdramas?year_of_release=2021&original_network=tvN
     */
    private const FILTERS = [
        'year_of_release',
        'original_network',
        'content_rating',
        'director',
        'rank',
        'slug',
    ];

    /**
     * Filter yang nilainya wajib berupa angka bulat.
     * Kalau tidak, query langsung ditolak dengan 400 daripada diteruskan
     * ke database sebagai teks bebas.
     */
    private const NUMERIC_FILTERS = [
        'year_of_release',
        'rank',
    ];

    /**
     * GET /api/kdramas
     *
     * Tanpa query string, mengembalikan semua data.
     */
    public function index()
    {
        $builder = $this->model->orderBy('id', 'ASC');

        foreach (self::FILTERS as $column) {
            $value = $this->request->getGet($column);

            if ($value === null || $value === '') {
                continue;
            }

            if (in_array($column, self::NUMERIC_FILTERS, true)) {
                if (! ctype_digit((string) $value)) {
                    return $this->failValidationErrors([
                        $column => "Filter {$column} harus berupa angka.",
                    ]);
                }

                $builder->where($column, (int) $value);

                continue;
            }

            $builder->where($column, (string) $value);
        }

        $data = $builder->findAll();

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
     * GET /api/kdramas/search/{keyword}
     *
     * Pencarian bebas pada kolom teks. Semua query dibangun dengan
     * query builder + parameter binding, tidak ada string SQL mentah,
     * jadi aman dari SQL injection.
     */
    public function search($keyword = null)
    {
        $keyword = trim((string) $keyword);

        if ($keyword === '') {
            return $this->failValidationErrors([
                'keyword' => 'Kata kunci pencarian tidak boleh kosong.',
            ]);
        }

        // Batasi panjang keyword supaya tidak jadi jalur yang murah
        // untuk membuat query berat.
        if (mb_strlen($keyword) > 100) {
            return $this->failValidationErrors([
                'keyword' => 'Kata kunci pencarian maksimal 100 karakter.',
            ]);
        }

        // groupStart/groupEnd membungkus seluruh kondisi OR supaya tidak
        // bercampur dengan filter lain kalau nanti dipakai bareng.
        $builder = $this->model->groupStart();

        foreach (self::SEARCHABLE as $column) {
            $builder->orLike($column, $keyword);
        }

        $builder->groupEnd()->orderBy('id', 'ASC');

        $data = $builder->findAll();

        return $this->respond([
            'status'  => 200,
            'message' => 'Hasil pencarian K-drama',
            'keyword' => $keyword,
            'total'   => count($data),
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

        // Slug: pakai yang dikirim user kalau ada, kalau tidak
        // otomatis dibuat dari nama.
        $data['slug'] = $this->resolveSlug($data, $name, null);

        if ($data['slug'] === false) {
            return $this->failValidationErrors([
                'slug' => 'Maaf, gagal menambahkan karena slug sudah digunakan oleh drama lain.',
            ]);
        }

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

// Slug hanya disentuh kalau memang ada di payload.
        // Kalau PATCH tidak mengirim slug, slug lama dibiarkan apa adanya
        // supaya slug tidak ikut berubah hanya karena nama diedit.
        if (array_key_exists('slug', $data)) {
            $nameForSlug = $data['name'] ?? (string) $existing['name'];

            $resolved = $this->resolveSlug($data, $nameForSlug, $id);

            if ($resolved === false) {
                return $this->failValidationErrors([
                    'slug' => 'Maaf Update gagal karena slug sudah di gunakan oleh drama lain',
                ]);
            }

            $data['slug'] = $resolved;
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

    /**
     * Tentukan nilai slug untuk disimpan.
     *
     * - User mengirim slug  -> slug itu dibersihkan, lalu dicek
     *   apakah sudah dipakai drama lain.
     * - User tidak mengirim -> slug dibuat otomatis dari nama,
     *   dengan angka tambahan kalau nama aslinya bentrok.
     *
     * $exceptId dipakai agar drama tidak dianggap bentrok dengan
     * dirinya sendiri saat update tanpa perubahan slug.
     *
     * @param  array<string, mixed> $data
     * @return string|false string bila aman, false bila slug bentrok
     */
    private function resolveSlug(array $data, string $name, $exceptId = null)
    {
        $raw = isset($data['slug']) ? trim((string) $data['slug']) : '';

        // --- Kasus 1: user tidak mengirim slug ---
        if ($raw === '') {
            return $this->model->makeUniqueSlug($name, $exceptId);
        }

        // --- Kasus 2: user mengirim slug ---
        $slug = $this->model->slugify($raw);

        // Slug jadi kosong setelah dibersihkan, mis. user kirim "###"
        if ($slug === '') {
            return $this->model->makeUniqueSlug($name, $exceptId);
        }

        if ($this->model->slugExists($slug, $exceptId)) {
            return false;
        }

        return $slug;
    }
}

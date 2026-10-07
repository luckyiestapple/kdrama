<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model untuk tabel `kdramas`.
 *
 * Sengaja `extends Model` (bukan BaseModel) supaya file ini tidak
 * bergantung pada app/Models/BaseModel.php yang belum dibuat.
 *
 * kolom id TIDAK ada di $allowedFields, jadi field itu tidak bisa
 * diset dari luar (aman dari mass-assignment).
 */
class KdramaModel extends Model
{
    /**
     * Nama tabel di database.
     */
    protected $table = 'kdramas';

    /**
     * Mengembalikan array, bukan object.
     * Cocok untuk API karena langsung di-json_encode tanpa ubah bentuk.
     */
    protected $returnType = 'array';

    /**
     * Isi kolom created_at / updated_at otomatis.
     */
    protected $useTimestamps = true;

    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Soft delete: hapus berarti isi deleted_at, bukan benar-benar hapus baris.
     * Butuh kolom deleted_at, sudah dibuat di migration.
     */
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';

    /**
     * Kolom yang boleh diisi lewat mass-assignment (insert/update dari input user).
     */
    protected $allowedFields = [
        'name',
        'aired_date',
        'year_of_release',
        'original_network',
        'aired_on',
        'number_of_episodes',
        'duration',
        'content_rating',
        'rating',
        'synopsis',
        'genre',
        'tags',
        'director',
        'screenwriter',
        'cast_members',
        'production_companies',
        'rank',
    ];

    /**
     * Kolom yang boleh dipakai untuk filter di endpoint index.
     * Dipakai controller untuk menolak nama filter yang tidak dikenal.
     */
    public array $filterable = [
        'year_of_release',
        'original_network',
        'content_rating',
        'director',
        'rank',
    ];

    /**
     * Aturan validasi saat insert/update.
     *
     * PENTING: `name` sengaja TIDAK memakai rule `required` di sini.
     * CI4 menolak payload yang tidak menyertakan field ber-rule `required`,
     * sehingga aturan itu akan membuat setiap PATCH parsial
     * (misal hanya kirim {"rating": 9.5}) selalu gagal.
     * Kewajiban `name` dicek di controller pada method create().
     *
     * Kolom `rating` pakai `decimal` sehingga CI4 otomatis memangkas
     * "9.20" jadi "9.2" supaya presisi kolom (3,1) tidak error.
     */
    protected $validationRules = [
        'name'              => 'min_length[2]|max_length[255]',
        'aired_date'        => 'permit_empty|max_length[100]',
        'year_of_release'   => 'permit_empty|integer|greater_than_equal_to[1900]|less_than_equal_to[2100]',
        'original_network'  => 'permit_empty|max_length[255]',
        'aired_on'          => 'permit_empty|max_length[100]',
        'number_of_episodes' => 'permit_empty|integer|greater_than[0]',
        'duration'          => 'permit_empty|max_length[50]',
        'content_rating'    => 'permit_empty|max_length[100]',
        'rating'            => 'permit_empty|numeric|less_than_equal_to[10]',
        'synopsis'          => 'permit_empty',
        'genre'             => 'permit_empty',
        'tags'              => 'permit_empty',
        'director'          => 'permit_empty',
        'screenwriter'      => 'permit_empty',
        'cast_members'      => 'permit_empty',
        'production_companies' => 'permit_empty',
        'rank'              => 'permit_empty|integer|greater_than[0]',
    ];

    /**
     * Pesan error validasi dalam bahasa Indonesia.
     */
    protected $validationMessages = [
        'name' => [
            'min_length'  => 'Nama kdrama minimal 2 karakter.',
            'max_length'  => 'Nama kdrama maksimal 255 karakter.',
        ],
        'year_of_release' => [
            'integer'     => 'Tahun rilis harus berupa angka.',
            'greater_than_equal_to' => 'Tahun rilis tidak boleh kurang dari 1900.',
            'less_than_equal_to'    => 'Tahun rilis tidak boleh lebih dari 2100.',
        ],
        'number_of_episodes' => [
            'integer'     => 'Jumlah episode harus berupa angka.',
            'greater_than' => 'Jumlah episode harus lebih dari 0.',
        ],
        'rating' => [
            'numeric'     => 'Rating harus berupa angka.',
            'less_than_equal_to' => 'Rating maksimal 10.',
        ],
        'rank' => [
            'integer'     => 'Rank harus berupa angka.',
            'greater_than' => 'Rank harus lebih dari 0.',
        ],
        'aired_date'       => ['max_length' => 'Tanggal tayang maksimal 100 karakter.'],
        'original_network' => ['max_length' => 'Jaringan asal maksimal 255 karakter.'],
        'aired_on'         => ['max_length' => 'Hari tayang maksimal 100 karakter.'],
        'duration'         => ['max_length' => 'Durasi maksimal 50 karakter.'],
        'content_rating'   => ['max_length' => 'Rating konten maksimal 100 karakter.'],
    ];

    /**
     * Urutan default saat menampilkan daftar.
     */
    public function orderByRank(string $direction = 'ASC')
    {
        return $this->orderBy('rank', $direction, true);
    }
}

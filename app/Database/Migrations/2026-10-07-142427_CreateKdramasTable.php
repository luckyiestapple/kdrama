<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Membuat tabel `kdramas`.
 *
 * Mengikuti app/README_KDRAMAS.md bagian 5 & 6:
 * - Driver PostgreSQL, primary key pakai SERIAL
 * - rating NUMERIC(3,1)
 * - created_at & updated_at TIMESTAMP NOT NULL
 * - TIDAK ada kolom deleted_at, jadi delete nanti hard delete
 *
 * Catatan: migration ini membuat TABEL, bukan database.
 * Database `kdrama` harus dibuat manual dulu.
 */
class CreateKdramasTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'SERIAL',
                'unsigned'       => false,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'aired_date' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'year_of_release' => [
                'type' => 'INTEGER',
                'null' => true,
            ],
            'original_network' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'aired_on' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'number_of_episodes' => [
                'type' => 'INTEGER',
                'null' => true,
            ],
            'duration' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'content_rating' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'rating' => [
                'type'       => 'NUMERIC',
                'constraint' => '3,1',
                'null'       => true,
            ],
            'synopsis' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'genre' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'tags' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'director' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'screenwriter' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'cast_members' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'production_companies' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'rank' => [
                'type' => 'INTEGER',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('kdramas', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('kdramas', true);
    }
}

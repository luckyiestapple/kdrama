<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDramasTable extends Migration
{
    public function up()
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
                'constraint' => '2,1',
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
        ]);

        $this->forge->addKey('id', true);

        // Nama tabel yang benar
        $this->forge->createTable('dramas');
    }

    public function down()
    {
        // Nama tabel yang benar
        $this->forge->dropTable('dramas');
    }
}
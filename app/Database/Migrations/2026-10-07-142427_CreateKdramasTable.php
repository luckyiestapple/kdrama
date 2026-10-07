<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKdramasTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
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
                'type' => 'INT',
                'constraint' => 11,
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
                'type' => 'INT',
                'constraint' => 11,
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
                'type'       => 'DECIMAL',
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
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],

            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('deleted_at');

        $this->forge->createTable('kdramas', true);
    }

    public function down()
    {
        $this->forge->dropTable('kdramas');
    }
}
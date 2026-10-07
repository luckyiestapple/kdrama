# REST API CodeIgniter 4 --- K-Drama

## Studi Kasus: CRUD Tabel K-Drama dengan PostgreSQL

Dokumentasi ini mengadaptasi panduan REST API CodeIgniter 4 berbasis
PostgreSQL dari studi kasus `mahasiswa` menjadi studi kasus `kdramas`.
Panduan sumber menjelaskan penggunaan migration, seeder, model,
`ResourceController`, resource route, Postman/cURL, dan troubleshooting
PostgreSQL. fileciteturn4file0L7-L14

## 1. Teknologi

-   PHP 8.1+
-   CodeIgniter 4.7.4
-   PostgreSQL
-   REST API + JSON
-   Postman / cURL
-   PHP extensions: `intl`, `mbstring`, `pgsql`, `pdo_pgsql`

## 2. Database

Buat database PostgreSQL terlebih dahulu:

``` sql
CREATE DATABASE kdrama;
```

Migration CodeIgniter digunakan untuk membuat **tabel**, bukan database.

## 3. Konfigurasi `.env`

``` env
CI_ENVIRONMENT = development

app.baseURL = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = kdrama
database.default.username = postgres
database.default.password =
database.default.DBDriver = Postgre
database.default.DBPrefix =
database.default.port = 5432
database.default.pConnect = false
database.default.DBDebug = true
database.default.charset = utf8
```

Untuk PostgreSQL, `database.default.charset = utf8` perlu ditambahkan
agar CI4 tidak mengirim `utf8mb4` sebagai `client_encoding`. Panduan
sumber juga menjelaskan masalah ini pada konfigurasi PostgreSQL.
fileciteturn4file0L50-L67

## 4. Konfigurasi `app/Config/Database.php`

Pastikan koneksi aktif menggunakan PostgreSQL:

``` php
public array $default = [
    'DSN'          => '',
    'hostname'     => 'localhost',
    'username'     => 'postgres',
    'password'     => '',
    'database'     => 'kdrama',
    'DBDriver'     => 'Postgre',
    'DBPrefix'     => '',
    'pConnect'     => false,
    'DBDebug'      => true,
    'charset'      => 'UTF8',
    'DBCollat'     => '',
    'swapPre'      => '',
    'encrypt'      => false,
    'compress'     => false,
    'strictOn'     => false,
    'failover'     => [],
    'port'         => 5432,
    'dateFormat'   => [
        'date'     => 'Y-m-d',
        'datetime' => 'Y-m-d H:i:s',
        'time'     => 'H:i:s',
    ],
];
```

Jangan mengubah file `vendor/codeigniter4/framework/...`.

## 5. Struktur Tabel `kdramas`

  Kolom                  Tipe
  ---------------------- -----------------------
  id                     SERIAL PRIMARY KEY
  name                   VARCHAR(255) NOT NULL
  aired_date             VARCHAR(100)
  year_of_release        INTEGER
  original_network       VARCHAR(255)
  aired_on               VARCHAR(100)
  number_of_episodes     INTEGER
  duration               VARCHAR(50)
  content_rating         VARCHAR(100)
  rating                 NUMERIC(3,1)
  synopsis               TEXT
  genre                  TEXT
  tags                   TEXT
  director               TEXT
  screenwriter           TEXT
  cast_members           TEXT
  production_companies   TEXT
  rank                   INTEGER
  created_at             TIMESTAMP
  updated_at             TIMESTAMP

Nama tabel yang digunakan adalah **`kdramas`**.

## 6. Migration

File:

``` text
app/Database/Migrations/2026-10-07-142427_CreateKdramasTable.php
```

``` php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

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
```

Jalankan:

``` bash
php spark migrate
```

Pola migration/seeder ini mengikuti struktur panduan sumber, yang
menjalankan migration sebelum seeder. fileciteturn4file0L68-L71
fileciteturn4file0L161-L166

## 7. Seeder

File:

``` text
app/Database/Seeds/KdramaSeeder.php
```

Struktur:

``` php
<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class KdramaSeeder extends Seeder
{
    /**
     * Run the K-drama seeder.
     *
     * Run:
     * php spark db:seed KdramaSeeder
     */
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $data = [
            // Data K-drama
        ];

        foreach ($data as &$drama) {
            $drama['created_at'] = $now;
            $drama['updated_at'] = $now;
        }

        unset($drama);

        $this->db->table('kdramas')->truncate();
        $this->db->table('kdramas')->insertBatch($data);
    }
}
```

Jalankan:

``` bash
php spark db:seed KdramaSeeder
```

Cek:

``` bash
php spark db:query "SELECT COUNT(*) FROM kdramas;"
```

Seeder pada panduan sumber juga mengisi `created_at` dan `updated_at`
sebelum `insertBatch()`. fileciteturn4file0L122-L163

## 8. Model

File:

``` text
app/Models/KdramaModel.php
```

``` php
<?php

namespace App\Models;

use CodeIgniter\Model;

class KdramaModel extends Model
{
    protected $table = 'kdramas';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

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

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
}
```

Panduan sumber menggunakan `$allowedFields`, `$useTimestamps`,
`$dateFormat`, dan model sebagai penghubung antara controller dan tabel
database. fileciteturn4file0L167-L169
fileciteturn4file0L173-L207

## 9. Endpoint REST API

  Method   Endpoint              Operasi              Status
  -------- --------------------- -------------------- --------
  GET      `/api/kdramas`        Semua K-drama        200
  GET      `/api/kdramas/{id}`   Detail K-drama       200
  POST     `/api/kdramas`        Tambah K-drama       201
  PUT      `/api/kdramas/{id}`   Ubah data            200
  PATCH    `/api/kdramas/{id}`   Ubah sebagian data   200
  DELETE   `/api/kdramas/{id}`   Hapus data           200

Pemetaan method HTTP ke CRUD mengikuti struktur panduan REST API sumber.
fileciteturn4file0L21-L32

## 10. Controller

File:

``` text
app/Controllers/Api/Kdramas.php
```

Contoh controller:

``` php
<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

class Kdramas extends ResourceController
{
    protected $modelName = 'App\Models\KdramaModel';
    protected $format = 'json';

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

    public function show($id = null)
    {
        $data = $this->model->find($id);

        if (! $data) {
            return $this->failNotFound(
                "K-drama dengan id {$id} tidak ditemukan."
            );
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Detail K-drama',
            'data'    => $data,
        ]);
    }
}
```

`ResourceController` pada panduan digunakan untuk menyediakan response
REST seperti `respond()`, `respondCreated()`, `failNotFound()`, dan
`failValidationErrors()`. fileciteturn4file0L214-L217

## 11. Routes

Pada `app/Config/Routes.php`:

``` php
$routes->group('api', [
    'namespace' => 'App\Controllers\Api'
], static function ($routes) {
    $routes->resource('kdramas', [
        'except' => 'new,edit'
    ]);
});
```

Cek:

``` bash
php spark routes
```

Target:

``` text
GET     api/kdramas
GET     api/kdramas/(.*)
POST    api/kdramas
PATCH   api/kdramas/(.*)
PUT     api/kdramas/(.*)
DELETE  api/kdramas/(.*)
```

Pola `resource()` dan pengecualian `new,edit` mengikuti panduan sumber.
fileciteturn4file0L321-L341

## 12. Menjalankan Server

``` bash
php spark serve
```

API:

``` text
http://localhost:8080/api/kdramas
```

Panduan sumber menggunakan `php spark serve` dan URL lokal
`/api/mahasiswa`; pada project ini endpoint disesuaikan menjadi
`/api/kdramas`. fileciteturn4file0L342-L346

## 13. Pengujian cURL

### GET semua data

``` bash
curl http://localhost:8080/api/kdramas
```

Expected:

``` text
200 OK
```

### GET detail

``` bash
curl http://localhost:8080/api/kdramas/1
```

Expected:

``` text
200 OK
```

### POST

``` bash
curl -X POST http://localhost:8080/api/kdramas \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Contoh K-Drama",
    "year_of_release": 2026,
    "rating": 8.5,
    "genre": "Romance, Drama",
    "synopsis": "Contoh sinopsis."
  }'
```

Expected:

``` text
201 Created
```

### PUT

``` bash
curl -X PUT http://localhost:8080/api/kdramas/1 \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Judul Diperbarui",
    "rating": 9.0
  }'
```

Expected:

``` text
200 OK
```

### PATCH

``` bash
curl -X PATCH http://localhost:8080/api/kdramas/1 \
  -H "Content-Type: application/json" \
  -d '{"rating":9.2}'
```

Expected:

``` text
200 OK
```

### DELETE

``` bash
curl -X DELETE http://localhost:8080/api/kdramas/1
```

Expected:

``` text
200 OK
```

### Data tidak ditemukan

``` bash
curl http://localhost:8080/api/kdramas/99999
```

Expected:

``` text
404 Not Found
```

Panduan sumber juga mencakup pengujian GET, POST, PUT, PATCH, DELETE,
validasi, dan data yang tidak ditemukan. fileciteturn4file0L369-L518

## 14. Postman

Buat collection:

``` text
API K-Drama
```

Variable:

``` text
base_url = http://localhost:8080/api
```

Request:

``` text
GET     {{base_url}}/kdramas
GET     {{base_url}}/kdramas/1
POST    {{base_url}}/kdramas
PUT     {{base_url}}/kdramas/1
PATCH   {{base_url}}/kdramas/1
DELETE  {{base_url}}/kdramas/1
```

Untuk POST, PUT, dan PATCH gunakan:

``` text
Body → raw → JSON
```

Header:

``` text
Content-Type: application/json
```

Panduan sumber menggunakan pola Postman yang sama untuk endpoint CRUD.
fileciteturn4file0L347-L356

## 15. Checklist Pengujian

  No   Skenario                      Method     Expected
  ---- ----------------------------- -------- ----------
  1    GET semua K-drama             GET             200
  2    GET detail berdasarkan ID     GET             200
  3    POST tambah K-drama           POST            201
  4    POST data tidak valid         POST            400
  5    PUT ubah data                 PUT             200
  6    PATCH sebagian data           PATCH           200
  7    DELETE K-drama                DELETE          200
  8    GET ID tidak ada              GET             404
  9    POST body kosong/JSON rusak   POST            400

Struktur checklist ini disesuaikan dari lembar pengujian pada panduan
sumber. fileciteturn4file0L525-L542

## 16. Troubleshooting

### `invalid value for parameter "client_encoding": "utf8mb4"`

Tambahkan:

``` env
database.default.charset = utf8
```

dan pastikan `Database.php` tidak memakai:

``` php
'charset' => 'utf8mb4',
'DBCollat' => 'utf8mb4_general_ci',
```

Panduan sumber mencantumkan error ini sebagai masalah charset default
CI4 untuk MySQL. fileciteturn4file0L543-L550

### `Unable to connect to the database`

Periksa service PostgreSQL serta hostname, database, username, password,
dan port. fileciteturn4file0L551-L556

### `Call to undefined function pg_connect()`

Aktifkan:

``` ini
extension=pgsql
extension=pdo_pgsql
```

lalu restart terminal/server. fileciteturn4file0L557-L559

### `relation "kdramas" does not exist`

Jalankan:

``` bash
php spark migrate
```

Panduan sumber menjelaskan bahwa relation/table yang belum ada biasanya
berarti migration belum dijalankan. fileciteturn4file0L570-L573

## 17. Urutan Menjalankan Project

``` bash
cd D:\Techx\hert\kdrama

composer install

php spark migrate

php spark db:seed KdramaSeeder

php spark routes

php spark serve
```

Kemudian buka:

``` text
http://localhost:8080/api/kdramas
```

## 18. Struktur Project

``` text
kdrama/
├── app/
│   ├── Config/
│   │   ├── Database.php
│   │   └── Routes.php
│   ├── Controllers/
│   │   └── Api/
│   │       └── Kdramas.php
│   ├── Database/
│   │   ├── Migrations/
│   │   │   └── 2026-10-07-142427_CreateKdramasTable.php
│   │   └── Seeds/
│   │       └── KdramaSeeder.php
│   └── Models/
│       └── KdramaModel.php
├── public/
├── vendor/
├── writable/
├── .env
├── composer.json
└── spark
```

## 19. Alur API

``` text
Client
  │
  │ HTTP Request
  ▼
Routes
  │
  ▼
Kdramas Controller
  │
  ▼
KdramaModel
  │
  ▼
PostgreSQL
  │
  ▼
JSON Response
  │
  ▼
Client
```

## 20. Catatan

-   Database PostgreSQL: `kdrama`
-   Tabel: `kdramas`
-   Driver: `Postgre`
-   Charset PostgreSQL: `UTF8`
-   Rating: `NUMERIC(3,1)`
-   Timestamps: `created_at`, `updated_at`
-   Migration membuat tabel, bukan database.
-   Seeder menggunakan `kdramas`.
-   Jangan mengubah file framework di `vendor/`.

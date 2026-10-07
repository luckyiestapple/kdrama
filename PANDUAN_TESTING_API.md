# Panduan Penggunaan & Testing API K-Drama

Dokumen ini adalah panduan lengkap dari **nol sampai semua route teruji**.
Acuan spesifikasi: `README_KDRAMAS.md`.

---

## Daftar Isi

1. [Prasyarat](#1-prasyarat)
2. [Setup dari Nol](#2-setup-dari-nol)
3. [Daftar Route](#3-daftar-route)
4. [Format Response](#4-format-response)
5. [Testing Semua Route](#5-testing-semua-route)
6. [Checklist 9 Skenario](#6-checklist-9-skenario)
7. [Testing dengan Postman](#7-testing-dengan-postman)
8. [Perilaku Edge Case](#8-perilaku-edge-case-yang-perlu-diketahui)
9. [Troubleshooting](#9-troubleshooting)

---

## 1. Prasyarat

| Kebutuhan | Keterangan |
|---|---|
| PHP | 8.2 atau lebih tinggi |
| Composer | Untuk `composer install` |
| PostgreSQL | Servis harus **sudah jalan** |
| Extension PHP | `intl`, `mbstring`, **`pgsql`**, **`pdo_pgsql`** |

Cek extension PostgreSQL sudah aktif atau belum:

```bash
php -m
```

Di daftar output harus ada `pgsql` dan `pdo_pgsql`. Kalau tidak ada, buka
`php.ini` lalu hapus tanda `;` (titik koma) pada:

```ini
extension=pgsql
extension=pdo_pgsql
```

Lalu restart Apache/XAMPP.

---

## 2. Setup dari Nol

### Langkah 1 — Masuk ke folder project

```bash
cd D:\Techx\hert\kdrama
```

### Langkah 2 — Pastikan service PostgreSQL jalan

Buka **SQLite Manager / pgAdmin / Services** di Windows, lalu start PostgreSQL.
Port default `5432`.

### Langkah 3 — Install dependency

```bash
composer install
```

> Kalau muncul error `Could not find a version of package ...` atau
> `ext-pdo_pgsql * -> *`, artinya extension PostgreSQL belum aktif.
> Kembali ke [bagian 1](#1-prasyarat).

### Langkah 4 — Cek `.env`

Pastikan isinya sudah PostgreSQL:

```env
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

> **`charset` wajib `utf8`, bukan `utf8mb4`.** Lihat
> [Troubleshooting](#9-troubleshooting).

Buat database `kdrama` **secara manual** di phpMyAdmin/pgAdmin:

```sql
CREATE DATABASE kdrama;
```

> Migration CodeIgniter membuat **TABEL**, bukan **DATABASE**.
> Database harus dibuat manual lebih dulu.

### Langkah 5 — Jalankan migration

```bash
php spark migrate
```

Output sukses:

```
Running migrations for... App\Database\Migrations\...
  1. CreateKdramasTable
```

Cek tabelnya sudah ada:

```bash
php spark db:table kdramas --show
```

### Langkah 6 — Jalankan seeder

```bash
php spark db:seed KdramaSeeder
```

Cek jumlah datanya:

```bash
php spark db:query "SELECT COUNT(*) FROM kdramas;"
```

Harus keluar `15`.

### Langkah 7 — Cek route terdaftar

```bash
php spark routes
```

Pastikan muncul 6 baris `api/kdramas`:

```
GET     api/kdramas      App\Controllers\Api\Kdramas::index
POST    api/kdramas      App\Controllers\Api\Kdramas::create
GET     api/kdramas/(.*) App\Controllers\Api\Kdramas::show
PUT     api/kdramas/(.*) App\Controllers\Api\Kdramas::update
PATCH   api/kdramas/(.*) App\Controllers\Api\Kdramas::update
DELETE  api/kdramas/(.*) App\Controllers\Api\Kdramas::delete
```

### Langkah 8 — Jalankan server

```bash
php spark serve
```

Server akan berjalan di `http://localhost:8080`. **Jangan tutup terminal ini**
selama testing.

---

## 3. Daftar Route

Semua route memakai prefix `/api/kdramas`.

| Method | Endpoint | Fungsi | Sukses |
|---|---|---|---|
| `GET` | `/api/kdramas` | Ambil semua K-drama | `200` |
| `GET` | `/api/kdramas/{id}` | Ambil detail satu K-drama | `200` |
| `POST` | `/api/kdramas` | Tambah K-drama baru | `201` |
| `PUT` | `/api/kdramas/{id}` | Ganti data K-drama | `200` |
| `PATCH` | `/api/kdramas/{id}` | Ubah sebagian data | `200` |
| `DELETE` | `/api/kdramas/{id}` | Hapus K-drama | `200` |

---

## 4. Format Response

Penting: **response sukses dan response error punya bentuk berbeda.**

### Response sukses

```json
{
    "status": 200,
    "message": "Daftar K-drama",
    "total": 15,
    "data": [ ... ]
}
```

`total` hanya ada di `GET /api/kdramas`.

### Response error

```json
{
    "status": 404,
    "code": 404,
    "messages": {
        "error": "K-drama dengan id 99999 tidak ditemukan."
    }
}
```

### Dua hal yang akan terlihat "aneh" tapi itu normal

| Field | Tipe | Penjelasan |
|---|---|---|
| `id` | `1` (angka) | `SERIAL` di PostgreSQL dikembalikan sebagai integer |
| `rating` | `"9.2"` (**string**) | Tipe `NUMERIC` di PostgreSQL **selalu** dikembalikan sebagai string |
| `created_at` | `"2026-10-07 14:30:00"` | Format `Y-m-d H:i:s` |

Kalau `rating` perlu jadi angka di JSON, bilang saja — tinggal ditambah
satu baris konversi di model.

---

## 5. Testing Semua Route

> **Perbedaan antar terminal.** Kamu memakai Windows, dan Windows CMD
> **tidak bisa** memakai single quote `'`. Karena itu tiap perintah
> di bawah punya 3 versi. Pilih sesuai terminal yang kamu pakai.

### 5.1 GET — Ambil semua K-drama

**CMD / PowerShell:**
```cmd
curl -X GET http://localhost:8080/api/kdramas
```

**Bash / Git Bash:**
```bash
curl -X GET http://localhost:8080/api/kdramas
```

**Harus:**
```json
{
    "status": 200,
    "message": "Daftar K-drama",
    "total": 15,
    "data": [
        {
            "id": 1,
            "name": "Move to Heaven",
            "aired_date": "May 14, 2021",
            "year_of_release": 2021,
            "original_network": "Netflix",
            "rating": "9.2",
            "rank": 1,
            "created_at": "2026-10-07 14:30:00",
            "updated_at": "2026-10-07 14:30:00"
        }
    ]
}
```

---

### 5.2 GET — Ambil detail satu K-drama

```cmd
curl -X GET http://localhost:8080/api/kdramas/1
```

**Harus** `200` dengan `message: "Detail K-drama"`.

---

### 5.3 POST — Tambah K-drama baru

**Bash / Git Bash:**
```bash
curl -X POST http://localhost:8080/api/kdramas \
  -H "Content-Type: application/json" \
  -d '{"name":"Contoh K-Drama","year_of_release":2026,"rating":8.5,"genre":"Romance, Drama","synopsis":"Contoh sinopsis."}'
```

**Windows CMD** (single quote tidak bisa dipakai):
```cmd
curl -X POST http://localhost:8080/api/kdramas -H "Content-Type: application/json" -d "{\"name\":\"Contoh K-Drama\",\"year_of_release\":2026,\"rating\":8.5,\"genre\":\"Romance, Drama\",\"synopsis\":\"Contoh sinopsis.\"}"
```

**Cara paling aman di Windows** — pakai file JSON. Buat `data.json`:
```json
{
  "name": "Contoh K-Drama",
  "year_of_release": 2026,
  "rating": 8.5,
  "genre": "Romance, Drama",
  "synopsis": "Contoh sinopsis."
}
```

Lalu jalankan (berlaku di semua terminal):
```cmd
curl -X POST http://localhost:8080/api/kdramas -H "Content-Type: application/json" -d @data.json
```

**Harus** HTTP `201 Created`:
```json
{
    "status": 201,
    "message": "K-drama berhasil ditambahkan",
    "data": {
        "id": 16,
        "name": "Contoh K-Drama",
        "rating": "8.5",
        ...
    }
}
```

> **Catatan PowerShell:** pakai `curl.exe`, bukan `curl`. Di PowerShell
> versi lama, `curl` adalah alias dari `Invoke-WebRequest` yang
> parameternya berbeda.

---

### 5.4 PUT — Ganti data

```bash
curl -X PUT http://localhost:8080/api/kdramas/1 \
  -H "Content-Type: application/json" \
  -d '{"name":"Judul Diperbarui","rating":9.0}'
```

**CMD:**
```cmd
curl -X PUT http://localhost:8080/api/kdramas/1 -H "Content-Type: application/json" -d "{\"name\":\"Judul Diperbarui\",\"rating\":9.0}"
```

**Harus** `200` dengan `message: "K-drama berhasil diperbarui"`.

---

### 5.5 PATCH — Ubah sebagian data

```bash
curl -X PATCH http://localhost:8080/api/kdramas/1 \
  -H "Content-Type: application/json" \
  -d '{"rating":9.2}'
```

**CMD:**
```cmd
curl -X PATCH http://localhost:8080/api/kdramas/1 -H "Content-Type: application/json" -d "{\"rating\":9.2}"
```

**Harus** `200`. Field lain tidak berubah.

> Kenapa PATCH hanya boleh sebagian field: aturan validasi model
> sengaja tidak memakai `required` pada `name`, supaya payload parsial
> tidak ditolak.

---

### 5.6 DELETE — Hapus K-drama

```bash
curl -X DELETE http://localhost:8080/api/kdramas/1
```

**Harus** `200`:
```json
{
    "status": 200,
    "message": "K-drama berhasil dihapus",
    "data": { "id": 1, "name": "...", ... }
}
```

`data` berisi data **sebelum** dihapus, jadi masih bisa dilihat though
datanya sudah hilang dari database.

> Ini **hard delete**, bukan soft delete. Baris benar-benar hilang dari
> database dan tidak bisa dikembalikan.

---

### 5.7 GET — Data tidak ditemukan (uji 404)

```bash
curl -X GET http://localhost:8080/api/kdramas/99999
```

**Harus** HTTP `404 Not Found`:
```json
{
    "status": 404,
    "code": 404,
    "messages": {
        "error": "K-drama dengan id 99999 tidak ditemukan."
    }
}
```

---

### 5.8 POST — Data tidak valid (uji 400)

**Kirim `name` kosong:**
```bash
curl -X POST http://localhost:8080/api/kdramas \
  -H "Content-Type: application/json" \
  -d '{"name":"","year_of_release":2026}'
```

**Kirim `rating` di atas 10:**
```bash
curl -X POST http://localhost:8080/api/kdramas \
  -H "Content-Type: application/json" \
  -d '{"name":"Rating Gila","rating":99}'
```

**Harus** HTTP `400 Bad Request`:
```json
{
    "status": 400,
    "code": 400,
    "messages": {
        "rating": "Rating maksimal 10."
    }
}
```

---

### 5.9 POST — Body kosong atau JSON rusak (uji 400)

```bash
curl -X POST http://localhost:8080/api/kdramas \
  -H "Content-Type: application/json" \
  -d '{jsonrusak'
```

**Harus** HTTP `400 Bad Request`:
```json
{
    "status": 400,
    "code": 400,
    "messages": {
        "body": "Body request wajib diisi dan harus berupa JSON yang valid."
    }
}
```

---

### 5.10 POST — Tidak kirim `name` sama sekali (uji 400)

```bash
curl -X POST http://localhost:8080/api/kdramas \
  -H "Content-Type: application/json" \
  -d '{"year_of_release":2026}'
```

**Harus** `400` dengan `messages.name`.

---

## 6. Checklist 9 Skenario

Jalankan berurutan, tandai ✅ atau ❌.

| # | Skenario | Method | Perintah | Expected |
|---|---|---|---|---|
| 1 | GET semua K-drama | `GET` | `/api/kdramas` | `200` |
| 2 | GET detail berdasarkan ID | `GET` | `/api/kdramas/1` | `200` |
| 3 | POST tambah K-drama | `POST` | `/api/kdramas` | `201` |
| 4 | POST data tidak valid | `POST` | rating `99` | `400` |
| 5 | PUT ubah data | `PUT` | `/api/kdramas/1` | `200` |
| 6 | PATCH sebagian data | `PATCH` | `/api/kdramas/1` | `200` |
| 7 | DELETE K-drama | `DELETE` | `/api/kdramas/1` | `200` |
| 8 | GET ID tidak ada | `GET` | `/api/kdramas/99999` | `404` |
| 9 | POST body kosong/JSON rusak | `POST` | `{rusak` | `400` |

Cara paling cepat cek status code tanpa parse output JSON:

```cmd
curl -s -o NUL -w "HTTP %%{http_code}\n" http://localhost:8080/api/kdramas
```

Di Bash/Git Bash, ganti `NUL` dengan `>/dev/null`:

```bash
curl -s -o /dev/null -w "HTTP %{http_code}\n" http://localhost:8080/api/kdramas
```

---

## 7. Testing dengan Postman

### Koleksi

Buat collection baru bernama `API K-Drama`.

### Variable

Tambahkan environment variable:

| Nama | Nilai |
|---|---|
| `base_url` | `http://localhost:8080/api` |
| `id` | `1` |

### Request

| Nama | Method | URL |
|---|---|---|
| Get All | `GET` | `{{base_url}}/kdramas` |
| Get Detail | `GET` | `{{base_url}}/kdramas/{{id}}` |
| Create | `POST` | `{{base_url}}/kdramas` |
| Update | `PUT` | `{{base_url}}/kdramas/{{id}}` |
| Patch | `PATCH` | `{{base_url}}/kdramas/{{id}}` |
| Delete | `DELETE` | `{{base_url}}/kdramas/{{id}}` |

### Setting body

Untuk `Create`, `Update`, dan `Patch`:

1. Tab **Body**
2. Pilih **raw**
3. Pilih **JSON** dari dropdown tipe
4. Isi JSON-nya

### Header

Untuk semua request yang mengirim body, tambahkan header:

```
Content-Type: application/json
```

### Cara cek status code

Klik request → tab **Body** di area response. Status code ada di bagian
atas, misal `201 Created` atau `404 Not Found`.

---

## 8. Perilaku Edge Case yang Perlu Diketahui

### 8.1 Kirim field yang tidak dikenal

Kalau kamu POST atau PUT dengan field yang tidak ada di `$allowedFields`,
field itu **diabaikan diam-diam**.

```bash
curl -X PUT http://localhost:8080/api/kdramas/1 \
  -H "Content-Type: application/json" \
  -d '{"namanya_salah":"test"}'
```

Field `id`, `created_at`, dan `updated_at` juga tidak bisa diset dari luar
(mass-assignment protection).

### 8.2 Kirim HANYA field yang tidak dikenal

Kalau payload-nya **murni** berisi field yang diabaikan, `Model::update()`
akan melempar exception dan kamu dapat **HTTP 500**, bukan 400.

```bash
# Payload ini tidak punya satu pun field yang valid
curl -X PUT http://localhost:8080/api/kdramas/1 \
  -H "Content-Type: application/json" \
  -d '{"ngawur":1}'
```

Ini perilaku yang belum rapi. Kalau kamu mau, saya bisa ubah jadi
mengembalikan `400` dengan pesan yang jelas.

### 8.3 `rating` dikirim sebagai teks

Nilai `rating` tetap diterima karena kolomnya `NUMERIC`:

```bash
curl -X POST http://localhost:8080/api/kdramas \
  -H "Content-Type: application/json" \
  -d '{"name":"Rating Teks","rating":"8.7"}'
```

Hasilnya tetap tersimpan `8.7`.

### 8.4 Duplicate `id` saat DELETE

`DELETE /api/kdramas/1` dua kali:

- Yang pertama → `200`
- Yang kedua → `404` (data sudah tidak ada)

---

## 9. Troubleshooting

### `invalid value for parameter "client_encoding": "utf8mb4"`

CI4 secara default memakai `utf8mb4`. PostgreSQL tidak mengenali itu.

Perbaiki `.env`:
```env
database.default.charset = utf8
```

Dan pastikan `app/Config/Database.php` tidak memakai:
```php
'charset' => 'utf8mb4',
'DBCollat' => 'utf8mb4_general_ci',
```

Kalau `.env` sudah benar tapi error masih muncul, tambahkan juga
`charset` dan `DBCollat` di `app/Config/Database.php`.

---

### `Unable to connect to the database`

Cek satu per satu:

1. Service PostgreSQL sudah jalan? (Windows Services / pgAdmin)
2. Port benar `5432`?
3. Username `postgres` dan password benar?
4. Database `kdrama` sudah dibuat?

Tes koneksi langsung dari CMD:
```cmd
psql -U postgres -h localhost -p 5432 -c "SELECT 1;"
```

---

### `Call to undefined function pg_connect()`

Extension PostgreSQL belum aktif. Buka `php.ini`:

```ini
extension=pgsql
extension=pdo_pgsql
```

Lalu **restart Apache/XAMPP**, bukan hanya terminal.

---

### `relation "kdramas" does not exist`

Migration belum dijalankan:

```bash
php spark migrate
```

---

### Error merah di editor (VS Code)

Error `Undefined type 'CodeIgniter\Model'`, `Undefined property '$model'`,
dan sejenisnya **bukan bug kode**. Itu karena folder `vendor/` belum ada.

```bash
composer install
```

Lalu restart PHP language server: `Ctrl+Shift+P` → **PHP: Restart Language Server**.

---

### `Class "KdramaSeeder" not found` saat seeding

Pastikan namespace di file seeder adalah `App\Database\Seeds` dan nama
class cocok dengan nama file:

```php
namespace App\Database\Seeds;

class KdramaSeeder extends Seeder
```

---

### Port 8080 sudah dipakai

`php spark serve` gagal dengan error port. Pakai port lain:

```bash
php spark serve --port 8081
```

Lalu akses `http://localhost:8081/api/kdramas`.

---

## Ringkasan Urutan Kerja

```bash
cd D:\Techx\hert\kdrama
composer install
php spark migrate
php spark db:seed KdramaSeeder
php spark routes
php spark serve
```

Buka browser:

```text
http://localhost:8080/api/kdramas
```

Kalau sudah muncul JSON dengan `"message": "Daftar K-drama"`, berarti
CRUD sudah siap dipakai. Jalankan [checklist 9 skenario](#6-checklist-9-skenario)
untuk memastikan semua route bekerja.

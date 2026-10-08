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
10. [Alur Kodingan](#10-alur-kodingan-request-lifecycle)

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
| `GET` | `/api/kdramas?year_of_release=2021` | Filter berdasarkan kolom | `200` |
| `GET` | `/api/kdramas/search/{keyword}` | Cari bebas pada kolom teks | `200` |
| `GET` | `/api/kdramas/{id}` | Ambil detail satu K-drama | `200` |
| `POST` | `/api/kdramas` | Tambah K-drama baru | `201` |
| `PUT` | `/api/kdramas/{id}` | Ganti data K-drama | `200` |
| `PATCH` | `/api/kdramas/{id}` | Ubah sebagian data | `200` |
| `DELETE` | `/api/kdramas/{id}` | Hapus K-drama | `200` |

### Kolom yang bisa difilter

`GET /api/kdramas` menerima query string berikut:

| Parameter | Tipe | Contoh |
|---|---|---|
| `year_of_release` | angka | `?year_of_release=2021` |
| `rank` | angka | `?rank=1` |
| `original_network` | teks, pencocokan persis | `?original_network=tvN` |
| `content_rating` | teks, pencocokan persis | `?content_rating=15+` |
| `director` | teks, pencocokan persis | `?director=Kim Won Suk` |
| `slug` | teks, pencocokan persis | `?slug=my-mister` |

Boleh dipakai berurutan, hasilnya digabung dengan AND:

```text
/api/kdramas?year_of_release=2021&original_network=tvN
```

> Filter angka wajib angka. Kalau mengirim `?year_of_release=abc`,
> jawabannya `400`, bukan diteruskan ke database.

### Kolom yang dicari oleh `/search`

`name`, `slug`, `director`, `screenwriter`, `cast_members`, `genre`,
`tags`, `original_network`, `synopsis`.

Pencarian memakai `LIKE` (mengandung), jadi `tvn` akan menemukan
`tvN, Netflix`. Semua kondisi dibungkus `groupStart()`/`groupEnd()`.

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

### 5.0.slug — Sudah termasuk dalam migration utama

Kolom `slug` sekarang dibuat langsung oleh migration utama
(`CreateKdramasTable`), jadi **tidak ada migration kedua**. Sekali
`php spark migrate` sudah cukup.

Kalau sebelumnya kamu sudah pernah menjalankan migration versi lama
sebelum kolom `slug` ada, tabelnya harus dibangun ulang:

```bash
php spark migrate:rollback
php spark migrate
php spark db:seed KdramaSeeder
```

Cek slug sudah terisi:

```bash
php spark db:query "SELECT id, name, slug FROM kdramas ORDER BY id LIMIT 5;"
```

Hasilnya kira-kira:

```text
id | name                     | slug
 1 | Move to Heaven           | move-to-heaven
 2 | Flower of Evil           | flower-of-evil
 3 | Hospital Playlist        | hospital-playlist
 4 | Hospital Playlist 2      | hospital-playlist-2
 5 | My Mister                | my-mister
```

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

---

### 5.11 GET — Filter berdasarkan tahun

```bash
curl -X GET "http://localhost:8080/api/kdramas?year_of_release=2021"
```

**Harus** `200`, dan hanya berisi drama tahun 2021
(Move to Heaven, Hospital Playlist 2, Vincenzo).

Gabung beberapa filter:

```bash
curl -X GET "http://localhost:8080/api/kdramas?year_of_release=2020&original_network=tvN"
```

Filter angka harus angka — kalau tidak, dapat `400`:

```bash
curl -X GET "http://localhost:8080/api/kdramas?year_of_release=abc"
```

```json
{
    "status": 400,
    "code": 400,
    "messages": {
        "year_of_release": "Filter year_of_release harus berupa angka."
    }
}
```

---

### 5.12 GET — Pencarian bebas

```bash
curl -X GET http://localhost:8080/api/kdramas/search/vincenzo
```

```bash
curl -X GET http://localhost:8080/api/kdramas/search/tvN
```

**Harus** `200`:

```json
{
    "status": 200,
    "message": "Hasil pencarian K-drama",
    "keyword": "vincenzo",
    "total": 1,
    "data": [ ... ]
}
```

Kata kunci kosong atau terlalu panjang dapat `400`.

---

### 5.13 POST — Slug otomatis dari nama

Kirim tanpa `slug`:

```bash
curl -X POST http://localhost:8080/api/kdramas \
  -H "Content-Type: application/json" \
  -d '{"name":"Winter Sonata 2026","year_of_release":2026}'
```

**Harus** `201`, dan slug-nya jadi `winter-sonata-2026`.

Kalau ada dua drama dengan nama sama, keduanya tetap aman — yang kedua
dapat suffix angka (`namanya-sama-2`).

---

### 5.14 POST — Slug dipakai drama lain (uji 400)

Kirim `slug` yang sudah dipakai:

```bash
curl -X POST http://localhost:8080/api/kdramas \
  -H "Content-Type: application/json" \
  -d '{"name":"Judul Baru","slug":"move-to-heaven"}'
```

**Harus** HTTP `400`:

```json
{
    "status": 400,
    "code": 400,
    "messages": {
        "slug": "Maaf, gagal menambahkan karena slug sudah digunakan oleh drama lain."
    }
}
```

---

### 5.15 PUT — Ubah slug jadi milik drama lain (uji pesan error)

```bash
curl -X PUT http://localhost:8080/api/kdramas/3 \
  -H "Content-Type: application/json" \
  -d '{"slug":"move-to-heaven"}'
```

**Harus** HTTP `400` dengan pesan yang kamu minta:

```json
{
    "status": 400,
    "code": 400,
    "messages": {
        "slug": "Maaf Update gagal karena slug sudah di gunakan oleh drama lain"
    }
}
```

---

### 5.16 PUT — Ganti slug dengan milik sendiri (harus boleh)

```bash
curl -X PUT http://localhost:8080/api/kdramas/3 \
  -H "Content-Type: application/json" \
  -d '{"slug":"hospital-playlist-3"}'
```

**Harus** `200`. Drama tidak dianggap bentrok dengan dirinya sendiri.

---

### 5.17 PATCH — Ubah slug jadi valid

```bash
curl -X PATCH http://localhost:8080/api/kdramas/3 \
  -H "Content-Type: application/json" \
  -d '{"slug":"hospital-playlist-season-3"}'
```

**Harus** `200`.

---

### 5.18 PATCH — Ubah nama saja, slug tidak berubah

```bash
curl -X PATCH http://localhost:8080/api/kdramas/5 \
  -H "Content-Type: application/json" \
  -d '{"name":"My Mister (Ganti Nama)"}'
```

**Harus** `200`, dan slug-nya **tetap** `my-mister`.

> Kalau nama diedit tapi slug tidak dikirim, slug sengaja dibiarkan.
> Kirim `slug` juga kalau kamu memang ingin slug ikut berubah.

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

### Tambahan: slug dan pencarian

| # | Skenario | Method | Endpoint | Expected |
|---|---|---|---|---|
| 10 | Filter berdasarkan tahun | `GET` | `/api/kdramas?year_of_release=2021` | `200` |
| 11 | Filter tahun bukan angka | `GET` | `/api/kdramas?year_of_release=abc` | `400` |
| 12 | Pencarian bebas | `GET` | `/api/kdramas/search/vincenzo` | `200` |
| 13 | POST tanpa slug (otomatis) | `POST` | `{"name":"Uji Slug Otomatis"}` | `201` |
| 14 | POST slug sudah dipakai | `POST` | slug `move-to-heaven` | `400` |
| 15 | PUT slug dipakai drama lain | `PUT` | `/api/kdramas/3` slug `move-to-heaven` | `400` + pesan khusus |
| 16 | PUT slug milik sendiri | `PUT` | `/api/kdramas/3` slug baru | `200` |
| 17 | PATCH nama saja, slug tetap | `PATCH` | `/api/kdramas/5` | `200`, slug tidak berubah |

> Jalankan nomor 10–17 **setelah** nomor 1–9, karena sebagian butuh data
> hasil seeder yang sudah ada.

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
| Filter Tahun | `GET` | `{{base_url}}/kdramas?year_of_release=2021` |
| Filter Gabung | `GET` | `{{base_url}}/kdramas?year_of_release=2020&original_network=tvN` |
| Search | `GET` | `{{base_url}}/kdramas/search/tvN` |
| Get Detail | `GET` | `{{base_url}}/kdramas/{{id}}` |
| Create | `POST` | `{{base_url}}/kdramas` |
| Update | `PUT` | `{{base_url}}/kdramas/{{id}}` |
| Patch | `PATCH` | `{{base_url}}/kdramas/{{id}}` |
| Ubah Slug Jadi Bentrok | `PUT` | `{{base_url}}/kdramas/{{id}}` |
| Delete | `DELETE` | `{{base_url}}/kdramas/{{id}}` |

> Untuk `Filter Tahun`, `Filter Gabung`, dan `Search`, jangan isi tab Body.
> Query string sudah cukup.

### Body per request

#### `Create` (POST) — slug otomatis

```json
{
  "name": "Vincenzo",
  "year_of_release": 2021,
  "rating": 9.0,
  "genre": "Comedy, Law, Crime",
  "synopsis": "Seorang konsiglieri mafia KoreaITA yang kembali ke Korea Selatan."
}
```

Tidak ada `slug` → akan dibuat otomatis jadi `vincenzo`.

#### `Update` (PUT)

```json
{
  "name": "Vincenzo (Judul Baru)",
  "rating": 9.2
}
```

#### `Patch` (PATCH) — ubah sebagian saja

```json
{
  "rating": 9.3
}
```

Field lain tidak ikut berubah. Slug juga **tidak** berubah, kecuali kamu
kirim `slug` secara eksplisit.

#### `Ubah Slug Jadi Bentrok` (PUT) — uji pesan error

```json
{
  "slug": "move-to-heaven"
}
```

Harus dapat `400` dengan pesan:
`Maaf Update gagal karena slug sudah di gunakan oleh drama lain`

### Setting body

Untuk `Create`, `Update`, `Patch`, dan `Ubah Slug Jadi Bentrok`:

1. Tab **Body**
2. Centang **raw**
3. Pilih **JSON** dari dropdown tipe
4. Tempel JSON di atas

### Header

Untuk semua request yang mengirim body, tambahkan header:

```
Content-Type: application/json
```

### Cara cek status code

Klik request → lihat area response bagian atas. Status code tertera di
sana, misal `200 OK`, `201 Created`, `400 Bad Request`, atau `404 Not Found`.

### Tips Postman

**Simpan otomatis**. Klik kanan request → **Save Response** → pilih nama,
lalu centang **Save response for this request** di folder. Berguna kalau
kamu perlu membandingkan sebelum/sesudah.

**Lihat HTTP verb yang sebenarnya.** Kalau memakai variabel `{{id}}`,
Postman mengirim method yang kamu pilih. Kalau mau memastikan tidak salah
method, lihat di menu dropdown method.

**Import collection.** Daripada membuat manual, kamu bisa import file
JSON langsung lewat **Import** → **Link / Raw Text / File** → tempel JSON
berikut, lalu klik **Import**:

```json
{
  "info": { "name": "API K-Drama", "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json" },
  "variable": [
    { "key": "base_url", "value": "http://localhost:8080/api" },
    { "key": "id", "value": "1" }
  ],
  "item": [
    { "name": "Get All", "request": { "method": "GET", "url": "{{base_url}}/kdramas" } },
    { "name": "Filter Tahun", "request": { "method": "GET", "url": "{{base_url}}/kdramas?year_of_release=2021" } },
    { "name": "Search", "request": { "method": "GET", "url": "{{base_url}}/kdramas/search/tvN" } },
    { "name": "Get Detail", "request": { "method": "GET", "url": "{{base_url}}/kdramas/{{id}}" } },
    {
      "name": "Create",
      "request": {
        "method": "POST",
        "header": [{ "key": "Content-Type", "value": "application/json" }],
        "body": { "mode": "raw", "raw": "{\n  \"name\": \"Vincenzo\",\n  \"year_of_release\": 2021,\n  \"rating\": 9.0\n}" },
        "url": "{{base_url}}/kdramas"
      }
    },
    {
      "name": "Patch",
      "request": {
        "method": "PATCH",
        "header": [{ "key": "Content-Type", "value": "application/json" }],
        "body": { "mode": "raw", "raw": "{\"rating\": 9.3}" },
        "url": "{{base_url}}/kdramas/{{id}}"
      }
    },
    {
      "name": "Delete",
      "request": { "method": "DELETE", "url": "{{base_url}}/kdramas/{{id}}" }
    }
  ]
}
```

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

---

## 10. Alur Kodingan (Request Lifecycle)

Bagian ini menjelaskan **sebuah request itu lewat file mana saja**, supaya
kamu bisa menelusuri dan memperbaiki kalau ada yang tidak sesuai.

### 10.1 Gambaran besar

```text
   Browser / curl / Postman
            │
            │  HTTP request  (GET /api/kdramas/1)
            ▼
   public/index.php          ← satu-satunya pintu masuk dari luar
            │
            ▼
   app/Config/Routes.php     ← mencocokkan URL + method HTTP
            │                   dengan method controller
            ▼
   Controllers/Api/Kdramas.php ← logika: baca input, validasi,
            │                   panggil model, bentuk response
            ▼
   Models/KdramaModel.php    ← nama tabel, kolom yang boleh diisi,
            │                   aturan validasi, timestamp
            ▼
   PostgreSQL (tabel kdramas)
            │
            ▼
   Response JSON  ──────────►  kembali ke client
```

### 10.2 Detail per file

#### `public/index.php`

Satu-satunya file yang boleh diakses dari luar. Semua request masuk ke sini
terlebih dulu, lalu framework mengambil alih. Jangan pernah mengarah web
server ke root project — harus ke folder `public/`.

#### `app/Config/Routes.php`

Mencocokkan URL dengan method controller:

```php
$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function ($routes) {
    $routes->get('kdramas/search/(:segment)', 'Kdramas::search/$1');

    $routes->resource('kdramas', ['except' => 'new,edit']);
});
```

Artinya:

| Yang ditulis | Caller ketik |
|---|---|
| `kdramas` + `GET` | `GET /api/kdramas` |
| `kdramas` + `POST` | `POST /api/kdramas` |
| `kdramas/search/(:segment)` | `GET /api/kdramas/search/iberia` |
| `kdramas/(:num)` + `GET` | `GET /api/kdramas/1` |
| `kdramas/(:num)` + `DELETE` | `DELETE /api/kdramas/1` |

> **Urutan penting.** Route `search` harus ditulis **sebelum** `resource()`.
> Kalau dibalik, `GET /api/kdramas/search/iberia` akan cocok ke
> `kdramas/(.*)` dan memanggil `show("search")`, hasilnya 404.

#### `app/Controllers/Api/Kdramas.php`

Inilah tempat logika berada.(resourceController yang menyediakan
`respond()`, `respondCreated()`, `failNotFound()`, `failValidationErrors()`).

Setiap method punya 3 langkah yang sama:

1. **Baca input** — dari body JSON, query string, atau URL
2. **Cek model** — kalau datanya tidak ada, kembalikan `failNotFound()`
3. **Balas** — `respond()` untuk 200, `respondCreated()` untuk 201

#### `app/Models/KdramaModel.php`

Menjaga aturan data. Empat hal penting di sini:

| Properti | Fungsi |
|---|---|
| `$table = 'kdramas'` | Nama tabel |
| `$allowedFields` | **Hanya kolom ini** yang boleh diisi dari luar. `id` tidak ada di sini, jadi tidak bisa di-ubah lewat API |
| `$validationRules` | Aturan yang harus lolos sebelum data disimpan |
| `$useTimestamps = true` | CI4 mengisi `created_at` dan `updated_at` otomatis |

### 10.3 Alur data saat POST (menambah drama)

```text
1. Client kirim JSON
   {"name":"Vincenzo","year_of_release":2021}

2. Controller: payload()
   getJSON(true)  →  diubah jadi array asosiatif

3. Controller: cek name kosong?
   ya  → failValidationErrors()  → 400, berhenti
   tidak → lanjut

4. Controller: resolveSlug()
   slug dikirim?  tidak  → buat otomatis dari name: "vincenzo"
   slug dikirim?  ya    → cek sudah dipakai drama lain?
                           ya  → 400, berhenti
                           tidak → pakai slug itu

5. Model: validasi
   gagal  → return false, controller balas failValidationErrors() → 400
   sukses → lanjut

6. Model: doProtectFields()
   buang semua key yang TIDAK ada di $allowedFields
   (jadi user tidak bisa mengarang kolom, dan tidak bisa menimpa id)

7. Model: setCreatedField() + setUpdatedField()
   tambahkan created_at dan updated_at

8. Database: INSERT

9. Controller: respondCreated()  → 201 Created + data drama yang baru
```

### 10.4 Alur data saat PATCH (ubah sebagian)

```text
1. Controller: model->find($id)
   tidak ketemu  → failNotFound() → 404

2. Controller: payload()
   body kosong  → failValidationErrors() → 400

3. Controller: HANYA proses field yang benar-benar dikirim
   mis. hanya "rating"  →  kolom lain tidak di sentuh

4. Model->update($id, $data)
   validasi field yang dikirim saja
   (itulah kenapa $validationRules tidak boleh ada rule `required`,
    kalau ada, setiap PATCH parsial akan ditolak)

5. Database: UPDATE ... SET rating = 9.2 WHERE id = 1
   created_at tetap, updated_at diperbarui

6. respond()  → 200 + data terbaru
```

### 10.5 Urutan validasi saat UPDATE

```text
1. Drama ada?                        tidak → 404
2. Body valid?                       tidak → 400
3. name (kalau dikirim) tidak kosong? tidak → 400
4. slug (kalau dikirim) bentrok?      ya   → 400 "Maaf Update gagal..."
5. Validasi model                    gagal → 400
6. Simpan                            → 200
```

Poin penting: nomor 4 dicek **sebelum** nomor 5. Kalau urutannya dibalik,
user akan melihat pesan error validasi umum, bukan pesan "slug sudah
dipakai" yang mereka butuhkan.

### 10.6 Kenapa slug dicek dua kali

Validasi slug terjadi di dua tempat, dan itu disengaja:

| Lapisan | Menangkap apa |
|---|---|
| PHP (`resolveSlug`) | Pesan error yang ramah: *"Maaf Update gagal karena slug sudah di gunakan oleh drama lain"* |
| Database (UNIQUE) | Dua request bersamaan yang lolos PHP bersamaan-sama. PHP tidak bisa mencegah ini |

Kalau hanya salah satu, yang weakest link-nya jadi bisa ditembus.

### 10.7 Cara menelusuri masalah

Kalau response tidak sesuai harapan, periksa dari belakang ke depan:

```text
JSON yang kamu terima sudah benar?
  ↓ ya
Cek ResponseTrait: status code-nya sesuai?
  ↓ ya
Cek controller: method mana yang kena?
  ↓ ya
Cek model: validasi lolos? kolom ter-filter?
  ↓ ya
Cek migration: kolomnya ada di tabel?
```

Contoh: `GET /api/kdramas` balas `500` dengan
`column "slug" does not exist` — artinya migration belum dijalankan
setelah kolom slug ditambahkan. Bukan salah kode controller.

### 10.8 Menambah fitur baru — checklist

```text
1. Butuh kolom baru?      → tambah di CreateKdramasTable.php
                            (lalu php spark migrate:rollback && migrate)
2. Kolom itu boleh diisi user? → tambah ke $allowedFields di model
3. Perlu validasi?       → tambah ke $validationRules (+ $validationMessages)
4. Perlu endpoint baru?  → tambah method di controller
5. Perlu URL baru?       → tambah route SEBELUM resource()
6. Uji dengan curl       → lihat bagan 5
```

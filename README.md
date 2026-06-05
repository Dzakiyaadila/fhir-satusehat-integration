<div align="center">

#  SATUSEHAT FHIR Integration

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![FHIR R4](https://img.shields.io/badge/FHIR-R4-00A3E0?style=for-the-badge)](https://hl7.org/fhir/R4/)
[![SQLite](https://img.shields.io/badge/SQLite-003B57?style=for-the-badge&logo=sqlite&logoColor=white)](https://sqlite.org)
[![Tests](https://img.shields.io/badge/Tests-20%20passed-brightgreen?style=for-the-badge)](/)

**Tugas Mata Kuliah Interoperabilitas Sistem Kesehatan**

</div>

---

## 📋 Daftar Isi

- [Tentang Proyek](#-tentang-proyek)
- [Arsitektur Sistem](#-arsitektur-sistem)
- [Tech Stack](#-tech-stack)
- [Anggota Kelompok](#-anggota-kelompok)
- [Prasyarat](#-prasyarat)
- [Cara Instalasi](#-cara-instalasi)
- [Konfigurasi Environment](#-konfigurasi-environment)
- [Menjalankan Aplikasi](#-menjalankan-aplikasi)
- [Endpoint API](#-endpoint-api)
- [Alur Sistem](#-alur-sistem)
- [Struktur Database](#-struktur-database)
- [Struktur Folder](#-struktur-folder)
- [Menjalankan Unit Test](#-menjalankan-unit-test)
- [Testing dengan Postman](#-testing-dengan-postman)
- [Deliverables](#-deliverables)

---

##  Tentang Proyek

Repositori ini berisi implementasi backend sistem integrasi antara **fasilitas kesehatan lokal** dengan platform kesehatan nasional **SATUSEHAT** milik Kementerian Kesehatan RI, menggunakan standar internasional **FHIR R4 (Fast Healthcare Interoperability Resources)**.

### Apa yang dilakukan sistem ini?

Sistem ini menjadi **jembatan data** antara klinik lokal dengan SATUSEHAT. Admin klinik cukup memasukkan NIK pasien dan NIK dokter — sistem secara otomatis menyelesaikan seluruh prosedur integrasi di latar belakang hingga mendapatkan **Encounter ID** resmi dari Kemenkes.

```
Admin Klinik → input NIK Pasien + NIK Dokter
                        ↓
            [Sistem Laravel Kita]
                        ↓
            [SATUSEHAT API Kemenkes]
                        ↓
            Encounter ID ✓ (bukti pendaftaran resmi)
```

### Prinsip Interoperabilitas yang Diterapkan:

- **FHIR R4** — standar internasional format data kesehatan
- **OAuth2** — autentikasi aman ke server Kemenkes
- **Local First** — data di-cache lokal untuk efisiensi
- **Single Source of Truth** — SATUSEHAT sebagai referensi utama data nasional
- **Sinkronisasi** — perbandingan `meta.lastUpdated` untuk memastikan data selalu terbaru

---

##  Arsitektur Sistem

```
┌─────────────────────────────────────────────────────────┐
│                    Admin Klinik                         │
│              (Input NIK via Postman/Frontend)           │
└─────────────────────────┬───────────────────────────────┘
                          │
┌─────────────────────────▼───────────────────────────────┐
│                Laravel 12 Backend                       │
│                                                         │
│  ┌─────────────┐  ┌──────────────┐  ┌───────────────┐  │
│  │ AuthService │  │MasterDataSvc │  │LocationService│  │
│  │  (OAuth2)   │  │(IHS Number)  │  │  (Ruangan)    │  │
│  └─────────────┘  └──────────────┘  └───────────────┘  │
│                                                         │
│  ┌─────────────────────────────────────────────────┐   │
│  │           EncounterService (FHIR R4)            │   │
│  │      POST arrived → PUT in-progress             │   │
│  └─────────────────────────────────────────────────┘   │
│                                                         │
│  ┌─────────────────────────────────────────────────┐   │
│  │           SQLite Database (Lokal)               │   │
│  │  ihs_lookups | locations | encounters | logs    │   │
│  └─────────────────────────────────────────────────┘   │
└─────────────────────────┬───────────────────────────────┘
                          │ FHIR R4 + OAuth2
┌─────────────────────────▼───────────────────────────────┐
│              SATUSEHAT API (Kemenkes RI)                │
│         api-satusehat-stg.dto.kemkes.go.id              │
└─────────────────────────────────────────────────────────┘
```

---

##  Tech Stack

| Komponen | Teknologi | Versi |
|---|---|---|
| Bahasa | PHP | 8.2+ |
| Framework | Laravel | 12 |
| Database | SQLite | 3 |
| HTTP Client | Laravel Http (Guzzle) | — |
| Standar Data | FHIR R4 (JSON) | R4 |
| Autentikasi | OAuth2 Client Credentials | — |
| Testing | PHPUnit (via Laravel) | — |
| API Target | SATUSEHAT Sandbox | STG |

---

##  Anggota Kelompok

| NIM | Nama | Stream |
|---|---|---|
| 24/542103/SV/25003 | Dzakiya Hakima Adila | Stream 1 — Setup Laravel |
| 24/545004/SV/25588 | Ayu Atikah | Stream 2 — AuthService |
| 24/541430/SV/24905 | Della Nurizki | Stream 3 — MasterDataService |
| 24/533524/SV/23914 | Mardhika Murni Pramestika | Stream 4 — LocationService |
| 24/541424/SV/24904 | Syakira Zahratul Firdaus | Stream 5 — EncounterService |
| 24/544411/SV/25413 | Rua Adelia | Stream 6 — Unit Testing |
| 24/544362/SV/25396 | Okta Alshina Arva Parahyta | Stream 7 — Logging & Postman |

---

##  Prasyarat

Pastikan semua tools berikut sudah terinstall sebelum memulai:

| Tools | Versi Minimum | Cek Instalasi |
|---|---|---|
| PHP | 8.2+ | `php --version` |
| Composer | 2.x | `composer --version` |
| Git | — | `git --version` |

> **Windows users:** Gunakan **Git Bash** atau **WSL** untuk menjalankan perintah terminal. Command `touch` tidak tersedia di CMD/PowerShell.

---

##  Cara Instalasi

### 1. Clone repositori

```bash
git clone https://github.com/Dzakiyaadila/fhir-satusehat-integration.git
cd fhir-satusehat-integration
```

### 2. Install dependencies PHP

```bash
composer install
```

### 3. Salin file environment

```bash
cp .env.example .env
```

### 4. Generate application key

```bash
php artisan key:generate
```

### 5. Buat file database SQLite

```bash
# Mac/Linux
touch database/database.sqlite

# Windows (Git Bash)
touch database/database.sqlite

# Windows (CMD/PowerShell) — buat file kosong manual di folder database/
# lalu rename menjadi database.sqlite
```

### 6. Jalankan migrasi database

```bash
php artisan migrate
```

---

##  Konfigurasi Environment

Buka file `.env` dan isi variabel berikut. **Minta credentials ke perwakilan kelompok yang memegang akun DTO Kemenkes.**

```env
APP_NAME=SATUSEHAT-Integration
APP_ENV=local
APP_PORT=8000

# Database — gunakan SQLite
DB_CONNECTION=sqlite
# Hapus atau comment baris DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# SATUSEHAT API Sandbox
SATUSEHAT_AUTH_URL=https://api-satusehat-stg.dto.kemkes.go.id/oauth2/v1
SATUSEHAT_BASE_URL=https://api-satusehat-stg.dto.kemkes.go.id/fhir-r4/v1
SATUSEHAT_CLIENT_ID=isi_dengan_client_id_sandbox
SATUSEHAT_CLIENT_SECRET=isi_dengan_client_secret_sandbox
SATUSEHAT_ORG_ID=isi_dengan_organization_uuid_dari_dashboard
```

>  **PENTING:** Jangan pernah commit file `.env` ke GitHub karena mengandung credentials sensitif. File ini sudah ada di `.gitignore`.

### NIK Dummy untuk Testing (Sandbox STG)

```
NIK Pasien  : (lihat di Postman publik SATUSEHAT → Patient - By NIK)
NIK Dokter  : (lihat di Postman publik SATUSEHAT → Practitioner - By NIK)
```

---

##  Menjalankan Aplikasi

```bash
php artisan serve
```

Server berjalan di `http://localhost:8000`

Verifikasi instalasi berhasil:
```bash
curl http://localhost:8000/api/test-setup
```

Expected response:
```json
{
    "status": "OK",
    "message": "SatuSehat Integration API is running",
    "environment": "local"
}
```

---

##  Endpoint API

Base URL: `http://localhost:8000/api`

| Method | Endpoint | Deskripsi | Stream |
|---|---|---|---|
| `GET` | `/test-setup` | Health check — verifikasi setup | 1 |
| `POST` | `/location/setup` | Setup ruangan klinik ke SATUSEHAT (sekali saja) | 4 |
| `POST` | `/register` | Daftarkan pasien — jalankan 4 langkah otomatis | 5 |
| `PUT` | `/encounter/{id}/in-progress` | Update status pasien masuk ruang periksa | 5 |
| `GET` | `/encounters` | Lihat riwayat semua kunjungan | 5 |
| `GET` | `/integration-logs` | Lihat log aktivitas API | 7 |

### Contoh Request — Daftarkan Pasien

```bash
POST http://localhost:8000/api/register
Content-Type: application/json

{
    "nik_pasien": "9271060312000001",
    "nik_dokter": "NIK_DOKTER_DUMMY"
}
```

### Contoh Response Sukses (201 Created)

```json
{
    "status": "success",
    "message": "Kunjungan (Encounter) Berhasil Didaftarkan!",
    "encounter_id": "c65b7d8b-b691-4d71-9dbf-561e15c2e8b5"
}
```

---

##  Alur Sistem

Setiap pendaftaran pasien menjalankan 4 langkah otomatis:

```
POST /api/register
        │
        ▼
[1] Autentikasi OAuth2
    → Dapat Access Token (cache 14000 detik)
        │
        ▼
[2] Pencarian IHS Number
    → Cek DB lokal dulu (local first)
    → Kalau tidak ada: GET /Patient?identifier=...
    → Dapat IHS Pasien + IHS Dokter
    → Simpan ke tabel ihs_lookups
        │
        ▼
[3] Persiapan Lokasi
    → Cek DB lokal dulu
    → Kalau tidak ada: POST /Location (FHIR R4)
    → Dapat Location_ID
    → Simpan ke tabel locations
        │
        ▼
[4] Pendaftaran Kunjungan
    → POST /Encounter (FHIR R4)
    → Status: arrived | Kelas: AMB | Timestamp: UTC+0
    → Dapat Encounter_ID ✓
    → Simpan ke tabel encounters
    → Catat ke integration_logs
        │
        ▼
Response: Encounter_ID
```

### Lifecycle Status Encounter

```
arrived ──────────────→ in-progress
(pasien baru tiba)      (masuk ruang periksa)
POST /register          PUT /encounter/{id}/in-progress
```

---

##  Struktur Database

### `ihs_lookups` — Cache Master Data Pasien & Dokter

```sql
id          INTEGER PRIMARY KEY
nik         VARCHAR(16)
tipe        ENUM('pasien', 'dokter')
ihs_number  VARCHAR NULLABLE      -- ID dari SATUSEHAT
nama        VARCHAR NULLABLE      -- Nama untuk payload Encounter
ditemukan   BOOLEAN DEFAULT false
UNIQUE(nik, tipe)
```

### `locations` — Master Data Ruangan

```sql
id                      INTEGER PRIMARY KEY
nama_ruangan            VARCHAR
location_id_satusehat   VARCHAR
org_id                  VARCHAR
```

### `encounters` — Rekam Kunjungan

```sql
id                      INTEGER PRIMARY KEY
nik_pasien              VARCHAR(16)
ihs_pasien              VARCHAR
nama_pasien             VARCHAR
ihs_dokter              VARCHAR
nama_dokter             VARCHAR
location_id_satusehat   VARCHAR
encounter_id_satusehat  VARCHAR UNIQUE    -- Bukti resmi dari SATUSEHAT
nomor_internal          VARCHAR
waktu_kunjungan         VARCHAR           -- ISO 8601 UTC+0
waktu_masuk_ruang       VARCHAR NULLABLE
status                  VARCHAR DEFAULT 'arrived'
```

### `integration_logs` — Audit Trail API

```sql
id               INTEGER PRIMARY KEY
step             VARCHAR    -- auth, patient, location, encounter_create, dst
http_status      INTEGER    -- 200, 201, 400, 401, 404
request_payload  TEXT       -- JSON yang dikirim ke SATUSEHAT
response_payload TEXT       -- JSON yang diterima dari SATUSEHAT
error_message    TEXT NULL  -- null jika sukses
```

---

## 📁 Struktur Folder

```
fhir-satusehat-integration/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── SatuSehatController.php    # Controller utama semua endpoint
│   ├── Models/
│   │   ├── IhsLookup.php                  # Master data pasien & dokter
│   │   ├── Location.php                   # Master data ruangan
│   │   ├── Encounter.php                  # Rekam kunjungan
│   │   └── IntegrationLog.php             # Log aktivitas API
│   └── Services/
│       ├── AuthService.php                # Stream 2: OAuth2 & token management
│       ├── MasterDataService.php          # Stream 3: IHS Number lookup
│       ├── LocationService.php            # Stream 4: Location setup
│       └── EncounterService.php           # Stream 5: Encounter FHIR R4
├── database/
│   ├── migrations/                        # Skema tabel database
│   └── database.sqlite                    # File database lokal (tidak di-commit)
├── routes/
│   └── api.php                            # Definisi semua endpoint API
├── tests/
│   └── Unit/
│       ├── AuthServiceTest.php            # 4 test cases
│       ├── MasterDataServiceTest.php      # 6 test cases
│       ├── LocationServiceTest.php        # 4 test cases
│       └── EncounterServiceTest.php       # 5 test cases
├── .env.example                           # Template konfigurasi
└── README.md                              # Dokumentasi ini
```

---

##  Menjalankan Unit Test

Proyek ini dilengkapi **20 unit test** yang mencakup semua skenario sukses dan gagal menggunakan `Http::fake()` — tidak memerlukan koneksi internet atau credentials asli.

### Jalankan semua test

```bash
php artisan test tests/Unit
```

### Jalankan per service

```bash
# Test AuthService (4 test)
php artisan test tests/Unit/AuthServiceTest.php

# Test MasterDataService (6 test)
php artisan test tests/Unit/MasterDataServiceTest.php

# Test LocationService (4 test)
php artisan test tests/Unit/LocationServiceTest.php

# Test EncounterService (5 test)
php artisan test tests/Unit/EncounterServiceTest.php
```

### Expected output

```
PASS  Tests\Unit\AuthServiceTest
✓ get access token success                          
✓ token is cached and not requested twice           
✓ force new token clears cache                      
✓ get access token throws exception on 401          

PASS  Tests\Unit\EncounterServiceTest
✓ create encounter success                          
✓ create encounter refresh token on 401             
✓ create encounter failed and rollback              
✓ update encounter to in progress success           
✓ update encounter not found                        

PASS  Tests\Unit\LocationServiceTest
✓ create location success                           
✓ get location from local db                        
✓ create location fails on 400                      
✓ create location refreshes token on 401            

PASS  Tests\Unit\MasterDataServiceTest
✓ get patient ihs success                           
✓ get practitioner ihs success                      
✓ get patient ihs from local db                     
✓ get patient ihs throws exception when not found   
✓ get practitioner ihs throws exception when not found
✓ get patient ihs throws exception from cached not found

Tests:  20 passed (50 assertions)
```

### Penjelasan teknik testing

| Teknik | Penjelasan |
|---|---|
| `Http::fake()` | Mock response dari SATUSEHAT — tidak hit API asli |
| `RefreshDatabase` | Database bersih di setiap test case |
| `Mockery::mock()` | Mock `AuthService` agar tidak perlu credentials asli |
| `assertDatabaseHas()` | Verifikasi data tersimpan ke SQLite |
| `expectException()` | Verifikasi error ditangani dengan benar |

---

##  Testing dengan Postman

### Import Collection

File Postman Collection tersedia di root folder: `satusehat-fhir-integration.postman_collection.json`

1. Buka Postman
2. Klik **Import** → pilih file JSON tersebut
3. Set environment **SATUSEHAT Sandbox** dengan variabel:

| Variable | Value |
|---|---|
| `auth_url` | `https://api-satusehat-stg.dto.kemkes.go.id/oauth2/v1` |
| `base_url` | `https://api-satusehat-stg.dto.kemkes.go.id/fhir-r4/v1` |
| `local_url` | `http://localhost:8000/api` |
| `client_id` | *(isi dengan Client ID sandbox)* |
| `client_secret` | *(isi dengan Client Secret sandbox)* |
| `org_id` | *(isi dengan Organization UUID dari dashboard)* |

### Urutan Request (jalankan berurutan)

| # | Folder | Request | Expected |
|---|---|---|---|
| HC | Health Check | GET `/test-setup` | 200 OK |
| R1 | Auth | Generate Token | 200, token tersimpan |
| R2 | Master Data | GET Patient by NIK | 200, IHS tersimpan |
| R3 | Master Data | GET Practitioner by NIK | 200, IHS tersimpan |
| R4 | Setup Lokasi | POST Location | 201 Created |
| R5 | Encounter | POST Encounter arrived | **201 Created + Encounter ID** |
| R6 | Encounter | PUT Encounter in-progress | 200 OK |
| C1 | Laravel | Setup Location via Laravel | 200 OK |
| C2 | Laravel | Daftarkan Pasien via Laravel | 201 Created |

### Error Cases yang diuji

| Request | Skenario | Expected |
|---|---|---|
| E1 | Auth credentials salah | 401 Unauthorized |
| E2 | NIK tidak terdaftar | 200 total:0 / 404 |
| E3 | POST /register NIK invalid | 422 error |
| E4 | POST /register format NIK salah | 422 validation |
| E5 | PUT in-progress Encounter ID tidak valid | 404 / 422 |
| E6 | PUT in-progress dua kali | error graceful |

---

##  Deliverables

| # | Item | Status |
|---|---|---|
| 1 | Source Code di GitHub | ✅ |
| 2 | Postman Collection (`.json`) | ✅ |
| 3 | Screenshot 201 Created + Encounter ID | will be added |
| 4 | Unit Test — 20 passed, 50 assertions | ✅ |

---


## 📄 Lisensi

Proyek ini dibuat untuk keperluan akademis — Tugas Mata Kuliah Interoperabilitas D4 Teknologi Rekayasa Perangkat Lunak, Universitas Gadjah Mada 2026.

---

<div align="center">
Dibuat dengan ❤️ oleh Kelompok 2 — super gurls
</div>

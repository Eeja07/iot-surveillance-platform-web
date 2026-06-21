# Sistem Manajemen Kamera (SMC V1)

![Versi Laravel](https://img.shields.io/badge/Laravel-v11.x-FF2D20?style=for-the-badge&logo=laravel)
![Versi PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php)
[![Lisensi: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=for-the-badge)](https://opensource.org/licenses/MIT)

<p align="center">
  <img src="https://placehold.co/600x300/293445/FFFFFF?text=Logo+Proyek+Kamera" alt="Logo Proyek" width="400"/>
</p>

Sistem Manajemen Kamera (SMC) adalah aplikasi web komprehensif yang dibangun menggunakan Laravel untuk membantu memantau dan mengelola beberapa perangkat kamera berbasis IoT (seperti ESP32-CAM). Aplikasi ini dirancang untuk menyediakan platform terpusat untuk registrasi perangkat, melihat riwayat rekaman, dan mengelola akses pengguna.

---

## Daftar Isi

1.  [Fitur Utama](#fitur-utama)
2.  [Struktur Menu & Hak Akses](#struktur-menu--hak-akses)
3.  [Teknologi yang Digunakan](#teknologi-yang-digunakan)
4.  [Panduan Instalasi](#panduan-instalasi)
5.  [Cara Penggunaan](#cara-penggunaan)
6.  [Lisensi](#lisensi)

---

## Fitur Utama

Berikut adalah penjelasan lebih detail mengenai fungsionalitas utama yang tersedia dalam sistem ini:

- **Dashboard Terpusat**:

  - **Statistik Cepat**: Menampilkan ringkasan data penting seperti jumlah total kamera, jumlah kamera yang aktif, dan total pengguna terdaftar.
  - **Pratinjau Langsung**: Menampilkan _feed_ gambar terbaru dari setiap kamera yang aktif untuk pemantauan cepat.

- **Manajemen Perangkat Kamera**:

  - **Registrasi Perangkat**: Admin dapat mendaftarkan perangkat kamera baru dan sistem akan secara otomatis menghasilkan `Device ID` dan `API Key` yang unik untuk otentikasi.
  - **CRUD Kamera**: Kemampuan untuk menambah, melihat, mengedit (nama, deskripsi, status), dan menghapus perangkat kamera.

- **Riwayat Rekaman**:

  - **Penerimaan Gambar via API**: Memiliki _endpoint_ API khusus (`/api/upload`) untuk menerima kiriman gambar dari perangkat ESP32-CAM.
  - **Penyimpanan Terstruktur**: Gambar disimpan dalam folder yang terorganisir berdasarkan `Device ID` dan tanggal (`YYYY-MM-DD`) untuk manajemen file yang mudah.
  - **Penghapusan Otomatis**: Tugas terjadwal (`Scheduled Task`) untuk secara otomatis menghapus rekaman yang lebih tua dari 30 hari, menjaga agar penyimpanan tidak penuh.
  - **Penelusuran Riwayat**: Antarmuka untuk menelusuri riwayat rekaman per kamera, dikelompokkan berdasarkan tanggal, dan melihat detail gambar per waktu.

- **Manajemen Pengguna & Hak Akses**:

  - **Berbasis Peran (RBAC)**: Menggunakan `spatie/laravel-permission`, Admin dapat membuat peran (misalnya, 'Admin', 'Viewer') dan menetapkan izin spesifik.
  - **Keamanan Akses**: Memastikan setiap pengguna hanya dapat mengakses fitur dan data yang sesuai dengan perannya.

- **Log & Notifikasi**:

  - **Log Aktivitas**: Mencatat semua tindakan penting yang dilakukan oleh pengguna di dalam sistem untuk tujuan audit.
  - **Sistem Peringatan**: Halaman khusus untuk menampilkan peringatan, seperti notifikasi kamera yang _offline_ (tidak mengirim data dalam interval waktu tertentu).

- **Log Deteksi Machine Learning (Fitur Masa Depan)**:
  - **Dasar untuk Pengembangan**: Fondasi telah disiapkan untuk mengintegrasikan model ML, dengan halaman log deteksi yang akan menampilkan gambar di mana objek berhasil diidentifikasi.

---

## Struktur Menu & Hak Akses

Berikut adalah rincian menu yang dapat diakses oleh setiap peran utama. Peran **Admin** memiliki akses ke semua menu.

### Peran: Admin

Memiliki kontrol penuh atas sistem, termasuk pengaturan dan manajemen pengguna.

| Menu Utama           | Sub-Menu           | URL Path                         |
| :------------------- | :----------------- | :------------------------------- |
| **Dashboard**        | -                  | `/dashboard`                     |
| **Manajemen Kamera** | -                  | `/dashboard/admin/cameras`       |
| **Riwayat Rekaman**  | -                  | `/dashboard/log/history`         |
| **Log Deteksi ML**   | -                  | `/dashboard/ml/detection-log`    |
| **Log Aktivitas**    | -                  | `/dashboard/log/activities`      |
| **Notifikasi**       | -                  | `/dashboard/admin/notifications` |
| **Pengaturan**       | Manajemen Pengguna | `/dashboard/settings/users`      |
|                      | Manajemen Peran    | `/dashboard/settings/roles`      |

### Peran: Viewer (Contoh)

Hanya dapat melihat data, tidak dapat mengubah atau menghapus.

| Menu Utama          | Sub-Menu | URL Path                 |
| :------------------ | :------- | :----------------------- |
| **Dashboard**       | -        | `/dashboard`             |
| **Riwayat Rekaman** | -        | `/dashboard/log/history` |

---

## Teknologi yang Digunakan

- **Backend**: Laravel 11, PHP 8.2+
- **Frontend**: Vite, Blade, Bootstrap 5, SASS, JavaScript
- **Database**: MySQL / MariaDB
- **Paket Utama**:
  - `spatie/laravel-permission`: Untuk Manajemen Peran & Hak Akses.
  - `laravel/breeze`: Untuk sistem otentikasi.

---

## Panduan Instalasi

Ikuti langkah-langkah berikut untuk menjalankan proyek ini di lingkungan lokal Anda.

### Prasyarat

- PHP >= 8.2
- Composer
- Node.js & NPM
- Server Database (MySQL/MariaDB)

### Langkah-langkah Instalasi

1.  **Clone repository ini:**

    ```bash
    git clone [URL_REPOSITORY_ANDA]
    cd [NAMA_FOLDER_PROYEK]
    ```

2.  **Install dependensi PHP:**

    ```bash
    composer install
    ```

3.  **Install dependensi JavaScript:**

    ```bash
    npm install
    ```

4.  **Buat file `.env`:**
    Salin file `.env.example` menjadi `.env`.

    ```bash
    cp .env.example .env
    ```

5.  **Konfigurasi Database:**
    Buka file `.env` dan sesuaikan pengaturan database Anda (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).

6.  **Generate Application Key:**

    ```bash
    php artisan key:generate
    ```

7.  **Jalankan Migrasi Database:**
    Perintah ini akan membuat semua tabel yang diperlukan, termasuk dari Spatie.

    ```bash
    php artisan migrate
    ```

8.  **(Opsional) Jalankan Seeder:**
    Jika Anda memiliki data awal, jalankan seeder.

    ```bash
    php artisan db:seed
    ```

9.  **Buat Symbolic Link untuk Storage:**

    ```bash
    php artisan storage:link
    ```

10. **Compile Aset Frontend:**
    ```bash
    npm run dev
    ```

---

## Cara Penggunaan

1.  **Jalankan server pengembangan Laravel:**

    ```bash
    php artisan serve
    ```

    Aplikasi akan berjalan di `http://127.0.0.1:8000`.

2.  **Akses Aplikasi:**
    Buka browser dan kunjungi `http://127.0.0.1:8000`.

3.  **Akun Default:**
    Anda bisa login menggunakan akun yang Anda buat atau yang berasal dari seeder.
    - **Admin**:
      - Email: `admin@gmail.com`
      - Password: `password`

---

## Lisensi

Proyek ini dilisensikan di bawah Lisensi MIT.

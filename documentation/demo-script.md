# Naskah demo

Rekaman layar sekitar 4 menit. Setiap langkah berisi apa yang dilakukan di layar dan subtitle yang ditampilkan.

## Sebelum merekam

1. Laragon: klik Start All (MySQL).
2. Reset data: `cd backend && php artisan migrate:fresh --seed`
3. Jalankan tiga terminal: `php artisan serve` dan `php artisan queue:work` (keduanya di `backend`), lalu `npm run dev` (di `frontend`).
4. Buka dua jendela browser berdampingan, satu jendela biasa dan satu jendela incognito, keduanya di http://127.0.0.1:5173.
5. Siapkan dua file: satu gambar (`.png` atau `.jpg`) dan `notes.txt` berisi `LAJUR-SIMULATED-VIRUS`.

## Langkah

| # | Di layar | Subtitle |
|---|---|---|
| 1 | Jendela kiri: klik akun demo **Admin Lajur**, lalu **Sign in**. | Login dengan JWT. Akun demo berasal dari seeder. |
| 2 | Tunjukkan papan: tiga kolom, setiap task menampilkan prioritas, assignee, dan due date. | Dashboard task: To do, In progress, Done. |
| 3 | Ketik `payment` di Search, ubah Priority ke High, ganti Sort. Lalu klik **Clear filters**. | Filter, pencarian, dan pengurutan dari API berpaginasi. |
| 4 | Jendela kanan: login sebagai **Rina Pratama**. | User kedua, untuk menunjukkan update real-time. |
| 5 | Kiri: **New task**, judul "Prepare sprint demo", prioritas High, assignee Rina, lalu **Create task**. Task muncul di jendela Rina. | Task dibuat dan langsung muncul di layar Rina (SSE). Email notifikasi masuk antrean. |
| 6 | Kiri: seret gambar ke kotak upload. Tunggu thumbnail muncul, lalu klik **Download**. | Upload drag-and-drop dengan progress. Job di background memindai file dan membuat thumbnail. |
| 7 | Kiri: upload `notes.txt`. Statusnya berubah merah: "Failed the virus scan". | Simulasi scan virus memblokir file yang terinfeksi. |
| 8 | Kanan: buka task yang sama dan kirim komentar. Komentar muncul di jendela kiri. | Komentar real-time. |
| 9 | Kanan: ubah Status ke In progress, lalu **Save changes**. Di jendela kiri, task pindah kolom. | Perubahan status langsung terlihat oleh user lain. |
| 10 | Kiri: tutup panel, centang dua task di To do, pilih "Move to Done", lalu **Apply**. | Ubah status massal berjalan sebagai job di antrean. |
| 11 | Kiri: klik **Export CSV**, lalu buka file yang terunduh. | Export CSV berjalan sebagai job di antrean. |
| 12 | Kiri: buka DevTools, tampilan ponsel (sekitar 390px). Ketuk tab kolom. | Tampilan responsif: di ponsel, kolom berubah menjadi tab. |
| 13 | Kiri: buka "Prepare sprint demo", **Delete task**, **Confirm delete**. Lalu **Sign out**. | Hapus task, lalu logout (token dicabut). |
| 14 | Terminal: jalankan `php artisan test` (backend), lalu `npm test` (frontend). | 61 test backend dan 38 test frontend lulus. E2E: `npm run test:e2e`. |
| 15 | Tunjukkan folder `documentation` dan `README.md`. | Dokumentasi: README, OpenAPI, skema database, arsitektur, panduan setup. |

## Opsional, kalau waktu cukup

- Sebelum langkah 13: login sebagai **Sari Wulandari** dan buka "Investigate slow search". Form-nya terkunci, karena member hanya bisa mengedit task yang dia buat atau yang di-assign ke dia.
- Setelah langkah 5: tunjukkan isi email notifikasi di `backend/storage/logs/laravel.log`.

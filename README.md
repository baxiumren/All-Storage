# 🗄️ MAHASTORAGE

Self-hosted **Storage & Raw Vault** — satu aplikasi, dua ruangan:

- **PRIVATE STORAGE** — file manager gambar/video/arsip dengan hotlink publik (`/uploads/...`)
- **RAW STORAGE** — hosting file `.txt` dengan raw link publik (`/raw/{id}`) yang bisa di-fetch dari codingan mana pun

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat&logo=php&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=flat)

---

## ✨ Fitur

### Dashboard Utama (`/home`)
- Ringkasan kedua ruangan: jumlah folder/file, raw link aktif, storage terpakai, aktivitas terakhir
- Sub menu switcher di navbar: Home · Storage · Raw

### Private Storage (`/dashboard`)
- Upload drag & drop (sequential per-file — stabil untuk banyak file), folder, preview lightbox,
  share link berkedaluwarsa + QR, recycle bin, batch rename, bulk ZIP, editor teks, folder color/icon,
  global search, grid/list view, dark + light mode

### Raw Storage (`/texts`)
- Create / upload / edit file `.txt` langsung dari browser
- **Raw link publik** `domain.com/raw/{id}` → keluaran `text/plain` polos (CORS `*`, siap di-fetch)
- Link tetap hidup walau file di-rename; mati otomatis kalau file dihapus
- File txt disimpan di `raws/` yang tertutup total dari web — keluar hanya via `/raw/{id}`

### Sesi (anti logout mendadak)
- Cookie 30 hari, idle timeout 7 hari **rolling** (tiap aktivitas memperpanjang)
- Tutup browser ≠ logout
- **Tanpa cek IP** — ganti jaringan/IP tidak menendang sesi (verifikasi via browser fingerprint)

### Keamanan
- Login bcrypt + lockout per-IP (5x gagal = 15 menit) · CSRF di semua POST
- Cookie `HttpOnly` + `SameSite` + `Secure` (HTTPS) · `session_regenerate_id` tiap login
- Path traversal guard (`realpath`) di semua endpoint · whitelist ekstensi + cek MIME
- PHP mati total di `uploads/` · `raws/` & `data/` terkunci dari web · `.git` diblokir
- Clean URL tanpa `.php` · semua folder dijawab **404** bila dibuka langsung · template 404/503 custom

---

## 🚀 Instalasi

1. Upload semua file ke hosting (cPanel: `public_html/`)
2. Login default: `admin` / `admin123` → **langsung ganti password** (menu kunci 🔑)
3. Permission: `uploads/` 755 · `raws/` 750 · `data/` 750
4. (cPanel) MultiPHP INI Editor: `upload_max_filesize=100M`, `post_max_size=110M`
5. HTTPS aktif? Buka komentar baris HSTS di `.htaccess`

## 📁 Struktur

```
ALL-ITEM-2/
├── index.php            # Login
├── home.php             # Dashboard utama (ringkasan 2 ruangan)
├── dashboard.php        # Private Storage
├── texts.php            # Raw Storage
├── raw.php              # Output raw text publik (/raw/{id})
├── share.php            # Share link file storage
├── 404.php · 503.php    # Error pages
├── config.private.php   # Kredensial + konstanta + session
├── api/                 # Endpoint JSON (CSRF + session)
│   └── texts.php        # Aksi Raw Storage
├── assets/              # CSS, JS, cursor
├── uploads/             # File storage (publik untuk hotlink)
├── raws/                # File txt (TERTUTUP — hanya via /raw/{id})
└── data/                # raw_links.json, log, trash meta (tertutup)
```

## 🎨 Tema

Midnight Vault — biru royal `#055ff0` (primary) + emas `#FFD700` di atas `#020b25`.
Font Fraunces + Instrument Sans. Custom cursor Mickey glove. Dark + light mode.

## 📝 License

MIT

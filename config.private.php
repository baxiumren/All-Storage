<?php
// File: config.private.php — MAHASTORAGE
// JANGAN BAGIKAN FILE INI!

define('SITE_NAME', 'MAHASTORAGE');

// Daftar user yang boleh login
// Format: 'username' => 'password_hash'
$valid_users = [
    'admin' => '$2y$10$c1WyvwQo4.vSPuxoxDNL7uv9Lkc.iafXwEclNre.pGAElMFBTVktq', // admin123
];

// Sesi: idle timeout 7 HARI, rolling (di-refresh tiap request).
// Selama masih dipakai gak akan pernah logout sendiri.
$session_timeout = 7 * 24 * 3600;

// === DIREKTORI ===
define('UPLOAD_DIR', __DIR__ . '/uploads');  // Private Storage (gambar/file)
define('RAWS_DIR',   __DIR__ . '/raws');     // Raw Storage (file txt)
define('DATA_DIR',   __DIR__ . '/data');
define('LINKS_FILE', DATA_DIR . '/raw_links.json'); // mapping id → nama file txt

// === UPLOAD SETTINGS ===
// Max ukuran file per file: 100MB
define('MAX_FILE_SIZE', 100 * 1024 * 1024);

// Ekstensi yang diizinkan (lowercase)
define('ALLOWED_EXTENSIONS', [
    // Gambar
    'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico',
    // Dokumen
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
    'txt', 'rtf', 'csv', 'odt', 'md', 'log',
    // Arsip
    'zip', 'rar', '7z', 'tar', 'gz',
    // Audio
    'mp3', 'wav', 'flac', 'm4a', 'ogg',
    // Video
    'mp4', 'avi', 'mkv', 'mov', 'wmv', 'webm',
    // Data
    'json', 'xml',
]);

// === LOGIN PROTECTION ===
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 menit

// ============================================================
// ASSET VERSIONING (anti cache basi)
// CSS/JS dikasih ?v=<waktu modifikasi file> — tiap deploy versi berubah,
// browser otomatis download yang baru. Gak ada lagi tampilan rusak
// gara-gara cache nyangkut.
// ============================================================
function asset_url($path) {
    $f = __DIR__ . '/' . $path;
    $v = file_exists($f) ? filemtime($f) : 1;
    return htmlspecialchars($path) . '?v=' . $v;
}

// ============================================================
// SESSION HARDENING
// Cookie session: HttpOnly (JS gak bisa baca), SameSite=Lax
// (tahan CSRF dasar), Secure otomatis kalau lewat HTTPS.
// Panggil ini SEBELUM akses $_SESSION, pengganti session_start().
// ============================================================
function secure_session_start() {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    // Umur data session di server harus >= idle timeout (7 hari + buffer)
    ini_set('session.gc_maxlifetime', (string)(8 * 24 * 3600));
    session_set_cookie_params([
        'lifetime' => 30 * 24 * 3600, // cookie 30 hari — tutup browser gak logout
        'path'     => '/',
        'domain'   => '',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('MAHAVAULT');
    session_start();

    if (!empty($_SESSION['loggedin'])) {
        global $session_timeout;
        $last    = $_SESSION['last_active'] ?? $_SESSION['login_time'] ?? 0;
        $expired = (time() - $last) > $session_timeout;
        // Verifikasi browser saja. SENGAJA TIDAK cek IP — IP HP/ISP sering
        // berganti dan dulu itu penyebab logout mendadak di tengah kerja.
        $hijack  = ($_SESSION['user_agent'] ?? '') !== ($_SERVER['HTTP_USER_AGENT'] ?? '');
        if ($expired || $hijack) {
            $_SESSION = [];
            session_destroy();
            session_start(); // sesi kosong baru, halaman akan lempar ke login
            return;
        }
        // Rolling: tiap request aktif memperpanjang idle timer + umur cookie
        $_SESSION['last_active'] = time();
        if (!isset($_SESSION['cookie_refreshed']) || time() - $_SESSION['cookie_refreshed'] > 86400) {
            setcookie(session_name(), session_id(), [
                'expires'  => time() + 30 * 24 * 3600,
                'path'     => '/',
                'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            $_SESSION['cookie_refreshed'] = time();
        }
    }
}

// ============================================================
// CSRF FUNCTIONS
// ============================================================
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify($token) {
    return !empty($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

// ============================================================
// LOGIN ATTEMPT FUNCTIONS
// ============================================================
function _attempts_file() {
    if (!file_exists(DATA_DIR)) {
        mkdir(DATA_DIR, 0750, true);
    }
    return DATA_DIR . '/login_attempts.json';
}

function get_login_attempts($ip) {
    $file = _attempts_file();
    if (!file_exists($file)) {
        return ['count' => 0, 'last_attempt' => 0, 'locked_until' => 0];
    }
    $data = json_decode(file_get_contents($file), true) ?? [];
    return $data[$ip] ?? ['count' => 0, 'last_attempt' => 0, 'locked_until' => 0];
}

function record_failed_attempt($ip) {
    $file = _attempts_file();
    $data = file_exists($file) ? (json_decode(file_get_contents($file), true) ?? []) : [];

    $rec = $data[$ip] ?? ['count' => 0, 'last_attempt' => 0, 'locked_until' => 0];
    $rec['count']++;
    $rec['last_attempt'] = time();

    if ($rec['count'] >= MAX_LOGIN_ATTEMPTS) {
        $rec['locked_until'] = time() + LOCKOUT_DURATION;
    }

    $data[$ip] = $rec;
    file_put_contents($file, json_encode($data), LOCK_EX);
}

function clear_login_attempts($ip) {
    $file = _attempts_file();
    if (!file_exists($file)) return;

    $data = json_decode(file_get_contents($file), true) ?? [];
    unset($data[$ip]);
    file_put_contents($file, json_encode($data), LOCK_EX);
}

function is_ip_locked($ip) {
    $rec = get_login_attempts($ip);
    if ($rec['locked_until'] > time()) {
        return $rec['locked_until'];
    }
    return false;
}

// ============================================================
// ACTIVITY LOGGING
// ============================================================
function log_activity($action, $user, $detail = '') {
    if (!file_exists(DATA_DIR)) mkdir(DATA_DIR, 0750, true);
    $line = date('Y-m-d H:i:s')
        . ' | ' . str_pad($action, 18)
        . ' | user:' . $user
        . ' | ' . $detail
        . PHP_EOL;
    file_put_contents(DATA_DIR . '/activity.log', $line, FILE_APPEND | LOCK_EX);
}

// ============================================================
// PATH VALIDATION
// ============================================================
/**
 * Validates that $folder is within $base (UPLOAD_DIR).
 * Returns the real absolute path if valid, false if traversal detected.
 * $folder must already exist on disk.
 */
function validate_upload_path($folder, $base = null) {
    if ($base === null) $base = UPLOAD_DIR;

    // Remove dangerous characters
    $folder = str_replace(["\0", '\\'], ['', '/'], $folder ?? '');
    $folder = trim($folder, '/');

    $target    = $base . ($folder !== '' ? '/' . $folder : '');
    $real      = realpath($target);
    $real_base = realpath($base);

    if ($real === false || $real_base === false) return false;

    // Must start with base path
    if (strpos($real, $real_base) !== 0) return false;

    return $real;
}

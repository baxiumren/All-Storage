<?php
require_once __DIR__ . '/../config.private.php';
secure_session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']); exit;
}
if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']); exit;
}

if (!file_exists(RAWS_DIR)) mkdir(RAWS_DIR, 0755, true);

// ── Helpers ──
// $force_ext: 'txt'/'zip' = paksa ekstensi itu · null = terima txt ATAU zip apa adanya
function raw_safe_name($name, $force_ext = null) {
    $name = basename((string)$name);
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $name) || $name === '.' || $name === '..') return false;
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if ($force_ext !== null) {
        if ($ext !== $force_ext) $name = pathinfo($name, PATHINFO_FILENAME) . '.' . $force_ext;
        return $name;
    }
    return in_array($ext, ['txt', 'zip'], true) ? $name : false;
}
function txt_safe_name($name) { return raw_safe_name($name, 'txt'); }
function txt_path($name) {
    $p    = RAWS_DIR . '/' . $name;
    $real = realpath(dirname($p));
    if ($real === false || $real !== realpath(RAWS_DIR)) return false;
    return $p;
}
function links_load() {
    return file_exists(LINKS_FILE) ? (json_decode(file_get_contents(LINKS_FILE), true) ?? []) : [];
}
function links_save($links) {
    if (!file_exists(DATA_DIR)) mkdir(DATA_DIR, 0750, true);
    file_put_contents(LINKS_FILE, json_encode($links, JSON_PRETTY_PRINT), LOCK_EX);
}
function links_id_for($name) {
    foreach (links_load() as $id => $f) if ($f === $name) return $id;
    return null;
}

// Batas ukuran isi txt: 5MB (teks doang, gak butuh lebih)
define('MAX_TXT_SIZE', 5 * 1024 * 1024);

$action = $_POST['action'] ?? '';

switch ($action) {

    case 'create': {
        $raw_name = trim((string)($_POST['name'] ?? ''));
        // nama kosong → generate otomatis (txt-xxxxxx), dijamin belum kepakai
        if ($raw_name === '') {
            do { $raw_name = 'txt-' . substr(bin2hex(random_bytes(4)), 0, 6); }
            while (file_exists(RAWS_DIR . '/' . $raw_name . '.txt'));
        }
        $name = txt_safe_name($raw_name);
        if ($name === false) { echo json_encode(['success' => false, 'message' => 'Nama tidak valid']); exit; }
        $path = txt_path($name);
        if (file_exists($path)) { echo json_encode(['success' => false, 'message' => 'File sudah ada']); exit; }
        file_put_contents($path, '', LOCK_EX);
        log_activity('RAW_CREATE', $_SESSION['username'], $name);
        echo json_encode(['success' => true, 'name' => $name]);
        break;
    }

    case 'view': {
        $name = txt_safe_name($_POST['name'] ?? '');
        $path = $name !== false ? txt_path($name) : false;
        if ($path === false || !is_file($path)) { echo json_encode(['success' => false, 'message' => 'File tidak ditemukan']); exit; }
        echo json_encode(['success' => true, 'content' => file_get_contents($path)]);
        break;
    }

    case 'save': {
        $name = txt_safe_name($_POST['name'] ?? '');
        $path = $name !== false ? txt_path($name) : false;
        if ($path === false || !is_file($path)) { echo json_encode(['success' => false, 'message' => 'File tidak ditemukan']); exit; }
        $content = (string)($_POST['content'] ?? '');
        if (strlen($content) > MAX_TXT_SIZE) { echo json_encode(['success' => false, 'message' => 'Isi terlalu besar (max 5MB)']); exit; }
        file_put_contents($path, $content, LOCK_EX);
        log_activity('RAW_EDIT', $_SESSION['username'], $name);
        echo json_encode(['success' => true, 'message' => 'Tersimpan']);
        break;
    }

    case 'upload': {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Upload gagal']); exit;
        }
        if ($_FILES['file']['size'] > MAX_TXT_SIZE) {
            echo json_encode(['success' => false, 'message' => 'File terlalu besar (max 5MB)']); exit;
        }
        $name = txt_safe_name(preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['file']['name']));
        if ($name === false) { echo json_encode(['success' => false, 'message' => 'Nama tidak valid']); exit; }
        // anti timpa: kasih counter
        $base = pathinfo($name, PATHINFO_FILENAME); $i = 1;
        while (file_exists(txt_path($name))) { $name = $base . '_' . $i . '.txt'; $i++; }
        move_uploaded_file($_FILES['file']['tmp_name'], txt_path($name));
        log_activity('RAW_UPLOAD', $_SESSION['username'], $name);
        echo json_encode(['success' => true, 'name' => $name]);
        break;
    }

    case 'upload_zip': {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Upload gagal']); exit;
        }
        if ($_FILES['file']['size'] > MAX_FILE_SIZE) {
            $mb = MAX_FILE_SIZE / 1024 / 1024;
            echo json_encode(['success' => false, 'message' => "File terlalu besar (max {$mb}MB)"]); exit;
        }
        // validasi zip beneran, bukan file lain yang di-rename
        if (class_exists('ZipArchive')) {
            $za = new ZipArchive();
            if ($za->open($_FILES['file']['tmp_name']) !== true) {
                echo json_encode(['success' => false, 'message' => 'File ZIP tidak valid']); exit;
            }
            $za->close();
        }
        $name = raw_safe_name(preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['file']['name']), 'zip');
        if ($name === false) { echo json_encode(['success' => false, 'message' => 'Nama tidak valid']); exit; }
        $base = pathinfo($name, PATHINFO_FILENAME); $i = 1;
        while (file_exists(txt_path($name))) { $name = $base . '_' . $i . '.zip'; $i++; }
        move_uploaded_file($_FILES['file']['tmp_name'], txt_path($name));
        log_activity('RAW_UPLOAD_ZIP', $_SESSION['username'], $name);
        echo json_encode(['success' => true, 'name' => $name]);
        break;
    }

    case 'rename': {
        $old = raw_safe_name($_POST['name'] ?? '');
        if ($old === false) { echo json_encode(['success' => false, 'message' => 'Nama tidak valid']); exit; }
        // nama baru dipaksa ikut ekstensi file lama (txt tetap txt, zip tetap zip)
        $new = raw_safe_name($_POST['new_name'] ?? '', strtolower(pathinfo($old, PATHINFO_EXTENSION)));
        if ($new === false) { echo json_encode(['success' => false, 'message' => 'Nama tidak valid']); exit; }
        $op = txt_path($old); $np = txt_path($new);
        if (!is_file($op)) { echo json_encode(['success' => false, 'message' => 'File tidak ditemukan']); exit; }
        if (file_exists($np)) { echo json_encode(['success' => false, 'message' => 'Nama sudah dipakai']); exit; }
        rename($op, $np);
        // raw link ngikut nama baru — link lama tetap hidup
        $links = links_load(); $changed = false;
        foreach ($links as $id => $f) if ($f === $old) { $links[$id] = $new; $changed = true; }
        if ($changed) links_save($links);
        log_activity('RAW_RENAME', $_SESSION['username'], "$old → $new");
        echo json_encode(['success' => true, 'name' => $new]);
        break;
    }

    case 'delete': {
        $name = raw_safe_name($_POST['name'] ?? '');
        $path = $name !== false ? txt_path($name) : false;
        if ($path === false || !is_file($path)) { echo json_encode(['success' => false, 'message' => 'File tidak ditemukan']); exit; }
        unlink($path);
        // cabut raw link-nya juga
        $links = links_load(); $changed = false;
        foreach ($links as $id => $f) if ($f === $name) { unset($links[$id]); $changed = true; }
        if ($changed) links_save($links);
        log_activity('RAW_DELETE', $_SESSION['username'], $name);
        echo json_encode(['success' => true, 'message' => 'File dihapus']);
        break;
    }

    case 'link_create': {
        $name = raw_safe_name($_POST['name'] ?? '');
        $path = $name !== false ? txt_path($name) : false;
        if ($path === false || !is_file($path)) { echo json_encode(['success' => false, 'message' => 'File tidak ditemukan']); exit; }
        $existing = links_id_for($name);
        if ($existing) { echo json_encode(['success' => true, 'id' => $existing]); exit; }
        $links = links_load();
        do { $id = substr(bin2hex(random_bytes(4)), 0, 6); } while (isset($links[$id]));
        $links[$id] = $name;
        links_save($links);
        log_activity('RAW_LINK', $_SESSION['username'], "$name → /raw/$id");
        echo json_encode(['success' => true, 'id' => $id]);
        break;
    }

    case 'link_delete': {
        $name = raw_safe_name($_POST['name'] ?? '');
        if ($name === false) { echo json_encode(['success' => false, 'message' => 'Nama tidak valid']); exit; }
        $links = links_load(); $changed = false;
        foreach ($links as $id => $f) if ($f === $name) { unset($links[$id]); $changed = true; }
        if (!$changed) { echo json_encode(['success' => false, 'message' => 'Link tidak ditemukan']); exit; }
        links_save($links);
        log_activity('RAW_UNLINK', $_SESSION['username'], $name);
        echo json_encode(['success' => true, 'message' => 'Raw link dicabut']);
        break;
    }

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}

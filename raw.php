<?php
// MAHASTORAGE — output raw text publik
// Diakses via /raw/{id} (rewrite) atau raw.php?id={id}
// Keluaran: text/plain polos, siap di-fetch dari codingan mana pun.
require_once 'config.private.php';

$id = preg_replace('/[^a-z0-9]/', '', strtolower($_GET['id'] ?? ''));
if ($id === '') { http_response_code(404); header('Content-Type: text/plain; charset=utf-8'); exit('Not found'); }

$links = file_exists(LINKS_FILE) ? (json_decode(file_get_contents(LINKS_FILE), true) ?? []) : [];
if (!isset($links[$id])) { http_response_code(404); header('Content-Type: text/plain; charset=utf-8'); exit('Not found'); }

$name = basename($links[$id]);
if (!preg_match('/^[a-zA-Z0-9._-]+$/', $name) || strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'txt') {
    http_response_code(404); header('Content-Type: text/plain; charset=utf-8'); exit('Not found');
}

$path = RAWS_DIR . '/' . $name;
$real = realpath($path);
if ($real === false || strpos($real, realpath(RAWS_DIR)) !== 0 || !is_file($real)) {
    http_response_code(404); header('Content-Type: text/plain; charset=utf-8'); exit('Not found');
}

header('Content-Type: text/plain; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *'); // boleh di-fetch dari domain mana pun
header('Cache-Control: no-cache');
readfile($real);
exit;

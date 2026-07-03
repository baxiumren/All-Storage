<?php
// MAHASTORAGE — Raw link publik (/raw/{id})
// txt : DEFAULT = text/plain polos (langsung bisa di-fetch codingan)
//       ?view=1 = halaman viewer (copy content/url)
// zip : langsung download
require_once 'config.private.php';

$id = preg_replace('/[^a-z0-9]/', '', strtolower($_GET['id'] ?? ''));
if ($id === '') { http_response_code(404); header('Content-Type: text/plain; charset=utf-8'); exit('Not found'); }

$links = file_exists(LINKS_FILE) ? (json_decode(file_get_contents(LINKS_FILE), true) ?? []) : [];
if (!isset($links[$id])) { http_response_code(404); header('Content-Type: text/plain; charset=utf-8'); exit('Not found'); }

$name = basename($links[$id]);
$ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
if (!preg_match('/^[a-zA-Z0-9._-]+$/', $name) || !in_array($ext, ['txt', 'zip'], true)) {
    http_response_code(404); header('Content-Type: text/plain; charset=utf-8'); exit('Not found');
}

$path = RAWS_DIR . '/' . $name;
$real = realpath($path);
if ($real === false || strpos($real, realpath(RAWS_DIR)) !== 0 || !is_file($real)) {
    http_response_code(404); header('Content-Type: text/plain; charset=utf-8'); exit('Not found');
}

// ── ZIP: langsung download ──
if ($ext === 'zip') {
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . addslashes($name) . '"');
    header('Content-Length: ' . filesize($real));
    header('X-Content-Type-Options: nosniff');
    readfile($real);
    exit;
}

// ── TXT DEFAULT: plain text polos, siap di-fetch dari mana pun ──
if (!isset($_GET['view'])) {
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Access-Control-Allow-Origin: *');
    header('Cache-Control: no-cache');
    readfile($real);
    exit;
}

// ── TXT ?view=1: halaman viewer ──
$content   = htmlspecialchars(file_get_contents($real));
$size      = filesize($real);
$u = ['B','KB','MB','GB']; $i = 0; $s = $size;
while ($s >= 1024 && $i < 3) { $s /= 1024; $i++; }
$sizeh     = round($s, 2) . ' ' . $u[$i];
$modified  = date('Y-m-d H:i', filemtime($real));
$proto     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$req_path  = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$base_path = preg_replace('~/(raw/[a-z0-9]+/?|raw\.php)$~', '', $req_path);
$base      = $proto . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim($base_path, '/');
$plain_url = $base . '/raw/' . $id;      // default = plain
$view_url  = $plain_url;                 // yang disalin tombol Copy URL
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($name) ?> — MAHASTORAGE</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect x='6' y='6' width='88' height='88' rx='26' fill='%23055ff0'/><rect x='9' y='9' width='82' height='82' rx='23' fill='none' stroke='%23FFD700' stroke-width='5'/><circle cx='50' cy='50' r='19' fill='none' stroke='white' stroke-width='7'/><circle cx='50' cy='50' r='6' fill='%23FFD700'/><path d='M50 21v10M50 69v10M21 50h10M69 50h10' stroke='white' stroke-width='7' stroke-linecap='round'/></svg>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600&family=Instrument+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
<style>
:root{--gold:#FFD700;--gold-dark:#b8860b;--blue:#055ff0;--blue-dark:#052cf0;--bg-dark:#020b25;--text:#ffffff;--glass:rgba(0,0,0,0.85);}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Instrument Sans',system-ui,sans-serif;min-height:100vh;color:var(--text);padding:20px;
    background:radial-gradient(900px circle at 50% -10%,rgba(5,95,240,.3),transparent 60%),
               radial-gradient(500px circle at 90% 100%,rgba(255,215,0,.05),transparent 60%),
               linear-gradient(160deg,var(--bg-dark) 0%,#010616 100%);}
.container{max-width:900px;margin:0 auto;}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px;}
.topbar a{color:var(--gold);text-decoration:none;font-weight:600;font-size:14px;}
.topbar a:hover{text-decoration:underline;}
.card{background:var(--glass);backdrop-filter:blur(14px);border:1px solid rgba(255,255,255,.08);
    border-radius:16px;box-shadow:0 12px 40px rgba(0,0,0,.55);overflow:hidden;border-top:4px solid var(--gold);
    animation:up .5s cubic-bezier(.16,1,.3,1) both;}
@keyframes up{from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:translateY(0);}}
.card-header{padding:20px 24px;border-bottom:1px solid rgba(255,255,255,.08);
    display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;}
.fname{font-family:'Fraunces',serif;font-size:20px;font-weight:600;word-break:break-all;}
.fmeta{color:rgba(255,255,255,.55);font-size:13px;margin-top:5px;}
.card-actions{display:flex;gap:8px;flex-wrap:wrap;}
.btn{border:none;border-radius:9px;padding:9px 18px;font-weight:600;font-size:14px;cursor:pointer;color:#fff;
    text-decoration:none;display:inline-flex;align-items:center;gap:6px;
    transition:background .15s,color .15s,transform .1s;font-family:inherit;}
.btn:hover{transform:translateY(-1px);}
.btn-copy{background:var(--blue);}
.btn-copy:hover{background:var(--gold);color:var(--bg-dark);}
.btn-url{background:var(--gold);color:var(--bg-dark);}
.btn-url:hover{background:var(--blue);color:#fff;}
pre{padding:24px;font-family:'Consolas',monospace;font-size:14px;line-height:1.6;
    white-space:pre-wrap;word-break:break-word;background:transparent!important;color:var(--text);}
#toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#0a1c42;color:#fff;
    padding:12px 22px;border-radius:10px;font-weight:600;opacity:0;transition:opacity .25s;z-index:100;
    border-left:4px solid var(--gold);pointer-events:none;white-space:nowrap;}
#toast.show{opacity:1;}
</style>
</head>
<body>
<div class="container">
    <div class="topbar">
        <a href="<?= htmlspecialchars($base) ?>/texts">&larr; Kembali ke Dashboard</a>
        <a href="<?= htmlspecialchars($plain_url) ?>">Plain Text &nearr;</a>
    </div>
    <div class="card">
        <div class="card-header">
            <div>
                <div class="fname"><?= htmlspecialchars($name) ?></div>
                <div class="fmeta"><?= $sizeh ?> &nbsp;&middot;&nbsp; Modified: <?= $modified ?></div>
            </div>
            <div class="card-actions">
                <button class="btn btn-copy" onclick="copyContent()">Copy Content</button>
                <button class="btn btn-url" onclick="copyUrl()">Copy URL</button>
            </div>
        </div>
        <pre id="raw-content"><?= $content ?></pre>
    </div>
</div>
<div id="toast"></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script>
function toast(msg){
    const t=document.getElementById('toast');
    t.textContent=msg;t.classList.add('show');
    setTimeout(()=>t.classList.remove('show'),2200);
}
function copyContent(){
    navigator.clipboard.writeText(document.getElementById('raw-content').textContent)
        .then(()=>toast('Konten disalin!'))
        .catch(()=>{prompt('Salin manual:',document.getElementById('raw-content').textContent);});
}
function copyUrl(){
    const url='<?= htmlspecialchars($view_url) ?>';
    navigator.clipboard.writeText(url)
        .then(()=>toast('URL disalin!'))
        .catch(()=>prompt('Salin URL:',url));
}
const pre=document.getElementById('raw-content');
if(window.hljs) hljs.highlightElement(pre);
</script>
</body>
</html>

<?php
require_once 'config.private.php';
secure_session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) { header('Location: index.php'); exit; }

$csrf_token = csrf_token();
if (!file_exists(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
if (!file_exists(RAWS_DIR))   mkdir(RAWS_DIR, 0755, true);

function h_format_size($b) {
    $u = ['B','KB','MB','GB']; $i = 0;
    while ($b >= 1024 && $i < 3) { $b /= 1024; $i++; }
    return round($b, 2) . ' ' . $u[$i];
}

// ── Statistik PRIVATE STORAGE (uploads/) ──
$ps_folders = 0; $ps_files = 0; $ps_size = 0; $ps_last = null;
if (is_dir(UPLOAD_DIR)) {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(UPLOAD_DIR, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $f) {
        $p = str_replace('\\', '/', $f->getPathname());
        if (strpos($p, '/.trash') !== false) continue;
        if ($f->isDir()) { $ps_folders++; continue; }
        if ($f->getFilename()[0] === '.') continue;
        $ps_files++;
        $ps_size += $f->getSize();
        if ($ps_last === null || $f->getMTime() > $ps_last['mtime']) {
            $ps_last = ['name' => $f->getFilename(), 'mtime' => $f->getMTime()];
        }
    }
}

// ── Statistik RAW STORAGE (raws/) ──
$rw_files = 0; $rw_size = 0; $rw_last = null;
if (is_dir(RAWS_DIR)) {
    foreach (scandir(RAWS_DIR) as $f) {
        if ($f[0] === '.') continue;
        $p = RAWS_DIR . '/' . $f;
        if (!is_file($p)) continue;
        $rw_files++;
        $rw_size += filesize($p);
        $mt = filemtime($p);
        if ($rw_last === null || $mt > $rw_last['mtime']) $rw_last = ['name' => $f, 'mtime' => $mt];
    }
}
$rw_links = 0;
if (file_exists(LINKS_FILE)) {
    $rw_links = count(json_decode(file_get_contents(LINKS_FILE), true) ?? []);
}

function h_ago($ts) {
    $d = time() - $ts;
    if ($d < 60)    return 'baru saja';
    if ($d < 3600)  return floor($d/60) . ' menit lalu';
    if ($d < 86400) return floor($d/3600) . ' jam lalu';
    return floor($d/86400) . ' hari lalu';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MAHASTORAGE — Home</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect x='6' y='6' width='88' height='88' rx='26' fill='%23055ff0'/><rect x='9' y='9' width='82' height='82' rx='23' fill='none' stroke='%23FFD700' stroke-width='5'/><circle cx='50' cy='50' r='19' fill='none' stroke='white' stroke-width='7'/><circle cx='50' cy='50' r='6' fill='%23FFD700'/><path d='M50 21v10M50 69v10M21 50h10M69 50h10' stroke='white' stroke-width='7' stroke-linecap='round'/></svg>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/app.css">
<link rel="stylesheet" href="assets/css/cursor.css">
<link rel="stylesheet" href="assets/css/loader.css">
</head>
<body>
<div class="bg-orb orb-1"></div><div class="bg-orb orb-2"></div><div class="bg-orb orb-3"></div>
<div class="bg-grid"></div>
<div class="layout">

<!-- ══ NAVBAR ══ -->
<nav class="navbar">
    <div class="nav-logo">
        <div class="nav-logo-icon"><i class="fas fa-vault"></i></div>
        <div><div class="nav-logo-text">MAHASTORAGE</div><div class="nav-logo-sub">Storage &amp; Raw Vault</div></div>
    </div>
    <div class="nav-actions">
        <div style="text-align:right;">
            <div class="nav-username"><?php echo htmlspecialchars($_SESSION['username']);?></div>
            <div class="nav-ip"><?php echo htmlspecialchars($_SESSION['ip']);?></div>
        </div>
        <button class="btn-icon" onclick="toggleTheme()" id="btnTheme" title="Toggle Light/Dark"><i class="fas fa-sun"></i></button>
        <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</nav>

<!-- ══ MAIN ══ -->
<div class="main" style="max-width:980px;">

    <div class="home-hero">
        <h1>Selamat datang kembali<span style="color:var(--gold)">.</span></h1>
        <p>Mau kerja di ruangan mana hari ini?</p>
    </div>

    <div class="home-cards">

        <!-- PRIVATE STORAGE -->
        <a href="dashboard.php" class="home-card">
            <div class="hc-head">
                <div class="hc-icon hc-icon-blue"><i class="fas fa-photo-film"></i></div>
                <div>
                    <div class="hc-title">PRIVATE STORAGE</div>
                    <div class="hc-sub">gambar, video, arsip — hotlink</div>
                </div>
            </div>
            <div class="hc-stats">
                <div class="hc-stat"><div class="hc-val"><?php echo $ps_folders;?></div><div class="hc-lbl">Folder</div></div>
                <div class="hc-stat"><div class="hc-val"><?php echo $ps_files;?></div><div class="hc-lbl">File</div></div>
                <div class="hc-stat"><div class="hc-val"><?php echo h_format_size($ps_size);?></div><div class="hc-lbl">Terpakai</div></div>
            </div>
            <div class="hc-last">
                <i class="fas fa-clock"></i>
                <?php echo $ps_last ? htmlspecialchars($ps_last['name']).' · '.h_ago($ps_last['mtime']) : 'belum ada file'; ?>
            </div>
            <div class="hc-open hc-open-blue">Buka Storage <i class="fas fa-arrow-right"></i></div>
        </a>

        <!-- RAW STORAGE -->
        <a href="texts.php" class="home-card">
            <div class="hc-head">
                <div class="hc-icon hc-icon-gold"><i class="fas fa-file-lines"></i></div>
                <div>
                    <div class="hc-title">RAW STORAGE</div>
                    <div class="hc-sub">file txt — raw link publik</div>
                </div>
            </div>
            <div class="hc-stats">
                <div class="hc-stat"><div class="hc-val"><?php echo $rw_files;?></div><div class="hc-lbl">File Txt</div></div>
                <div class="hc-stat"><div class="hc-val"><?php echo $rw_links;?></div><div class="hc-lbl">Raw Link</div></div>
                <div class="hc-stat"><div class="hc-val"><?php echo h_format_size($rw_size);?></div><div class="hc-lbl">Terpakai</div></div>
            </div>
            <div class="hc-last">
                <i class="fas fa-clock"></i>
                <?php echo $rw_last ? htmlspecialchars($rw_last['name']).' · '.h_ago($rw_last['mtime']) : 'belum ada file'; ?>
            </div>
            <div class="hc-open hc-open-gold">Buka Raw <i class="fas fa-arrow-right"></i></div>
        </a>

    </div>

</div><!-- /main -->

<footer class="footer">
    <span>MAHASTORAGE — Storage &amp; Raw Vault</span>
    <div class="footer-meta">
        <span>Login: <?php echo date('d M H:i',$_SESSION['login_time']);?></span>
        <span>Sesi: aktif selama dipakai</span>
    </div>
</footer>
</div><!-- /layout -->

<script>
function toggleTheme(){
    const isLight = document.body.classList.toggle('light');
    const ic = document.querySelector('#btnTheme i');
    if(ic){ ic.className = isLight ? 'fas fa-moon' : 'fas fa-sun'; }
    localStorage.setItem('storageTheme', isLight ? 'light' : 'dark');
}
if(localStorage.getItem('storageTheme')==='light'){
    document.body.classList.add('light');
    const ic=document.querySelector('#btnTheme i'); if(ic) ic.className='fas fa-moon';
}
</script>
<script src="assets/js/loader.js"></script>
</body>
</html>

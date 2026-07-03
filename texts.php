<?php
require_once 'config.private.php';
secure_session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) { header('Location: index.php'); exit; }

$csrf_token = csrf_token();
if (!file_exists(RAWS_DIR)) mkdir(RAWS_DIR, 0755, true);

function t_format_size($b) {
    $u = ['B','KB','MB','GB']; $i = 0;
    while ($b >= 1024 && $i < 3) { $b /= 1024; $i++; }
    return round($b, 2) . ' ' . $u[$i];
}

// Daftar file txt + zip di raws/
$txt_files = [];
foreach (scandir(RAWS_DIR) as $f) {
    if ($f[0] === '.') continue;
    $p = RAWS_DIR . '/' . $f;
    if (!is_file($p)) continue;
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    if (!in_array($ext, ['txt', 'zip'], true)) continue;
    $txt_files[] = ['name' => $f, 'ext' => $ext, 'size' => filesize($p), 'mtime' => filemtime($p)];
}
usort($txt_files, fn($a,$b) => $b['mtime'] - $a['mtime']);

// Mapping raw link: nama file → id
$links   = file_exists(LINKS_FILE) ? (json_decode(file_get_contents(LINKS_FILE), true) ?? []) : [];
$by_name = [];
foreach ($links as $id => $fname) $by_name[$fname] = $id;

$total_size = array_sum(array_column($txt_files, 'size'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MAHASTORAGE — Raw Storage</title>
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
    <a href="home.php" class="nav-logo" style="text-decoration:none;" title="Ke Dashboard Utama">
        <div class="nav-logo-icon"><i class="fas fa-vault"></i></div>
        <div><div class="nav-logo-text">MAHASTORAGE</div><div class="nav-logo-sub">Storage &amp; Raw Vault</div></div>
    </a>

    <div class="nav-switch">
        <a href="home.php" title="Dashboard Utama"><i class="fas fa-house"></i></a>
        <a href="dashboard.php"><i class="fas fa-photo-film"></i> Storage</a>
        <a href="texts.php" class="active"><i class="fas fa-file-lines"></i> Raw</a>
    </div>

    <div class="nav-stats">
        <div class="nav-stat"><div class="nav-stat-val"><?php echo count($txt_files);?></div><div class="nav-stat-lbl">File Txt</div></div>
        <div class="nav-stat"><div class="nav-stat-val"><?php echo count($links);?></div><div class="nav-stat-lbl">Raw Link</div></div>
        <div class="nav-stat"><div class="nav-stat-val"><?php echo t_format_size($total_size);?></div><div class="nav-stat-lbl">Terpakai</div></div>
    </div>

    <div class="nav-actions">
        <div style="text-align:right;">
            <div class="nav-username"><?php echo htmlspecialchars($_SESSION['username']);?></div>
            <div class="nav-ip"><?php echo htmlspecialchars($_SESSION['ip']);?></div>
        </div>
        <button class="btn-icon" onclick="toggleTheme()" id="btnTheme" title="Toggle Light/Dark"><i class="fas fa-sun"></i></button>
        <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <button class="btn-icon nav-burger" id="navBurger" onclick="toggleMobileMenu(event)" aria-label="Menu">
        <span class="burger-lines"><span></span><span></span><span></span></span>
    </button>
</nav>

<!-- ══ MOBILE MENU ══ -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mm-user">
        <div class="mm-avatar"><i class="fas fa-user"></i></div>
        <div>
            <div class="mm-username"><?php echo htmlspecialchars($_SESSION['username']);?></div>
            <div class="mm-ip"><?php echo htmlspecialchars($_SESSION['ip']);?></div>
        </div>
    </div>
    <div class="mm-sep"></div>
    <button class="mm-item" onclick="location.href='home.php'"><i class="fas fa-house"></i> Dashboard Utama</button>
    <button class="mm-item" onclick="location.href='dashboard.php'"><i class="fas fa-photo-film"></i> Private Storage</button>
    <div class="mm-sep"></div>
    <button class="mm-item" onclick="closeMobileMenu();toggleTheme()"><i class="fas fa-adjust"></i> Light / Dark Mode</button>
    <button class="mm-item mm-danger" onclick="location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button>
</div>
<div class="mobile-menu-backdrop" id="mobileMenuBackdrop" onclick="closeMobileMenu()"></div>

<!-- ══ MAIN ══ -->
<div class="main">

    <!-- Action panel -->
    <div class="action-row">
        <div class="glass-card panel">
            <div class="panel-heading"><i class="fas fa-file-circle-plus"></i> New Text File</div>
            <div id="createMessage" class="msg-box"></div>
            <div class="field-row">
                <div class="field">
                    <label class="field-label">Nama file <span style="text-transform:none;letter-spacing:0;color:var(--text-muted);">(opsional)</span></label>
                    <input type="text" id="newTxtName" class="field-input" placeholder="kosongkan = nama otomatis" autocomplete="off" onkeydown="if(event.key==='Enter')createTxt()">
                </div>
                <button onclick="createTxt()" class="btn btn-primary"><i class="fas fa-plus"></i> Create</button>
            </div>
            <p style="font-size:11px;color:var(--text-muted);margin-top:9px;"><i class="fas fa-info-circle"></i> Kosongkan nama → otomatis <code style="color:var(--gold);">txt-a7k2q9.txt</code> · langsung kebuka di editor</p>
        </div>

        <div class="glass-card panel">
            <div class="panel-heading"><i class="fas fa-cloud-upload-alt"></i> Upload TXT / ZIP</div>
            <div id="uploadMessage" class="msg-box"></div>
            <div id="txtDropArea" class="drop-zone">
                <div class="drop-icon"><i class="fas fa-file-arrow-up"></i></div>
                <div class="drop-title">Drop file .txt atau .zip di sini</div>
                <div class="drop-sub">atau <span style="color:var(--maroon-bright);cursor:pointer;" onclick="document.getElementById('txt_upload').click()">klik untuk pilih</span> · txt max 5MB · zip max 100MB</div>
                <input type="file" id="txt_upload" multiple accept=".txt,.zip" style="display:none;">
            </div>
        </div>
    </div>

    <!-- File table -->
    <div class="glass-card file-table-wrap" id="fileTableWrap">
        <div class="file-table-head">
            <h2><i class="fas fa-file-lines"></i> Text Files</h2>
            <div class="search-wrap">
                <i class="fas fa-search search-icon"></i>
                <input type="search" id="txtSearch" class="search-input" placeholder="Cari file txt..." autocomplete="off" oninput="filterTxt(this.value)">
            </div>
            <span class="search-count"><?php echo count($txt_files);?> file</span>
        </div>

        <?php if (empty($txt_files)): ?>
        <div class="empty-state">
            <div class="empty-icon">📝</div>
            <h3>Belum ada file teks</h3>
            <p>Bikin file baru atau upload .txt di atas</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Raw Link</th>
                    <th>Size</th>
                    <th>Modified</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($txt_files as $tf):
                $id     = $by_name[$tf['name']] ?? '';
                $is_txt = $tf['ext'] === 'txt';
            ?>
            <tr data-name="<?php echo htmlspecialchars($tf['name']);?>">
                <td>
                    <div class="name-cell">
                        <span class="file-emoji"><?php echo $is_txt ? '📝' : '📦';?></span>
                        <span class="fname"><?php echo htmlspecialchars($tf['name']);?></span>
                        <span class="ext-badge"><?php echo strtoupper($tf['ext']);?></span>
                    </div>
                </td>
                <td>
                    <?php if ($id): ?>
                    <span class="raw-link-chip" onclick="copyRawLink('<?php echo htmlspecialchars($id);?>')" title="Klik untuk copy">
                        <i class="fas fa-bolt"></i> /raw/<?php echo htmlspecialchars($id);?> <i class="fas fa-copy"></i>
                    </span>
                    <?php else: ?>
                    <span class="raw-nolink">belum ada link</span>
                    <?php endif; ?>
                </td>
                <td><span class="size-text"><?php echo t_format_size($tf['size']);?></span></td>
                <td><span class="date-text"><?php echo date('Y-m-d H:i',$tf['mtime']);?></span></td>
                <td>
                    <div class="actions">
                        <?php if ($id): ?>
                        <button onclick="deleteRawLink('<?php echo htmlspecialchars($tf['name']);?>')" class="btn btn-gold btn-sm" title="Cabut raw link"><i class="fas fa-bolt-lightning"></i></button>
                        <?php else: ?>
                        <button onclick="createRawLink('<?php echo htmlspecialchars($tf['name']);?>')" class="btn btn-gold btn-sm" title="Buat raw link"><i class="fas fa-bolt"></i></button>
                        <?php endif; ?>
                        <?php if ($is_txt): ?>
                        <button onclick="openTxtEditor('<?php echo htmlspecialchars($tf['name']);?>')" class="btn btn-info btn-sm" title="Edit"><i class="fas fa-edit"></i></button>
                        <?php endif; ?>
                        <button onclick="showRenameTxt('<?php echo htmlspecialchars($tf['name']);?>')" class="btn btn-ghost btn-sm" title="Rename"><i class="fas fa-font"></i></button>
                        <?php if ($is_txt): ?>
                        <button onclick="downloadTxt('<?php echo htmlspecialchars($tf['name']);?>')" class="btn btn-ghost btn-sm" title="Download"><i class="fas fa-download"></i></button>
                        <?php elseif ($id): ?>
                        <a href="raw.php?id=<?php echo htmlspecialchars($id);?>" download class="btn btn-ghost btn-sm" title="Download ZIP"><i class="fas fa-download"></i></a>
                        <?php endif; ?>
                        <button onclick="deleteTxt('<?php echo htmlspecialchars($tf['name']);?>')" class="btn btn-danger btn-sm" title="Hapus"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        <div class="no-results" id="noResults"><i class="fas fa-search"></i><p>Tidak ada hasil</p></div>
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

<!-- ══ EDITOR MODAL ══ -->
<div id="txtEditModal" class="modal-overlay">
    <div class="modal" style="max-width:780px;">
        <div class="modal-header">
            <h3><i class="fas fa-code"></i> Edit: <span id="txtEditName"></span></h3>
            <div style="display:flex;gap:7px;">
                <button onclick="saveTxtEdit()" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Save</button>
                <button onclick="closeModal('txtEditModal')" class="btn btn-ghost btn-sm"><i class="fas fa-times"></i></button>
            </div>
        </div>
        <div class="modal-body">
            <div id="txtEditMsg" class="msg-box" style="margin-bottom:10px;"></div>
            <textarea id="txtEditContent" class="edit-textarea" spellcheck="false"></textarea>
        </div>
    </div>
</div>

<!-- ══ RENAME MODAL ══ -->
<div id="txtRenameModal" class="modal-overlay">
    <div class="modal" style="max-width:450px;">
        <div class="modal-header">
            <h3><i class="fas fa-font"></i> Rename</h3>
            <button onclick="closeModal('txtRenameModal')" class="btn btn-ghost btn-sm"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div id="txtRenameMsg" class="msg-box" style="margin-bottom:14px;"></div>
            <label class="field-label">Nama baru</label>
            <input type="text" id="txtRenameInput" class="field-input" autocomplete="off">
        </div>
        <div class="modal-footer">
            <button onclick="performRenameTxt()" class="btn btn-primary"><i class="fas fa-check"></i> Rename</button>
            <button onclick="closeModal('txtRenameModal')" class="btn btn-ghost"><i class="fas fa-times"></i> Cancel</button>
        </div>
    </div>
</div>

<!-- ══ CONFIRM MODAL ══ -->
<div id="confirmModal" class="modal-overlay">
    <div class="modal" style="max-width:420px;">
        <div class="modal-body" style="text-align:center;padding:32px 28px 16px;">
            <span class="confirm-icon" id="confirmIcon">⚠️</span>
            <p class="confirm-message" id="confirmMsg">Yakin?</p>
        </div>
        <div class="modal-footer" style="justify-content:center;gap:10px;">
            <button id="confirmOkBtn" class="btn btn-danger" onclick="confirmResolve(true)"><i class="fas fa-check"></i> Confirm</button>
            <button class="btn btn-ghost" onclick="confirmResolve(false)"><i class="fas fa-times"></i> Cancel</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="toast">
    <i id="toastIcon" class="fas fa-check-circle" style="color:var(--success);"></i>
    <span id="toastMessage">Done!</span>
</div>

<script>
const CSRF_TOKEN = '<?php echo $csrf_token; ?>';
const BASE_URL   = (()=>{ const p=window.location.pathname.split('/'); p.pop(); return window.location.origin+p.join('/'); })();
</script>
<script src="assets/js/texts.js"></script>
<script src="assets/js/loader.js"></script>
</body>
</html>

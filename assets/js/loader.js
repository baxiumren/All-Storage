/* ═══════════════════════════════════════════════════════════
   MAHASTORAGE — Page loader overlay
   Muncul saat pindah halaman / submit form, hilang otomatis.
   ═══════════════════════════════════════════════════════════ */
(function(){
    const ov = document.createElement('div');
    ov.className = 'page-loader';
    ov.id = 'pageLoader';
    ov.innerHTML = '<div class="loader"></div><div class="pl-text">MAHASTORAGE</div>';
    if (document.body) document.body.appendChild(ov);
    else document.addEventListener('DOMContentLoaded', () => document.body.appendChild(ov));

    let hideTimer = null;
    function show(){
        ov.classList.add('show');
        // jaga-jaga: kalau navigasi batal (mis. download), sembunyikan lagi
        clearTimeout(hideTimer);
        hideTimer = setTimeout(() => ov.classList.remove('show'), 8000);
    }
    function hide(){ clearTimeout(hideTimer); ov.classList.remove('show'); }
    window.showPageLoader = show;
    window.hidePageLoader = hide;

    // Link internal yang bikin pindah halaman → tampilkan loader
    document.addEventListener('click', e => {
        const a = e.target.closest('a[href]');
        if (!a) return;
        if (a.target === '_blank' || a.hasAttribute('download')) return;
        const href = a.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript')) return;
        show();
    });

    // Submit form (login, dll)
    document.addEventListener('submit', () => show());

    // Balik via tombol back (bfcache) → jangan nyangkut
    window.addEventListener('pageshow', e => { if (e.persisted) hide(); });
})();

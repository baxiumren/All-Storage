<?php http_response_code(403); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>403 — Forbidden</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect x='6' y='6' width='88' height='88' rx='26' fill='%23055ff0'/><rect x='9' y='9' width='82' height='82' rx='23' fill='none' stroke='%23FFD700' stroke-width='5'/><circle cx='50' cy='50' r='19' fill='none' stroke='white' stroke-width='7'/><circle cx='50' cy='50' r='6' fill='%23FFD700'/><path d='M50 21v10M50 69v10M21 50h10M69 50h10' stroke='white' stroke-width='7' stroke-linecap='round'/></svg>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600&family=Instrument+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{--gold:#FFD700;--blue:#055ff0;--blue-dark:#052cf0;--bg:#020b25;}
body{font-family:'Instrument Sans','Segoe UI',sans-serif;color:#fff;min-height:100vh;
    display:flex;align-items:center;justify-content:center;overflow:hidden;text-align:center;
    background:
        radial-gradient(1000px 600px at 50% -15%, rgba(5,60,240,.35), transparent 62%),
        radial-gradient(600px 600px at 90% 100%, rgba(255,215,0,.06), transparent 60%),
        var(--bg);}
.orb{position:fixed;border-radius:50%;filter:blur(100px);pointer-events:none;
    animation:drift 14s ease-in-out infinite alternate;}
.o1{width:520px;height:520px;background:radial-gradient(circle,rgba(5,44,240,.4),transparent 70%);top:-180px;left:-160px;}
.o2{width:380px;height:380px;background:radial-gradient(circle,rgba(255,215,0,.1),transparent 70%);bottom:-120px;right:-100px;animation-delay:-5s;}
@keyframes drift{from{transform:translate(0,0);}to{transform:translate(28px,18px);}}
.wrap{position:relative;z-index:1;padding:20px;animation:up .7s cubic-bezier(.16,1,.3,1) both;}
@keyframes up{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}
.lock{width:86px;height:86px;margin:0 auto 18px;border-radius:24px;
    background:linear-gradient(145deg,#2f7bff,var(--blue-dark));
    border:1px solid rgba(255,215,0,.4);
    display:flex;align-items:center;justify-content:center;
    box-shadow:0 16px 44px -10px rgba(5,60,240,.8),inset 0 1px 0 rgba(255,255,255,.25);
    animation:shake 3.2s ease-in-out infinite;}
@keyframes shake{0%,86%,100%{transform:rotate(0);}88%{transform:rotate(-6deg);}92%{transform:rotate(6deg);}96%{transform:rotate(-3deg);}}
.lock svg{width:40px;height:40px;}
.code{font-family:'Fraunces',serif;font-size:clamp(96px,20vw,170px);font-weight:600;line-height:1;
    background:linear-gradient(180deg,#fff 20%,var(--gold) 70%,#b8860b);
    -webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;
    filter:drop-shadow(0 10px 40px rgba(255,215,0,.25));}
h1{font-family:'Fraunces',serif;font-size:clamp(20px,4vw,28px);font-weight:600;margin:6px 0 10px;}
p{color:#8fa3cc;font-size:14px;max-width:400px;margin:0 auto 28px;line-height:1.6;}
.btn{display:inline-flex;align-items:center;gap:9px;padding:13px 26px;border-radius:14px;
    background:linear-gradient(180deg,#2f7bff,var(--blue) 50%,var(--blue-dark));
    border:1px solid rgba(255,215,0,.35);color:#fff;text-decoration:none;font-weight:700;font-size:14px;
    box-shadow:0 10px 30px -8px rgba(5,60,240,.8),inset 0 1px 0 rgba(255,255,255,.25);
    transition:all .25s;}
.btn:hover{transform:translateY(-2px);filter:brightness(1.12);
    box-shadow:0 14px 38px -8px rgba(5,95,240,.9),0 0 0 1px rgba(255,215,0,.5);}
.line{width:80px;height:1px;margin:0 auto 22px;
    background:linear-gradient(90deg,transparent,rgba(255,215,0,.7),transparent);}
@media(prefers-reduced-motion:reduce){*{animation:none!important;}}
</style>
</head>
<body>
<div class="orb o1"></div><div class="orb o2"></div>
<div class="wrap">
    <div class="lock">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="4" y="10" width="16" height="11" rx="2.5" fill="rgba(255,215,0,.9)" stroke="#1a1200"/>
            <path d="M8 10V7a4 4 0 0 1 8 0v3" stroke="#fff"/>
            <circle cx="12" cy="15" r="1.6" fill="#1a1200" stroke="none"/>
            <path d="M12 16.4v1.8" stroke="#1a1200"/>
        </svg>
    </div>
    <div class="code">403</div>
    <div class="line"></div>
    <h1>Area Terkunci</h1>
    <p>Brankas ini tidak bisa dibuka langsung. Isinya hanya keluar lewat pintu resmi — kalau kau pemiliknya, masuk lewat dashboard.</p>
    <a class="btn" href="/">&larr; Kembali ke Beranda</a>
</div>
</body>
</html>

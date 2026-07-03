/* ═══════════════════════════════════════════════════════════
   MAHASTORAGE — Raw Storage (texts.php)
   ═══════════════════════════════════════════════════════════ */

// ══ HELPERS ══
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
['txtEditModal','txtRenameModal','confirmModal'].forEach(id=>{
    const el=document.getElementById(id);
    if(el) el.addEventListener('click', function(e){ if(e.target===this) closeModal(id); });
});
document.addEventListener('keydown', e=>{
    if(e.key==='Escape'){['txtEditModal','txtRenameModal','confirmModal'].forEach(closeModal);closeMobileMenu();}
});

function showToast(msg,type='success'){
    const toast=document.getElementById('toast'),icon=document.getElementById('toastIcon');
    document.getElementById('toastMessage').textContent=msg;
    const c={success:'var(--success)',error:'var(--danger)',warn:'var(--gold)',info:'var(--info)'};
    const ic={success:'check-circle',error:'exclamation-circle',warn:'exclamation-triangle',info:'info-circle'};
    icon.className='fas fa-'+(ic[type]||'check-circle'); icon.style.color=c[type]||c.success;
    toast.classList.add('show'); setTimeout(()=>toast.classList.remove('show'),3200);
}

let _confirmCallback=null;
function customConfirm(msg,onYes){
    _confirmCallback=onYes;
    document.getElementById('confirmMsg').textContent=msg;
    openModal('confirmModal');
}
function confirmResolve(yes){
    closeModal('confirmModal');
    if(yes&&_confirmCallback)_confirmCallback();
    _confirmCallback=null;
}

function api(data){
    return fetch('api/texts.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:new URLSearchParams({...data,csrf_token:CSRF_TOKEN})
    }).then(r=>r.json());
}
function showMsg(elId,msg,type){
    const el=document.getElementById(elId);
    el.className='msg-box '+(type==='success'?'msg-success':'msg-error');
    el.innerHTML='<i class="fas fa-'+(type==='success'?'check-circle':'exclamation-circle')+'"></i> '+msg;
}

// ══ THEME ══
function toggleTheme(){
    const isLight=document.body.classList.toggle('light');
    const ic=document.querySelector('#btnTheme i');
    if(ic) ic.className=isLight?'fas fa-moon':'fas fa-sun';
    localStorage.setItem('storageTheme',isLight?'light':'dark');
}
if(localStorage.getItem('storageTheme')==='light'){
    document.body.classList.add('light');
    const ic=document.querySelector('#btnTheme i'); if(ic) ic.className='fas fa-moon';
}

// ══ MOBILE MENU ══
function toggleMobileMenu(e){
    if(e) e.stopPropagation();
    const menu=document.getElementById('mobileMenu');
    const bd=document.getElementById('mobileMenuBackdrop');
    const burger=document.getElementById('navBurger');
    const open=menu.classList.toggle('open');
    bd.classList.toggle('open',open);
    burger.classList.toggle('open',open);
    document.body.style.overflow=open?'hidden':'';
}
function closeMobileMenu(){
    document.getElementById('mobileMenu')?.classList.remove('open');
    document.getElementById('mobileMenuBackdrop')?.classList.remove('open');
    document.getElementById('navBurger')?.classList.remove('open');
    document.body.style.overflow='';
}

// ══ CREATE TXT ══
function genTxtName(){
    // nama otomatis: txt-xxxxxx (huruf+angka acak, gaya raw id)
    const c='abcdefghjkmnpqrstuvwxyz23456789';
    let s='';for(let i=0;i<6;i++)s+=c[Math.floor(Math.random()*c.length)];
    return 'txt-'+s;
}
function createTxt(){
    let name=document.getElementById('newTxtName').value.trim();
    if(!name){name=genTxtName();showToast('Nama otomatis: '+name+'.txt','info');}
    if(!/^[a-zA-Z0-9._-]+$/.test(name)){showMsg('createMessage','Hanya huruf, angka, titik, _ dan -','error');return;}
    api({action:'create',name}).then(r=>{
        if(r.success){
            showToast('File dibuat!','success');
            // langsung buka editor untuk file baru
            sessionStorage.setItem('openEditor',r.name);
            location.reload();
        } else showMsg('createMessage',r.message,'error');
    });
}
// habis reload, kalau ada file baru → langsung buka editornya
document.addEventListener('DOMContentLoaded',()=>{
    const pending=sessionStorage.getItem('openEditor');
    if(pending){sessionStorage.removeItem('openEditor');openTxtEditor(pending);}
});

// ══ UPLOAD TXT ══
const txtDrop =document.getElementById('txtDropArea');
const txtInput=document.getElementById('txt_upload');
txtDrop.addEventListener('dragover', e=>{e.preventDefault();txtDrop.classList.add('active');});
txtDrop.addEventListener('dragleave',()=>txtDrop.classList.remove('active'));
txtDrop.addEventListener('drop', e=>{e.preventDefault();txtDrop.classList.remove('active');if(e.dataTransfer.files.length)uploadTxtFiles(e.dataTransfer.files);});
txtDrop.addEventListener('click', ()=>txtInput.click());
txtInput.addEventListener('change', e=>{if(e.target.files.length)uploadTxtFiles(e.target.files);});

function uploadTxtFiles(files){
    const list=[...files].filter(f=>f.name.toLowerCase().endsWith('.txt'));
    const skipped=files.length-list.length;
    if(skipped>0) showToast(skipped+' file ditolak (hanya .txt)','warn');
    if(!list.length) return;
    let done=0,failed=0;
    function next(i){
        if(i>=list.length){
            showToast(`${done}/${list.length} txt terupload`+(failed?`, ${failed} gagal`:''),failed?'warn':'success');
            setTimeout(()=>location.reload(),1100);
            return;
        }
        const fd=new FormData();
        fd.append('action','upload');
        fd.append('csrf_token',CSRF_TOKEN);
        fd.append('file',list[i]);
        fetch('api/texts.php',{method:'POST',body:fd}).then(r=>r.json())
            .then(r=>{r.success?done++:failed++;next(i+1);})
            .catch(()=>{failed++;next(i+1);});
    }
    next(0);
}

// ══ RAW LINK ══
function copyRawLink(id){
    const url=BASE_URL+'/raw/'+id;
    if(navigator.clipboard) navigator.clipboard.writeText(url).then(()=>showToast('Raw link disalin!','success'));
    else {
        const el=document.createElement('input');el.style.position='absolute';el.style.left='-9999px';
        el.value=url;document.body.appendChild(el);el.select();document.execCommand('copy');
        document.body.removeChild(el);showToast('Raw link disalin!','success');
    }
}
function createRawLink(name){
    api({action:'link_create',name}).then(r=>{
        if(r.success){
            copyRawLink(r.id);
            setTimeout(()=>location.reload(),700);
        } else showToast(r.message,'error');
    });
}
function deleteRawLink(name){
    customConfirm('Cabut raw link "'+name+'"? Link lama jadi mati.',()=>{
        api({action:'link_delete',name}).then(r=>{
            showToast(r.message,r.success?'success':'error');
            if(r.success) setTimeout(()=>location.reload(),800);
        });
    });
}

// ══ EDITOR ══
let editingTxt='';
function openTxtEditor(name){
    editingTxt=name;
    document.getElementById('txtEditName').textContent=name;
    document.getElementById('txtEditMsg').style.display='none';
    document.getElementById('txtEditContent').value='Loading…';
    openModal('txtEditModal');
    api({action:'view',name}).then(r=>{
        document.getElementById('txtEditContent').value=r.success?r.content:'';
        if(!r.success) showMsg('txtEditMsg',r.message,'error');
    });
}
function saveTxtEdit(){
    api({action:'save',name:editingTxt,content:document.getElementById('txtEditContent').value}).then(r=>{
        showMsg('txtEditMsg',r.message,r.success?'success':'error');
        if(r.success) showToast('Tersimpan!','success');
    });
}

// ══ RENAME ══
let renamingTxt='';
function showRenameTxt(name){
    renamingTxt=name;
    document.getElementById('txtRenameInput').value=name.replace(/\.txt$/i,'');
    document.getElementById('txtRenameMsg').style.display='none';
    openModal('txtRenameModal');
    setTimeout(()=>document.getElementById('txtRenameInput').focus(),120);
}
function performRenameTxt(){
    const newName=document.getElementById('txtRenameInput').value.trim();
    if(!newName){showMsg('txtRenameMsg','Nama tidak boleh kosong','error');return;}
    if(!/^[a-zA-Z0-9._-]+$/.test(newName)){showMsg('txtRenameMsg','Hanya huruf, angka, titik, _ dan -','error');return;}
    api({action:'rename',name:renamingTxt,new_name:newName}).then(r=>{
        if(r.success){showToast('Renamed!','success');setTimeout(()=>location.reload(),700);}
        else showMsg('txtRenameMsg',r.message,'error');
    });
}

// ══ DOWNLOAD & DELETE ══
function downloadTxt(name){
    api({action:'view',name}).then(r=>{
        if(!r.success){showToast(r.message,'error');return;}
        const blob=new Blob([r.content],{type:'text/plain'});
        const a=document.createElement('a');
        a.href=URL.createObjectURL(blob);a.download=name;
        document.body.appendChild(a);a.click();document.body.removeChild(a);
        URL.revokeObjectURL(a.href);
    });
}
function deleteTxt(name){
    customConfirm('Hapus "'+name+'" permanen? Raw link-nya ikut mati.',()=>{
        api({action:'delete',name}).then(r=>{
            showToast(r.message,r.success?'success':'error');
            if(r.success) setTimeout(()=>location.reload(),800);
        });
    });
}

// ══ SEARCH ══
function filterTxt(q){
    q=q.trim().toLowerCase();
    let visible=0;
    document.querySelectorAll('#fileTableWrap tbody tr').forEach(tr=>{
        const hit=!q||tr.dataset.name.toLowerCase().includes(q);
        tr.style.display=hit?'':'none';
        if(hit)visible++;
    });
    const nr=document.getElementById('noResults');
    if(nr) nr.style.display=(q&&!visible)?'block':'none';
}

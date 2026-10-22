<?php
// ============================================================
//  Configuração inicial
// ============================================================
declare(strict_types=1);
session_start();

// Se mais tarde quiseres ligar à BD, descomenta:
// require_once __DIR__ . '/db.php';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Gestão Inteligente de Contactos</title>

  <style>
    /* =========================================================
       RESET / BASE
       ========================================================= */
    * { box-sizing: border-box; }

    body {
      margin: 0;
      font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
      background: #f4f6f8;
      color: #222;
      min-height: 100vh;
    }

    /* =========================================================
       CABEÇALHO
       ========================================================= */
    header {
      text-align: center;
      padding: 24px 16px;
      background: #1f2937;
      color: #fff;
    }

    header h1 { margin: 0 0 8px; font-size: 1.4rem; }
    header p  { margin: 0; font-size: .9rem; opacity: .8; }

    /* =========================================================
       LAYOUT PRINCIPAL
       ========================================================= */
    main {
      max-width: 720px;
      margin: 24px auto;
      padding: 0 16px;
      display: flex;
      flex-direction: column;
      gap: 24px;
    }

    .card {
      background: #fff;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,.06);
    }

    .card h2 {
      margin-top: 0;
      font-size: 1.1rem;
      border-bottom: 1px solid #eee;
      padding-bottom: 8px;
    }

    .hint {
      font-size: .85rem;
      color: #666;
      margin: 12px 0 0;
    }

    /* =========================================================
       MENSAGENS FLASH
       ========================================================= */
    .flash {
      max-width: 720px;
      margin: 16px auto 0;
      padding: 12px 16px;
      border-radius: 8px;
      font-size: .9rem;
    }
    .flash--success { background: #d1fae5; color: #065f46; }
    .flash--error   { background: #fee2e2; color: #991b1b; }

    /* =========================================================
       VÍDEO / IMAGEM
       ========================================================= */
    .video-wrapper {
      position: relative;
      width: 100%;
      aspect-ratio: 4 / 3;
      background: #000;
      border-radius: 8px;
      overflow: hidden;
      margin-bottom: 12px;
    }

    #video, #preview {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: none;
    }

    #video.active, #preview.active { display: block; }

    /* =========================================================
       BOTÕES
       ========================================================= */
    .buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }

    button, .file-label {
      border: none;
      border-radius: 8px;
      padding: 10px 16px;
      font-size: .95rem;
      cursor: pointer;
      transition: opacity .15s, background .15s;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-family: inherit;
    }

    button:disabled { opacity: .4; cursor: not-allowed; }

    .primary   { background: #2563eb; color: #fff; }
    .primary:hover:not(:disabled) { background: #1d4ed8; }

    .secondary { background: #e5e7eb; color: #111; }
    .secondary:hover:not(:disabled) { background: #d1d5db; }

    /* =========================================================
       FORMULÁRIO
       ========================================================= */
    form {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    form label {
      display: flex;
      flex-direction: column;
      font-size: .9rem;
      font-weight: 500;
      gap: 4px;
    }

    form input {
      padding: 10px;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      font-size: 1rem;
      font-family: inherit;
    }

    form input:focus {
      outline: 2px solid #2563eb;
      border-color: transparent;
    }

    form button { align-self: flex-start; }

    /* =========================================================
       LISTA DE CONTACTOS
       ========================================================= */
    #search {
      width: 100%;
      padding: 10px;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      margin-bottom: 12px;
      font-family: inherit;
      font-size: 1rem;
    }

    #contactList {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    #contactList li {
      padding: 12px;
      border-bottom: 1px solid #eee;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 8px;
    }

    #contactList li:last-child { border-bottom: none; }
    #contactList small { color: #666; display: block; }

    #contactList button {
      padding: 6px 10px;
      font-size: .8rem;
    }
  </style>
</head>
<body>

  <header>
    <h1>📇 Gestão Inteligente de Contactos</h1>
    <p>Registe contactos manualmente ou a partir de um cartão de visita.</p>
  </header>

  <?php if ($flash): ?>
    <div class="flash flash--<?= htmlspecialchars($flash['tipo']) ?>">
      <?= htmlspecialchars($flash['msg']) ?>
    </div>
  <?php endif; ?>

  <main>

    <!-- =========================================================
         1. CAPTURA (WEBCAM OU FICHEIRO)
         ========================================================= -->
    <section class="card">
      <h2>1. Capturar cartão de visita</h2>

      <div class="video-wrapper">
        <video id="video" autoplay playsinline></video>
        <canvas id="canvas" style="display:none;"></canvas>
        <img id="preview" alt="Pré-visualização do cartão" />
      </div>

      <div class="buttons">
        <button id="btnStart"   class="primary"   type="button">📷 Ligar câmara</button>
        <button id="btnCapture" class="primary"   type="button" disabled>📸 Tirar foto</button>
        <button id="btnRetry"   class="secondary" type="button" disabled>🔄 Repetir</button>

        <label class="secondary file-label">
          🖼️ Carregar imagem
          <input type="file" id="fileInput" accept="image/*" hidden />
        </label>
      </div>

      <p class="hint">👉 Quando tiveres a foto, preenche o formulário abaixo (ou usa o OCR mais tarde).</p>
    </section>

    <!-- =========================================================
         2. FORMULÁRIO DE CONTACTO
         ========================================================= -->
    <section class="card">
      <h2>2. Dados do contacto</h2>

      <form id="contactForm" method="post" action="index.php">
        <input type="hidden" name="acao" value="criar" />

        <label>Nome
          <input type="text" name="nome" required maxlength="120" />
        </label>

        <label>Empresa
          <input type="text" name="empresa" maxlength="120" />
        </label>

        <label>Telefone
          <input type="tel" name="telefone" maxlength="30" />
        </label>

        <label>Email
          <input type="email" name="email" maxlength="120" />
        </label>

        <button type="submit" class="primary">💾 Guardar contacto</button>
      </form>
    </section>

    <!-- =========================================================
         3. LISTA + PESQUISA
         ========================================================= -->
    <section class="card">
      <h2>3. Contactos guardados</h2>

      <input type="search" id="search" placeholder="🔍 Pesquisar por nome ou empresa..." />

      <ul id="contactList">
        <!-- preenchido por app.js -->
      </ul>
    </section>

  </main>

  <script>
    // ============================================================
    //  1. WEBCAM
    // ============================================================
    const video      = document.getElementById('video');
    const canvas     = document.getElementById('canvas');
    const preview    = document.getElementById('preview');
    const btnStart   = document.getElementById('btnStart');
    const btnCapture = document.getElementById('btnCapture');
    const btnRetry   = document.getElementById('btnRetry');
    const fileInput  = document.getElementById('fileInput');

    let stream = null;

    btnStart.addEventListener('click', async () => {
      try {
        stream = await navigator.mediaDevices.getUserMedia({
          video: { facingMode: 'environment' },
          audio: false
        });
        video.srcObject = stream;
        video.classList.add('active');
        preview.classList.remove('active');

        btnStart.disabled   = true;
        btnCapture.disabled = false;
        btnRetry.disabled   = false;

        btnCapture.onclick = () => {
          const ctx = canvas.getContext('2d');
          canvas.width  = video.videoWidth;
          canvas.height = video.videoHeight;
          ctx.drawImage(video, 0, 0);

          const dataUrl = canvas.toDataURL('image/png');
          preview.src = dataUrl;
          preview.classList.add('active');
          video.classList.remove('active');

          pararCamera();
          console.log('Imagem capturada:', dataUrl.length, 'bytes');

          // 👉 Aqui podes ligar o OCR (Tesseract.js) mais tarde
          // reconhecerCartao(dataUrl);
        };

        btnRetry.onclick = () => {
          preview.classList.remove('active');
          preview.src = '';
          btnStart.click();
        };

      } catch (err) {
        alert('Não foi possível aceder à câmara: ' + err.message);
        console.error(err);
      }
    });

    function pararCamera() {
      if (stream) {
        stream.getTracks().forEach(t => t.stop());
        stream = null;
      }
      video.srcObject = null;
      video.classList.remove('active');
      btnStart.disabled   = false;
      btnCapture.disabled = true;
    }

    // Carregar imagem do disco
    fileInput.addEventListener('change', e => {
      const file = e.target.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = ev => {
        preview.src = ev.target.result;
        preview.classList.add('active');
        video.classList.remove('active');
        btnCapture.disabled = false;
        btnRetry.disabled   = false;

        // 👉 Aqui podes ligar o OCR também:
        // reconhecerCartao(ev.target.result);
      };
      reader.readAsDataURL(file);
    });

    // ============================================================
    //  2. CONTACTOS (localStorage enquanto não há backend PHP)
    // ============================================================
    const form        = document.getElementById('contactForm');
    const contactList = document.getElementById('contactList');
    const searchInput = document.getElementById('search');

    let contactos = JSON.parse(localStorage.getItem('contactos') || '[]');

    function guardarNoStorage() {
      localStorage.setItem('contactos', JSON.stringify(contactos));
    }

    function renderContactos(filtro = '') {
      const f = (filtro || '').toLowerCase();
      contactList.innerHTML = '';

      const filtrados = contactos.filter(c =>
        (c.nome    || '').toLowerCase().includes(f) ||
        (c.empresa || '').toLowerCase().includes(f)
      );

      if (!filtrados.length) {
        contactList.innerHTML = '<li><small>Sem contactos.</small></li>';
        return;
      }

      filtrados.forEach(c => {
        const idx = contactos.indexOf(c);
        const li = document.createElement('li');
        li.innerHTML = `
          <div>
            <strong>${c.nome}</strong>
            <small>${c.empresa  || '—'}</small>
            <small>${c.telefone || ''} ${c.email ? '· ' + c.email : ''}</small>
          </div>
          <button class="secondary" data-index="${idx}" type="button">🗑️</button>
        `;
        contactList.appendChild(li);
      });

      contactList.querySelectorAll('button[data-index]').forEach(btn => {
        btn.onclick = () => {
          const idx = +btn.dataset.index;
          if (confirm('Eliminar este contacto?')) {
            contactos.splice(idx, 1);
            guardarNoStorage();
            renderContactos(searchInput.value);
          }
        };
      });
    }

    form.addEventListener('submit', () => {
      // Guarda também em localStorage para aparecer na lista imediatamente.
      // O form segue normalmente para o PHP (sem preventDefault).
      const data = new FormData(form);
      contactos.push({
        nome:     (data.get('nome')     || '').trim(),
        empresa:  (data.get('empresa')  || '').trim(),
        telefone: (data.get('telefone') || '').trim(),
        email:    (data.get('email')    || '').trim(),
      });
      guardarNoStorage();
      renderContactos(searchInput.value);
    });

    searchInput.addEventListener('input', e => renderContactos(e.target.value));

    renderContactos();
  </script>
</body>
</html>

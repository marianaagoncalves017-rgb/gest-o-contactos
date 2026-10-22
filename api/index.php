<?php
// ============================================================
//  api/index.php — Gestão Inteligente de Contactos
//  Versão compatível com Vercel (sem sessões PHP persistentes)
// ============================================================
declare(strict_types=1);

// Na Vercel não usamos session_start() — não há persistência.
// Os contactos ficam no localStorage do browser (app.js).
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

  <main>

    <!-- =========================================================
         1. CAPTURA (WEBCAM OU FICHEIRO)
         ========================================================= -->
    <section class="card">
      <h2>1. Capturar cartão de visita</h2>

      <div class="video-wrapper">
        <video id="video" autoplay playsinline muted></video>
        <canvas id="canvas" style="display:none;"></canvas>
        <img id="preview" alt="Pré-visualização do cartão" />
      </div>

      <div class="buttons">
        <button id="btnStart"   class="primary"   type="button">📷 Ligar câmara</button>
        <button id="btnStop"    class="secondary" type="button" disabled>⏹️ Desligar câmara</button>
        <button id="btnCapture" class="primary"   type="button" disabled>📸 Tirar foto</button>
        <button id="btnRetry"   class="secondary" type="button" disabled>🔄 Repetir</button>

        <label class="secondary file-label">
          🖼️ Carregar imagem
          <input type="file" id="fileInput" accept="image/*" hidden />
        </label>
      </div>

      <p class="hint">👉 Quando tiveres a foto, preenche o formulário abaixo.</p>
    </section>

    <!-- =========================================================
         2. FORMULÁRIO DE CONTACTO
         ========================================================= -->
    <section class="card">
      <h2>2. Dados do contacto</h2>

      <form id="contactForm">
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

  <script src="/app.js"></script>
</body>
</html>

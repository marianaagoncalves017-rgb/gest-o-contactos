<?php
// ============================================================
//  Configuração inicial
// ============================================================
declare(strict_types=1);
session_start();

// Se mais tarde quiseres ligar à BD, descomenta:
// require_once __DIR__ . '/db.php';

// Variáveis para mensagens
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Gestão Inteligente de Contactos</title>
  <link rel="stylesheet" href="style.css" />
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

    <!-- ============================================= -->
    <!-- 1. CAPTURA (WEBCAM OU FICHEIRO)               -->
    <!-- ============================================= -->
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

    <!-- ============================================= -->
    <!-- 2. FORMULÁRIO DE CONTACTO                     -->
    <!-- ============================================= -->
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

    <!-- ============================================= -->
    <!-- 3. LISTA + PESQUISA                           -->
    <!-- ============================================= -->
    <section class="card">
      <h2>3. Contactos guardados</h2>

      <input type="search" id="search" placeholder="🔍 Pesquisar por nome ou empresa..." />

      <ul id="contactList">
        <!-- preenchido por app.js (localStorage) -->
      </ul>
    </section>

  </main>

  <script src="app.js"></script>
</body>
</html>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Leitor de Cartões - Câmara</title>

  <style>
    * { box-sizing: border-box; }

    body {
      margin: 0;
      font-family: system-ui, -apple-system, sans-serif;
      background: #f4f6f8;
      color: #222;
      display: flex;
      flex-direction: column;
      align-items: center;
      min-height: 100vh;
      padding: 20px;
    }

    header {
      text-align: center;
      margin-bottom: 24px;
    }

    .card {
      background: #fff;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,.06);
      width: 100%;
      max-width: 600px;
    }

    .video-wrapper {
      position: relative;
      width: 100%;
      aspect-ratio: 4 / 3;
      background: #000;
      border-radius: 8px;
      overflow: hidden;
      margin-bottom: 16px;
    }

    #video, #preview {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: none;
    }

    #video.active, #preview.active { display: block; }

    .buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      justify-content: center;
    }

    button, .file-label {
      border: none;
      border-radius: 8px;
      padding: 12px 20px;
      font-size: 1rem;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-family: inherit;
    }

    button:disabled { opacity: .4; cursor: not-allowed; }

    .primary { background: #2563eb; color: #fff; }
    .primary:hover:not(:disabled) { background: #1d4ed8; }

    .secondary { background: #e5e7eb; color: #111; }
    .secondary:hover:not(:disabled) { background: #d1d5db; }
  </style>
</head>
<body>

  <header>
    <h1>📷 Teste de Câmara</h1>
  </header>

  <main class="card">
    <div class="video-wrapper">
      <video id="video" autoplay playsinline muted></video>
      <canvas id="canvas" style="display:none;"></canvas>
      <img id="preview" alt="Pré-visualização" />
    </div>

    <div class="buttons">
      <button id="btnStart" class="primary" type="button">📷 Ligar câmara</button>
      <button id="btnStop" class="secondary" type="button" disabled>⏹️ Desligar</button>
      <button id="btnCapture" class="primary" type="button" disabled>📸 Tirar foto</button>
      <button id="btnRetry" class="secondary" type="button" disabled>🔄 Repetir</button>
    </div>
  </main>

  <script src="/app.js"></script>
</body>
</html>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Leitor de Cartões - OCR</title>
  
  <!-- Tesseract.js - Versão Estável via CDN -->
  <script src="https://unpkg.com/tesseract.js@4.0.2/dist/tesseract.min.js"></script>
  
  <style>
    * { box-sizing: border-box; }
    body { font-family: system-ui, sans-serif; background: #f4f6f8; padding: 20px; margin: 0; }
    .card { background: #fff; padding: 20px; border-radius: 12px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
    .video-wrapper { position: relative; width: 100%; aspect-ratio: 4/3; background: #000; border-radius: 8px; overflow: hidden; margin-bottom: 12px; }
    #video, #preview { width: 100%; height: 100%; object-fit: cover; display: none; }
    #video.active, #preview.active { display: block; }
    .buttons { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; margin-bottom: 16px; }
    button { padding: 10px 16px; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; }
    button:disabled { opacity: .5; cursor: not-allowed; }
    .primary { background: #2563eb; color: #fff; }
    .secondary { background: #e5e7eb; color: #111; }
    .form-group { display: flex; flex-direction: column; gap: 4px; margin-bottom: 12px; }
    .form-group label { font-weight: 500; font-size: .9rem; }
    .form-group input { padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem; }
    #status { text-align: center; font-weight: 500; color: #2563eb; min-height: 24px; margin-bottom: 10px; }
  </style>
</head>
<body>
  <div class="card">
    <h2 style="text-align:center;">📷 Leitor de Cartões</h2>
    
    <div class="video-wrapper">
      <video id="video" autoplay playsinline muted></video>
      <canvas id="canvas" style="display:none;"></canvas>
      <img id="preview" alt="Pré-visualização" />
    </div>

    <div id="status"></div>

    <div class="buttons">
      <button id="btnStart" class="primary">📷 Ligar câmara</button>
      <button id="btnStop" class="secondary" disabled>⏹️ Desligar</button>
      <button id="btnCapture" class="primary" disabled>📸 Tirar foto</button>
      <button id="btnRetry" class="secondary" disabled>🔄 Repetir</button>
    </div>

    <hr>

    <form id="contactForm">
      <div class="form-group">
        <label>Nome</label>
        <input type="text" id="nome" />
      </div>
      <div class="form-group">
        <label>Empresa</label>
        <input type="text" id="empresa" />
      </div>
      <div class="form-group">
        <label>Telefone</label>
        <input type="tel" id="telefone" />
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" id="email" />
      </div>
    </form>
  </div>

  <script src="/app.js"></script>
</body>
</html>

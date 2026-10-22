<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Leitor de Cartões - OCR</title>
  
  <!-- Tesseract.js -->
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

  <!-- CÓDIGO JS AQUI DENTRO -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const video      = document.getElementById('video');
      const canvas     = document.getElementById('canvas');
      const preview    = document.getElementById('preview');
      const btnStart   = document.getElementById('btnStart');
      const btnStop    = document.getElementById('btnStop');
      const btnCapture = document.getElementById('btnCapture');
      const btnRetry   = document.getElementById('btnRetry');

      const statusEl   = document.getElementById('status');
      const inputNome  = document.getElementById('nome');
      const inputEmpresa = document.getElementById('empresa');
      const inputTelefone = document.getElementById('telefone');
      const inputEmail = document.getElementById('email');

      let stream = null;

      async function ligarCamera() {
        if (stream) return;

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
          alert("O teu navegador não suporta acesso à câmara ou não estás em HTTPS.");
          return;
        }

        try {
          stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
          video.srcObject = stream;
          video.classList.add('active');
          preview.classList.remove('active');

          btnStart.disabled   = true;
          btnStop.disabled    = false;
          btnCapture.disabled = false;
          statusEl.textContent = '';

        } catch (err) {
          console.error('Erro na câmara:', err);
          if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
            alert('ERRO: Permissão negada!\n\nPermite o acesso à câmara no teu browser.');
          } else if (err.name === 'NotFoundError') {
            alert('ERRO: Nenhuma câmara encontrada neste dispositivo.');
          } else if (err.name === 'NotReadableError') {
            alert('ERRO: A câmara está a ser usada por outro programa.');
          } else {
            alert('Erro ao ligar a câmara: ' + err.message);
          }
        }
      }

      function desligarCamera() {
        if (stream) {
          stream.getTracks().forEach(track => track.stop());
          stream = null;
        }
        video.srcObject = null;
        video.classList.remove('active');

        btnStart.disabled   = false;
        btnStop.disabled    = true;
        btnCapture.disabled = true;
      }

      function tirarFoto() {
        if (!stream) return;

        canvas.width  = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        const dataUrl = canvas.toDataURL('image/png');
        preview.src = dataUrl;
        preview.classList.add('active');
        video.classList.remove('active');

        desligarCamera();
        btnRetry.disabled = false;

        processarOCR(dataUrl);
      }

      function repetir() {
        preview.src = '';
        preview.classList.remove('active');
        btnRetry.disabled = true;
        
        inputNome.value = '';
        inputEmpresa.value = '';
        inputTelefone.value = '';
        inputEmail.value = '';
        statusEl.textContent = '';

        ligarCamera();
      }

      async function processarOCR(imageData) {
        statusEl.textContent = '🔍 A extrair dados do cartão...';
        
        if (typeof Tesseract === 'undefined') {
          statusEl.textContent = '❌ Erro: O leitor de texto não carregou. Verifica a internet.';
          return;
        }

        try {
          const worker = await Tesseract.createWorker('por+eng');
          const ret = await worker.recognize(imageData);
          const texto = ret.data.text;

          const emailRegex = /[\w.-]+@[\w.-]+\.[a-zA-Z]{2,}/g;
          const emails = texto.match(emailRegex);
          if (emails) inputEmail.value = emails[0];

          const phoneRegex = /(\+?\d[\d\s\-().]{7,}\d)/g;
          const phones = texto.match(phoneRegex);
          if (phones) inputTelefone.value = phones[0].trim();

          const linhas = texto.split('\n').map(l => l.trim()).filter(l => l.length > 2);
          if (linhas.length > 0) inputNome.value = linhas[0];
          if (linhas.length > 1) inputEmpresa.value = linhas[1];

          statusEl.textContent = '✅ Dados preenchidos! Podes corrigir se algo estiver errado.';
          await worker.terminate();

        } catch (err) {
          console.error('Erro no OCR:', err);
          statusEl.textContent = '❌ Erro ao ler o texto. Tenta novamente.';
        }
      }

      btnStart.addEventListener('click', ligarCamera);
      btnStop.addEventListener('click', desligarCamera);
      btnCapture.addEventListener('click', tirarFoto);
      btnRetry.addEventListener('click', repetir);

      window.addEventListener('beforeunload', desligarCamera);
    });
  </script>
</body>
</html>

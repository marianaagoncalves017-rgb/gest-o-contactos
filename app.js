document.addEventListener('DOMContentLoaded', () => {
  const video = document.getElementById('video');
  const canvas = document.getElementById('canvas');
  const preview = document.getElementById('preview');
  const btnStart = document.getElementById('btnStart');
  const btnStop = document.getElementById('btnStop');
  const btnCapture = document.getElementById('btnCapture');
  const btnRetry = document.getElementById('btnRetry');
  const statusEl = document.getElementById('status');

  let stream = null;

  // ==========================================
  // 1. CÂMARA (Exatamente como funcionava)
  // ==========================================
  async function ligarCamera() {
    if (stream) return;
    
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      alert("O navegador não suporta câmara ou não estás em HTTPS.");
      return;
    }

    try {
      // Usa a câmara padrão (sem forçar facingMode)
      stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
      video.srcObject = stream;
      video.classList.add('active');
      preview.classList.remove('active');
      
      btnStart.disabled = true;
      btnStop.disabled = false;
      btnCapture.disabled = false;
      statusEl.textContent = '';
    } catch (err) {
      console.error('Erro na câmara:', err);
      alert('Erro ao ligar câmara: ' + err.message);
    }
  }

  function desligarCamera() {
    if (stream) {
      stream.getTracks().forEach(t => t.stop());
      stream = null;
    }
    video.srcObject = null;
    video.classList.remove('active');
    btnStart.disabled = false;
    btnStop.disabled = true;
    btnCapture.disabled = true;
  }

  function tirarFoto() {
    if (!stream) return;
    
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    const dataUrl = canvas.toDataURL('image/png');
    
    preview.src = dataUrl;
    preview.classList.add('active');
    video.classList.remove('active');
    
    desligarCamera();
    btnRetry.disabled = false;
    
    // Dispara o preenchimento automático
    processarOCR(dataUrl);
  }

  function repetir() {
    preview.src = '';
    preview.classList.remove('active');
    btnRetry.disabled = true;
    statusEl.textContent = '';
    
    // Limpa os campos para o próximo cartão
    document.getElementById('nome').value = '';
    document.getElementById('empresa').value = '';
    document.getElementById('telefone').value = '';
    document.getElementById('email').value = '';
    
    ligarCamera();
  }

  // ==========================================
  // 2. OCR (Apenas depois de tirar a foto)
  // ==========================================
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

      // Extrair Email
      const emailRegex = /[\w.-]+@[\w.-]+\.[a-zA-Z]{2,}/g;
      const emails = texto.match(emailRegex);
      if (emails) document.getElementById('email').value = emails[0];

      // Extrair Telefone
      const phoneRegex = /(\+?\d[\d\s\-().]{7,}\d)/g;
      const phones = texto.match(phoneRegex);
      if (phones) document.getElementById('telefone').value = phones[0].trim();

      // Nome / Empresa (heurística básica: as primeiras duas linhas)
      const linhas = texto.split('\n').map(l => l.trim()).filter(l => l.length > 2);
      if (linhas.length > 0) document.getElementById('nome').value = linhas[0];
      if (linhas.length > 1) document.getElementById('empresa').value = linhas[1];

      statusEl.textContent = '✅ Dados preenchidos! Podes corrigir se algo estiver errado.';
      await worker.terminate();

    } catch (err) {
      console.error('Erro no OCR:', err);
      statusEl.textContent = '❌ Erro ao ler o texto. Tenta novamente.';
    }
  }

  // ==========================================
  // 3. Eventos
  // ==========================================
  btnStart.addEventListener('click', ligarCamera);
  btnStop.addEventListener('click', desligarCamera);
  btnCapture.addEventListener('click', tirarFoto);
  btnRetry.addEventListener('click', repetir);

  window.addEventListener('beforeunload', desligarCamera);
});

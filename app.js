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
  // 1. CÂMARA (A prioridade máxima)
  // ==========================================
  async function ligarCamera() {
    if (stream) return;
    
    // Verifica se a API existe
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      alert("O teu navegador não suporta acesso à câmara ou a ligação não é segura (HTTPS).");
      return;
    }

    try {
      // Removemos o facingMode para garantir compatibilidade com o PC
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
    
    // Inicia o OCR
    processarOCR(dataUrl);
  }

  function repetir() {
    preview.src = '';
    preview.classList.remove('active');
    btnRetry.disabled = true;
    statusEl.textContent = '';
    // Limpar campos
    document.getElementById('nome').value = '';
    document.getElementById('empresa').value = '';
    document.getElementById('telefone').value = '';
    document.getElementById('email').value = '';
    ligarCamera();
  }

  // ==========================================
  // 2. OCR (Tesseract.js)
  // ==========================================
  async function processarOCR(imageData) {
    statusEl.textContent = '🔍 A ler o cartão... aguarda uns segundos.';
    
    // Verifica se o Tesseract foi carregado da internet
    if (typeof Tesseract === 'undefined') {
      statusEl.textContent = '❌ Erro: Biblioteca de OCR não carregada (verifica a internet).';
      return;
    }

    try {
      const worker = await Tesseract.createWorker('por+eng');
      const ret = await worker.recognize(imageData);
      const texto = ret.data.text;
      console.log('Texto detetado:', texto);

      // Email
      const emailRegex = /[\w.-]+@[\w.-]+\.[a-zA-Z]{2,}/g;
      const emails = texto.match(emailRegex);
      if (emails && emails.length > 0) {
        document.getElementById('email').value = emails[0];
      }

      // Telefone
      const phoneRegex = /(\+?\d[\d\s\-().]{7,}\d)/g;
      const phones = texto.match(phoneRegex);
      if (phones && phones.length > 0) {
        document.getElementById('telefone').value = phones[0].trim();
      }

      // Nome / Empresa (Heurística de linhas)
      const linhas = texto.split('\n').map(l => l.trim()).filter(l => l.length > 2);
      if (linhas.length > 0) document.getElementById('nome').value = linhas[0];
      if (linhas.length > 1) document.getElementById('empresa').value = linhas[1];

      statusEl.textContent = '✅ Leitura concluída! Corrige os campos se necessário.';
      await worker.terminate();

    } catch (err) {
      console.error('Erro no OCR:', err);
      statusEl.textContent = '❌ Erro ao processar imagem. Tenta outra vez.';
    }
  }

  // ==========================================
  // 3. EVENTOS
  // ==========================================
  btnStart.addEventListener('click', ligarCamera);
  btnStop.addEventListener('click', desligarCamera);
  btnCapture.addEventListener('click', tirarFoto);
  btnRetry.addEventListener('click', repetir);

  window.addEventListener('beforeunload', desligarCamera);
});

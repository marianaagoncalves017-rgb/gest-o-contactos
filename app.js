document.addEventListener('DOMContentLoaded', () => {
  const video      = document.getElementById('video');
  const canvas     = document.getElementById('canvas');
  const preview    = document.getElementById('preview');
  const btnStart   = document.getElementById('btnStart');
  const btnStop    = document.getElementById('btnStop');
  const btnCapture = document.getElementById('btnCapture');
  const btnRetry   = document.getElementById('btnRetry');

  // Elementos do formulário e status (novos)
  const statusEl   = document.getElementById('status');
  const inputNome  = document.getElementById('nome');
  const inputEmpresa = document.getElementById('empresa');
  const inputTelefone = document.getElementById('telefone');
  const inputEmail = document.getElementById('email');

  let stream = null;

  // ==========================================
  // CÂMARA (Intacta como estava)
  // ==========================================
  async function ligarCamera() {
    if (stream) return;

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      alert("O teu navegador não suporta acesso à câmara ou estás a aceder por HTTP em vez de HTTPS.");
      return;
    }

    try {
      stream = await navigator.mediaDevices.getUserMedia({
        video: true, 
        audio: false
      });

      video.srcObject = stream;
      video.classList.add('active');
      preview.classList.remove('active');

      btnStart.disabled   = true;
      btnStop.disabled    = false;
      btnCapture.disabled = false;

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

    // 👉 AQUI: Chama a função que vai ler o cartão e preencher
    processarOCR(dataUrl);
  }

  function repetir() {
    preview.src = '';
    preview.classList.remove('active');
    btnRetry.disabled = true;
    
    // Limpa os campos para o próximo cartão
    if(inputNome) inputNome.value = '';
    if(inputEmpresa) inputEmpresa.value = '';
    if(inputTelefone) inputTelefone.value = '';
    if(inputEmail) inputEmail.value = '';
    if(statusEl) statusEl.textContent = '';

    ligarCamera();
  }

  // ==========================================
  // OCR (Nova funcionalidade)
  // ==========================================
  async function processarOCR(imageData) {
    if (statusEl) statusEl.textContent = '🔍 A extrair dados do cartão...';
    
    if (typeof Tesseract === 'undefined') {
      if (statusEl) statusEl.textContent = '❌ Erro: O leitor de texto não carregou. Verifica a internet.';
      return;
    }

    try {
      const worker = await Tesseract.createWorker('por+eng');
      const ret = await worker.recognize(imageData);
      const texto = ret.data.text;

      // Extrair Email
      const emailRegex = /[\w.-]+@[\w.-]+\.[a-zA-Z]{2,}/g;
      const emails = texto.match(emailRegex);
      if (emails && inputEmail) inputEmail.value = emails[0];

      // Extrair Telefone
      const phoneRegex = /(\+?\d[\d\s\-().]{7,}\d)/g;
      const phones = texto.match(phoneRegex);
      if (phones && inputTelefone) inputTelefone.value = phones[0].trim();

      // Nome / Empresa (Heurística: assume as primeiras linhas com texto)
      const linhas = texto.split('\n').map(l => l.trim()).filter(l => l.length > 2);
      if (linhas.length > 0 && inputNome) inputNome.value = linhas[0];
      if (linhas.length > 1 && inputEmpresa) inputEmpresa.value = linhas[1];

      if (statusEl) statusEl.textContent = '✅ Dados preenchidos! Podes corrigir se algo estiver errado.';
      await worker.terminate();

    } catch (err) {
      console.error('Erro no OCR:', err);
      if (statusEl) statusEl.textContent = '❌ Erro ao ler o texto. Tenta novamente.';
    }
  }

  // ==========================================
  // Eventos
  // ==========================================
  btnStart.addEventListener('click', ligarCamera);
  btnStop.addEventListener('click', desligarCamera);
  btnCapture.addEventListener('click', tirarFoto);
  btnRetry.addEventListener('click', repetir);

  window.addEventListener('beforeunload', desligarCamera);
});

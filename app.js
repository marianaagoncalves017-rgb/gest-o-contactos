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

  // --- CÂMARA ---
  async function ligarCamera() {
    if (stream) return;
    try {
      stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
      video.srcObject = stream;
      video.classList.add('active');
      preview.classList.remove('active');
      btnStart.disabled = true;
      btnStop.disabled = false;
      btnCapture.disabled = false;
    } catch (err) {
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
    
    // Iniciar OCR
    processarOCR(dataUrl);
  }

  function repetir() {
    preview.src = '';
    preview.classList.remove('active');
    btnRetry.disabled = true;
    statusEl.textContent = '';
    // Limpar campos? Podes querer manter, mas vamos limpar para novo scan
    document.getElementById('nome').value = '';
    document.getElementById('empresa').value = '';
    document.getElementById('telefone').value = '';
    document.getElementById('email').value = '';
    ligarCamera();
  }

  // --- OCR ---
  async function processarOCR(imageData) {
    statusEl.textContent = '🔍 A ler o cartão... (pode demorar alguns segundos)';
    
    try {
      // Usar o worker do Tesseract
      const worker = await Tesseract.createWorker('por+eng');
      const ret = await worker.recognize(imageData);
      const texto = ret.data.text;
      console.log('Texto detetado:', texto);

      // Extrair Email (Regex simples)
      const emailRegex = /[\w.-]+@[\w.-]+\.[a-zA-Z]{2,}/g;
      const emails = texto.match(emailRegex);
      if (emails && emails.length > 0) {
        document.getElementById('email').value = emails[0];
      }

      // Extrair Telefone (Regex para formatos comuns)
      // Apanha números com 9 a 15 dígitos, com ou sem espaços/traços
      const phoneRegex = /(\+?\d[\d\s\-().]{7,}\d)/g;
      const phones = texto.match(phoneRegex);
      if (phones && phones.length > 0) {
        // Limpar o número encontrado
        const numLimpo = phones[0].replace(/[\s\-().]/g, '');
        document.getElementById('telefone').value = numLimpo;
      }

      // Tentar adivinhar Nome/Empresa (Heurística simples: linhas curtas)
      const linhas = texto.split('\n').filter(l => l.trim().length > 2);
      if (linhas.length > 0) {
        // A primeira linha costuma ser o nome
        document.getElementById('nome').value = linhas[0].trim();
      }
      if (linhas.length > 1) {
        // A segunda linha pode ser a empresa
        document.getElementById('empresa').value = linhas[1].trim();
      }

      statusEl.textContent = '✅ Leitura concluída! Corrige os campos se necessário.';
      await worker.terminate();

    } catch (err) {
      console.error(err);
      statusEl.textContent = '❌ Erro ao ler a imagem. Tenta novamente.';
    }
  }

  // --- EVENTOS ---
  btnStart.addEventListener('click', ligarCamera);
  btnStop.addEventListener('click', desligarCamera);
  btnCapture.addEventListener('click', tirarFoto);
  btnRetry.addEventListener('click', repetir);

  window.addEventListener('beforeunload', desligarCamera);
});

document.addEventListener('DOMContentLoaded', () => {
  const video      = document.getElementById('video');
  const canvas     = document.getElementById('canvas');
  const preview    = document.getElementById('preview');
  const btnStart   = document.getElementById('btnStart');
  const btnStop    = document.getElementById('btnStop');
  const btnCapture = document.getElementById('btnCapture');
  const btnRepeat  = document.getElementById('btnRepeat');
  const statusText = document.getElementById('statusText');

  let stream = null;

  async function ligarCamera() {
    if (stream) return;

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      alert("O navegador não suporta a API da câmara ou não está num ambiente seguro.");
      return;
    }

    try {
      if (statusText) statusText.textContent = "A pedir acesso à câmara...";

      // Pede permissão e acesso à câmara
      stream = await navigator.mediaDevices.getUserMedia({
        video: { width: { ideal: 1280 }, height: { ideal: 720 } },
        audio: false
      });

      // Associa a stream ao elemento <video>
      video.srcObject = stream;
      video.setAttribute('playsinline', true); // Essencial para Safari / iOS
      
      // FORÇA A REPRODUÇÃO DO VÍDEO (sem isto fica ecrã preto)
      await video.play();

      preview.classList.remove('active');

      // Atualiza os botões
      btnStart.disabled   = true;
      btnStop.disabled    = false;
      btnCapture.disabled = false;
      btnRepeat.disabled  = true;
      
      if (statusText) statusText.textContent = "Câmara ligada com sucesso!";

    } catch (err) {
      console.error('Erro detalhado da câmara:', err);
      
      if (statusText) statusText.textContent = "Erro: " + err.name;

      if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
        alert('PERMISSÃO NEGADA:\n\nClica no ícone do CADEADO 🔒 na barra do navegador (ao lado do https://) e altera a câmara para "Permitir". Depois atualiza a página.');
      } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
        alert('Nenhuma câmara foi encontrada no teu dispositivo.');
      } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
        alert('A câmara já está a ser usada por outra aplicação (Zoom, Teams, outra tab, etc.). Fecha-a e tenta novamente.');
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
    preview.classList.remove('active');

    btnStart.disabled   = false;
    btnStop.disabled    = true;
    btnCapture.disabled = true;
    if (statusText) statusText.textContent = "Câmara desligada.";
  }

  function tirarFoto() {
    if (!stream) return;

    canvas.width  = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;
    
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    preview.classList.add('active');

    btnCapture.disabled = true;
    btnRepeat.disabled  = false;
  }

  function repetir() {
    preview.classList.remove('active');
    btnRepeat.disabled = true;
    btnCapture.disabled = false;
  }

  // Event Listeners
  btnStart.addEventListener('click', ligarCamera);
  btnStop.addEventListener('click', desligarCamera);
  btnCapture.addEventListener('click', tirarFoto);
  btnRepeat.addEventListener('click', repetir);

  window.addEventListener('beforeunload', desligarCamera);
});

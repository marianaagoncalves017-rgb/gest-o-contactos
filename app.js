document.addEventListener('DOMContentLoaded', () => {
  const video      = document.getElementById('video');
  const canvas     = document.getElementById('canvas');
  const preview    = document.getElementById('preview');
  const btnStart   = document.getElementById('btnStart');
  const btnStop    = document.getElementById('btnStop');
  const btnCapture = document.getElementById('btnCapture');
  const btnRepeat  = document.getElementById('btnRepeat'); // Ajustado para corresponder ao ID no index.php
  const statusText = document.getElementById('statusText');

  let stream = null;

  async function ligarCamera() {
    if (stream) return;

    // Verifica se o browser suporta a API e se está em HTTPS
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
      video.setAttribute('playsinline', true); // Requisito para funcionar no iOS/Safari
      await video.play(); // Garante que o stream de vídeo começa a ser reproduzido imediatamente

      preview.classList.remove('active');

      btnStart.disabled   = true;
      btnStop.disabled    = false;
      btnCapture.disabled = false;
      btnRepeat.disabled  = true;
      if (statusText) statusText.textContent = "";

    } catch (err) {
      console.error('Erro na câmara:', err);
      
      if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
        alert('ERRO: Permissão negada!\n\nClica no ícone do cadeado ao lado do endereço do site e permite o acesso à câmara.');
      } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
        alert('ERRO: Nenhuma câmara encontrada neste dispositivo.');
      } else if (err.name === 'NotReadableError') {
        alert('ERRO: A câmara está a ser usada por outro programa (Zoom, Teams, etc). Fecha-os e tenta novamente.');
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

  btnStart.addEventListener('click', ligarCamera);
  btnStop.addEventListener('click', desligarCamera);
  btnCapture.addEventListener('click', tirarFoto);
  btnRepeat.addEventListener('click', repetir);

  window.addEventListener('beforeunload', desligarCamera);
});

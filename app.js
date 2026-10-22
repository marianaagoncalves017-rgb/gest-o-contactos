document.addEventListener('DOMContentLoaded', () => {
  const video      = document.getElementById('video');
  const canvas     = document.getElementById('canvas');
  const preview    = document.getElementById('preview');
  const btnStart   = document.getElementById('btnStart');
  const btnStop    = document.getElementById('btnStop');
  const btnCapture = document.getElementById('btnCapture');
  const btnRetry   = document.getElementById('btnRetry');

  let stream = null;

  async function ligarCamera() {
    if (stream) return;

    try {
      // Tenta pedir a câmara
      stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment' },
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
      
      // Explicar o erro ao utilizador
      if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
        alert('ERRO: Permissão negada!\n\nClica no cadeado ao lado do endereço do site e permite o acesso à câmara.');
      } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
        alert('ERRO: Nenhuma câmara encontrada neste dispositivo.');
      } else if (err.name === 'NotReadableError') {
        alert('ERRO: A câmara está a ser usada por outro programa (Zoom, Teams, etc). Fecha-os e tenta novamente.');
      } else {
        alert('Erro desconhecido ao ligar a câmara: ' + err.message);
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

    preview.src = canvas.toDataURL('image/png');
    preview.classList.add('active');
    video.classList.remove('active');

    desligarCamera();
    btnRetry.disabled = false;
  }

  function repetir() {
    preview.src = '';
    preview.classList.remove('active');
    btnRetry.disabled = true;
    ligarCamera();
  }

  btnStart.addEventListener('click', ligarCamera);
  btnStop.addEventListener('click', desligarCamera);
  btnCapture.addEventListener('click', tirarFoto);
  btnRetry.addEventListener('click', repetir);

  window.addEventListener('beforeunload', desligarCamera);
});

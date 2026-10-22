/* ============================================================
   app.js — Gestão Inteligente de Contactos
   ------------------------------------------------------------
   Responsável por:
     • ligar / desligar a webcam
     • tirar foto (guardar frame no canvas → imagem)
     • carregar imagem do disco
     • gerir contactos em localStorage (temporário)
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

  /* ==========================================================
     1. ELEMENTOS DO DOM
     ========================================================== */
  const video      = document.getElementById('video');
  const canvas     = document.getElementById('canvas');
  const preview    = document.getElementById('preview');

  const btnStart   = document.getElementById('btnStart');
  const btnStop    = document.getElementById('btnStop');
  const btnCapture = document.getElementById('btnCapture');
  const btnRetry   = document.getElementById('btnRetry');
  const fileInput  = document.getElementById('fileInput');

  const form        = document.getElementById('contactForm');
  const contactList = document.getElementById('contactList');
  const searchInput = document.getElementById('search');

  /* ==========================================================
     2. WEBCAM — LIGAR / DESLIGAR
     ========================================================== */
  let stream = null;          // guarda o MediaStream ativo
  let ultimaImagem = null;    // dataURL do último frame capturado

  /**
   * Liga a câmara.
   * - Pede permissão ao browser
   * - Coloca o stream no <video>
   * - Atualiza estado dos botões
   */
  async function ligarCamera() {
    if (stream) return; // já está ligada

    try {
      stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment' }, // traseira em mobile
        audio: false
      });

      video.srcObject = stream;
      video.classList.add('active');
      preview.classList.remove('active');

      btnStart.disabled   = true;
      btnStop.disabled    = false;
      btnCapture.disabled = false;

    } catch (err) {
      console.error('Erro ao ligar câmara:', err);
      alert(
        'Não foi possível aceder à câmara.\n\n' +
        'Verifica:\n' +
        '• se estás em localhost ou HTTPS\n' +
        '• se deste permissão ao browser\n\n' +
        'Detalhe: ' + err.message
      );
    }
  }

  /**
   * Desliga a câmara.
   * - Para todas as tracks do stream
   * - Liberta o <video>
   * - Atualiza estado dos botões
   */
  function desligarCamera() {
    if (stream) {
      stream.getTracks().forEach(track => track.stop());
      stream = null;
    }
    video.srcObject = null;
    video.classList.remove('active');

    btnStart.disabled   = false;
    btnStop.disabled    = true;
    btnCapture.disabled = !video.classList.contains('active') && !ultimaImagem;
  }

  /* ==========================================================
     3. TIRAR FOTO
     ========================================================== */
  function tirarFoto() {
    if (!stream) {
      alert('A câmara não está ligada.');
      return;
    }

    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;

    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    ultimaImagem = canvas.toDataURL('image/png');

    preview.src = ultimaImagem;
    preview.classList.add('active');
    video.classList.remove('active');

    // desliga a câmara após capturar
    desligarCamera();

    btnRetry.disabled   = false;
    btnCapture.disabled = true;

    console.log('Imagem capturada:', ultimaImagem.length, 'bytes');

    // 👉 Aqui podes ligar o OCR mais tarde:
    // reconhecerCartao(ultimaImagem);
  }

  /* ==========================================================
     4. REPETIR (apagar preview e voltar a ligar câmara)
     ========================================================== */
  function repetir() {
    ultimaImagem = null;
    preview.src = '';
    preview.classList.remove('active');

    btnRetry.disabled   = true;
    btnCapture.disabled = true;

    ligarCamera();
  }

  /* ==========================================================
     5. CARREGAR IMAGEM DO DISCO
     ========================================================== */
  function carregarImagem(file) {
    if (!file) return;

    const reader = new FileReader();
    reader.onload = ev => {
      ultimaImagem = ev.target.result;
      preview.src = ultimaImagem;
      preview.classList.add('active');
      video.classList.remove('active');

      btnCapture.disabled = true;
      btnRetry.disabled   = false;

      // 👉 Aqui podes ligar o OCR também:
      // reconhecerCartao(ultimaImagem);
    };
    reader.readAsDataURL(file);
  }

  /* ==========================================================
     6. EVENTOS DOS BOTÕES
     ========================================================== */
  btnStart.addEventListener('click', ligarCamera);
  btnStop.addEventListener('click', desligarCamera);
  btnCapture.addEventListener('click', tirarFoto);
  btnRetry.addEventListener('click', repetir);

  fileInput.addEventListener('change', e => {
    carregarImagem(e.target.files[0]);
    e.target.value = ''; // permite voltar a escolher o mesmo ficheiro
  });

  // Segurança: se a página for fechada, desliga a câmara
  window.addEventListener('beforeunload', desligarCamera);

  /* ==========================================================
     7. CONTACTOS (localStorage — temporário até haver BD)
     ========================================================== */
  let contactos = JSON.parse(localStorage.getItem('contactos') || '[]');

  function guardarNoStorage() {
    localStorage.setItem('contactos', JSON.stringify(contactos));
  }

  function renderContactos(filtro = '') {
    const f = (filtro || '').toLowerCase();
    contactList.innerHTML = '';

    const filtrados = contactos.filter(c =>
      (c.nome    || '').toLowerCase().includes(f) ||
      (c.empresa || '').toLowerCase().includes(f)
    );

    if (!filtrados.length) {
      contactList.innerHTML = '<li><small>Sem contactos.</small></li>';
      return;
    }

    filtrados.forEach(c => {
      const idx = contactos.indexOf(c);
      const li = document.createElement('li');
      li.innerHTML = `
        <div>
          <strong>${c.nome}</strong>
          <small>${c.empresa  || '—'}</small>
          <small>${c.telefone || ''} ${c.email ? '· ' + c.email : ''}</small>
        </div>
        <button class="secondary" data-index="${idx}" type="button">🗑️</button>
      `;
      contactList.appendChild(li);
    });

    contactList.querySelectorAll('button[data-index]').forEach(btn => {
      btn.onclick = () => {
        const idx = +btn.dataset.index;
        if (confirm('Eliminar este contacto?')) {
          contactos.splice(idx, 1);
          guardarNoStorage();
          renderContactos(searchInput.value);
        }
      };
    });
  }

  form.addEventListener('submit', () => {
    // Guarda também em localStorage para aparecer já na lista.
    // O form segue normalmente para o PHP (sem preventDefault).
    const data = new FormData(form);
    contactos.push({
      nome:     (data.get('nome')     || '').trim(),
      empresa:  (data.get('empresa')  || '').trim(),
      telefone: (data.get('telefone') || '').trim(),
      email:    (data.get('email')    || '').trim(),
    });
    guardarNoStorage();
    renderContactos(searchInput.value);
  });

  searchInput.addEventListener('input', e => renderContactos(e.target.value));

  // Render inicial
  renderContactos();
});

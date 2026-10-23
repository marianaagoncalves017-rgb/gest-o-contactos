<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leitor de Cartões - OCR (Frente & Verso)</title>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@4/dist/tesseract.min.js"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 40px 20px;
        }

        .card {
            background: #ffffff;
            border-radius: 16px;
            padding: 32px 40px;
            width: 100%;
            max-width: 620px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }

        h2 {
            text-align: center;
            margin-bottom: 24px;
            color: #000;
            font-size: 22px;
            font-weight: 700;
        }

        .video-container {
            position: relative;
            width: 100%;
            height: 380px;
            background-color: #000;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        video, canvas {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        canvas {
            display: none;
        }

        .preview.active canvas {
            display: block;
        }

        .preview.active video {
            display: none;
        }

        .buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 24px;
            justify-content: space-between;
        }

        button {
            flex: 1 1 calc(50% - 10px);
            padding: 10px 14px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .btn-primary {
            background-color: #3b82f6;
            color: white;
        }

        .btn-secondary {
            background-color: #e5e7eb;
            color: #374151;
        }

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .status {
            font-size: 14px;
            color: #ef4444;
            margin-bottom: 16px;
            min-height: 20px;
            text-align: center;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 6px;
        }

        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
        }
    </style>
</head>
<body>

    <div class="card">
        <h2>📷 Leitor de Cartões</h2>

        <div class="video-container preview" id="preview">
            <video id="video" autoplay playsinline muted></video>
            <canvas id="canvas"></canvas>
        </div>

        <div class="buttons">
            <button class="btn-primary" id="btnStart">📷 Ligar câmara</button>
            <button class="btn-secondary" id="btnFlip" disabled>🔄 Virar câmara</button>
            <button class="btn-primary" id="btnCapture" disabled>📸 Tirar Foto Frente</button>
            <button class="btn-primary" id="btnCaptureBack" disabled>📸 Tirar Foto Verso</button>
            <button class="btn-secondary" id="btnRepeat" disabled>🔁 Limpar / Recomeçar</button>
            <button class="btn-secondary" id="btnStop" disabled style="flex: 1 1 100%;">⏹️ Desligar</button>
        </div>

        <div class="status" id="statusText"></div>

        <form id="cardForm">
            <div class="form-group">
                <label for="inputName">Nome</label>
                <input type="text" id="inputName">
            </div>
            <div class="form-group">
                <label for="inputCompany">Empresa</label>
                <input type="text" id="inputCompany">
            </div>
            <div class="form-group">
                <label for="inputPhone">Telefone</label>
                <input type="text" id="inputPhone">
            </div>
            <div class="form-group">
                <label for="inputEmail">Email</label>
                <input type="email" id="inputEmail">
            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const preview = document.getElementById('preview');
        const btnStart = document.getElementById('btnStart');
        const btnFlip = document.getElementById('btnFlip');
        const btnStop = document.getElementById('btnStop');
        const btnCapture = document.getElementById('btnCapture');
        const btnCaptureBack = document.getElementById('btnCaptureBack');
        const btnRepeat = document.getElementById('btnRepeat');
        const statusText = document.getElementById('statusText');

        const inputName = document.getElementById('inputName');
        const inputCompany = document.getElementById('inputCompany');
        const inputPhone = document.getElementById('inputPhone');
        const inputEmail = document.getElementById('inputEmail');

        let stream = null;
        let currentFacingMode = "environment";

        async function ligarCamera() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert("O teu navegador não suporta a API de câmara neste endereço.");
                return;
            }

            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }

            try {
                statusText.style.color = "#3b82f6";
                statusText.textContent = "A pedir permissão da câmara...";

                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { exact: currentFacingMode } },
                    audio: false
                }).catch(async () => {
                    return await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: currentFacingMode },
                        audio: false
                    });
                });

                video.srcObject = stream;
                video.setAttribute('playsinline', true);
                await video.play();

                preview.classList.remove('active');
                btnStart.disabled = true;
                btnFlip.disabled = false;
                btnStop.disabled = false;
                btnCapture.disabled = false;
                btnCaptureBack.disabled = false;
                btnRepeat.disabled = false;
                statusText.textContent = "";

            } catch (err) {
                console.error("Erro na câmara:", err);
                statusText.style.color = "#ef4444";
                alert("Erro ao ligar câmara: " + err.message);
            }
        }

        function alternarCamera() {
            currentFacingMode = (currentFacingMode === "environment") ? "user" : "environment";
            ligarCamera();
        }

        function desligarCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
            video.srcObject = null;
            preview.classList.remove('active');

            btnStart.disabled = false;
            btnFlip.disabled = true;
            btnStop.disabled = true;
            btnCapture.disabled = true;
            btnCaptureBack.disabled = true;
            btnRepeat.disabled = true;
            statusText.textContent = "";
        }

        function capturarImagem(lado) {
            if (!stream) return;

            canvas.width = video.videoWidth || 1280;
            canvas.height = video.videoHeight || 720;

            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            const processCanvas = document.createElement('canvas');
            const pCtx = processCanvas.getContext('2d');
            
            processCanvas.width = canvas.width;
            processCanvas.height = canvas.height;

            pCtx.filter = 'contrast(160%) grayscale(100%)';
            pCtx.drawImage(canvas, 0, 0);

            processarOCR(processCanvas.toDataURL('image/png'), lado);
        }

        function recomecar() {
            preview.classList.remove('active');
            inputName.value = "";
            inputCompany.value = "";
            inputPhone.value = "";
            inputEmail.value = "";
            statusText.textContent = "";
        }

        async function processarOCR(imageData, lado) {
            statusText.style.color = "#3b82f6";
            statusText.textContent = `⏳ A ler texto da foto (${lado})...`;

            try {
                if (typeof Tesseract === 'undefined') throw new Error("Biblioteca OCR não carregada");

                const worker = await Tesseract.createWorker();
                await worker.loadLanguage('por+eng');
                await worker.initialize('por+eng');

                const { data: { text } } = await worker.recognize(imageData);
                await worker.terminate();

                console.log(`TEXTO DETETADO (${lado}):\n`, text);

                if (!text || text.trim().length === 0) {
                    throw new Error("Sem texto detetado");
                }

                let camposEncontrados = 0;

                // 1. EMAIL (Apenas preenche se encontrar e o campo estiver vazio)
                const emailMatch = text.match(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/gi);
                if (emailMatch) {
                    let email = emailMatch[0].toLowerCase().replace('gerol@', 'geral@').replace('.etpe.', '.etpc.');
                    inputEmail.value = email;
                    camposEncontrados++;
                }

                // 2. TELEFONE (Apenas preenche se encontrar)
                const phoneMatch = text.match(/(?:\+351\s?)?(?:2\d{2}[\s.-]?\d{3}[\s.-]?\d{3}|9[1236]\d{1}[\s.-]?\d{3}[\s.-]?\d{3})/g);
                if (phoneMatch) {
                    let phone = phoneMatch[0].replace(/\s+/g, '');
                    if (phone.startsWith('251')) phone = '231' + phone.slice(3);
                    inputPhone.value = phone;
                    camposEncontrados++;
                }

                // 3. NOME E EMPRESA
                const linhasLimpas = text.split('\n')
                    .map(l => l.trim())
                    .filter(l => {
                        if (l.length < 3) return false;
                        if (/^[€§|!.:;_\-=\s\d]+$/.test(l)) return false;
                        if (/^(2030|pessoas|portugal|união|europeia|cofinanciado|secretaria|estudante|student)/i.test(l)) return false;
                        return true;
                    });

                if (linhasLimpas.length > 0) {
                    // Procura empresa se o campo ainda estiver vazio
                    const idxEmpresa = linhasLimpas.findIndex(l => /(escola|técnica|profissional|cantanhede|etpc|empresa|ltd|sa)/i.test(l));
                    if (idxEmpresa !== -1 && !inputCompany.value) {
                        inputCompany.value = linhasLimpas[idxEmpresa];
                        linhasLimpas.splice(idxEmpresa, 1);
                        camposEncontrados++;
                    }

                    // Procura nome se o campo ainda estiver vazio
                    const linhaNome = linhasLimpas.find(l => /[a-zA-Z]{3,}/.test(l) && !/^(rua|av|avenida|3060)/i.test(l));
                    if (linhaNome && !inputName.value) {
                        inputName.value = linhaNome;
                        camposEncontrados++;
                    }
                }

                statusText.style.color = "#10b981";
                if (camposEncontrados > 0) {
                    statusText.textContent = `✅ Leitura da foto (${lado}) concluída!`;
                } else {
                    statusText.textContent = `ℹ️ Leitura concluída (${lado}), mas nenhum campo novo foi identificado.`;
                }

            } catch (err) {
                console.error("Erro no OCR:", err);
                statusText.style.color = "#ef4444";
                statusText.textContent = "❌ Não foi possível ler texto legível nesta captura.";
            }
        }

        btnStart.addEventListener('click', ligarCamera);
        btnFlip.addEventListener('click', alternarCamera);
        btnStop.addEventListener('click', desligarCamera);
        btnCapture.addEventListener('click', () => capturarImagem('Frente'));
        btnCaptureBack.addEventListener('click', () => capturarImagem('Verso'));
        btnRepeat.addEventListener('click', recomecar);
    });
    </script>
</body>
</html>

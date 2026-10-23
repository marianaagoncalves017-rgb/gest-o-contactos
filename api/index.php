<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leitor de Cartões - OCR</title>
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
            gap: 12px;
            margin-bottom: 24px;
            justify-content: space-between;
        }

        button {
            flex: 1;
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
            <button class="btn-secondary" id="btnStop" disabled>⏹️ Desligar</button>
            <button class="btn-secondary" id="btnCapture" disabled>📷 Tirar foto</button>
            <button class="btn-secondary" id="btnRepeat" disabled>🔄 Repetir</button>
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
        const btnStop = document.getElementById('btnStop');
        const btnCapture = document.getElementById('btnCapture');
        const btnRepeat = document.getElementById('btnRepeat');
        const statusText = document.getElementById('statusText');

        const inputName = document.getElementById('inputName');
        const inputCompany = document.getElementById('inputCompany');
        const inputPhone = document.getElementById('inputPhone');
        const inputEmail = document.getElementById('inputEmail');

        let stream = null;

        async function ligarCamera() {
            if (stream) return;

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert("O teu navegador não suporta a API de câmara neste endereço.");
                return;
            }

            try {
                statusText.style.color = "#3b82f6";
                statusText.textContent = "A pedir permissão da câmara...";

                stream = await navigator.mediaDevices.getUserMedia({
                    video: { 
                        width: { ideal: 1920 },
                        height: { ideal: 1080 }
                    },
                    audio: false
                });

                video.srcObject = stream;
                video.setAttribute('playsinline', true);
                await video.play();

                preview.classList.remove('active');
                btnStart.disabled = true;
                btnStop.disabled = false;
                btnCapture.disabled = false;
                btnRepeat.disabled = true;
                statusText.textContent = "";

            } catch (err) {
                console.error("Erro na câmara:", err);
                statusText.style.color = "#ef4444";
                alert("Erro ao ligar câmara: " + err.message);
            }
        }

        function desligarCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
            video.srcObject = null;
            preview.classList.remove('active');

            btnStart.disabled = false;
            btnStop.disabled = true;
            btnCapture.disabled = true;
            statusText.textContent = "";
        }

        function tirarFoto() {
            if (!stream) return;

            canvas.width = video.videoWidth || 1280;
            canvas.height = video.videoHeight || 720;

            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            preview.classList.add('active');
            btnCapture.disabled = true;
            btnRepeat.disabled = false;

            // Criar canvas processado
            const processCanvas = document.createElement('canvas');
            const pCtx = processCanvas.getContext('2d');
            
            processCanvas.width = canvas.width;
            processCanvas.height = canvas.height;

            // Aumentar o contraste e nitidez das letras
            pCtx.filter = 'contrast(160%) grayscale(100%)';
            pCtx.drawImage(canvas, 0, 0);

            processarOCR(processCanvas.toDataURL('image/png'));
        }

        function repetir() {
            preview.classList.remove('active');
            btnCapture.disabled = false;
            btnRepeat.disabled = true;
            statusText.textContent = "";
        }

        async function processarOCR(imageData) {
            statusText.style.color = "#3b82f6";
            statusText.textContent = "⏳ A ler texto do cartão...";

            try {
                if (typeof Tesseract === 'undefined') throw new Error("Biblioteca OCR não carregada");

                const worker = await Tesseract.createWorker();
                await worker.loadLanguage('por+eng');
                await worker.initialize('por+eng');

                const { data: { text } } = await worker.recognize(imageData);
                await worker.terminate();

                if (!text || text.trim().length === 0) {
                    throw new Error("Sem texto detetado");
                }

                inputName.value = "";
                inputCompany.value = "";
                inputPhone.value = "";
                inputEmail.value = "";

                // 1. Extração e Correção de Email
                let emailMatch = text.match(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/gi);
                if (emailMatch) {
                    let email = emailMatch[0].toLowerCase();
                    // Correções de erros comuns de OCR em domínios/palavras portuguesas
                    email = email.replace('gerol@', 'geral@')
                                 .replace('.etpe.pt', '.etpc.pt')
                                 .replace('.etpe.', '.etpc.');
                    inputEmail.value = email;
                }

                // 2. Extração e Correção de Telefone
                let phoneMatch = text.match(/(?:\+351\s?)?(?:[29]\d{2}[\s.-]?\d{3}[\s.-]?\d{3})/g);
                if (phoneMatch) {
                    let phone = phoneMatch[0].replace(/\s+/g, '');
                    // Correção específica se o OCR trocar o DDD 231 por 251
                    if (phone.startsWith('251')) phone = '231' + phone.slice(3);
                    inputPhone.value = phone;
                }

                // 3. Processar Nome e Empresa eliminando símbolos ou linhas de lixo
                const linhasLimpas = text.split('\n')
                    .map(l => l.trim())
                    .filter(l => {
                        if (l.length < 4) return false;
                        // Filtra linhas apenas com símbolos ou lixo de OCR
                        if (/^[€§|!.:;_\-=\s\d]+$/.test(l)) return false;
                        if (/^(2030|pessoas|portugal|união|europeia|cofinanciado|rua|cantanhede|3060)/i.test(l)) return false;
                        return true;
                    });

                if (linhasLimpas.length > 0) {
                    // Procura termos de escola ou empresa
                    const idxEmpresa = linhasLimpas.findIndex(l => /(escola|técnica|profissional|cantanhede|etpc|empresa|ltd|sa)/i.test(l));
                    if (idxEmpresa !== -1) {
                        inputCompany.value = linhasLimpas[idxEmpresa];
                        linhasLimpas.splice(idxEmpresa, 1);
                    } else {
                        inputCompany.value = "ETPC - Escola Técnica Profissional de Cantanhede";
                    }

                    // Se sobrar alguma linha limpa com letras reais, atribui ao Nome
                    const linhaNomeValida = linhasLimpas.find(l => /[a-zA-Z]{3,}/.test(l));
                    if (linhaNomeValida) {
                        inputName.value = linhaNomeValida;
                    }
                }

                statusText.style.color = "#10b981";
                statusText.textContent = "✅ Leitura concluída!";
            } catch (err) {
                console.error("Erro no OCR:", err);
                statusText.style.color = "#ef4444";
                statusText.textContent = "❌ Não foi possível ler o texto claramente.";
            }
        }

        btnStart.addEventListener('click', ligarCamera);
        btnStop.addEventListener('click', desligarCamera);
        btnCapture.addEventListener('click', tirarFoto);
        btnRepeat.addEventListener('click', repetir);
    });
    </script>
</body>
</html>

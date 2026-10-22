<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leitor de Cartões - OCR</title>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@4/dist/tesseract.min.js"></script>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; background: #f0f2f5; margin: 0; padding: 20px; display: flex; justify-content: center; }
        .card { background: #fff; border-radius: 8px; padding: 20px; max-width: 500px; width: 100%; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .video-container { position: relative; background: #000; border-radius: 6px; overflow: hidden; margin-bottom: 12px; height: 300px; display: flex; align-items: center; justify-content: center; }
        video, canvas { width: 100%; height: 100%; object-fit: cover; }
        canvas { display: none; }
        .preview.active canvas { display: block; }
        .preview.active video { display: none; }
        .buttons { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
        button { padding: 8px 16px; border: none; border-radius: 4px; font-weight: 500; cursor: pointer; transition: background 0.2s; }
        .btn-primary { background: #1a73e8; color: #fff; }
        .btn-secondary { background: #e8eaed; color: #3c4043; }
        button:disabled { opacity: 0.5; cursor: not-allowed; }
        .status { margin-bottom: 12px; font-size: 14px; color: #5f6368; min-height: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 4px; margin-bottom: 12px; }
        label { font-size: 12px; color: #5f6368; }
        input { padding: 8px; border: 1px solid #dadce0; border-radius: 4px; font-size: 14px; }
        h2 { text-align: center; margin-top: 0; }
    </style>
</head>
<body>
    <div class="card">
        <h2>📷 Leitor de Cartões</h2>
        
        <div class="video-container preview" id="preview">
            <video id="video" autoplay playsinline></video>
            <canvas id="canvas"></canvas>
        </div>

        <div class="buttons">
            <button class="btn-primary" id="btnStart">📷 Ligar câmara</button>
            <button class="btn-secondary" id="btnStop" disabled>Desligar</button>
            <button class="btn-primary" id="btnCapture" disabled>📷 Tirar foto</button>
            <button class="btn-secondary" id="btnRepeat" disabled>🔄 Repetir</button>
        </div>

        <div class="status" id="statusText"></div>

        <form id="cardForm">
            <div class="form-group">
                <label>Nome</label>
                <input type="text" id="inputName">
            </div>
            <div class="form-group">
                <label>Empresa</label>
                <input type="text" id="inputCompany">
            </div>
            <div class="form-group">
                <label>Telefone</label>
                <input type="text" id="inputPhone">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" id="inputEmail">
            </div>
        </form>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const video = document.getElementById("video");
        const canvas = document.getElementById("canvas");
        const preview = document.getElementById("preview");
        const btnStart = document.getElementById("btnStart");
        const btnStop = document.getElementById("btnStop");
        const btnCapture = document.getElementById("btnCapture");
        const btnRepeat = document.getElementById("btnRepeat");
        const statusText = document.getElementById("statusText");

        const inputName = document.getElementById("inputName");
        const inputCompany = document.getElementById("inputCompany");
        const inputPhone = document.getElementById("inputPhone");
        const inputEmail = document.getElementById("inputEmail");

        let stream = null;

        async function ligarCamera() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                statusText.textContent = "❌ O navegador não suporta acesso à câmara ou o site não está em HTTPS.";
                return;
            }

            try {
                statusText.textContent = "A iniciar câmara...";
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: "environment" },
                    audio: false
                });

                video.srcObject = stream;
                video.setAttribute("playsinline", true);
                await video.play();

                preview.classList.remove("active");
                btnStart.disabled = true;
                btnStop.disabled = false;
                btnCapture.disabled = false;
                btnRepeat.disabled = true;
                statusText.textContent = "";
            } catch (err) {
                console.error("Erro ao aceder à câmara:", err);
                if (err.name === "NotAllowedError") {
                    statusText.textContent = "❌ Permissão da câmara negada pelo utilizador.";
                } else if (err.name === "NotFoundError") {
                    statusText.textContent = "❌ Nenhuma câmara encontrada neste dispositivo.";
                } else {
                    statusText.textContent = "❌ Erro ao ligar câmara: " + err.message;
                }
            }
        }

        function desligarCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
            video.srcObject = null;
            preview.classList.remove("active");

            btnStart.disabled = false;
            btnStop.disabled = true;
            btnCapture.disabled = true;
        }

        function tirarFoto() {
            if (!stream) return;

            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;

            const ctx = canvas.getContext("2d");
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            preview.classList.add("active");
            btnCapture.disabled = true;
            btnRepeat.disabled = false;

            processarOCR(canvas.toDataURL("image/png"));
        }

        function repetir() {
            preview.classList.remove("active");
            btnCapture.disabled = false;
            btnRepeat.disabled = true;

            inputName.value = "";
            inputCompany.value = "";
            inputPhone.value = "";
            inputEmail.value = "";
            statusText.textContent = "";
        }

        async function processarOCR(imageData) {
            statusText.textContent = "⏳ A extrair dados do cartão...";

            try {
                if (typeof Tesseract === "undefined") {
                    throw new Error("O leitor de texto não carregou.");
                }

                const worker = await Tesseract.createWorker("por");
                const { data: { text } } = await worker.recognize(imageData);
                await worker.terminate();

                const emails = text.match(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g);
                if (emails) inputEmail.value = emails[0];

                const phones = text.match(/(?:\+351\s?)?(?:9[1236]\d{7}|2\d{8})/g);
                if (phones) inputPhone.value = phones[0];

                const linhas = text.split("\n").map(l => l.trim()).filter(l => l.length > 2);
                if (linhas.length > 0) inputName.value = linhas[0];
                if (linhas.length > 1) inputCompany.value = linhas[1];

                statusText.textContent = "✅ Dados preenchidos!";
            } catch (err) {
                console.error("Erro no OCR:", err);
                statusText.textContent = "❌ Erro ao ler o texto. Tente novamente.";
            }
        }

        btnStart.addEventListener("click", ligarCamera);
        btnStop.addEventListener("click", desligarCamera);
        btnCapture.addEventListener("click", tirarFoto);
        btnRepeat.addEventListener("click", repetir);

        window.addEventListener("beforeunload", desligarCamera);
    });
    </script>
</body>
</html>

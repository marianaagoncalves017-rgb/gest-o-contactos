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

    <!-- Script separado e carregado com defer -->
    <script src="app.js" defer></script>
</body>
</html>

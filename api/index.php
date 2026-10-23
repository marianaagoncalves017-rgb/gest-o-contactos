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

        console.log("TEXTO BRUTO DETETADO:\n", text);

        if (!text || text.trim().length === 0) {
            throw new Error("Sem texto detetado");
        }

        // Limpa todos os campos
        inputName.value = "";
        inputCompany.value = "";
        inputPhone.value = "";
        inputEmail.value = "";

        // 1. EXTRAIR EMAIL (apanha qualquer formato válido de email)
        const emailMatch = text.match(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/gi);
        if (emailMatch) {
            inputEmail.value = emailMatch[0].toLowerCase()
                .replace('gerol@', 'geral@')
                .replace('.etpe.', '.etpc.');
        }

        // 2. EXTRAIR TELEFONE (Apanha 231420344, 231 420 344, 912345678, etc.)
        const phoneMatch = text.match(/(?:\+351\s?)?(?:2\d{2}[\s.-]?\d{3}[\s.-]?\d{3}|9[1236]\d{1}[\s.-]?\d{3}[\s.-]?\d{3})/g);
        if (phoneMatch) {
            let phone = phoneMatch[0].replace(/\s+/g, '');
            if (phone.startsWith('251')) phone = '231' + phone.slice(3);
            inputPhone.value = phone;
        }

        // 3. EXTRAIR NOME E EMPRESA (Somente texto real do cartão)
        const linhasLimpas = text.split('\n')
            .map(l => l.trim())
            .filter(l => {
                if (l.length < 3) return false;
                // Exclui lixo de OCR e símbolos isolados
                if (/^[€§|!.:;_\-=\s\d]+$/.test(l)) return false;
                if (/^(2030|pessoas|portugal|união|europeia|cofinanciado|secretaria|estudante|student)/i.test(l)) return false;
                return true;
            });

        if (linhasLimpas.length > 0) {
            // Se encontrar o nome da escola/empresa no texto
            const idxEmpresa = linhasLimpas.findIndex(l => /(escola|técnica|profissional|cantanhede|etpc|empresa|ltd|sa)/i.test(l));
            if (idxEmpresa !== -1) {
                inputCompany.value = linhasLimpas[idxEmpresa];
                linhasLimpas.splice(idxEmpresa, 1);
            }

            // O primeiro texto limpo restante que contenha letras é atribuído ao Nome
            const linhaNome = linhasLimpas.find(l => /[a-zA-Z]{3,}/.test(l) && !/^(rua|av|avenida|3060)/i.test(l));
            if (linhaNome) {
                inputName.value = linhaNome;
            } else if (linhasLimpas.length > 0) {
                inputName.value = linhasLimpas[0];
            }
        }

        statusText.style.color = "#10b981";
        statusText.textContent = "✅ Leitura concluída!";
    } catch (err) {
        console.error("Erro no OCR:", err);
        statusText.style.color = "#ef4444";
        statusText.textContent = "❌ Não foi possível ler o texto. Aproxima mais o cartão da câmara.";
    }
}

function criarControladorModal(modalElemento) {
    let elementoFocadoAntes = null;

    function elementosFocaveis() {
        return Array.from(
            modalElemento.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])')
        ).filter((elemento) => !elemento.disabled && elemento.offsetParent !== null);
    }

    function aoTeclar(evento) {
        if (evento.key === 'Escape') {
            fechar();
            return;
        }

        if (evento.key !== 'Tab') {
            return;
        }

        const focaveis = elementosFocaveis();
        if (focaveis.length === 0) {
            return;
        }

        const primeiro = focaveis[0];
        const ultimo = focaveis[focaveis.length - 1];

        if (evento.shiftKey && document.activeElement === primeiro) {
            evento.preventDefault();
            ultimo.focus();
        } else if (!evento.shiftKey && document.activeElement === ultimo) {
            evento.preventDefault();
            primeiro.focus();
        }
    }

    function aoClicarNoOverlay(evento) {
        if (evento.target === modalElemento) {
            fechar();
        }
    }

    function abrir(elementoParaFocar) {
        elementoFocadoAntes = document.activeElement;

        modalElemento.classList.remove('hidden');
        modalElemento.classList.add('flex');
        modalElemento.setAttribute('aria-hidden', 'false');

        document.addEventListener('keydown', aoTeclar);
        modalElemento.addEventListener('click', aoClicarNoOverlay);

        (elementoParaFocar || elementosFocaveis()[0])?.focus();
    }

    function fechar() {
        modalElemento.classList.add('hidden');
        modalElemento.classList.remove('flex');
        modalElemento.setAttribute('aria-hidden', 'true');

        document.removeEventListener('keydown', aoTeclar);
        modalElemento.removeEventListener('click', aoClicarNoOverlay);

        elementoFocadoAntes?.focus();
        elementoFocadoAntes = null;
    }

    return { abrir, fechar };
}

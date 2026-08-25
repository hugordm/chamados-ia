document.addEventListener('DOMContentLoaded', () => {
    const formStatus = document.getElementById('form-status');
    const modalConfirmarStatus = document.getElementById('modal-confirmar-status');

    if (formStatus && modalConfirmarStatus) {
        const controladorModal = criarControladorModal(modalConfirmarStatus);
        const botaoCancelar = document.getElementById('modal-confirmar-status-cancelar');
        const botaoConfirmar = document.getElementById('modal-confirmar-status-confirmar');
        const valorStatus = document.getElementById('modal-confirmar-status-valor');

        formStatus.addEventListener('submit', (evento) => {
            evento.preventDefault();
            valorStatus.textContent = formStatus.querySelector('#status').value;
            controladorModal.abrir(botaoCancelar);
        });

        botaoCancelar.addEventListener('click', () => controladorModal.fechar());

        botaoConfirmar.addEventListener('click', () => {
            controladorModal.fechar();
            formStatus.submit();
        });
    }

    const formNovoChamado = document.getElementById('form-novo-chamado');
    if (formNovoChamado) {
        formNovoChamado.addEventListener('submit', () => {
            const botao = document.getElementById('btn-enviar');
            botao.disabled = true;
            botao.textContent = 'Analisando com IA...';
        });
    }

    document.querySelectorAll('[data-toggle-senha]').forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            document.querySelectorAll(checkbox.dataset.toggleSenha).forEach((campo) => {
                campo.type = checkbox.checked ? 'text' : 'password';
            });
        });
    });

    document.querySelectorAll('.js-toggle-senha-olho').forEach((botao) => {
        const campo = document.querySelector(botao.dataset.alvo);
        const iconeMostrar = botao.querySelector('.icone-olho-mostrar');
        const iconeOcultar = botao.querySelector('.icone-olho-ocultar');

        if (!campo || !iconeMostrar || !iconeOcultar) {
            return;
        }

        botao.addEventListener('click', () => {
            const vaiMostrar = campo.type === 'password';
            campo.type = vaiMostrar ? 'text' : 'password';
            botao.setAttribute('aria-label', vaiMostrar ? 'Ocultar senha' : 'Mostrar senha');
            iconeMostrar.classList.toggle('hidden', vaiMostrar);
            iconeOcultar.classList.toggle('hidden', !vaiMostrar);
        });
    });
});

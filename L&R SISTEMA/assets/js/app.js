document.addEventListener('DOMContentLoaded', function () {
    var sidebar = document.getElementById('sidebar');
    var toggle = document.getElementById('sidebarToggle');

    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('recolhida');
        });
    }

    // Confirmação simples para ações destrutivas (exclusões).
    document.querySelectorAll('[data-confirmar]').forEach(function (elemento) {
        elemento.addEventListener('click', function (evento) {
            var mensagem = elemento.getAttribute('data-confirmar') || 'Confirma esta ação?';
            if (!window.confirm(mensagem)) {
                evento.preventDefault();
            }
        });
    });
});

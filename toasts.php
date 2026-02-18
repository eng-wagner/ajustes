<style>
    /* Animação personalizada: Deslizar da direita para a esquerda */
    .deslizar-direita-esquerda {
        animation: slideInRight 0.5s cubic-bezier(0.25, 0.8, 0.25, 1) forwards;
    }

    @keyframes slideInRight {
        0% {
            transform: translateX(100%); /* Começa fora da tela (à direita) */
            opacity: 0;
        }
        100% {
            transform: translateX(0); /* Termina na posição original */
            opacity: 1;
        }
    }
</style>

<div class="toast-container position-fixed top-0 end-0 mt-4 me-4" style="z-index: 9999;">

    <?php if (isset($_SESSION['toast_erro'])): ?>
        <div class="toast align-items-center text-bg-danger border-0 deslizar-direita-esquerda mb-2" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body fs-6 fw-medium">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $_SESSION['toast_erro']; ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fechar"></button>
            </div>
        </div>
        <?php unset($_SESSION['toast_erro']); // Destrói a mensagem após exibir ?>
    <?php endif; ?>


    <?php if (isset($_SESSION['toast_sucesso'])): ?>
        <div class="toast align-items-center text-bg-success border-0 deslizar-direita-esquerda mb-2" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body fs-6 fw-medium">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= $_SESSION['toast_sucesso']; ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fechar"></button>
            </div>
        </div>
        <?php unset($_SESSION['toast_sucesso']); // Destrói a mensagem após exibir ?>
    <?php endif; ?>

</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Pega todos os elementos que tem a classe .toast
        var toastElList = [].slice.call(document.querySelectorAll('.toast'));
        
        // Inicia cada um deles (com delay de 3 segundos para sumir sozinho)
        var toastList = toastElList.map(function (toastEl) {
            return new bootstrap.Toast(toastEl, { delay: 3000 });
        });
        
        // Mostra na tela
        toastList.forEach(toast => toast.show());
    });
</script>
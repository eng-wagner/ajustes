<?php if (isset($_SESSION['toast_sucesso']) || isset($_SESSION['toast_erro'])): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            
            // 1. Cria a configuração padrão do Toast do SweetAlert2
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            // 2. Verifica se existe sucesso na sessão
            <?php if (isset($_SESSION['toast_sucesso'])): ?>
                Toast.fire({
                    icon: 'success',
                    title: '<?= addslashes($_SESSION['toast_sucesso']); ?>'
                });
                <?php unset($_SESSION['toast_sucesso']); // Limpa a sessão ?>
            <?php endif; ?>

            // 3. Verifica se existe erro na sessão
            <?php if (isset($_SESSION['toast_erro'])): ?>
                Toast.fire({
                    icon: 'error',
                    title: '<?= addslashes($_SESSION['toast_erro']); ?>'
                });
                <?php unset($_SESSION['toast_erro']); // Limpa a sessão ?>
            <?php endif; ?>
            
        });
    </script>
<?php endif; ?>
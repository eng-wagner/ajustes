<aside id="sidebar">
    <?php
    $currentPage = basename($_SERVER['PHP_SELF']);
    ?>
    <div class="d-flex">
        <button class="toggle-btn" type="button">
            <i class="lni lni-menu"></i>                    
        </button>
        <div class="sidebar-logo">
            <a href="#"><span class="welcome">Bem Vindo,</span><br><?= $firstName?></a>
        </div>                
    </div>
    <ul class="sidebar-nav">
        <li class="sidebar-item">
            <a href="dashboard.php" class="sidebar-link">
                <i class="lni lni-dashboard"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <?php
        if(isset($currentPage) && $currentPage == "usuarios.php")
        {
            ?>
            <li class="sidebar-item">
                <a href="?new=true" class="sidebar-link">
                    <i class="lni lni-user"></i>
                    <span>Novo Usuário</span>
                </a>
            </li>
            <?php
        }
        if(isset($currentPage) && $currentPage == "instituicoes.php")
        {
            ?>
            <li class="sidebar-item">
                <a href="?new=true" class="sidebar-link">
                    <i class="lni lni-apartment"></i>
                    <span>Nova Instituição</span>
                </a>
            </li>
            <?php
        }
        if(isset($currentPage) && $currentPage == "contabilidades.php")
        {
            ?>
            <li class="sidebar-item">
                <a href="?new=true" class="sidebar-link">
                    <i class="lni lni-book"></i>
                    <span>Nova Contabilidade</span>
                </a>
            </li>
            <?php
        }
        if(isset($currentPage) && $currentPage == "gerenciarcota.php" || $currentPage == "gerenciarDocsPend.php")
        {
            ?>
            <li class="sidebar-item">
                <a href="?new=true" class="sidebar-link">
                    <i class="lni lni-notepad"></i>
                    <span>Novo Documento</span>
                </a>
            </li>
            <?php
        }
        ?>
        <li class="sidebar-item">
            <a href="buscar.php" class="sidebar-link">
                <i class="lni lni-search"></i>
                <span>Buscar Processos</span>
            </a>
        </li>
        <?php
            if ($perfil != "ofc") {
            ?>                
            <li class="sidebar-item">
                <a href="?pddeAE=true" class="sidebar-link">
                    <i class="lni lni-check-box"></i>
                    <span>Prestação de Contas PDDE</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="?pddeAF=true" class="sidebar-link">
                    <i class="lni lni-investment"></i>
                    <span>Análise Financeira</span>
                </a>
            </li>
        <?php
            }
            if ($perfil != "ofp") {
            ?>
            <li class="sidebar-item">
                <a href="?analiseTC=true" class="sidebar-link">
                    <i class="lni lni-check-box"></i>
                    <span>Prestação de Contas TC</span>
                </a>
            </li>
        <?php
            }
        ?>
        <li class="sidebar-item">
            <a href="#" class="sidebar-link has-dropdown collapsed" data-bs-toggle="collapse" data-bs-target="#relatorios" aria-expanded="false" aria-controls="relatorios">
                <i class="lni lni-layout"></i>
                <span>Relatórios</span>
            </a>
            <ul id="relatorios" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                <?php
                if ($perfil != "ofc") {
                    ?>
                                            
                <li class="sidebar-item">
                    <a href="relatorio.php?Prg=0&St=0" class="sidebar-link">Prestação de Contas</a>
                </li>
                <li class="sidebar-item">
                    <a href="pendencia.php?Prg=0&Reg=2&user=99" class="sidebar-link">Pendências PDDE</a>
                </li>
                <?php
                }
                if ($perfil != "ofp") {
                ?>
                <li class="sidebar-item">
                    <a href="pendenciaTc.php?Reg=2&user=99" class="sidebar-link">Pendências TC</a>
                </li>
                <?php
                }                   
                ?>
            </ul>                    
        </li>
        <li class="sidebar-item">
            <a href="gerarcota.php" class="sidebar-link">
                <i class="lni lni-pencil-alt"></i>
                <span>Gerar Cota</span>
            </a>
        </li>
        <?php
        if ($perfil == "adm") {
            echo '<li class="sidebar-item"><a href="gerenciamento.php" class="sidebar-link"><i class="lni lni-cog"></i><span>Gerenciar</span></a></li>';
        }
        ?>
    </ul>
    <div class="sidebar-footer">
        <a href="#" data-bs-toggle="modal" data-bs-target="#logoffModal" class="sidebar-link">
            <i class="lni lni-exit"></i>
            <span>Sair</span>
        </a>
    </div>
</aside>
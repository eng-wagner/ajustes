<?php
ob_start();
session_start();

require __DIR__ . "/source/autoload.php";
use Source\Database\Connect;

$timezone = new DateTimeZone("America/Sao_Paulo");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik+Doodle+Shadow&display=swap" rel="stylesheet">
    <title>E-mail</title>
    <style>
        h1{
            font-family: 'Rubik Doodle Shadow', system-ui;
            font-size: 56px;
        }
    </style>
</head>
<body>
    <div class="container-lg">
        <h1 class="text-center">E-mail</h1>
    </div>
          
    <div class="container-lg">
        <?php
         $sql = Connect::getInstance()->prepare("SELECT i.instituicao, i.email, p.tipo, c.c_email FROM processos p JOIN instituicoes i ON p.instituicao_id = i.id JOIN contabilidades c 
         ON c.id = i.cont_id WHERE p.id = :idProc");
                 
        $sql->bindParam('idProc',$_GET['idProc']);
        if($sql->execute())
        {
            if($proc = $sql->fetch()){
                $instituicao = $proc->instituicao;
                $iEmail = $proc->email;          
                $tipo = $proc->tipo;
                $cEmail = $proc->c_email;
            }
        }

        if(substr($instituicao, 0, 3) == "APM")
        {
            $apm = true;
        } 
        else
        {
            $apm = false;
        }
        
        ?>

        <p><b>Para:</b> <a href="mailto:<?= $iEmail; ?>"><?= $iEmail; ?></a></p>
        <p><b>CC:</b> <?= $cEmail; ?></p>
        <p><b>Assunto:</b> Prestação de Contas - <?= $tipo; ?> - <?= $instituicao?></p>
        <hr>
        <?php
        if($apm == true){

        ?>
        <p><b>Prezado(a) Diretor(a) Executivo(a) da Associação de Pais e Mestres (APM) e <br/>
            Diretor(a) Escolar / Responsável pela Direção</b></p>
        <?php
        }
        else
        {
            ?>
            <p><b>Prezado(a) Presidente / Responsável legal</b></p>
            <?php
        }
        ?>
        <p style="text-indent: 3em;">Procedemos a conferência da prestação de contas do <b><u>1º Quadrimestre do Exercício de 2025</u></b> e constatamos a existência das pendências 
            relacionadas no quadro abaixo.
        </p>
        
        <p style="text-indent: 3em;">De acordo com o Decreto nº 20.113/2017, em seu Art. 59, §4º e §5º, as referidas pendências devem ser regularizadas <b>em até 48h</b>, no entanto considerando as demandas escolares, <b>estendemos
            o prazo</b> para apresentação das regularizações para <span style="background-color: yellow;"><b>4 dias úteis</b></span>.
        </p>

        <p style="text-indent: 3em;">Lembramos que os arquivos compartilhados no Google Drive devem estar no formato PDF pesquisável legível, conforme padrão estabelecido <b>no Comunicado Nº 04/2025 - SE-33</b>.
        </p>

        <p style="text-indent: 3em;">Informamos que o envio de documentos para complementação ou regularização deverá ser feito em resposta a esse e-mail <b>além de compartilhados no Google Drive</b> na pasta PENDÊNCIAS, com digitalização fiel aos documentos originais, colorido e legível de acordo com as orientações do Manual de Gestão e o padrão estabelecido pelo Comunicado.
        </p>

        <table style="border-collapse: collapse;">
            <thead>
                <tr style="background-color: #bebebe; text-align: center;">
                    <th style="border: 1px solid; width: 100px;">Item do Demonstrativo</th>
                    <th style="border: 1px solid; width: 180px;">Documento</th>
                    <th style="border: 1px solid; width: 180px;">Nº Doc</th>
                    <th style="border: 1px solid; width: 180px;">Data de Emissão</th>
                    <th style="border: 1px solid; width: 180px;">Favorecido</th>
                    <th style="border: 1px solid; width: 180px;">Valor</th>                    
                    <th style="border: 1px solid; width: 250px;">Pendência</th>
                    <th style="border: 1px solid; width: 250px;">Providências</th>                    
                    <th style="border: 1px solid; width: 180px;">Glosa</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = Connect::getInstance()->prepare("SELECT p.itemDRD, d.documento, p.numDocPend, p.dataDocPend, p.fornecedor, p.valorDoc, t.pendencia, p.providencias, 
                p.glosaDoc, p.etapa_id, p.resolvido, p.ativado FROM pendencias_tc24 p JOIN tipo_documento d ON p.docPend_id = d.id JOIN tipo_pendencia t ON p.pend_id = t.id WHERE p.proc_id = :idProc");                                                    
                $sql->bindParam('idProc',$_GET['idProc']);
                if($sql->execute())
                {
                    while($proc = $sql->fetch())
                    {                        
                        $itemDRD = $proc->itemDRD;
                        $documento = $proc->documento;        
                        $numDocPend = $proc->numDocPend;
                        $dataDocPend = $proc->dataDocPend;
                        $fornecedor = $proc->fornecedor;
                        $valorDoc = $proc->valorDoc;
                        $pendencia = $proc->pendencia;
                        $providencias = $proc->providencias;
                        $valorGlosa = $proc->glosaDoc;
                        $etapaId = $proc->etapa_id;
                        $checkResolved = $proc->resolvido;
                        $ativado = $proc->ativado;
                        
                        $dataDocPend = new DateTime($dataDocPend,$timezone);
                        $dataDocPend = $dataDocPend->format('d/m/Y');                        

                        if($checkResolved == 0 && $ativado == 1)
                        {
                            echo '<tr>';                            
                            echo '<td style="border: 1px solid; text-align: center;">' . $itemDRD . '</td>';
                            echo '<td style="border: 1px solid;">' . $documento . '</td>';
                            echo '<td style="border: 1px solid; text-align: center;">' . $numDocPend . '</td>';
                            echo '<td style="border: 1px solid; text-align: center;">' . $dataDocPend . '</td>';
                            echo '<td style="border: 1px solid;">' . $fornecedor . '</td>';
                            echo '<td style="border: 1px solid;">R$ ' . number_format($valorDoc, 2, ",", ".") . '</td>';                            
                            echo '<td style="border: 1px solid;">' . $pendencia . '</td>';
                            echo '<td style="border: 1px solid;">' . $providencias . '</td>';
                            echo '<td style="border: 1px solid;">R$ ' . number_format($valorGlosa, 2, ",", ".") . '</td>';
                            echo '</tr>';                                            
                        }
                        else
                        {
                            
                        }                        
                    }
                }
                ?>
            </tbody>
        </table>              
        <br />
        <?php
        if($apm == true){
            $signatario = "Diretor(a) Executivo(a) da Associação de Pais e Mestres (APM) e Diretor(a) Escolar / Responsável pela Direção ou Contador(a)";
        }
        else
        {
            $signatario = "Presidente da Instituição, Procurador, Membros do Conselho Fiscal ou Contador(a)";
        }
        ?>
        <p style="text-indent: 3em;">Ademais, reiteramos que, <b>todos os documentos que necessitarem de assinatura, seja do <?= $signatario ?></b>, deverão ser assinados de forma eletrônica, 
            seguindo os procedimentos já adotados na elaboração do Plano de Trabalho e Aditamento 2025.
        </p>

        <p style="text-indent: 3em;">Orientamos que nos próximos quadrimestres a Entidade se atente a ordem cronológica da data de emissão das despesas e ao lançamento no quadrimestre ao qual cada despesa compete, como segue nos termos do Decreto 20.113/2017.</p>

        <p style="text-indent: 3em;">Ressaltamos que após a regularização das pendências, a referida prestação de contas será encaminhada para análise financeira da Secretaria da Fazenda (SEFAZ), por meio da qual outras ocorrências poderão ser, eventualmente, identificadas.</p>

        <p style="text-indent: 3em;"><b><i>Obséquio confirmar o recebimento desta mensagem.</i></b></p>
        
        <br />

    </div>    

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js" integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+" crossorigin="anonymous"></script>
</body>
</html>
<?php
ob_flush();
?>
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
        
        ?>

        <p><b>Para:</b> <a href="mailto:<?= $iEmail; ?>"><?= $iEmail; ?></a></p>
        <p><b>CC:</b> <?= $cEmail; ?></p>
        <p><b>Assunto:</b> Prestação de Contas <?= $tipo; ?> - <?= $instituicao?></p>
        <hr>
        <p><b>Prezado(a) Diretor(a) Executivo(a) da Associação de Pais e Mestres (APM) e <br/>
            Diretor(a) Escolar / Responsável pela Direção</b></p>
        
        <p style="text-indent: 3em;">Procedemos a conferência da <b>prestação de contas do exercício de 2024 do programa <u><span style="background-color: yellow;"><?= $tipo; ?></span></u></b> e informamos a existência das pendências 
            relacionadas abaixo, as quais deverão ser regularizadas <b>em até 7 dias corridos</b>.
        </p>
        
        <p style="text-indent: 3em;">Lembramos que os arquivos compartilhados no Google Drive devem estar no formato PDF pesquisável legível conforme padrão estabelecido no 
            Comunicado nº 15/2024 - SE-33, anexo à Rede nº 473/2024 - SE.
        </p>

        <table style="border-collapse: collapse;">
            <thead>
                <tr style="background-color: #bebebe; text-align: center;">
                    <th style="border: 1px solid; width: 100px;">Item do Demonstrativo</th>
                    <th style="border: 1px solid; width: 180px;">Documento</th>
                    <th style="border: 1px solid; width: 180px;">Favorecido</th>
                    <th style="border: 1px solid; width: 100px;">Nº do Documento</th>
                    <th style="border: 1px solid; width: 100px;">Data do Documento</th>
                    <th style="border: 1px solid; width: 250px;">Pendência</th>
                    <th style="border: 1px solid; width: 250px;">Providências</th>                    
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = Connect::getInstance()->prepare("SELECT p.itemDRD, d.documento, p.favorecido, p.numDocPend, p.dataDocPend, t.pendencia, p.providencias, p.etapa_id, p.resolvido, p.ativado 
                FROM pendencias_24 p JOIN tipo_documento d ON p.docPend_id = d.id JOIN tipo_pendencia t ON p.pend_id = t.id WHERE p.proc_id = :idProc");                                                    
                $sql->bindParam('idProc',$_GET['idProc']);
                if($sql->execute())
                {
                    while($proc = $sql->fetch())
                    {                        
                        $itemDRD = $proc->itemDRD;
                        $documento = $proc->documento;        
                        $favorecido = $proc->favorecido;
                        $numDocPend = $proc->numDocPend;
                        $dataDocPend = $proc->dataDocPend;
                        $pendencia = $proc->pendencia;
                        $providencias = $proc->providencias;
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
                            echo '<td style="border: 1px solid;">' . $favorecido . '</td>';
                            echo '<td style="border: 1px solid; text-align: center;">' . $numDocPend . '</td>';
                            echo '<td style="border: 1px solid; text-align: center;">' . $dataDocPend . '</td>';
                            echo '<td style="border: 1px solid;">' . $pendencia . '</td>';
                            echo '<td style="border: 1px solid;">' . $providencias . '</td>';                            
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
        <p style="text-indent: 3em;"><b><span style="background-color: yellow;">Conforme orientação do Comunicado Nº 15/2024 - SE-33 a digitalização dos documentos fiscais devem ser fiel a via original (colorida) de acordo com as 
            orientações do Anexo I desse mesmo comunicado.</span></b>
        </p>

        <p style="text-indent: 3em;">Por gentileza, substituir APENAS os documentos acima na pasta da Prestação de Contas do PDDE, no link indicado na Rede nº 473/2024 e Comunicado nº 15/2024.</p>

        <p style="text-indent: 3em;">Informamos que <b>após</b> a regularização, a referida prestação de contas passará pela análise sob aspecto financeiro. <b><u>Por meio dessa análise, outras ocorrências poderão ser eventualmente identificadas</u></b>.</p>

        <p style="text-indent: 3em;"><span style="background-color: yellow;">Solicitamos encaminhamento aos demais dirigentes para ciência.</span></p>
        
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
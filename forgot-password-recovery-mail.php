<?php

use PHPMailer\PHPMailer\PHPMailer;

// if(!class_exists('PHPMailer')) {
//     require('phpmailer/class.phpmailer.php');
// 	require('phpmailer/class.smtp.php');
// }

require_once 'include/Constants.php';

$mail = new PHPMailer();

// $emailBody = "<div>,<br><br><p>Click this link to recover your password<br><a href='" . PROJECT_HOME . "reset_password.php?api=" . $api . "'> CLICK AQUI </a><br><br></p>Regards,<br> Admin.</div>";

$emailBody = "<table border='0' cellpadding='0' cellspacing='0' style='  border: none; background-color: #fff; border-collapse: collapse; font-family: roboto;' width='100%'>
                <tr> 
                <th style='background-color:#00BFFF; color:white; padding: 15px;'>
                    <a  href='https://notepic.com.br/'><img src='https://notepic.com.br/img/logo1.png' width='auto' height='70px' /></a>
                </th>
                </tr>
                <tr>
                <td style='color:#000; font-size: 1.5em; text-align: center; padding: 15px;'>
                    <a style='font-weight: bold;'>Recuperação de senha</a>
                </td>
                </tr>
                <tr>
                <td style='box-shadow: 2px 2px 2px rgba(0,0,0,0.1); text-align:center;background-color:#fff; color:#777777;'>
                    <p style='margin:30px; padding:10px;'>
                    Olá, <span style='font-weight: bold;'>$usuario</span>!<br><br>
                    Você pode criar uma nova senha para a sua conta notepic clicando no botão abaixo. Preencha os campos para recuperar seu acesso e voltar a se dar bem nos estudos!
                    <br><br>
                    Abraços,<br>
                    <span style='font-weight: bold;'>Equipe Notepic.</span>
                    <br><br><br>
                        <a href='" . PROJECT_HOME . "reset_password.php?api=" . $api . "'>
                        <button align='center' style='box-shadow: 2px 2px 2px rgba(0,0,0,0.1);border: none; border-radius: 2px; color:#fff; font-weight: bold; font-size:1em; background-color:#00BFFF; padding:10px; padding-left:15px; padding-right:15px;'>Criar nova senha</button>
                        </a>
                    </p>
                </td>
                </tr>      
                <tr style='  background-color:#f7f7f7;'>
                <td style='text-align: justify-all; padding-top: 20px; padding-bottom: 5px; font-size: 0.8em;'>
                    <p style='margin-top:30px; color:#000;'>Não responda a esta mensagem. Este e-mail foi enviado a você por um sistema automático que não processa respostas para auxiliar na recuperação da senha da sua conta no notepic. Para obter mais ajuda, acesse o nosso site e vá até o <a style='text-decoration: none;' href='https://notepic.com.br/'>contato</a> ou envie um e-mail para <a style='font-weight: bold;'>suporte@notepic.com.br</a>. Iremos te ajudar com o que for necessário.</p>
                </td>
                </tr>
                <tr style='  background-color:#f7f7f7;'>
                    <td style='padding-top: 10px; padding-bottom: 5px;'>
                    <hr style='height: 1px; border: 0px;border-top: 0.5px solid #d3d3d3;background-color: #dddddd;' width='85%'>
                    </td>
                </tr>
                <tr style='  background-color:#f7f7f7;'>
                <td style='text-align: center; padding-bottom: 15px;'>
                    <a style='color:#000; text-decoration: none;' href='https://notepic.com.br/'>Notepic. | www.notepic.com.br</a>
                </td>
                </tr>
            </table>";

//Set who the message is to be sent from
$mail->setFrom("no-reply@notepic.com.br", 'Notepic');
//Set who the message is to be sent to
$mail->addAddress($email);
//Set the subject line
$mail->Subject = "Redenifição de senha Notepic";
//Read an HTML message body from an external file, convert referenced images to embedded,
//convert HTML into a basic plain-text alternative body
$mail->MsgHTML($emailBody);
//Replace the plain text body with one created manually
$mail->IsHTML(true);
$mail->CharSet = 'UTF-8';

$isSendEmail = $mail->Send();

?>

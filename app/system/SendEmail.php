<?php

namespace bng\System;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class SendEmail
{
    public function sendEmail($subject, $body, $data)
    {
        $mail = new PHPMailer(true);

        try {
            //Server settings
            //$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = MAIL_SMTP;                              //Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $mail->Username   = MAIL_USERNAME;                          //SMTP username
            $mail->Password   = MAIL_PASSWORD;                          //SMTP password
            $mail->SMTPSecure =  PHPMailer::ENCRYPTION_STARTTLS;            //Enable implicit TLS encryption
            $mail->Port       = 587;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            //Recipients
            $mail->setFrom(MAIL_USERNAME);    //atention
            $mail->addAddress($data['to']);     //Add a recipient

            //Content
            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body    = $this->{$body}($data);

            $mail->send();

            return ['status' => 'Message has been sent'];
        } catch (Exception $e) {
            return ['status' => 'error',
                    "message" => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"
            ];
        }
    }

    private function emailBodyNewAgent($data)
    {
        $link = $data['link'];
        $html = "<p> Para concluir o registro, acesse o link:<p>";
        $html .= "<a href='". $link ."'>Concluir registro<a/>";
        return $html;
    }

    private function codeRecoverPassword($data)
    {
        $code = $data['code'];
        $html = "<p> Código para redefinição de senha:<p>";
        $html .= "<h3>{$code}<a/>";
        return $html;
    }
}
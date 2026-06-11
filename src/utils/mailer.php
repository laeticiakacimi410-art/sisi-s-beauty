<?php


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . "/../../vendor/autoload.php";

class Mailer {

    private $fromEmail = "titakacimi2004@gmail.com";
    private $fromName = "Sisi's Beauty";
    private $password = "nbbjzunytxzugyew";

    
    private static $lastSendTime = 0;
    private static $lastSendTo = '';

    public function sendMail($to, $subject, $message) {

        
        $now = microtime(true);
        if (self::$lastSendTo === $to && ($now - self::$lastSendTime) < 2) {
            return [
                "success" => false,
                "error" => "Envoi ignoré (envoi multiple détecté)"
            ];
        }
        
        self::$lastSendTime = $now;
        self::$lastSendTo = $to;

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $this->fromEmail;
            $mail->Password = $this->password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;

            $mail->send();

            return [
                "success" => true,
                "message" => "Email envoyé avec succès"
            ];

        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Erreur envoi email",
                "error" => $mail->ErrorInfo
            ];
        }
    }
}
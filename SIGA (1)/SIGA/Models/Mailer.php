<?php
require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class Mailer {
    /**
     * Envía un correo HTML por SMTP usando la configuración de Config/mail.php.
     *
     * @throws PHPMailerException si la configuración SMTP falta o el envío falla.
     */
    public static function send($toEmail, $toName, $subject, $htmlBody) {
        $config = require __DIR__ . '/../Config/mail.php';

        if (empty($config['username']) || empty($config['password'])) {
            throw new PHPMailerException('Configuración SMTP incompleta. Completa Config/mail.php.');
        }

        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = $config['encryption'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $config['port'];
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($config['from_email'] ?: $config['username'], $config['from_name']);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;

        return $mail->send();
    }
}

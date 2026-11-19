<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

final class EmailController
{
    public static function sendPasswordResetCode(string $email, string $code): void
    {
        $host = trim((string) getenv('MAIL_HOST'));
        $fromAddress = trim((string) getenv('MAIL_FROM_ADDRESS'));
        $username = trim((string) getenv('MAIL_USERNAME'));
        $password = (string) getenv('MAIL_PASSWORD');
        $encryption = strtolower(trim((string) (getenv('MAIL_ENCRYPTION') ?: 'tls')));
        $port = filter_var(
            getenv('MAIL_PORT') ?: '587',
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 65535]]
        );

        if ($host === '' || !filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('MAIL_HOST e MAIL_FROM_ADDRESS devem estar configurados.');
        }
        if ($port === false) {
            throw new RuntimeException('MAIL_PORT deve ser um número entre 1 e 65535.');
        }
        if (($username === '') !== ($password === '')) {
            throw new RuntimeException('Configure MAIL_USERNAME e MAIL_PASSWORD juntos.');
        }
        if (!in_array($encryption, ['tls', 'ssl'], true)) {
            throw new RuntimeException('MAIL_ENCRYPTION deve ser tls ou ssl.');
        }

        $mailer = new PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host = $host;
        $mailer->Port = $port;
        $mailer->SMTPAuth = $username !== '';
        if ($mailer->SMTPAuth) {
            $mailer->Username = $username;
            $mailer->Password = $password;
        }
        $mailer->SMTPSecure = $encryption === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->CharSet = PHPMailer::CHARSET_UTF8;
        $mailer->setFrom(
            $fromAddress,
            trim((string) (getenv('MAIL_FROM_NAME') ?: 'Logic Gate'))
        );
        $mailer->addAddress($email);
        $mailer->Subject = 'Código para redefinir sua senha - Logic Gate';
        $mailer->Body = "Seu código para redefinir a senha é: {$code}\n\n"
            . "Ele expira em 15 minutos. Se você não solicitou essa alteração, ignore este e-mail.";
        $mailer->send();
    }
}

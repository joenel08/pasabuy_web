<?php
require __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/config/config.php';

use PHPMailer\PHPMailer\PHPMailer;

$m = new PHPMailer(true);
try {
    $m->isSMTP();
    $m->Host       = $config['mail']['host'];
    $m->SMTPAuth   = true;
    $m->Username   = $config['mail']['username'];
    $m->Password   = $config['mail']['password'];
    $m->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $m->Port       = $config['mail']['port'];
    $m->SMTPDebug  = 2;                                  // verbose
    $m->Debugoutput = 'echo';

    $m->setFrom($config['mail']['from_email'], $config['mail']['from_name']);
    $m->addAddress('anywherefind2@gmail.com', 'Test');
    $m->Subject = 'PasaBuy SMTP test';
    $m->Body    = 'If you see this, SMTP works.';
    $m->send();
    echo "\n\n✅ SENT";
} catch (Throwable $e) {
    echo "\n\n❌ FAILED: " . $e->getMessage();
}
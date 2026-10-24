#!/usr/bin/env php
<?php
// 独立 SMTP 告警邮件发送器: 不启动 Laravel, 应用挂了也能发
// 用法: php mail-alert.php <to> <subject> <body>

$envFile = '/var/www/app/.env';
$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (preg_match('/^(MAIL_[A-Z_]+)=(.*)$/', $line, $m)) {
        $env[$m[1]] = trim($m[2], "\"'");
    }
}

$host = $env['MAIL_HOST'] ?? '';
$port = (int)($env['MAIL_PORT'] ?? 465);
$user = $env['MAIL_USERNAME'] ?? '';
$pass = $env['MAIL_PASSWORD'] ?? '';
$from = $env['MAIL_FROM_ADDRESS'] ?? $user;
$fromName = $env['MAIL_FROM_NAME'] ?? 'HealthAlert';
$to   = $argv[1] ?? '';
$subject = $argv[2] ?? 'Health Alert';
$body = $argv[3] ?? '';

if (!$host || !$user || !$to) {
    fwrite(STDERR, "mail-alert: missing config\n");
    exit(1);
}

$fp = stream_socket_client("ssl://{$host}:{$port}", $errno, $errstr, 15);
if (!$fp) {
    fwrite(STDERR, "mail-alert: connect failed: {$errstr}\n");
    exit(1);
}

function smtp_read($fp) {
    $data = '';
    while (($line = fgets($fp, 515)) !== false) {
        $data .= $line;
        if (isset($line[3]) && $line[3] === ' ') break;
    }
    return $data;
}

smtp_read($fp);
fwrite($fp, "EHLO health-alert\r\n"); smtp_read($fp);
fwrite($fp, "AUTH LOGIN\r\n"); smtp_read($fp);
fwrite($fp, base64_encode($user) . "\r\n"); smtp_read($fp);
$auth = '';
fwrite($fp, base64_encode($pass) . "\r\n");
$auth = smtp_read($fp);
if (strpos($auth, '235') === false) {
    fwrite(STDERR, "mail-alert: auth failed\n");
    exit(1);
}
fwrite($fp, "MAIL FROM:<{$from}>\r\n"); smtp_read($fp);
fwrite($fp, "RCPT TO:<{$to}>\r\n"); smtp_read($fp);
fwrite($fp, "DATA\r\n"); smtp_read($fp);

$encSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
$headers = "From: {$fromName} <{$from}>\r\n"
    . "To: <{$to}>\r\n"
    . "Subject: {$encSubject}\r\n"
    . "MIME-Version: 1.0\r\n"
    . "Content-Type: text/plain; charset=UTF-8\r\n"
    . "Content-Transfer-Encoding: 8bit\r\n";
fwrite($fp, $headers . "\r\n" . $body . "\r\n.\r\n");
$send = smtp_read($fp);
fwrite($fp, "QUIT\r\n");
fclose($fp);

if (strpos($send, '250') === false) {
    fwrite(STDERR, "mail-alert: send rejected: " . trim($send) . "\n");
    exit(1);
}
echo "mail-sent\n";

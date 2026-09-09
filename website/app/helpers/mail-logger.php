<?php

/**
 * mail-logger.php
 *
 * Writes a copy of every outgoing email to a .txt file in storage/mail-log/.
 * Useful for development environments where mail() is not configured.
 */

function logEmail(string $to, string $subject, string $body, string $headers): void
{
    $logDir = ROOT . 'storage/mail-log';

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d_His');
    $filename  = $logDir . '/' . $timestamp . '_' . uniqid() . '.txt';

    $content  = 'Date: '    . date('Y-m-d H:i:s') . "\n";
    $content .= 'To: '      . $to                 . "\n";
    $content .= 'Subject: ' . $subject             . "\n";
    $content .= 'Headers: ' . $headers             . "\n";
    $content .= str_repeat('-', 40) . "\n";
    $content .= $body . "\n";

    file_put_contents($filename, $content);
}

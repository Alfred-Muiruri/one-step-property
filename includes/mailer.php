<?php
/**
 * Minimal SMTP client (no Composer needed). Supports SSL (465) and STARTTLS (587) with AUTH LOGIN.
 * send() returns [bool ok, string error].
 */
class SmtpMailer
{
    private $sock;

    public function send(string $to, string $subject, string $html, string $text = ''): array
    {
        $to      = str_replace(["\r", "\n"], '', $to);
        $subject = str_replace(["\r", "\n"], ' ', $subject);
        if ($text === '') {
            $text = trim(html_entity_decode(strip_tags(str_replace(['<br>', '</p>'], "\n", $html))));
        }

        if (SMTP_HOST === '') {
            if (DEV_MODE) {
                $line = '[' . date('Y-m-d H:i:s') . "] TO: $to | SUBJECT: $subject\n$text\n" . str_repeat('-', 60) . "\n";
                @file_put_contents(__DIR__ . '/../storage/mail.log', $line, FILE_APPEND | LOCK_EX);
                return [true, ''];
            }
            return [false, 'SMTP is not configured'];
        }

        try {
            $ctx = stream_context_create(['ssl' => [
                'verify_peer'      => SMTP_VERIFY_PEER,
                'verify_peer_name' => SMTP_VERIFY_PEER,
            ]]);
            $remote = (SMTP_SECURE === 'ssl' ? 'ssl://' : 'tcp://') . SMTP_HOST . ':' . SMTP_PORT;
            $this->sock = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
            if (!$this->sock) throw new Exception("Could not connect to mail server ($errstr)");
            stream_set_timeout($this->sock, 20);

            $this->expect(220);
            $me = $_SERVER['SERVER_NAME'] ?? 'localhost';
            $this->cmd("EHLO $me", 250);
            if (SMTP_SECURE === 'tls') {
                $this->cmd('STARTTLS', 220);
                if (!stream_socket_enable_crypto($this->sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new Exception('TLS negotiation failed');
                }
                $this->cmd("EHLO $me", 250);
            }
            if (SMTP_USER !== '') {
                $this->cmd('AUTH LOGIN', 334);
                $this->cmd(base64_encode(SMTP_USER), 334);
                $this->cmd(base64_encode(SMTP_PASS), 235);
            }
            $this->cmd('MAIL FROM:<' . SMTP_FROM_EMAIL . '>', 250);
            $this->cmd("RCPT TO:<$to>", [250, 251]);
            $this->cmd('DATA', 354);
            fwrite($this->sock, $this->build($to, $subject, $html, $text) . "\r\n.\r\n");
            $this->expect(250);
            try { $this->cmd('QUIT', 221); } catch (Throwable $e) { /* ignore */ }
            fclose($this->sock);
            return [true, ''];
        } catch (Throwable $e) {
            if (is_resource($this->sock)) @fclose($this->sock);
            error_log('SMTP error: ' . $e->getMessage());
            return [false, substr($e->getMessage(), 0, 250)];
        }
    }

    private function build(string $to, string $subject, string $html, string $text): string
    {
        $b = '=_osp_' . bin2hex(random_bytes(8));
        $fromName = '=?UTF-8?B?' . base64_encode(SMTP_FROM_NAME) . '?=';
        $h  = 'Date: ' . date('r') . "\r\n";
        $h .= "From: $fromName <" . SMTP_FROM_EMAIL . ">\r\n";
        $h .= "To: <$to>\r\n";
        $h .= 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n";
        $h .= 'Message-ID: <' . bin2hex(random_bytes(10)) . '@' . ($_SERVER['SERVER_NAME'] ?? 'localhost') . ">\r\n";
        $h .= "MIME-Version: 1.0\r\n";
        $h .= "Content-Type: multipart/alternative; boundary=\"$b\"\r\n\r\n";
        $h .= "--$b\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($text)) . "\r\n";
        $h .= "--$b\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html)) . "\r\n";
        $h .= "--$b--";
        return $h;
    }

    private function cmd(string $line, $expect): void
    {
        fwrite($this->sock, $line . "\r\n");
        $this->expect($expect);
    }

    private function expect($codes): void
    {
        $codes = (array)$codes;
        $resp = '';
        while (($line = fgets($this->sock, 515)) !== false) {
            $resp .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') break;
        }
        $code = (int)substr($resp, 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new Exception('Mail server said: ' . trim($resp));
        }
    }
}

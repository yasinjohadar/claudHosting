<?php

namespace App\Services;

use App\Models\EmailSetting;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;

class SmtpConnectionTestService
{
    /**
     * @param  array{host: string, port: int, encryption: string, username: string, password: string}  $config
     * @return array{success: bool, message: string, detail?: string, suggested_port?: int, suggested_encryption?: string}
     */
    public function test(array $config, bool $allowPortFallback = true): array
    {
        $host = trim($config['host'] ?? '');
        $port = (int) ($config['port'] ?? 0);
        $encryption = EmailSetting::normalizeEncryption($port, strtolower($config['encryption'] ?? 'tls'));
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';

        if ($host === '' || $port <= 0) {
            return [
                'success' => false,
                'message' => 'يرجى إدخال عنوان SMTP والمنفذ بشكل صحيح.',
            ];
        }

        if ($username === '' || $password === '') {
            return [
                'success' => false,
                'message' => 'يرجى إدخال اسم المستخدم وكلمة المرور.',
            ];
        }

        if (! extension_loaded('openssl')) {
            return [
                'success' => false,
                'message' => 'امتداد OpenSSL غير مفعّل على السيرفر. SMTP يتطلب OpenSSL للاتصال الآمن.',
            ];
        }

        $result = $this->attemptConnection($host, $port, $encryption, $username, $password);

        if (
            $allowPortFallback
            && ! $result['success']
            && $port === 587
            && $encryption === 'tls'
            && $this->shouldRetryWithSslPort($result['detail'] ?? '')
        ) {
            $fallback = $this->test([
                'host' => $host,
                'port' => 465,
                'encryption' => 'ssl',
                'username' => $username,
                'password' => $password,
            ], false);

            if ($fallback['success']) {
                return [
                    'success' => true,
                    'message' => 'تم الاتصال بنجاح عبر المنفذ 465 (SSL). حدّث الإعدادات إلى المنفذ 465 والتشفير SSL.',
                    'suggested_port' => 465,
                    'suggested_encryption' => 'ssl',
                ];
            }
        }

        return $result;
    }

    /**
     * @return array{success: bool, message: string, detail?: string}
     */
    private function attemptConnection(
        string $host,
        int $port,
        string $encryption,
        string $username,
        string $password
    ): array {
        $transport = $this->buildTransport($host, $port, $encryption, $username, $password);

        try {
            $transport->start();
            $transport->stop();

            return [
                'success' => true,
                'message' => 'تم الاتصال بخادم SMTP والمصادقة بنجاح دون إرسال بريد.',
            ];
        } catch (TransportExceptionInterface $e) {
            $detail = trim($e->getMessage());

            return [
                'success' => false,
                'message' => $this->translateError($detail, $encryption, $port, $host),
                'detail' => $detail,
            ];
        } catch (\Throwable $e) {
            $detail = trim($e->getMessage());

            return [
                'success' => false,
                'message' => 'فشل اختبار الاتصال: '.$detail,
                'detail' => $detail,
            ];
        }
    }

    private function buildTransport(
        string $host,
        int $port,
        string $encryption,
        string $username,
        string $password
    ): EsmtpTransport {
        $implicitTls = EmailSetting::usesImplicitTls($port, $encryption);

        $transport = new EsmtpTransport($host, $port, $implicitTls);
        $transport->setAutoTls(! $implicitTls && $encryption === 'tls');
        $transport->setUsername($username);
        $transport->setPassword($password);

        if ($localDomain = $this->resolveLocalDomain()) {
            $transport->setLocalDomain($localDomain);
        }

        /** @var SocketStream $stream */
        $stream = $transport->getStream();
        $stream->setTimeout((float) config('mail.smtp_timeout', env('MAIL_SMTP_TIMEOUT', 30)));

        $streamOptions = $stream->getStreamOptions();
        $streamOptions['ssl']['peer_name'] = $host;

        if (! $this->shouldVerifyPeer()) {
            $streamOptions['ssl']['verify_peer'] = false;
            $streamOptions['ssl']['verify_peer_name'] = false;
        }

        $stream->setStreamOptions($streamOptions);

        return $transport;
    }

    private function shouldVerifyPeer(): bool
    {
        return filter_var(
            config('mail.smtp_verify_peer', env('MAIL_SMTP_VERIFY_PEER', true)),
            FILTER_VALIDATE_BOOL
        );
    }

    private function shouldRetryWithSslPort(string $detail): bool
    {
        $lower = strtolower($detail);

        return str_contains($lower, 'starttls')
            || str_contains($lower, 'did not match expected')
            || str_contains($lower, 'peer certificate')
            || str_contains($lower, 'wrong version number');
    }

    private function resolveLocalDomain(): ?string
    {
        $host = parse_url((string) config('app.url', ''), PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : null;
    }

    private function translateError(string $message, string $encryption, int $port, string $host): string
    {
        $lower = strtolower($message);

        if (str_contains($lower, 'did not match expected') || str_contains($lower, 'peer certificate')) {
            $presentedCn = $this->extractCertificateCn($message);

            return $this->withDetail(
                'السيرفر لا يتصل بـ '.$host.' مباشرة — يتم تحويل SMTP محلياً'
                .($presentedCn ? ' (شهادة: '.$presentedCn.')' : '')
                .'. جرّب المنفذ 465 مع SSL، أو استخدم SMTP الاستضافة بدل Gmail. تحقق أيضاً من ملف /etc/hosts وإعدادات البريد على السيرفر.',
                $message
            );
        }

        if (str_contains($lower, 'wrong version number') || (str_contains($lower, 'ssl://') && $port === 587)) {
            return $this->withDetail(
                'تعارض بين المنفذ ونوع التشفير: المنفذ 587 يستخدم TLS (STARTTLS) وليس SSL المباشر. اختر TLS مع 587 أو SSL مع 465.',
                $message
            );
        }

        if (str_contains($lower, 'authenticate') || str_contains($lower, 'authentication') || str_contains($lower, '535')) {
            return $this->withDetail(
                'فشلت المصادقة: تحقق من اسم المستخدم وكلمة المرور (استخدم App Password لـ Gmail).',
                $message
            );
        }

        if (str_contains($lower, 'connection refused') || str_contains($lower, 'could not connect')) {
            return $this->withDetail(
                'تعذر الاتصال بخادم SMTP. تحقق من عنوان الخادم والمنفذ '.$port.'.',
                $message
            );
        }

        if (str_contains($lower, 'starttls') || str_contains($lower, 'unable to connect with starttls')) {
            $hint = $encryption === 'tls' && $port === 587
                ? 'قد يكون SMTP على المنفذ 587 محوّلاً محلياً على السيرفر — جرّب المنفذ 465 مع SSL.'
                : 'تحقق من نوع التشفير والمنفذ (587 لـ TLS، 465 لـ SSL).';

            return $this->withDetail('فشل التشفير (STARTTLS/TLS): '.$hint, $message);
        }

        if (str_contains($lower, 'certificate') || str_contains($lower, 'ssl') || str_contains($lower, 'tls')) {
            return $this->withDetail(
                'فشل التحقق من شهادة SSL/TLS. جرّب المنفذ 465 مع SSL، أو ثبّت ca-certificates على السيرفر.',
                $message
            );
        }

        if (str_contains($lower, 'timed out') || str_contains($lower, 'timeout')) {
            return $this->withDetail(
                'انتهت مهلة الاتصال. تحقق من أن السيرفر يسمح بالاتصال الصادر إلى الخادم على المنفذ '.$port.'.',
                $message
            );
        }

        return $this->withDetail('فشل اختبار الاتصال.', $message);
    }

    private function extractCertificateCn(string $message): ?string
    {
        if (preg_match('/CN=`([^`]+)`/', $message, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function withDetail(string $message, string $technical): string
    {
        if ($technical === '') {
            return $message;
        }

        return $message.' — التفاصيل: '.$technical;
    }
}

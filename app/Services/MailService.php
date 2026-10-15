<?php

namespace App\Services;

use App\Http\Requests\MailRequest;
use App\Libraries\QueryExceptionLibrary;
use Dipokhalder\EnvEditor\EnvEditor;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Dipokhalder\Settings\Facades\Settings;

class MailService
{
    public $envService;

    public function __construct(EnvEditor $envEditor)
    {
        $this->envService = $envEditor;
    }

    /**
     * @throws Exception
     */
    public function list()
    {
        try {
            return Settings::group('mail')->all();
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function update(MailRequest $request)
    {
        try {
            Settings::group('mail')->set($request->validated());
            $this->envService->addData([
                'MAIL_MAILER'       => 'smtp',
                'MAIL_HOST'         => $request->mail_host,
                'MAIL_PORT'         => $request->mail_port,
                'MAIL_USERNAME'     => $request->mail_username,
                'MAIL_PASSWORD'     => $request->mail_password,
                'MAIL_ENCRYPTION'   => $request->mail_encryption,
                'MAIL_FROM_ADDRESS' => $request->mail_from_email,
                'MAIL_FROM_NAME'    => $request->mail_from_name
            ]);
            Artisan::call('optimize:clear');
            return $this->list();
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * Send a plain test email using saved mail settings.
     *
     * @throws Exception
     */
    public function sendTest(string $toEmail): bool
    {
        try {
            $mail = Settings::group('mail')->all();
            $host = (string) ($mail['mail_host'] ?? '');
            $from = (string) ($mail['mail_from_email'] ?? '');

            if ($host === '' || $from === '') {
                throw new Exception('Please save mail settings (host and from email) before sending a test.', 422);
            }

            $this->applyRuntimeMailConfig($mail);

            $site = config('app.name', 'Shopperzz');
            $body = "This is a test email from {$site}.\n\n"
                . "If you received this message, your SMTP mail settings are working correctly.\n\n"
                . 'Sent at: ' . now()->toDateTimeString();

            Mail::raw($body, function ($message) use ($toEmail, $site) {
                $message->to($toEmail)->subject("[{$site}] Mail configuration test");
            });

            return true;
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            $message = $exception->getMessage() ?: 'Failed to send test email.';
            throw new Exception($message, 422);
        }
    }

    protected function applyRuntimeMailConfig(array $mail): void
    {
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host', $mail['mail_host'] ?? null);
        Config::set('mail.mailers.smtp.port', $mail['mail_port'] ?? null);
        Config::set('mail.mailers.smtp.username', $mail['mail_username'] ?? null);
        Config::set('mail.mailers.smtp.password', $mail['mail_password'] ?? null);
        Config::set('mail.mailers.smtp.encryption', $mail['mail_encryption'] ?? null);
        Config::set('mail.from.address', $mail['mail_from_email'] ?? null);
        Config::set('mail.from.name', $mail['mail_from_name'] ?? config('app.name'));
    }
}

<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramBotService;
use Illuminate\Console\Command;

class SetupTelegramWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sabayra:telegram-webhook
        {--set : Register the webhook URL with Telegram Bot API}
        {--delete : Delete active webhook (enables local getUpdates polling)}
        {--info : Display official bot verification & webhook status}
        {--url= : Custom HTTPS base URL or full webhook URL override}';

    /**
     * The console command description.
     */
    protected $description = 'Configure or inspect the Official SABAYRA Telegram Bot webhook for multi-tenant one-click linking';

    /**
     * Execute the console command.
     */
    public function handle(TelegramBotService $botService): int
    {
        $token = trim((string) config('services.telegram.bot_token', ''));

        if ($token === '') {
            $this->error('TELEGRAM_BOT_TOKEN is not configured.');

            return self::FAILURE;
        }

        if ($this->option('delete')) {
            $deleteResult = $botService->deleteWebhook($token);

            if (! $deleteResult['ok']) {
                $this->error('Failed to delete webhook: ' . ($deleteResult['error'] ?? 'Unknown error'));

                return self::FAILURE;
            }

            $this->info('Telegram webhook deleted successfully (local getUpdates polling enabled).');

            return self::SUCCESS;
        }

        if ($this->option('set')) {
            $baseOrFullUrl = trim((string) (
                $this->option('url')
                ?: config('app.url')
                ?: (getenv('RENDER_EXTERNAL_URL') ?: '')
            ));

            if ($baseOrFullUrl === '') {
                $this->error('No public HTTPS URL found. Pass --url=https://your-domain.com or set APP_URL.');

                return self::FAILURE;
            }

            $webhookUrl = str_ends_with(rtrim($baseOrFullUrl, '/'), '/webhooks/telegram')
                ? rtrim($baseOrFullUrl, '/')
                : rtrim($baseOrFullUrl, '/') . '/webhooks/telegram';

            if (! str_starts_with(strtolower($webhookUrl), 'https://')) {
                $this->error("Telegram requires an HTTPS webhook URL. Given: {$webhookUrl}");

                return self::FAILURE;
            }

            $secret = (string) config('services.telegram.webhook_secret', '');
            if ($secret === '') {
                $this->warn('TELEGRAM_WEBHOOK_SECRET is empty. Setting webhook without a secret header is not recommended in production.');
            }

            $setResult = $botService->setWebhook($token, $webhookUrl, $secret !== '' ? $secret : null);

            if (! $setResult['ok']) {
                $this->error('Failed to register Telegram webhook: ' . ($setResult['error'] ?? 'Unknown error'));

                return self::FAILURE;
            }

            $this->info("Telegram webhook registered successfully at: {$webhookUrl}");

            return self::SUCCESS;
        }

        // Default / --info: Verify bot identity & webhook info safely (never printing raw token)
        $me = $botService->getMe($token);
        if (! $me['ok']) {
            $this->error('Bot verification failed: ' . ($me['error'] ?? 'Invalid token'));

            return self::FAILURE;
        }

        $botUsername = $me['bot']['username'] ?? config('services.telegram.bot_username');
        $this->info("Official Bot Verified: @{$botUsername}");

        $webhookInfo = $botService->getWebhookInfo($token);
        if (! $webhookInfo['ok']) {
            $this->warn('Could not read webhook info: ' . ($webhookInfo['error'] ?? 'Unknown error'));

            return self::FAILURE;
        }

        $info = $webhookInfo['info'] ?? [];
        $registeredUrl = (string) ($info['url'] ?? '');

        $this->line('Webhook URL: ' . ($registeredUrl !== '' ? $registeredUrl : '(not set — polling mode)'));
        $this->line('Pending Updates: ' . (string) ($info['pending_update_count'] ?? 0));

        if (! empty($info['last_error_message'])) {
            $this->warn('Last Webhook Error: ' . (string) $info['last_error_message']);
        }

        return self::SUCCESS;
    }
}

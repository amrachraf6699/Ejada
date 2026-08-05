<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramService
{
    public function sendDocument(string $path, string $caption): void
    {
        $token = (string) config('services.telegram.bot_token');
        $chatId = (string) config('services.telegram.chat_id');
        if ($token === '' || $chatId === '') {
            throw new RuntimeException('إعدادات Telegram غير مكتملة. عيّن TELEGRAM_BOT_TOKEN و TELEGRAM_CHAT_ID.');
        }
        if (! is_file($path)) {
            throw new RuntimeException('الملف المطلوب إرساله إلى Telegram غير موجود.');
        }
        $response = Http::timeout((int) config('services.telegram.timeout', 30))
            ->attach('document', file_get_contents($path), basename($path))
            ->post("https://api.telegram.org/bot{$token}/sendDocument", ['chat_id' => $chatId, 'caption' => $caption]);
        if ($response->failed()) {
            throw new RuntimeException('تعذر إرسال الملف إلى Telegram: ' . $response->body());
        }
    }
}

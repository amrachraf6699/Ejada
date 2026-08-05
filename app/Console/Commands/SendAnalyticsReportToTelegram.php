<?php

namespace App\Console\Commands;

use App\Services\AnalyticsReportService;
use Illuminate\Console\Command;
use Throwable;

class SendAnalyticsReportToTelegram extends Command
{
    protected $signature = 'analytics:report-telegram';
    protected $description = 'Generate Arabic analytics reports and send them to Telegram';
    public function handle(AnalyticsReportService $reports): int { try { $reports->send(); $this->info('تم إرسال التقارير إلى Telegram.'); return self::SUCCESS; } catch (Throwable $e) { report($e); $this->error($e->getMessage()); return self::FAILURE; } }
}

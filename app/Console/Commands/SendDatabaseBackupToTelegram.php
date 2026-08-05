<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Throwable;

class SendDatabaseBackupToTelegram extends Command
{
    protected $signature = 'database:backup-telegram';
    protected $description = 'Create a database backup and send it to Telegram';
    public function handle(DatabaseBackupService $backups): int { try { $this->info('تم إرسال النسخة: ' . $backups->send()); return self::SUCCESS; } catch (Throwable $e) { report($e); $this->error($e->getMessage()); return self::FAILURE; } }
}

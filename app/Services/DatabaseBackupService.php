<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    public function create(): string
    {
        $directory = storage_path('app/backups');
        File::ensureDirectoryExists($directory);
        $driver = config('database.default');
        $stamp = now('Africa/Cairo')->format('Y-m-d_His');
        $path = match ($driver) {
            'sqlite' => $this->sqliteBackup($directory, $stamp),
            'mysql' => $this->dumpBackup('mysqldump', $this->mysqlArguments(), $directory . "/database_{$stamp}.sql", 'MYSQL_PWD', config('database.connections.mysql.password')),
            'pgsql' => $this->dumpBackup('pg_dump', $this->postgresArguments(), $directory . "/database_{$stamp}.sql", 'PGPASSWORD', config('database.connections.pgsql.password')),
            default => throw new \RuntimeException("نسخ قاعدة البيانات غير مدعوم للمحرك: {$driver}"),
        };
        $this->prune($directory);
        return $path;
    }

    public function send(): string
    {
        $path = $this->create();
        app(TelegramService::class)->sendDocument($path, 'نسخة احتياطية لقاعدة بيانات المحفظ - ' . now('Africa/Cairo')->translatedFormat('j F Y، H:i'));
        return $path;
    }

    private function sqliteBackup(string $directory, string $stamp): string
    {
        $database = config('database.connections.sqlite.database');
        if (! is_string($database) || ! is_file($database)) throw new \RuntimeException('ملف قاعدة بيانات SQLite غير موجود.');
        $path = $directory . "/database_{$stamp}.sqlite";
        File::copy($database, $path);
        return $path;
    }

    private function dumpBackup(string $binary, array $arguments, string $path, string $passwordKey, ?string $password): string
    {
        $process = new Process([$binary, ...$arguments]);
        $process->setTimeout(300)->setEnv([$passwordKey => (string) $password])->run();
        if (! $process->isSuccessful()) throw new \RuntimeException("تعذر تنفيذ {$binary}: " . $process->getErrorOutput());
        File::put($path, $process->getOutput());
        return $path;
    }

    private function mysqlArguments(): array { $db = config('database.connections.mysql'); return ['--host=' . $db['host'], '--port=' . $db['port'], '--user=' . $db['username'], '--single-transaction', '--routines', '--events', $db['database']]; }
    private function postgresArguments(): array { $db = config('database.connections.pgsql'); return ['--host=' . $db['host'], '--port=' . $db['port'], '--username=' . $db['username'], '--format=plain', $db['database']]; }
    private function prune(string $directory): void { foreach (File::files($directory) as $file) if ($file->getMTime() < now()->subDays(7)->timestamp) File::delete($file->getPathname()); }
}

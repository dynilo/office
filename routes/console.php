<?php

use App\Application\Providers\Exceptions\LlmProviderException;
use App\Application\Tasks\Services\RunQueuedResearchTaskService;
use App\Support\Auth\AuthAccessValidation;
use App\Support\Backup\BackupBaselineService;
use App\Support\Database\PgvectorRuntimeValidation;
use App\Support\Database\PostgresqlRuntimeValidation;
use App\Support\Memory\EmbeddingProviderRuntimeValidation;
use App\Support\Observability\ObservabilityService;
use App\Support\Redis\RedisRuntimeValidation;
use App\Support\Workers\QueueProcessValidation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('research-agent:run-one', function (RunQueuedResearchTaskService $service) {
    try {
        $task = $service->runOne();
    } catch (LlmProviderException $exception) {
        $this->error('Research task failed: '.$exception->getMessage());

        return Command::FAILURE;
    }

    if ($task === null) {
        $this->comment('No queued research task available.');

        return Command::SUCCESS;
    }

    $this->info('Completed research task '.$task->id);

    return Command::SUCCESS;
})->purpose('Run one queued research task end-to-end.');

Artisan::command('observability:diagnose', function (ObservabilityService $observability) {
    $this->line(json_encode($observability->diagnostics(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return Command::SUCCESS;
})->purpose('Show the current observability configuration diagnostics.');

Artisan::command('backup:manifest', function (BackupBaselineService $backup) {
    $this->line(json_encode($backup->backupPlan(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return Command::SUCCESS;
})->purpose('Show the configured backup baseline plan.');

Artisan::command('restore:manifest', function (BackupBaselineService $backup) {
    $this->line(json_encode($backup->restorePlan(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return Command::SUCCESS;
})->purpose('Show the configured restore baseline plan.');

Artisan::command('postgresql:validate-runtime', function (PostgresqlRuntimeValidation $validation) {
    $report = $validation->report();

    $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $report['ready'] === true ? Command::SUCCESS : Command::FAILURE;
})->purpose('Validate live PostgreSQL runtime connectivity and schema alignment.');

Artisan::command('redis:validate-runtime', function (RedisRuntimeValidation $validation) {
    $report = $validation->report();

    $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $report['ready'] === true ? Command::SUCCESS : Command::FAILURE;
})->purpose('Validate live Redis runtime connectivity for queue, cache, and broadcast paths.');

Artisan::command('pgvector:validate-runtime', function (PgvectorRuntimeValidation $validation) {
    $report = $validation->report();

    $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $report['ready'] === true ? Command::SUCCESS : Command::FAILURE;
})->purpose('Validate live pgvector runtime readiness for memory storage and similarity search.');

Artisan::command('workers:validate-runtime', function (QueueProcessValidation $validation) {
    $report = $validation->report();

    $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $report['ready'] === true ? Command::SUCCESS : Command::FAILURE;
})->purpose('Validate worker supervision and queue process expectations.');

Artisan::command('auth:validate-runtime', function (AuthAccessValidation $validation) {
    $report = $validation->report();

    $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $report['ready'] === true ? Command::SUCCESS : Command::FAILURE;
})->purpose('Validate authentication and role-protected route expectations.');

Artisan::command('embedding-provider:validate-runtime', function (EmbeddingProviderRuntimeValidation $validation) {
    $report = $validation->report();

    $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $report['ready'] === true ? Command::SUCCESS : Command::FAILURE;
})->purpose('Validate real embedding provider runtime readiness and normalized output expectations.');

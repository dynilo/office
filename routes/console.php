<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Application\Providers\Exceptions\LlmProviderException;
use App\Application\Tasks\Services\RunQueuedResearchTaskService;
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

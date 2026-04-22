<?php

namespace App\Providers;

use App\Domain\Agents\Contracts\AgentRepository;
use App\Domain\Executions\Contracts\ExecutionRepository;
use App\Domain\Tasks\Contracts\TaskRepository;
use App\Application\Providers\Contracts\LlmProvider;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentAgentRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentExecutionRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentTaskRepository;
use App\Infrastructure\Providers\OpenAiCompatibleProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AgentRepository::class, EloquentAgentRepository::class);
        $this->app->bind(ExecutionRepository::class, EloquentExecutionRepository::class);
        $this->app->bind(TaskRepository::class, EloquentTaskRepository::class);
        $this->app->bind(LlmProvider::class, function ($app): LlmProvider {
            return new OpenAiCompatibleProvider(config('providers.openai_compatible'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

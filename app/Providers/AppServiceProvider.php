<?php

namespace App\Providers;

use App\Application\Documents\Services\DocumentParserRegistry;
use App\Application\Memory\Contracts\EmbeddingGenerator;
use App\Application\Memory\Contracts\KnowledgeSimilaritySearch;
use App\Application\Providers\Contracts\LlmProvider;
use App\Application\Providers\Services\ProviderFailoverService;
use App\Domain\Agents\Contracts\AgentRepository;
use App\Domain\Executions\Contracts\ExecutionRepository;
use App\Domain\Tasks\Contracts\TaskRepository;
use App\Infrastructure\Documents\Parsers\PlainTextDocumentParser;
use App\Infrastructure\Memory\NullEmbeddingGenerator;
use App\Infrastructure\Memory\OpenAiCompatibleEmbeddingGenerator;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentAgentRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentExecutionRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentTaskRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\PgvectorKnowledgeSimilaritySearch;
use App\Infrastructure\Providers\OpenAiCompatibleProvider;
use App\Support\Database\PostgresqlProductionReadiness;
use App\Support\Exceptions\InvalidStateException;
use App\Support\Queue\RedisQueueProductionReadiness;
use App\Support\Security\SecretRedactor;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
        $this->app->bind(AgentRepository::class, EloquentAgentRepository::class);
        $this->app->bind(ExecutionRepository::class, EloquentExecutionRepository::class);
        $this->app->bind(TaskRepository::class, EloquentTaskRepository::class);
        $this->app->bind(EmbeddingGenerator::class, function ($app): EmbeddingGenerator {
            if (config('providers.embeddings.default') === 'openai_compatible') {
                return new OpenAiCompatibleEmbeddingGenerator(
                    config('providers.embeddings.openai_compatible'),
                    $app->make(SecretRedactor::class),
                );
            }

            return new NullEmbeddingGenerator;
        });
        $this->app->bind(KnowledgeSimilaritySearch::class, PgvectorKnowledgeSimilaritySearch::class);
        $this->app->singleton(DocumentParserRegistry::class, function ($app): DocumentParserRegistry {
            return new DocumentParserRegistry([
                $app->make(PlainTextDocumentParser::class),
            ]);
        });
        $this->app->bind(LlmProvider::class, function ($app): LlmProvider {
            $default = (string) config('providers.default', 'openai_compatible');

            if ($default === 'failover') {
                $order = config('providers.failover.order', []);
                $providers = [];

                foreach ($order as $name) {
                    $providers[$name] = $this->makeLlmProvider($name, $app->make(SecretRedactor::class));
                }

                return new ProviderFailoverService(
                    providers: $providers,
                    order: $order,
                    fallbackOnRetriableOnly: (bool) config('providers.failover.fallback_on_retriable_only', true),
                );
            }

            return $this->makeLlmProvider($default, $app->make(SecretRedactor::class));
        });
    }

    private function makeLlmProvider(string $name, SecretRedactor $redactor): LlmProvider
    {
        return match ($name) {
            'openai_compatible' => new OpenAiCompatibleProvider(
                config('providers.openai_compatible'),
                $redactor,
            ),
            'openai_compatible_secondary' => new OpenAiCompatibleProvider(
                config('providers.openai_compatible_secondary'),
                $redactor,
            ),
            default => throw new InvalidStateException("LLM provider [{$name}] is not supported."),
        };
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        app(PostgresqlProductionReadiness::class)->assertProductionSafe();
        app(RedisQueueProductionReadiness::class)->assertProductionSafe();
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Console\Commands;

use App\Domain\Company\Company;
use App\Domain\Company\JobBoardProvider;
use App\Infrastructure\Services\JobBoardClientFactory;
use Illuminate\Console\Command;

class AddCompanyCommand extends Command
{
    protected $signature = 'companies:add
                          {provider : The job board provider (e.g. workable, lever, ashby)}
                          {slug : The company slug on the provider}
                          {--name= : Override the detected company name}';

    protected $description = 'Add a new company from a supported job board provider';

    public function __construct(
        private readonly JobBoardClientFactory $clientFactory
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $providerValue = $this->argument('provider');
        $slug = $this->argument('slug');

        $provider = JobBoardProvider::tryFrom($providerValue);

        if (! $provider) {
            $validProviders = implode(', ', array_column(JobBoardProvider::cases(), 'value'));
            $this->error("Invalid provider '{$providerValue}'. Valid providers: {$validProviders}");

            return self::FAILURE;
        }

        $existing = Company::query()->where('provider', $provider->value)
            ->where('provider_slug', $slug)
            ->first();

        if ($existing) {
            $this->error("Company with provider '{$provider->value}' and slug '{$slug}' already exists: {$existing->name}");

            return self::FAILURE;
        }

        $this->info("Validating slug '{$slug}' on {$provider->value}...");

        $client = $this->clientFactory->make($provider);
        $detectedName = $client->validateSlug($slug);

        if (! $detectedName) {
            $this->error("Could not validate slug '{$slug}' on {$provider->value}. The slug may be invalid or the provider is unreachable.");

            return self::FAILURE;
        }

        $name = $this->option('name') ?? $detectedName;
        $description = $client->fetchCompanyDescription($slug);

        $company = Company::query()->create([
            'name' => $name,
            'description' => $description,
            'provider' => $provider->value,
            'provider_slug' => $slug,
            'is_active' => true,
        ]);

        $this->info("Company '{$company->name}' added successfully (ID: {$company->id}).");

        return self::SUCCESS;
    }
}

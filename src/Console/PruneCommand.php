<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Console;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Invelity\WizardPackage\Contracts\PrunableStore;
use Invelity\WizardPackage\StoreManager;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'wizard:prune')]
final class PruneCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wizard:prune
                            {--store= : The store to prune, the default store when omitted}
                            {--days=30 : Remove wizard states that have not changed for this many days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove abandoned wizard states';

    /**
     * Execute the console command.
     */
    public function handle(StoreManager $stores): int
    {
        $name = $this->option('store');
        $name = is_string($name) && $name !== '' ? $name : $stores->getDefaultInstance();
        $days = $this->option('days');

        if (! is_numeric($days) || (int) $days < 0) {
            $this->components->error('The --days option must be a whole number of at least zero.');

            return self::FAILURE;
        }

        $store = $stores->store($name);

        if (! $store instanceof PrunableStore) {
            $this->components->error("The [{$name}] wizard store does not support pruning.");

            return self::FAILURE;
        }

        $pruned = $store->prune(CarbonImmutable::now()->subDays((int) $days));

        $this->components->info("{$pruned} wizard state(s) pruned from the [{$name}] store.");

        return self::SUCCESS;
    }
}

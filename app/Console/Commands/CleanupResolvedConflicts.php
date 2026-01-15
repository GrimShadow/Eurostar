<?php

namespace App\Console\Commands;

use App\Models\RealtimeConflict;
use Illuminate\Console\Command;

class CleanupResolvedConflicts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conflicts:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up resolved conflicts older than 24 hours';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting cleanup of resolved conflicts...');

        $deleted = RealtimeConflict::whereNotNull('resolved_at')
            ->where('resolved_at', '<', now()->subDay())
            ->delete();

        $this->info("Deleted {$deleted} resolved conflicts older than 24 hours");

        return Command::SUCCESS;
    }
}

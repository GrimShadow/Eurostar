<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupExpiredCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:cleanup-expired {--locks : Also clean expired cache locks}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired cache entries from the database cache table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $currentTimestamp = now()->timestamp;

        // Clean expired cache entries
        $deletedCache = DB::table('cache')
            ->where('expiration', '<', $currentTimestamp)
            ->delete();

        $this->info("Cleaned up {$deletedCache} expired cache entries");

        $deletedLocks = 0;

        // Optionally clean expired cache locks
        if ($this->option('locks')) {
            $deletedLocks = DB::table('cache_locks')
                ->where('expiration', '<', $currentTimestamp)
                ->delete();

            $this->info("Cleaned up {$deletedLocks} expired cache locks");
        }

        Log::info('Cache cleanup completed', [
            'deleted_cache_entries' => $deletedCache,
            'deleted_cache_locks' => $deletedLocks,
        ]);

        return Command::SUCCESS;
    }
}

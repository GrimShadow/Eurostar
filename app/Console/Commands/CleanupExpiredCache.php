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
    protected $signature = 'cache:cleanup-expired 
                            {--locks : Also clean expired cache locks}
                            {--chunk=1000 : Number of records to delete per batch}';

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
        $chunkSize = (int) $this->option('chunk');

        $totalDeleted = 0;
        $deletedCache = 0;

        $this->info("Starting cache cleanup (chunk size: {$chunkSize})...");

        // Clean expired cache entries in chunks to avoid disk space issues
        do {
            // Use chunked deletion to avoid large temporary files
            $deleted = DB::table('cache')
                ->where('expiration', '<', $currentTimestamp)
                ->limit($chunkSize)
                ->delete();

            $deletedCache += $deleted;
            $totalDeleted += $deleted;

            if ($deleted > 0) {
                $this->info("Deleted {$deleted} expired cache entries (total: {$totalDeleted})...");

                // Small delay to avoid overwhelming the database
                usleep(100000); // 0.1 second
            }

        } while ($deleted > 0);

        $this->info("Cleaned up {$deletedCache} expired cache entries");

        // ALSO clean entries older than 1 hour (even if not expired)
        // This prevents accumulation when cleanup fails or is delayed
        $oldTimestamp = now()->subHour()->timestamp;
        $deletedOld = 0;

        $this->info('Cleaning entries older than 1 hour...');

        do {
            $deleted = DB::table('cache')
                ->where('expiration', '<', $oldTimestamp)
                ->limit($chunkSize)
                ->delete();

            $deletedOld += $deleted;

            if ($deleted > 0) {
                $this->info("Deleted {$deleted} old cache entries (older than 1 hour, total: {$deletedOld})...");
                usleep(100000);
            }
        } while ($deleted > 0);

        if ($deletedOld > 0) {
            $this->info("Cleaned up {$deletedOld} old cache entries (older than 1 hour)");
        }

        $deletedLocks = 0;

        // Optionally clean expired cache locks
        if ($this->option('locks')) {
            $this->info('Cleaning expired cache locks...');

            do {
                $deleted = DB::table('cache_locks')
                    ->where('expiration', '<', $currentTimestamp)
                    ->limit($chunkSize)
                    ->delete();

                $deletedLocks += $deleted;

                if ($deleted > 0) {
                    $this->info("Deleted {$deleted} expired cache locks (total: {$deletedLocks})...");
                    usleep(100000);
                }
            } while ($deleted > 0);

            $this->info("Cleaned up {$deletedLocks} expired cache locks");
        }

        Log::info('Cache cleanup completed', [
            'deleted_cache_entries' => $deletedCache,
            'deleted_old_entries' => $deletedOld,
            'deleted_cache_locks' => $deletedLocks,
        ]);

        return Command::SUCCESS;
    }
}

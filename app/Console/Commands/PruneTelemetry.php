<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PruneTelemetry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:prune-telemetry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune historical camera telemetry records older than 7 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cutoff = now()->subDays(7);
        $this->info("Pruning telemetry records older than {$cutoff->toDateTimeString()}...");

        $deletedCount = 0;
        do {
            $deleted = \App\Models\CameraTelemetry::where('created_at', '<', $cutoff)
                ->limit(1000)
                ->delete();
            $deletedCount += $deleted;
        } while ($deleted > 0);

        $this->info("Total deleted telemetry records: {$deletedCount}");
        \Illuminate\Support\Facades\Log::info("TELEMETRY_PRUNED", [
            'count' => $deletedCount,
            'cutoff' => $cutoff->toDateTimeString()
        ]);

        return self::SUCCESS;
    }
}

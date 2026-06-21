<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\ImageRecord;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Auto Cleanup CCTV Images
|--------------------------------------------------------------------------
|
| Menyimpan hanya 14 hari data terbaru.
| Menghapus object MinIO dan metadata database secara bersamaan.
|
*/

Schedule::call(function () {

    Log::info('AUTO_CLEANUP_STARTED');

    $cutoff = now()->subDays(14);

    $batchSize = 32000;
    $maxDeletePerRun = 32000;

    $totalDeleted = 0;
    $totalFailed = 0;

    do {

        $records = ImageRecord::query()
            ->where('captured_at', '<', $cutoff)
            ->orderBy('id')
            ->limit($batchSize)
            ->get();

        Log::info('AUTO_CLEANUP_BATCH', [
            'count' => $records->count(),
        ]);

        if ($records->isEmpty()) {
            break;
        }

        $idsToDelete = [];
        $processed = 0;

        foreach ($records as $record) {

            $processed++;

            if ($processed % 100 === 0) {
                Log::info('AUTO_CLEANUP_PROGRESS', [
                    'processed' => $processed,
                    'total' => $records->count(),
                    'success' => count($idsToDelete),
                    'failed' => $totalFailed,
                ]);
            }

            try {

                $deleted = Storage::disk('s3')->delete($record->path);

                if ($deleted) {

                    $idsToDelete[] = $record->id;

                } else {

                    $totalFailed++;

                    Log::warning('MINIO_DELETE_FAILED', [
                        'id' => $record->id,
                        'path' => $record->path,
                    ]);
                }

            } catch (\Throwable $e) {

                $totalFailed++;

                Log::warning('MINIO_DELETE_EXCEPTION', [
                    'id' => $record->id,
                    'path' => $record->path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('AUTO_CLEANUP_DELETE_DB_START', [
            'rows' => count($idsToDelete),
        ]);

        if (!empty($idsToDelete)) {

            $deletedRows = ImageRecord::query()
                ->whereIn('id', $idsToDelete)
                ->delete();

            $totalDeleted += $deletedRows;

            Log::info('AUTO_CLEANUP_DELETE_DB_DONE', [
                'deleted_rows' => $deletedRows,
                'total_deleted' => $totalDeleted,
            ]);
        }

    } while (
        !$records->isEmpty()
        && $totalDeleted < $maxDeletePerRun
    );

    Log::info('AUTO_CLEANUP_14_DAYS', [
        'deleted_records' => $totalDeleted,
        'failed_objects' => $totalFailed,
        'cutoff' => $cutoff->toDateTimeString(),
    ]);

})
->everyMinute()
->name('cleanup-image-records')
->withoutOverlapping();




//->dailyAt('02:00')

<?php

namespace App\Console\Commands;

use Aws\S3\S3Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupOrphanMinio extends Command
{
    protected $signature = 'cleanup:orphan-minio';

    protected $description = 'Cleanup orphan MinIO objects older than 14 days';

    public function handle()
    {
        ini_set('memory_limit', '512M');

        $cutoffTimestamp = now()
            ->subDays(14)
            ->timestamp;

        Log::info('ORPHAN_CLEANUP_STARTED', [
            'cutoff_timestamp' => $cutoffTimestamp,
            'cutoff_date' => date('Y-m-d H:i:s', $cutoffTimestamp),
        ]);

        $disk = Storage::disk('s3');

        $adapter = $disk->getAdapter();

        $client = new S3Client([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);

        $bucket = env('AWS_BUCKET');

        $continuationToken = null;

        $processed = 0;
        $eligible = 0;
        $deleted = 0;
        $failed = 0;

        do {

            $params = [
                'Bucket' => $bucket,
                'Prefix' => 'camera/',
                'MaxKeys' => 1000,
            ];

            if ($continuationToken) {
                $params['ContinuationToken'] = $continuationToken;
            }

            $result = $client->listObjectsV2($params);

            foreach ($result['Contents'] ?? [] as $object) {

                $processed++;

                $key = $object['Key'];

                if (
                    !preg_match(
                        '#camera\/[^\/]+\/([0-9]+)(?:\.[0-9]+)?\.jpg$#',
                        $key,
                        $matches
                    )
                ) {
                    continue;
                }

                $timestamp = (int) $matches[1];

                if ($timestamp >= $cutoffTimestamp) {
                    continue;
                }

                $eligible++;

                try {

                    $ok = $disk->delete($key);

                    if ($ok) {

                        $deleted++;

                    } else {

                        $failed++;

                        Log::warning('ORPHAN_DELETE_FAILED', [
                            'path' => $key,
                        ]);
                    }

                } catch (\Throwable $e) {

                    $failed++;

                    Log::error('ORPHAN_DELETE_EXCEPTION', [
                        'path' => $key,
                        'error' => $e->getMessage(),
                    ]);
                }

                if ($processed % 1000 === 0) {

                    Log::info('ORPHAN_CLEANUP_PROGRESS', [
                        'processed' => $processed,
                        'eligible' => $eligible,
                        'deleted' => $deleted,
                        'failed' => $failed,
                        'last_key' => $key,
                    ]);

                    $this->info(
                        "Processed={$processed} Deleted={$deleted}"
                    );
                }
            }

            $continuationToken =
                $result['NextContinuationToken'] ?? null;

        } while ($result['IsTruncated'] ?? false);

        Log::info('ORPHAN_CLEANUP_FINISHED', [
            'processed' => $processed,
            'eligible' => $eligible,
            'deleted' => $deleted,
            'failed' => $failed,
        ]);

        $this->info("DONE");
        $this->info("Processed : {$processed}");
        $this->info("Eligible  : {$eligible}");
        $this->info("Deleted   : {$deleted}");
        $this->info("Failed    : {$failed}");

        return self::SUCCESS;
    }
}

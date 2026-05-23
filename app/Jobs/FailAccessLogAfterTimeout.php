<?php

namespace App\Jobs;

use App\Models\AccessLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FailAccessLogAfterTimeout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $accessLogId)
    {
    }

    public function handle(): void
    {
        $accessLog = AccessLog::find($this->accessLogId);

        if (!$accessLog || $accessLog->access_status !== 'pending') {
            return;
        }

        $accessLog->access_status = 'failed';
        $accessLog->notes = 'No response from device in 5 seconds.';
        $accessLog->save();
    }
}

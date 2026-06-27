<?php

namespace App\Jobs;

use App\Models\TherapySessionVideoCall;
use App\Services\SessionVideoProcessingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessSessionVideoRecordingJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $videoCallId,
    ) {}

    public function handle(SessionVideoProcessingService $processor): void
    {
        $videoCall = TherapySessionVideoCall::query()->find($this->videoCallId);
        if (! $videoCall) {
            return;
        }

        $processor->process($videoCall);
    }
}

<?php

namespace App\Jobs;

use App\Models\IvrCall;
use App\Services\Ivr\IvrGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendIvrCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // les ré-essais sont gérés par ProcessDueIvrCalls, pas par la queue

    public function __construct(public IvrCall $call) {}

    public function handle(IvrGateway $gateway): void
    {
        $this->call->increment('attempt_count');
        $this->call->update(['last_attempt_at' => now()]);

        try {
            $status = $gateway->placeCall($this->call);
            $this->call->update(['status' => $status]);
        } catch (\Throwable $e) {
            $this->call->update(['status' => 'failed']);
            report($e);
        }
    }
}

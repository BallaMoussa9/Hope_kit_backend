<?php

namespace App\Console\Commands;

use App\Jobs\SendIvrCallJob;
use App\Models\IvrCall;
use Illuminate\Console\Command;

class ProcessDueIvrCalls extends Command
{
    protected $signature = 'ivr:process-due-calls';

    protected $description = "Déclenche les appels IVR arrivés à échéance, et relance ceux restés sans réponse.";

    public function handle(): int
    {
        $maxAttempts = config('ivr.max_attempts', 3);
        $retryDelay = config('ivr.retry_delay_minutes', 120);

        // 1) Nouveaux appels arrivés à échéance
        $due = IvrCall::where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->get();

        // 2) Appels sans réponse à retenter (dans la limite de max_attempts)
        $retries = IvrCall::where('status', 'no_answer')
            ->where('attempt_count', '<', $maxAttempts)
            ->where('last_attempt_at', '<=', now()->subMinutes($retryDelay))
            ->get();

        $toProcess = $due->merge($retries);

        foreach ($toProcess as $call) {
            SendIvrCallJob::dispatch($call);
        }

        // 3) Abandon des appels qui ont dépassé le nombre de tentatives
        IvrCall::where('status', 'no_answer')
            ->where('attempt_count', '>=', $maxAttempts)
            ->update(['status' => 'cancelled']);

        $this->info("{$toProcess->count()} appel(s) mis en file d'attente.");

        return self::SUCCESS;
    }
}

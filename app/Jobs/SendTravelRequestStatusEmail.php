<?php

namespace App\Jobs;

use App\Mail\TravelRequestStatusMail;
use App\Models\TravelRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendTravelRequestStatusEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $travelRequestUuid;
    protected string $action;

    /**
     * Create a new job instance.
     */
    public function __construct(string $travelRequestUuid, string $action)
    {
        $this->travelRequestUuid = $travelRequestUuid;
        $this->action = $action;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $travelRequest = TravelRequest::with('user')->where('uuid', $this->travelRequestUuid)->first();

        if (!$travelRequest) {
            Log::warning("TravelRequest não encontrada para envio de e-mail: {$this->travelRequestUuid}");
            return;
        }

        Mail::to($travelRequest->user->email)
            ->send(new TravelRequestStatusMail($travelRequest, $this->action));
    }
}

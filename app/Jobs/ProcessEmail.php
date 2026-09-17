<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use Illuminate\Mail\Mailable;

class ProcessEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private User $user,
        private Mailable $mailable
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->user)->send($this->mailable);
    }
}
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ViewEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:view {--limit=5 : Number of emails to show}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'View stored emails from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');

        $emails = DB::table('mail')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        if ($emails->isEmpty()) {
            $this->info('No emails found in the database.');

            return;
        }

        $this->info("Showing {$emails->count()} most recent emails:");
        $this->newLine();

        foreach ($emails as $email) {
            $this->line("📧 Email #{$email->id}");
            $this->line("   From: {$email->from}");
            $this->line("   To: {$email->to}");
            $this->line("   Subject: {$email->subject}");
            $this->line("   Sent: {$email->created_at}");
            $this->newLine();
        }
    }
}

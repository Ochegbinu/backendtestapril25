<?php
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendWeeklyExpenseReport implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $admins = User::where('role', 'Admin')->get();
        

        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new \App\Mail\ExpenseReportMail($admin));
        }
    }
}

<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExpenseReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $admin;

    public function __construct($admin)
    {
        $this->admin = $admin;
    }

    public function build()
    {
        return $this->subject('Weekly Expense Report')
                    ->markdown('emails.expense.report')
                    ->with([
                        'admin' => $this->admin,
                    ]);
    }
}

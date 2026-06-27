<?php

namespace App\Console\Commands;

use App\Services\PaymentService;
use Illuminate\Console\Command;

class PaymentRemindersCommand extends Command
{
    protected $signature = 'psiconecta:payment-reminders';

    protected $description = 'Lembretes de cobranças clínicas pendentes para pacientes com portal.';

    public function handle(PaymentService $payments): int
    {
        if (! config('payment.patient_notifications_enabled', true)) {
            $this->warn(__('Notificações de pagamento ao paciente desactivadas.'));

            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($payments->paymentsDueForPatientReminder() as $payment) {
            if ($payments->notifyPatientAboutPayment($payment, 'reminder')) {
                $sent++;
            }
        }

        $this->info(__('Lembretes de pagamento enviados: :count', ['count' => $sent]));

        return self::SUCCESS;
    }
}

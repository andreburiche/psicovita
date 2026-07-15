<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Notifications\Support\BrandedMailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentAwaitingManualConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Payment $payment,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = (string) config('app.name', 'PsiConecta');
        $paymentUrl = route('payments.show', $this->payment, absolute: true);
        $amount = number_format((float) $this->payment->amount, 2, ',', '.');
        $patientName = $this->payment->patient?->name ?? __('Paciente');

        return BrandedMailMessage::create()
            ->subject($appName.' — '.__('Pagamento aguardando confirmação'))
            ->view('emails.payment-awaiting-manual-confirmation', [
                'appName' => $appName,
                'userName' => $notifiable->name,
                'patientName' => $patientName,
                'amount' => $amount,
                'paymentUrl' => $paymentUrl,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $patientName = $this->payment->patient?->name ?? __('Paciente');
        $amount = number_format((float) $this->payment->amount, 2, ',', '.');

        return [
            'payment_id' => $this->payment->id,
            'context' => 'pending_confirmation',
            'title' => __('Pagamento aguardando confirmação'),
            'message' => __(':patient indicou que pagou R$ :amount via PIX. Confirme no painel.', [
                'patient' => $patientName,
                'amount' => $amount,
            ]),
            'amount' => (float) $this->payment->amount,
            'patient_name' => $patientName,
            'action_url' => route('payments.show', $this->payment),
        ];
    }
}

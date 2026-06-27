<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\Support\BrandedMailMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PatientPaymentDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Payment $payment,
        public string $context = 'created',
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
        $paymentUrl = route('patient.payments.show', $this->payment, absolute: true);
        $amount = number_format((float) $this->payment->amount, 2, ',', '.');

        return BrandedMailMessage::create()
            ->subject($appName.' — '.$this->title())
            ->view('emails.patient-payment-due', [
                'appName' => $appName,
                'userName' => $notifiable->name,
                'amount' => $amount,
                'statusLabel' => $this->payment->status->label(),
                'body' => $this->bodyMessage(),
                'paymentUrl' => $paymentUrl,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'payment_id' => $this->payment->id,
            'context' => $this->context,
            'title' => $this->title(),
            'message' => $this->bodyMessage(),
            'amount' => (float) $this->payment->amount,
            'status' => $this->payment->status->value,
            'action_url' => route('patient.payments.show', $this->payment),
        ];
    }

    private function title(): string
    {
        return match ($this->context) {
            'overdue' => __('Cobrança em atraso'),
            'reminder' => __('Lembrete de pagamento'),
            default => __('Nova cobrança de sessão'),
        };
    }

    private function bodyMessage(): string
    {
        $amount = number_format((float) $this->payment->amount, 2, ',', '.');

        return match ($this->context) {
            'overdue' => __('A cobrança de R$ :amount está em atraso. Regularize o pagamento no portal.', ['amount' => $amount]),
            'reminder' => __('A cobrança de R$ :amount continua pendente. Pode pagar com PIX ou cartão no portal.', ['amount' => $amount]),
            default => __('Foi registada uma cobrança de R$ :amount. Consulte e pague no portal do paciente.', ['amount' => $amount]),
        };
    }
}

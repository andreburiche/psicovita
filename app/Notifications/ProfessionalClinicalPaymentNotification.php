<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\Support\BrandedMailMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProfessionalClinicalPaymentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Payment $payment,
        public string $context = 'received',
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
            ->subject($appName.' — '.$this->title())
            ->view('emails.professional-clinical-payment', [
                'appName' => $appName,
                'userName' => $notifiable->name,
                'patientName' => $patientName,
                'amount' => $amount,
                'body' => $this->bodyMessage($patientName, $amount),
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
            'context' => $this->context,
            'title' => $this->title(),
            'message' => $this->bodyMessage($patientName, $amount),
            'amount' => (float) $this->payment->amount,
            'patient_name' => $patientName,
            'action_url' => route('payments.show', $this->payment),
        ];
    }

    private function title(): string
    {
        return match ($this->context) {
            'overdue' => __('Cobrança clínica em atraso'),
            default => __('Pagamento clínico recebido'),
        };
    }

    private function bodyMessage(string $patientName, string $amount): string
    {
        return match ($this->context) {
            'overdue' => __('A cobrança de R$ :amount de :patient está em atraso no gateway.', [
                'amount' => $amount,
                'patient' => $patientName,
            ]),
            default => __(':patient pagou R$ :amount. O valor foi confirmado no gateway.', [
                'patient' => $patientName,
                'amount' => $amount,
            ]),
        };
    }
}

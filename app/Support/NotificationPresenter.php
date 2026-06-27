<?php

namespace App\Support;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

final class NotificationPresenter
{
    /**
     * @return array{
     *     title: string,
     *     message: string,
     *     action_url: ?string,
     *     is_unread: bool,
     *     created_at: ?\Illuminate\Support\Carbon,
     *     icon: string,
     *     tone: string,
     * }
     */
    public static function present(DatabaseNotification $notification): array
    {
        $data = is_array($notification->data) ? $notification->data : [];

        $title = filled($data['title'] ?? null)
            ? (string) $data['title']
            : self::defaultTitle($notification, $data);

        $message = filled($data['message'] ?? null)
            ? (string) $data['message']
            : self::defaultMessage($notification, $data);

        return [
            'title' => $title,
            'message' => $message,
            'action_url' => filled($data['action_url'] ?? null) ? (string) $data['action_url'] : null,
            'is_unread' => $notification->read_at === null,
            'created_at' => $notification->created_at,
            'icon' => self::iconFor($notification),
            'tone' => self::toneFor($notification),
        ];
    }

    private static function toneFor(DatabaseNotification $notification): string
    {
        return match (self::typeKey($notification)) {
            'newconversationmessagenotification' => 'message',
            'subscriptionexpiringnotification' => 'subscription',
            'subscriptionpaymentconfirmedadminnotification' => 'subscription',
            'patientpaymentduenotification', 'professionalclinicalpaymentnotification' => 'payment',
            'clinicteaminvitationnotification', 'patientportalinvitationnotification',
            'sessiongroupmemberinvitenotification', 'sessionfamilyguestinvitenotification',
            'sessionobserverinvitenotification' => 'invite',
            'whatsappconsentremindernotification' => 'whatsapp',
            'professionaldailyagendanotification', 'professionalupcomingsessionremindernotification',
            'therapysessiontomorrowreminder' => 'schedule',
            default => 'system',
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function defaultTitle(DatabaseNotification $notification, array $data): string
    {
        if (filled($data['sender_name'] ?? null)) {
            return __('Nova mensagem de :name', ['name' => $data['sender_name']]);
        }

        return match (self::typeKey($notification)) {
            'subscriptionexpiringnotification' => __('Assinatura'),
            'patientpaymentduenotification' => __('Pagamento'),
            'professionalclinicalpaymentnotification' => __('Pagamento clínico'),
            'clinicteaminvitationnotification' => __('Convite da clínica'),
            'patientportalinvitationnotification' => __('Convite do portal'),
            'whatsappconsentremindernotification' => __('WhatsApp'),
            default => __('Notificação'),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function defaultMessage(DatabaseNotification $notification, array $data): string
    {
        if (filled($data['preview'] ?? null)) {
            return (string) $data['preview'];
        }

        if (filled($data['body'] ?? null)) {
            return (string) $data['body'];
        }

        return match (self::typeKey($notification)) {
            'subscriptionexpiringnotification' => __('Confira os detalhes da sua assinatura.'),
            'patientpaymentduenotification' => __('Há uma cobrança pendente na sua conta.'),
            default => '',
        };
    }

    private static function iconFor(DatabaseNotification $notification): string
    {
        return match (self::typeKey($notification)) {
            'newconversationmessagenotification' => 'messages',
            'subscriptionexpiringnotification' => 'banknote',
            'patientpaymentduenotification', 'professionalclinicalpaymentnotification' => 'wallet',
            'clinicteaminvitationnotification', 'patientportalinvitationnotification' => 'users',
            'whatsappconsentremindernotification' => 'plug',
            'professionaldailyagendanotification', 'professionalupcomingsessionremindernotification',
            'therapysessiontomorrowreminder' => 'calendar',
            default => 'bell',
        };
    }

    private static function typeKey(DatabaseNotification $notification): string
    {
        return Str::lower(class_basename((string) $notification->type));
    }

    public static function indexUrl(\App\Models\User $user): string
    {
        $routeName = $user->defaultAppRouteName();

        if (in_array($routeName, ['dashboard', 'patient.home'], true)) {
            return route($routeName).'#notificacoes';
        }

        return route('dashboard').'#notificacoes';
    }
}

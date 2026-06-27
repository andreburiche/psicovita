{{ $appName }} — {{ __('Redefinir palavra-passe') }}

{{ __('Olá, :name!', ['name' => $userName]) }}

{{ __('Recebemos um pedido para redefinir a palavra-passe da sua conta no :app.', ['app' => $appName]) }}

{{ __('Utilize a ligação abaixo. O link expira em :count minutos.', ['count' => $expireMinutes]) }}

{{ $resetUrl }}

{{ __('Se não solicitou esta alteração, ignore este e-mail. A sua palavra-passe permanece inalterada.') }}

— {{ $appName }}

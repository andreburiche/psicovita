{{ $appName }} — {{ __('Confirmar e-mail') }}

{{ __('Olá, :name!', ['name' => $userName]) }}

{{ __('Obrigado por se juntar ao :app. Para concluir o registo, confirme o endereço de e-mail visitando a ligação abaixo.', ['app' => $appName]) }}

{{ $verificationUrl }}

{{ __('Se não criou uma conta no :app, ignore este e-mail.', ['app' => $appName]) }}

— {{ $appName }}

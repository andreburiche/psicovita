# PsiConecta (PsicoVita)

Plataforma Laravel para gestão clínica de psicologia e portal do paciente.

## Documentação

Toda a documentação está em **[docs/](docs/README.md)**:

| Documento | Descrição |
|-----------|-----------|
| [docs/README.md](docs/README.md) | Índice e visão geral |
| [docs/APLICACAO.md](docs/APLICACAO.md) | Funcionalidades, regras, ERD, rotas |
| [docs/REQUISITOS.md](docs/REQUISITOS.md) | Requisitos funcionais e não funcionais |
| [docs/INSTALACAO.md](docs/INSTALACAO.md) | Instalação e deploy |
| [docs/CONFIGURACAO.md](docs/CONFIGURACAO.md) | Variáveis de ambiente |
| [docs/COMUNICACAO.md](docs/COMUNICACAO.md) | Conversas, WhatsApp, chatbot, notificações |

## Início rápido

```bash
composer install
cp .env.example .env   # ou copy no Windows
php artisan key:generate
php artisan migrate
npm ci && npm run build
php artisan serve --port=8080
```

## Stack

Laravel 12 · PHP 8.2+ · Blade · Tailwind · Alpine.js · Asaas · Jitsi · Sanctum · Socialite

## Licença

MIT

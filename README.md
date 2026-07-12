# PsiConecta (PsicoVita)

Plataforma Laravel para gestão clínica de psicologia e portal do paciente.

**Marca comercial:** PsicoVita · **Código:** PsiConecta  
**Repositório:** [github.com/andreburiche/psicovita](https://github.com/andreburiche/psicovita)

---

## Requisitos e versões

### Runtime (mínimo / alvo)

| Componente | Versão exigida | Notas |
|------------|----------------|--------|
| **PHP** | `^8.2` | Imagem Docker: `php:8.2-apache-bookworm`. Extensões: `mbstring`, `openssl`, `pdo`, `pdo_mysql` / `sqlite3`, `curl`, `gd`, `zip`, `intl`, `opcache` |
| **Composer** | `2.x` | Gestão de dependências PHP |
| **Node.js** | `18+` (recomendado 20/24 LTS) | Build de assets com Vite |
| **npm** | `9+` | Incluído com Node |
| **MySQL** | `8.0` | Produção / Docker (`mysql:8.0`). Em dev local também pode usar **SQLite** |
| **Servidor web** | Apache 2.4+ ou Nginx | Document root = pasta `public/` |

### Ambiente verificado (desenvolvimento)

| Ferramenta | Versão observada |
|------------|------------------|
| PHP | 8.2.12 |
| Laravel | 12.58.0 |
| Composer | 2.9.7 |
| Node.js | 24.15.0 |
| npm | 11.12.1 |

---

## Stack da aplicação

### Backend (PHP / Composer)

| Pacote | Versão (constraint) | Função |
|--------|---------------------|--------|
| `laravel/framework` | `^12.0` | Framework principal |
| `laravel/sanctum` | `^4.3` | API tokens / autenticação SPA |
| `laravel/socialite` | `^5.28` | Login social (Google) |
| `laravel/tinker` | `^2.10.1` | REPL Artisan |
| `barryvdh/laravel-dompdf` | `^3.1` | Geração de PDF |
| `phpoffice/phpspreadsheet` | `^5.8` | Exportação Excel |

**Dev:**

| Pacote | Versão | Função |
|--------|--------|--------|
| `phpunit/phpunit` | `^11.5.3` | Testes |
| `laravel/breeze` | `^2.4` | Scaffold auth (Blade) |
| `laravel/pint` | `^1.13` | Formatação de código |
| `laravel/sail` | `^1.41` | Ambiente Docker opcional |
| `laravel/pail` | `^1.2.2` | Logs em tempo real |
| `fakerphp/faker` | `^1.23` | Dados de teste |
| `mockery/mockery` | `^1.6` | Mocks |
| `nunomaduro/collision` | `^8.6` | Erros no CLI |

### Frontend (Node / npm)

| Pacote | Versão | Função |
|--------|--------|--------|
| `vite` | `^6.0.11` | Bundler |
| `laravel-vite-plugin` | `^1.2.0` | Integração Laravel ↔ Vite |
| `tailwindcss` | `^3.1.0` | CSS utility |
| `@tailwindcss/forms` | `^0.5.2` | Estilos de formulário |
| `@tailwindcss/vite` | `^4.0.0` | Plugin Vite (opcional) |
| `alpinejs` | `^3.4.2` | Interatividade no Blade |
| `axios` | `^1.7.4` | HTTP no browser |
| `imask` | `^7.6.1` | Máscaras de input |
| `postcss` / `autoprefixer` | `^8.4` / `^10.4` | Pipeline CSS |
| `concurrently` | `^9.0.1` | Scripts `composer dev` |

**UI:** Blade · Tailwind CSS · Alpine.js

### Docker (`docker-compose.yml`)

| Serviço | Imagem | Porta local |
|---------|--------|-------------|
| App (Apache + PHP 8.2) | build `Dockerfile` | `8080 → 80` |
| **MySQL** | `mysql:8.0` | `3307 → 3306` |
| **Evolution API** (WhatsApp) | `evoapicloud/evolution-api:v2.3.7` | `8082 → 8080` |
| Postgres (Evolution) | `postgres:15-alpine` | interno |
| Redis (Evolution) | `redis:7-alpine` | interno |

### Integrações externas

| Integração | Uso |
|------------|-----|
| **Asaas** | Assinaturas SaaS, PIX, cartão, webhooks |
| **Jitsi** (`meet.jit.si` ou self-hosted) | Teleconsulta / vídeo |
| **Google OAuth** (Socialite) | Login social |
| **WhatsApp** — Meta Cloud API ou Evolution API | Mensagens e chatbot |
| **IA** — OpenAI, Ollama (local) ou `mock` | Assistente clínico / textos |
| **Filas** | `database` / `sync` / Redis (conforme `.env`) |

### App móvel (repositório separado)

App Flutter do paciente: pasta / projecto `psiconecta_app` (fora deste README da API web).

---

## Documentação

### Manuais de utilizador e operação

| Documento | Descrição |
|-----------|-----------|
| [documentos/README.md](documentos/README.md) | Índice dos manuais |
| [documentos/MANUAL_PACIENTE.md](documentos/MANUAL_PACIENTE.md) | Portal do paciente |
| [documentos/MANUAL_ADMINISTRADOR.md](documentos/MANUAL_ADMINISTRADOR.md) | Admin / LGPD / assinaturas |
| [documentos/MANUAL_TECNICO.md](documentos/MANUAL_TECNICO.md) | Instalação, `.env`, deploy |

### Documentação de produto e engenharia

| Documento | Descrição |
|-----------|-----------|
| [docs/README.md](docs/README.md) | Índice e visão geral |
| [docs/APLICACAO.md](docs/APLICACAO.md) | Funcionalidades, regras, ERD, rotas |
| [docs/REQUISITOS.md](docs/REQUISITOS.md) | Requisitos funcionais e não funcionais |
| [docs/INSTALACAO.md](docs/INSTALACAO.md) | Instalação e deploy |
| [docs/CONFIGURACAO.md](docs/CONFIGURACAO.md) | Variáveis de ambiente |
| [docs/COMUNICACAO.md](docs/COMUNICACAO.md) | Conversas, WhatsApp, chatbot, notificações |

---

## Início rápido

```bash
composer install
cp .env.example .env   # ou copy no Windows
php artisan key:generate
php artisan migrate
npm ci && npm run build
php artisan serve --port=8080
```

Com Docker (app + MySQL):

```bash
docker compose up -d --build
```

Evolution (WhatsApp local):

```bash
docker compose up -d evolution
```

---

## Perfis

| Perfil | Área |
|--------|------|
| Paciente | `/area-paciente` |
| Profissional | `/dashboard` |
| Administrador | `/admin/...` |

---

## Licença

MIT

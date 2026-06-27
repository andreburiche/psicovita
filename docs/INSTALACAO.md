# PsiConecta — Instalação e deploy

> Guia para ambiente local (Windows/XAMPP) e checklist de produção.

---

## 1. Pré-requisitos

| Software | Versão mínima |
|----------|---------------|
| PHP | 8.2+ (extensões: mbstring, openssl, pdo, sqlite3 ou pdo_mysql, curl, gd, zip) |
| Composer | 2.x |
| Node.js | 18+ (para build de assets) |
| MySQL ou SQLite | SQLite incluído no XAMPP para dev |

**Opcional (integrações):**
- Docker — Evolution API (WhatsApp)
- Conta Asaas (sandbox/produção)
- Google Cloud Console — login social

---

## 2. Instalação local (XAMPP)

### 2.1 Clonar / copiar o projecto

```text
c:\xampp\htdocs\PsiConecta
```

### 2.2 Dependências PHP

```bash
cd c:\xampp\htdocs\PsiConecta
composer install
```

### 2.3 Ambiente

```bash
copy .env.example .env
php artisan key:generate
```

Edite `.env`:
- `APP_URL=http://127.0.0.1:8080`
- `DB_CONNECTION=sqlite` (ou MySQL com credenciais XAMPP)

Crie a base SQLite se necessário:

```bash
# Windows PowerShell
New-Item -ItemType File -Force database\database.sqlite
```

### 2.4 Base de dados

```bash
php artisan migrate
php artisan db:seed   # opcional: dados demo
```

### 2.5 Frontend

```bash
npm ci
npm run build
```

Em desenvolvimento com hot reload:

```bash
npm run dev
```

### 2.6 Servidor

```bash
php artisan serve --port=8080
```

Aceda a: `http://127.0.0.1:8080`

> Alternativa: configurar Virtual Host Apache apontando para `public/`.

### 2.7 Scheduler e filas (recomendado)

Em produção, configure cron:

```cron
* * * * * cd /caminho/PsiConecta && php artisan schedule:run >> /dev/null 2>&1
```

Worker de filas (se `QUEUE_CONNECTION=database`):

```bash
php artisan queue:work
```

---

## 3. Pós-instalação (bases existentes / LGPD)

Se migrar dados legados com campos em texto claro:

```bash
php artisan psiconecta:encrypt-cpf
php artisan psiconecta:encrypt-patient-contacts
php artisan psiconecta:encrypt-user-emails
php artisan psiconecta:encrypt-message-bodies
```

---

## 4. Contas demo (ambiente local)

Com seeders activos, na página de login (ambiente `local`) aparecem botões de teste:

| Perfil | E-mail | Senha |
|--------|--------|-------|
| Profissional | `profissional@psiconecta.test` | `password` |
| Paciente | `paciente@psiconecta.test` | `password` |
| Admin | `admin@psiconecta.local` | `password` |

---

## 5. Integrações — ordem sugerida

1. **Mail** — `MAIL_MAILER=log` em dev; SMTP em produção
2. **Asaas sandbox** — assinatura e pagamentos (`ASAAS_*`)
3. **Google OAuth** — login social (`GOOGLE_*`) — ver [CONFIGURACAO.md](CONFIGURACAO.md)
4. **Jitsi** — teleconsulta (default `meet.jit.si` funciona sem config)
5. **WhatsApp Evolution** — Docker + `php artisan psiconecta:evolution-webhook-sync`
6. **OpenAI / Ollama** — IA assistente (`AI_PROVIDER`, `OPENAI_*`)

---

## 6. Checklist de produção

| Item | Acção |
|------|-------|
| `APP_ENV=production` | Desactivar debug |
| `APP_DEBUG=false` | — |
| `APP_KEY` | Gerado e secreto |
| HTTPS | Certificado válido; `APP_URL` com `https://` |
| Base de dados | MySQL com backups |
| `php artisan config:cache` | Após deploy |
| `php artisan route:cache` | Opcional |
| `npm run build` | Assets compilados |
| Permissões | `storage/` e `bootstrap/cache/` graváveis |
| Scheduler | Cron activo |
| Webhook Asaas | URL pública `POST /webhooks/asaas` |
| Webhook WhatsApp | URLs em Meta ou Evolution |
| LGPD | `LGPD_DPO_EMAIL` real; políticas publicadas |

---

## 7. Testes

```bash
php artisan test
```

Executar antes de cada deploy. Testes críticos:

```bash
php artisan test --filter=SubscriptionTest
php artisan test --filter=PatientPaymentPortalTest
php artisan test --filter=SocialAuthTest
php artisan test --filter=ComplianceTest
```

---

## 8. Resolução de problemas

| Problema | Solução |
|----------|---------|
| Página sem estilos | `npm run build` ou `npm run dev` |
| `419 Page Expired` | Limpar cookies; verificar `SESSION_DOMAIN` |
| Migration falha SQLite | `php artisan migrate:fresh` **só em dev** |
| OAuth redirect mismatch | `APP_URL` = URL do browser; URI igual no Google Console |
| WhatsApp sem resposta | `CHATBOT_WHATSAPP_ENABLED=true`; sync webhook Evolution |
| Asaas 401 | `ASAAS_API_KEY` do ambiente correcto (sandbox vs produção) |

---

*Variáveis detalhadas: [CONFIGURACAO.md](CONFIGURACAO.md)*

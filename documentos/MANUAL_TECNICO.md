# Manual Técnico — PsicoVita / PsiConecta

> Guia para instalação, configuração, deploy e operação.  
> Público: desenvolvedores, DevOps e suporte técnico.  
> Versão: julho de 2026

---

## 1. Visão da arquitectura

```
[Browser / App Flutter]
        │ HTTPS
        ▼
[Laravel 12 — PsiConecta]
  ├── MySQL
  ├── Filas (database) + Scheduler (cron)
  ├── Asaas (pagamentos) — opcional
  ├── WhatsApp (Meta ou Evolution) — opcional
  ├── IA (OpenAI / Ollama local / mock)
  └── Jitsi (teleconsulta)
```

| Camada | Tecnologia |
|--------|------------|
| Backend | Laravel 12, PHP 8.2+ |
| Frontend web | Blade, Tailwind, Alpine, Vite |
| Auth | Breeze (web) + Sanctum (API) + Socialite |
| BD | MySQL (produção) / SQLite (testes) |
| App móvel | Flutter (`psiconecta_app`, fora do deploy web) |

Documentação de domínio: [docs/APLICACAO.md](../docs/APLICACAO.md)

---

## 2. Requisitos

| Software | Mínimo |
|----------|--------|
| PHP | 8.2+ (`mbstring`, `openssl`, `pdo_mysql`, `curl`, `gd`, `zip`) |
| Composer | 2.x |
| Node.js | 18+ (build de assets) |
| MySQL | 8.x (produção) |
| Extensão opcional | Docker (Evolution API) |

---

## 3. Instalação local (XAMPP)

```powershell
cd c:\xampp\htdocs\PsiConecta
composer install
copy .env.example .env
php artisan key:generate
# Configurar MySQL no .env
php artisan migrate
php artisan db:seed   # opcional
npm ci
npm run build
php artisan storage:link
php artisan serve --host=0.0.0.0 --port=8080
```

Detalhes: [docs/INSTALACAO.md](../docs/INSTALACAO.md)

### Contas demo (após seed, só local)

| Perfil | E-mail | Senha |
|--------|--------|-------|
| Admin | `admin@psiconecta.local` | `password` |
| Profissional | `profissional@psiconecta.test` | `password` |
| Paciente | `paciente@psiconecta.test` | `password` |

---

## 4. Variáveis de ambiente críticas

Referência completa: [docs/CONFIGURACAO.md](../docs/CONFIGURACAO.md)

### 4.1 Local vs HostGator produção

| Variável | Local | Produção HostGator |
|----------|-------|---------------------|
| `APP_ENV` | `local` | `production` |
| `APP_DEBUG` | `true` | `false` |
| `APP_URL` | `http://127.0.0.1:8080` | `https://psicovita.online` (sem `/public`) |
| `LOG_LEVEL` | `debug` | `error` |
| `DB_*` | XAMPP root | Credenciais cPanel |
| `AI_PROVIDER` | `ollama` / `openai` / `claude` / `gemini` | `mock` ou `openai` |
| `WHATSAPP_ENABLED` | opcional Evolution local | `false` ou Meta/VPS |
| `GOOGLE_REDIRECT_URI` | `http://127.0.0.1:8080/...` | `https://dominio/auth/google/callback` |

### 4.2 Assinaturas

| Variável | Efeito |
|----------|--------|
| `SUBSCRIPTION_TRIAL_DAYS` | Duração do trial (default 14) |
| `SUBSCRIPTION_MANUAL_ACTIVATION` | Admin pode validar pagamentos |
| `SUBSCRIPTION_REQUIRE_ADMIN_AFTER_PAYMENT` | Activa plano só com pagamento **+** admin |

### 4.3 IA

| Valor `AI_PROVIDER` | Uso |
|---------------------|-----|
| `mock` | Sem custo — respostas simuladas (recomendado no Start) |
| `openai` / `chatgpt` | API OpenAI — `OPENAI_API_KEY` |
| `claude` / `anthropic` | Anthropic — `CLAUDE_API_KEY` ou `ANTHROPIC_API_KEY` |
| `gemini` / `google` | Google AI — `GEMINI_API_KEY` |
| `ollama` | Só máquina local — `OPENAI_BASE_URL=http://127.0.0.1:11434/v1` |

Script CPU local (se CUDA falhar): `scripts/ollama-cpu.ps1`

### 4.4 WhatsApp

| Driver | Variáveis |
|--------|-----------|
| `meta` | `WHATSAPP_ACCESS_TOKEN`, `PHONE_NUMBER_ID`, webhook verify |
| `evolution` | `EVOLUTION_API_URL`, `API_KEY`, `INSTANCE`, `WEBHOOK_URL` |

Evolution em Docker: `docker compose up -d evolution` (porta **8082**).  
**Não** roda no Plano Start HostGator — precisa VPS ou Meta Cloud API.

---

## 5. Deploy na HostGator (Plano Start)

### 5.1 Pré-requisitos no cPanel

1. Domínio com HTTPS (SSL).  
2. MySQL: criar base + utilizador.  
3. PHP 8.2+.  
4. Document Root = pasta **`public/`** do Laravel (evitar `/public` no URL).  

### 5.2 Enviar código

- Git clone / upload **sem** `.env` local, **sem** `node_modules`, idealmente **com** `vendor` se não houver Composer no servidor, ou `composer install --no-dev` via SSH.  
- Build local: `npm ci && npm run build` → enviar `public/build/`.  

### 5.3 `.env` de produção (mínimo)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://psicovita.online
APP_KEY=base64:...

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

AI_PROVIDER=mock
WHATSAPP_ENABLED=false
CHATBOT_WHATSAPP_ENABLED=false

GOOGLE_REDIRECT_URI=https://psicovita.online/auth/google/callback
```

### 5.4 Comandos pós-deploy

```bash
php artisan key:generate          # só se APP_KEY vazio
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Permissões: `storage/` e `bootstrap/cache/` graváveis.

### 5.5 Cron (obrigatório)

```cron
* * * * * cd /home/USUARIO/caminho/PsiConecta && php artisan schedule:run >> /dev/null 2>&1
```

Filas (se `QUEUE_CONNECTION=database`):

```cron
* * * * * cd /home/USUARIO/caminho/PsiConecta && php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1
```

### 5.6 Erro `/public/public/`

Causa: Document Root na pasta errada + `APP_URL` com `/public`.  
Solução: Document Root → `.../public` e `APP_URL=https://dominio` sem sufixo.

Existe `.htaccess` na raiz do projecto para reescrever para `public/` se o Document Root for a raiz do Laravel.

---

## 6. Scheduler e jobs relevantes

| Comando | Frequência típica |
|---------|-------------------|
| `psiconecta:professional-daily-agenda` | Diário (ex.: 07:00) |
| `psiconecta:professional-session-upcoming` | Cada minuto |
| `psiconecta:session-reminders` | Diário 18:00 |
| `psiconecta:expire-subscriptions` | Diário 00:30 |
| `psiconecta:subscription-reminders` | Diário 08:00 |
| `psiconecta:payment-reminders` | Diário 09:00 |
| `psiconecta:compliance-prune` | Mensal |

Definição: `bootstrap/app.php` → `withSchedule`.

---

## 7. Integrações

### 7.1 Asaas

- Webhook: `POST /webhooks/asaas`  
- Configurar token (`ASAAS_WEBHOOK_TOKEN`)  
- Sandbox vs produção: `ASAAS_ENVIRONMENT`  

### 7.2 Google OAuth

- Redirect autorizado = `GOOGLE_REDIRECT_URI` exacto (ex.: `https://psicovita.online/auth/google/callback`)
- `APP_URL` deve ser a URL pública sem `/public` no fim
- `InvalidStateException`: sessão perdida / F5 no callback / URI incorrecta  

**Erro `Not Acceptable!` / Mod_Security no login Google**

O WAF da HostGator bloqueia o callback com `?code=` e `state=`. O ficheiro `public/.htaccess` já tenta isentar esse caminho.

Se continuar a falhar:

1. **cPanel → Security → ModSecurity** → desactivar para o domínio `psicovita.online` (ou só testar).
2. Pedir ao suporte HostGator whitelist de:  
   `https://psicovita.online/auth/google/callback`
3. Confirmar no Google Cloud Console a URI exacta (HTTPS, sem `/public`).
4. No `.env` de produção:
   ```env
   APP_URL=https://psicovita.online
   GOOGLE_REDIRECT_URI=https://psicovita.online/auth/google/callback
   ```
5. Depois de alterar `.env`: limpar cache (`php artisan config:clear`) ou apagar `bootstrap/cache/config.php`.

### 7.3 Evolution API (local)

```powershell
cd c:\xampp\htdocs\PsiConecta
docker compose up -d evolution
.\scripts\evolution-setup.ps1
php artisan psiconecta:evolution-webhook-sync
```

Manager: `http://127.0.0.1:8082/manager`

### 7.4 API REST

- Base: `/api/v1`  
- Spec: `/api/v1/openapi.json`  
- Auth: Sanctum  

---

## 8. Segurança operacional

1. Nunca commitir `.env`.  
2. `APP_DEBUG=false` em produção.  
3. Não alterar `APP_KEY` se já houver dados encriptados (e-mails, CPF, mensagens).  
4. HTTPS obrigatório em produção.  
5. Rotacionar secrets se forem expostos (Gmail App Password, Google Client Secret, Asaas).  
6. Páginas de erro: `resources/views/errors/{404,403,419,429,500,503}.blade.php`.  

---

## 9. Testes

```bash
php artisan test
php artisan test --filter=AdminProfessionalSubscriptionTest
php artisan test --filter=SubscriptionTest
php artisan test --filter=PatientApiTest
```

---

## 10. App Flutter

Pasta típica: `c:\xampp\htdocs\psiconecta_app` (fora do Laravel).

```env
API_BASE_URL=https://psicovita.online/api/v1
```

Não se publica na HostGator — vai para lojas (Play/App Store).

---

## 11. Checklist de go-live

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` HTTPS correcto  
- [ ] MySQL cPanel + migrate  
- [ ] `npm run build` e assets em `public/build`  
- [ ] Document Root = `public/`  
- [ ] Cron `schedule:run`  
- [ ] Mail SMTP a funcionar  
- [ ] Google OAuth com URI de produção  
- [ ] IA: `mock` ou OpenAI  
- [ ] WhatsApp: desligado ou Meta/VPS  
- [ ] SSL válido  
- [ ] Seeders de demo **não** executados em produção  

---

## 12. Referências rápidas

| Recurso | Caminho |
|---------|---------|
| Índice docs engenharia | [docs/README.md](../docs/README.md) |
| Manual paciente | [MANUAL_PACIENTE.md](MANUAL_PACIENTE.md) |
| Manual administrador | [MANUAL_ADMINISTRADOR.md](MANUAL_ADMINISTRADOR.md) |
| Docker / Evolution | `docker-compose.yml`, `scripts/evolution-*.ps1` |
| Ollama CPU | `scripts/ollama-cpu.ps1` |

---

*Mantenha este manual alinhado com alterações de deploy, `.env` e integrações.*

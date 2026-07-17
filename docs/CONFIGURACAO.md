# PsiConecta — Configuração

> Referência de variáveis de ambiente (`.env`) e ficheiros `config/`.

---

## 1. Aplicação

| Variável | Default | Descrição |
|----------|---------|-----------|
| `APP_NAME` | Laravel | Nome exibido na UI |
| `APP_ENV` | local | `local`, `production`, etc. |
| `APP_KEY` | — | **Obrigatório** (`php artisan key:generate`) |
| `APP_DEBUG` | true | `false` em produção |
| `APP_URL` | — | URL base (ex.: `http://127.0.0.1:8080`) |
| `APP_PUBLIC_URL` | = APP_URL | URL pública para links em e-mails |
| `APP_LOCALE` | pt_BR | Idioma da interface |
| `SERVER_PORT` | 8080 | Porta do `artisan serve` |

---

## 2. Base de dados e sessão

| Variável | Descrição |
|----------|-----------|
| `DB_CONNECTION` | `sqlite` ou `mysql` |
| `DB_DATABASE` | Caminho SQLite ou nome MySQL |
| `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD` | MySQL |
| `SESSION_DRIVER` | `database` recomendado |
| `SESSION_LIFETIME` | Minutos (default 120) |
| `QUEUE_CONNECTION` | `database` para jobs |
| `CACHE_STORE` | `database` |

---

## 3. E-mail

| Variável | Descrição |
|----------|-----------|
| `MAIL_MAILER` | `log` (dev), `smtp`, `ses`, `resend` |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` | SMTP |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Remetente |

---

## 4. LGPD

| Variável | Descrição |
|----------|-----------|
| `LGPD_DPO_NAME` | Nome do encarregado |
| `LGPD_DPO_EMAIL` | E-mail DPO (acesso admin LGPD) |
| `LGPD_COMPANY_NAME` | Razão social na documentação |
| `LGPD_RESPONSE_SLA_DAYS` | SLA resposta titulares (default 15) |
| `LGPD_DSR_RETENTION_DAYS` | Retenção pedidos DSR |
| `LGPD_AUDIT_RETENTION_DAYS` | Retenção logs auditoria |
| `LGPD_AUDIT_EXPORT_INCLUDE_USER_EMAIL` | Export CSV inclui e-mail |
| `LGPD_AUDIT_EXPORT_INCLUDE_IP_ADDRESS` | Export CSV inclui IP |

---

## 5. Asaas — pagamentos e assinatura

| Variável | Descrição |
|----------|-----------|
| `ASAAS_ENABLED` | `true` para chamadas HTTP reais |
| `ASAAS_API_KEY` | Token API |
| `ASAAS_ENVIRONMENT` | `sandbox` ou `production` |
| `ASAAS_BASE_URL` | URL API (sandbox/prod) |
| `ASAAS_WEBHOOK_TOKEN` | Validação `POST /webhooks/asaas` |
| `ASAAS_SPLIT_ENABLED` | Repasse ao profissional em cobranças |
| `ASAAS_PLATFORM_WALLET_ID` | Carteira da plataforma (split) |
| `ASAAS_CONNECT_ENABLED` | Criação subconta no perfil |
| `ASAAS_CONNECT_POSTAL_CODE`, `ADDRESS`, etc. | Morada default Connect |
| `ASAAS_PIX_FALLBACK_*` | QR estático se Asaas indisponível |
| `PAYMENT_PLATFORM_FEE_PERCENT` | Comissão % (ex.: 10) |
| `PAYMENT_DEFAULT_SESSION_AMOUNT` | Valor default sessão (R$) |
| `PAYMENT_AUTO_CHARGE_ON_SESSION` | Gera cobrança ao criar sessão |
| `PAYMENT_PATIENT_NOTIFICATIONS_ENABLED` | Notifica cobrança ao paciente |
| `PAYMENT_PATIENT_REMINDER_DAYS` | Dias até lembrete |
| `PAYMENT_PROFESSIONAL_NOTIFICATIONS_ENABLED` | Notifica profissional |
| `SUBSCRIPTION_TRIAL_DAYS` | Trial (default 14) |
| `SUBSCRIPTION_EXPIRING_SOON_DAYS` | Janela aviso expiração |
| `SUBSCRIPTION_ANNUAL_DISCOUNT_PERCENT` | Desconto anual fallback |
| `CLINIC_MAX_TEAM_MEMBERS` | Máx. membros equipa |
| `CLINIC_INVITATION_EXPIRES_DAYS` | Validade convite equipa |

---

## 6. Inteligência artificial

| Variável | Default | Descrição |
|----------|---------|-----------|
| `AI_ASSISTANT_ENABLED` | true | Assistente clínico |
| `AI_PROVIDER` | openai | `openai` / `chatgpt` · `claude` / `anthropic` · `gemini` / `google` · `ollama` · `mock` |
| `OPENAI_API_KEY` | — | Chave OpenAI (quando `AI_PROVIDER=openai`) |
| `OPENAI_BASE_URL` | api.openai.com/v1 | Para Ollama: `http://127.0.0.1:11434/v1` |
| `OPENAI_CHAT_MODEL` | gpt-4o-mini | Modelo chat OpenAI/Ollama |
| `OPENAI_TRANSCRIBE_MODEL` | whisper-1 | Transcrição áudio (só OpenAI Whisper) |
| `OPENAI_TIMEOUT` / `AI_TIMEOUT` | 120 | Timeout HTTP (segundos) |
| `CLAUDE_API_KEY` ou `ANTHROPIC_API_KEY` | — | Chave Anthropic (`AI_PROVIDER=claude`) |
| `CLAUDE_CHAT_MODEL` | claude-sonnet-4-20250514 | Modelo Claude |
| `CLAUDE_BASE_URL` | api.anthropic.com | Base Anthropic |
| `GEMINI_API_KEY` | — | Chave Google AI (`AI_PROVIDER=gemini`) |
| `GEMINI_CHAT_MODEL` | gemini-2.0-flash | Modelo Gemini |
| `GEMINI_BASE_URL` | generativelanguage.googleapis.com/v1beta | Base Gemini |

**Notas:** troca de provedor = alterar `AI_PROVIDER` + chave correspondente + `php artisan config:clear`. Transcrição de áudio real continua só com OpenAI (Whisper); Claude/Gemini/Ollama usam simulação na transcrição.

---

## 7. Teleconsulta (Jitsi)

| Variável | Default | Descrição |
|----------|---------|-----------|
| `JITSI_DOMAIN` | meet.jit.si | Domínio Jitsi |
| `JITSI_ROOM_PREFIX` | psiconecta | Prefixo salas |
| `SESSION_RECORDING_DISK` | local | Disco gravações |

---

## 8. WhatsApp

| Variável | Descrição |
|----------|-----------|
| `WHATSAPP_ENABLED` | Activa integração |
| `WHATSAPP_DRIVER` | `meta` ou `evolution` |
| `WHATSAPP_DEFAULT_CALLING_CODE` | `55` (Brasil) |
| `WHATSAPP_WEBHOOK_VERIFY_TOKEN` | Verificação webhook Meta |
| `WHATSAPP_API_URL` | Graph API URL |
| `WHATSAPP_ACCESS_TOKEN` | Token Meta |
| `WHATSAPP_PHONE_NUMBER_ID` | ID número Meta |
| `EVOLUTION_API_URL` | URL Evolution (ex.: `http://127.0.0.1:8082`) |
| `EVOLUTION_API_KEY` | API key Evolution |
| `EVOLUTION_INSTANCE` | Nome instância |
| `EVOLUTION_WEBHOOK_TOKEN` | Token opcional webhook |

**Comando sync webhook Evolution:**

```bash
php artisan psiconecta:evolution-webhook-sync
```

---

## 9. Chatbot

| Variável | Default | Descrição |
|----------|---------|-----------|
| `CHATBOT_ENABLED` | true | Motor chatbot |
| `CHATBOT_WIDGET_ENABLED` | true | Widget no site |
| `CHATBOT_WHATSAPP_ENABLED` | false | Respostas automáticas WhatsApp |
| `CHATBOT_AI_ENABLED` | false | LLM para intents/sentimento |

---

## 10. Login social

| Variável | Descrição |
|----------|-----------|
| `GOOGLE_CLIENT_ID` | OAuth Google (vazio = botão oculto) |
| `GOOGLE_CLIENT_SECRET` | Segredo Google |
| `GOOGLE_REDIRECT_URI` | Default: `{APP_URL}/auth/google/callback` |
| `FACEBOOK_CLIENT_ID` | OAuth Facebook |
| `FACEBOOK_CLIENT_SECRET` | Segredo Facebook |
| `FACEBOOK_REDIRECT_URI` | Default: `{APP_URL}/auth/facebook/callback` |

### Google Cloud — URIs

- Origem: `http://127.0.0.1:8080`
- Redirect: `http://127.0.0.1:8080/auth/google/callback`

---

## 11. Portal do paciente

| Variável | Descrição |
|----------|-----------|
| `PATIENT_PORTAL_INVITATION_EXPIRES_DAYS` | Validade convite portal |

---

## 12. Branding

| Variável | Descrição |
|----------|-----------|
| `APP_LOGO` | Caminho em `public/` (ex.: `images/Logo.png`) |
| `APP_LOGO_ALT` | Texto alternativo logo |

---

## 13. Comandos Artisan agendados

| Comando | Horário | Função |
|---------|---------|--------|
| `psiconecta:session-reminders` | 07:00 | Lembrete sessão amanhã |
| `psiconecta:expire-subscriptions` | 00:30 | Expira trials/assinaturas |
| `psiconecta:subscription-reminders` | 08:00 | Aviso assinatura a expirar |
| `psiconecta:payment-reminders` | 09:00 | Lembretes cobranças pendentes |
| `psiconecta:compliance-prune` | dia 1, 03:00 | Limpeza dados conformidade |

---

## 14. Ficheiros de configuração

| Ficheiro | Conteúdo |
|----------|----------|
| `config/psiconecta.php` | WhatsApp, IA, Jitsi, chatbot |
| `config/asaas.php` | Gateway Asaas |
| `config/services.php` | Google, Facebook, mail externos |
| `config/profile.php` | Limites perfil / uploads |
| `config/clinical_scales.php` | Escalas BAI, BDI, etc. |
| `config/ui-icons.php` | Mapa ícones UI |

---

*Após alterar `.env`: `php artisan config:clear`*

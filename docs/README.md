# PsiConecta — Documentação

> Plataforma Laravel para gestão clínica de psicologia e portal do paciente.  
> Última atualização: junho de 2026.

---

## Índice da documentação

| Documento | Conteúdo |
|-----------|----------|
| [**APLICACAO.md**](APLICACAO.md) | Funcionalidades, regras de negócio, ERD, permissões, rotas, serviços |
| [**REQUISITOS.md**](REQUISITOS.md) | Requisitos funcionais, não funcionais, perfis e critérios de aceite |
| [**INSTALACAO.md**](INSTALACAO.md) | Instalação local (XAMPP), dependências, migrations, build frontend |
| [**CONFIGURACAO.md**](CONFIGURACAO.md) | Variáveis de ambiente, integrações e comandos Artisan |
| [**COMUNICACAO.md**](COMUNICACAO.md) | Conversas, WhatsApp, chatbot, suporte e notificações |

---

## Visão rápida

**PsiConecta** (marca comercial **PsicoVita**) permite que cada profissional opere um consultório isolado: pacientes, sessões, prontuário, pagamentos, documentos e comunicação. Os pacientes acedem ao **portal** (`/area-paciente`) para pagamentos, consultas online, conversas e direitos LGPD.

### Perfis de utilizador

| Perfil | Área |
|--------|------|
| Profissional | Dashboard, agenda, pacientes, financeiro, IA, conversas |
| Paciente | Portal do paciente |
| Administrador | Dashboard + conformidade LGPD + configuração do site |
| DPO | Gestão LGPD (e-mail configurado em `LGPD_DPO_EMAIL`) |
| Agente de suporte | Mesa de suporte (`support_agent`) |

### Stack

- **Backend:** Laravel 12, PHP 8.2+
- **Frontend:** Blade, Tailwind CSS, Alpine.js
- **Auth:** Breeze (sessão web) + Sanctum (API) + Socialite (Google/Facebook)
- **BD:** SQLite (dev) / MySQL (produção)
- **Pagamentos:** Asaas (assinatura SaaS + cobranças clínicas)
- **Vídeo:** Jitsi Meet
- **WhatsApp:** Meta Cloud API ou Evolution API

### Módulos principais

```
Clínico      → Pacientes, Sessões, Agenda, Anamnese, Prontuário, Escalas, Metas
Financeiro   → Cobranças, Split Asaas, Portal pagamentos, Assinatura SaaS
Comunicação  → Conversas, WhatsApp, Chatbot, Suporte, Notificações
Teleconsulta → Salas Jitsi, convites, portal consultas online
Conformidade → LGPD (titular + admin), auditoria, encriptação de dados
Integração   → API REST v1, webhooks Asaas/WhatsApp, OpenAPI
```

### Comandos úteis

```bash
php artisan serve --port=8080
php artisan migrate
php artisan test
php artisan config:clear
npm run build          # assets de produção
npm run dev            # Vite em desenvolvimento
```

### Links internos importantes

| URL | Descrição |
|-----|-----------|
| `/dashboard` | Área do profissional |
| `/area-paciente` | Portal do paciente |
| `/assinatura` | Checkout plano SaaS |
| `/admin/lgpd` | Conformidade (admin/DPO) |
| `/admin/suporte` | Mesa de suporte |
| `/api/v1/openapi.json` | Especificação OpenAPI |

---

*Mantenha esta documentação alinhada com o código ao introduzir novas funcionalidades.*

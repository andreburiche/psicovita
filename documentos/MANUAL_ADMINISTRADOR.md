# Manual do Administrador — PsicoVita

> Guia para o **administrador da plataforma** e **DPO**.  
> Versão: julho de 2026

---

## 1. Quem usa este manual

| Perfil | Acesso típico |
|--------|----------------|
| **Administrador** (`role = admin`) | Tudo: LGPD, site, assinaturas, WhatsApp, chatbot, suporte |
| **DPO** | E-mail igual a `LGPD_DPO_EMAIL` — gestão LGPD (solicitações, métricas, auditoria) |
| **Agente de suporte** | Mesa de suporte (`/admin/suporte`) |

Login: `/login` → após autenticação, área administrativa no menu **Site / Admin**.

---

## 2. Visão geral do painel

No menu lateral (área autenticada), o admin encontra tipicamente:

| Secção | URL aproximada | Função |
|--------|----------------|--------|
| Assinaturas profissionais | `/admin/assinaturas` | Quem pagou / validar pagamentos manuais |
| Planos do site | `/admin/site/planos` | Editar planos SaaS (preços, limites) |
| Redes sociais / settings | `/admin/site/redes-sociais` | Links e definições do site |
| Parceiros (landing) | `/admin/site/parceiros` | Parceiros na página inicial |
| LGPD — Métricas | `/admin/lgpd/metricas` | KPIs e SLA |
| LGPD — Solicitações | `/admin/lgpd/solicitacoes` | Pedidos dos titulares |
| LGPD — Auditoria | `/admin/lgpd/auditoria` | Logs + export |
| Acessibilidade | `/admin/lgpd/acessibilidade` | Relatório |
| WhatsApp | `/admin/integracoes/whatsapp` | Testar e sincronizar webhook |
| Chatbot intents | `/admin/chatbot/intents` | Respostas automáticas |
| Suporte | `/admin/suporte` | Mesa de atendimento |
| Métricas suporte | `/admin/suporte/metricas` | Dashboard chatbot/suporte |

---

## 3. Assinaturas profissionais (controlo SaaS)

**Caminho:** `/admin/assinaturas`

### 3.1 O que ver

- Lista de profissionais e estado da assinatura (trial, activa, expirada, pagamento em atraso…)  
- Plano actual, método de pagamento, validade, última renovação  
- Filtros por texto, estado e plano  
- Cards de resumo por estado  

### 3.2 Fluxo após o trial expirar (regra actual)

1. O profissional **perde acesso clínico** e deve ir a `/assinatura` pagar.  
2. O sistema regista o **pagamento confirmado**.  
3. O admin valida em **Validar pagamento** / **Renovar / ajustar**.  
4. Só com **pagamento + validação admin** o plano fica **activo** (quando `SUBSCRIPTION_REQUIRE_ADMIN_AFTER_PAYMENT=true`).  

### 3.3 Validar pagamento manualmente

1. Em Assinaturas, abra **Validar pagamento**.  
2. Confirme se aparece **Pagamento confirmado = Sim**.  
   - Se **Não**, o profissional ainda não concluiu o checkout/pagamento.  
3. Escolha o **plano pago** (Essencial, Premium, Clínica).  
4. Escolha o **ciclo** (mensal/anual) ou data de validade.  
5. Adicione nota interna (opcional).  
6. Clique em **Confirmar pagamento manual**.  

O profissional recupera o acesso clínico conforme o plano escolhido.

### 3.4 Matriz rápida de planos

| Plano | Pacientes (aprox.) | IA | Equipa multi-user |
|-------|--------------------|----|-------------------|
| Trial | 10 | Sim | Não |
| Essencial | 50 | Não | Não |
| Premium | Ilimitado | Sim | Não |
| Clínica | Ilimitado | Sim | Sim |

---

## 4. Planos do site

**Caminho:** `/admin/site/planos`

Permite ajustar nomes, preços e features dos planos mostrados na landing / checkout (conforme implementação activa).

---

## 5. Conformidade LGPD

**Caminho base:** `/admin/lgpd/...`

### 5.1 Solicitações de titulares

1. Abra **Solicitações**.  
2. Veja o tipo (acesso, correção, eliminação, portabilidade…).  
3. Abra o detalhe, actualize o estado e responda conforme o SLA.  
4. Exporte dados do titular quando necessário (com cuidado — dados pessoais).  

### 5.2 Auditoria

- Consulte logs de acções sensíveis.  
- Exporte CSV quando necessário (respeite flags LGPD no `.env`).  

### 5.3 Métricas e acessibilidade

- KPIs de pedidos e tempos de resposta.  
- Relatório de acessibilidade para conformidade de interface.

**DPO:** configure `LGPD_DPO_EMAIL` com o e-mail real do encarregado.

---

## 6. Site e marketing

| Função | Onde |
|--------|------|
| Redes / settings | `/admin/site/redes-sociais` |
| Parceiros da landing | `/admin/site/parceiros` |

Actualize textos e links com cuidado — reflectem-se na página pública.

---

## 7. WhatsApp e chatbot

### 7.1 Integração WhatsApp

**Caminho:** `/admin/integracoes/whatsapp`

- Testar ligação (Meta ou Evolution, conforme `.env`)  
- Sincronizar webhook  

Na HostGator Start, Evolution local **não** funciona; use Meta Cloud API ou Evolution em VPS.

### 7.2 Intents do chatbot

**Caminho:** `/admin/chatbot/intents`

Crie/edite intenções e respostas automáticas do widget do site.

### 7.3 Mesa de suporte

**Caminho:** `/admin/suporte`

1. Veja conversas abertas.  
2. **Assuma** o atendimento.  
3. Responda, transfira ou resolva.  

Agentes com perfil `support_agent` usam esta mesa sem acesso total de admin.

---

## 8. Boas práticas administrativas

1. Nunca deixe `APP_DEBUG=true` em produção.  
2. Valide assinaturas só com pagamento confirmado (quando a regra dual estiver activa).  
3. Trate pedidos LGPD dentro do SLA (`LGPD_RESPONSE_SLA_DAYS`).  
4. Não partilhe exports de auditoria / dados de titulares por canais inseguros.  
5. Mantenha `LGPD_DPO_EMAIL` e contactos de suporte actualizados.  

---

## 9. Problemas comuns

| Problema | Acção |
|----------|--------|
| Profissional sem acesso após pagar | Verificar pagamento confirmado + validar em Assinaturas |
| Login Google falha em produção | URI de redirect = `https://dominio/auth/google/callback` |
| WhatsApp não envia | Ver `/admin/integracoes/whatsapp` e `.env` (driver/URL) |
| Pedido LGPD sem resposta | Filtrar pendentes em Solicitações e actualizar estado |

---

## 10. Referências

- Detalhe funcional: [docs/APLICACAO.md](../docs/APLICACAO.md)  
- Manual técnico / deploy: [MANUAL_TECNICO.md](MANUAL_TECNICO.md)  
- Manual do paciente: [MANUAL_PACIENTE.md](MANUAL_PACIENTE.md)  

---

*Este manual cobre a operação administrativa da plataforma, não o dia-a-dia clínico do profissional.*

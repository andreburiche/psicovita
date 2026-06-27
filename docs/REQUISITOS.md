# PsiConecta — Requisitos

> Requisitos funcionais (RF), não funcionais (RNF) e critérios de aceite por área.  
> Complementa [APLICACAO.md](APLICACAO.md).

---

## 1. Objectivo do sistema

O PsiConecta deve permitir que profissionais de saúde mental gerem a prática clínica (pacientes, sessões, prontuário, financeiro) em ambiente **multi-tenant por consultório** (`professional_id`), com portal para pacientes, conformidade LGPD e monetização via assinatura SaaS.

---

## 2. Stakeholders

| Stakeholder | Necessidade principal |
|-------------|----------------------|
| Profissional / clínica | Gestão clínica, agenda, cobranças, comunicação |
| Paciente | Acesso a pagamentos, consultas online, mensagens, privacidade |
| Administrador da plataforma | LGPD, métricas, configuração global |
| DPO | Tratamento de solicitações de titulares |
| Agente de suporte | Atendimento via mesa de suporte e chatbot |
| Integrador externo | API REST para sistemas terceiros (profissionais) |

---

## 3. Requisitos funcionais

### 3.1 Autenticação e contas (RF-AUTH)

| ID | Requisito |
|----|-----------|
| RF-AUTH-01 | Registo de profissional com e-mail, senha, função profissional e aceite de termos |
| RF-AUTH-02 | Registo automático como **paciente** se o e-mail existir na ficha de exactamente um consultório |
| RF-AUTH-03 | Login com e-mail e senha (sessão web) |
| RF-AUTH-04 | Login social Google e Facebook (OAuth), com conclusão de registo para contas novas |
| RF-AUTH-05 | Vinculação de conta social a utilizador existente pelo mesmo e-mail |
| RF-AUTH-06 | Definição de senha no perfil para contas só sociais |
| RF-AUTH-07 | Verificação de e-mail obrigatória (`MustVerifyEmail`) |
| RF-AUTH-08 | Recuperação de senha por e-mail |
| RF-AUTH-09 | Ativação do portal do paciente via convite (`/portal/activar/{token}`) |
| RF-AUTH-10 | API token Sanctum apenas para perfil **profissional** |

### 3.2 Gestão clínica (RF-CLIN)

| ID | Requisito |
|----|-----------|
| RF-CLIN-01 | CRUD de pacientes isolado por consultório |
| RF-CLIN-02 | Ficha do paciente com abas: resumo, prontuário, financeiro, documentos |
| RF-CLIN-03 | Anamnese configurável (formulários + respostas) |
| RF-CLIN-04 | CRUD de sessões terapêuticas com detecção de conflitos de horário |
| RF-CLIN-05 | Agenda mensal com bloqueios de horário |
| RF-CLIN-06 | Prontuário clínico encriptado com políticas de acesso |
| RF-CLIN-07 | Documentos clínicos (atestado, declaração, receita) em PDF |
| RF-CLIN-08 | Escalas clínicas (BAI, BDI, Estresse) |
| RF-CLIN-09 | Metas terapêuticas por paciente |
| RF-CLIN-10 | Solicitações documentais a instituições (ofício PDF, e-mail, anexos) |
| RF-CLIN-11 | Exportação de sessões (PDF/Excel) e relatórios |

### 3.3 Teleconsulta (RF-VIDEO)

| ID | Requisito |
|----|-----------|
| RF-VIDEO-01 | Sala de vídeo Jitsi por sessão (`/therapy-sessions/{id}/video`) |
| RF-VIDEO-02 | Convites para observador, família e grupo |
| RF-VIDEO-03 | Portal do paciente: listar e entrar em consultas online |
| RF-VIDEO-04 | Devolutiva pós-sessão com IA (plano com `use_ai`) |

### 3.4 Financeiro e assinatura (RF-FIN)

| ID | Requisito |
|----|-----------|
| RF-FIN-01 | Trial de 14 dias ao registar profissional |
| RF-FIN-02 | Planos Essencial, Premium e Clínica com limites de pacientes e features |
| RF-FIN-03 | Checkout assinatura via Asaas (PIX e cartão, mensal/anual) |
| RF-FIN-04 | Webhook Asaas para confirmar pagamentos e renovações |
| RF-FIN-05 | Cobrança automática opcional ao criar sessão |
| RF-FIN-06 | Portal do paciente: listar, pagar PIX/cartão |
| RF-FIN-07 | Split de repasse ao profissional (Asaas Connect / carteira) |
| RF-FIN-08 | Notificações de cobrança e lembretes agendados |
| RF-FIN-09 | Equipa clínica (plano Clínica): convites e membros multi-utilizador |

### 3.5 Comunicação (RF-COM)

| ID | Requisito |
|----|-----------|
| RF-COM-01 | Conversas clínicas profissional ↔ paciente (mensagens encriptadas) |
| RF-COM-02 | Integração WhatsApp (Meta ou Evolution) com consentimento LGPD |
| RF-COM-03 | Chatbot widget no site (intents configuráveis) |
| RF-COM-04 | Mesa de suporte para agentes (assumir, transferir, resolver) |
| RF-COM-05 | Chatbot WhatsApp para visitantes e pacientes (menu, handoff) |
| RF-COM-06 | Notificações in-app (database) + e-mail para eventos relevantes |
| RF-COM-07 | Feed e dropdown de notificações no header |

### 3.6 IA (RF-IA)

| ID | Requisito |
|----|-----------|
| RF-IA-01 | Assistente IA: transcrição, texto, recomendações no prontuário |
| RF-IA-02 | Provedores: OpenAI, Ollama (local) ou mock |
| RF-IA-03 | Feature `use_ai` bloqueada no plano Essencial |
| RF-IA-04 | Chatbot com classificação de intents via LLM (opcional) |

### 3.7 LGPD e conformidade (RF-LGPD)

| ID | Requisito |
|----|-----------|
| RF-LGPD-01 | Portal do paciente: pedidos de acesso, correção, eliminação, portabilidade, oposição, revogação |
| RF-LGPD-02 | Exportação de dados do titular (JSON e PDF) |
| RF-LGPD-03 | Admin: métricas, auditoria, gestão de solicitações, relatório acessibilidade |
| RF-LGPD-04 | Encriptação de CPF, e-mails, telefones e corpos de mensagem |
| RF-LGPD-05 | Trilha de auditoria em acções sensíveis |
| RF-LGPD-06 | Páginas legais: termos, privacidade, DPIA IA |

### 3.8 API e integrações (RF-API)

| ID | Requisito |
|----|-----------|
| RF-API-01 | API REST v1 com autenticação Bearer (Sanctum) |
| RF-API-02 | CRUD pacientes, sessões, pagamentos, prontuário (profissional) |
| RF-API-03 | OpenAPI em `/api/v1/openapi.json` |
| RF-API-04 | Webhooks: Asaas, WhatsApp Meta, Evolution |
| RF-API-05 | Lookup CEP interno (`GET /api/cep/{cep}`) |

### 3.9 Administração do site (RF-ADMIN)

| ID | Requisito |
|----|-----------|
| RF-ADMIN-01 | Configuração de redes sociais e conteúdo da landing |
| RF-ADMIN-02 | Gestão de planos de assinatura na BD |
| RF-ADMIN-03 | Parceiros da landing page |
| RF-ADMIN-04 | Painel integrações WhatsApp (teste + sync webhook) |
| RF-ADMIN-05 | CRUD intents do chatbot |
| RF-ADMIN-06 | Métricas do chatbot / suporte |

---

## 4. Requisitos não funcionais (RNF)

| ID | Categoria | Requisito |
|----|-----------|-----------|
| RNF-01 | Segurança | HTTPS em produção; senhas com bcrypt; tokens API revogáveis |
| RNF-02 | Privacidade | Dados clínicos e PII encriptados em repouso (Laravel encrypted casts) |
| RNF-03 | Isolamento | Dados clínicos filtrados por `professional_id` / `clinicalPracticeId()` |
| RNF-04 | Disponibilidade | Filas e scheduler para lembretes e expiração de assinaturas |
| RNF-05 | Performance | Throttle em rotas sensíveis (login, LGPD export, API) |
| RNF-06 | Acessibilidade | Skip links, labels, contraste (relatório admin) |
| RNF-07 | I18n | Interface e validações em `pt_BR` |
| RNF-08 | Testabilidade | Suite PHPUnit (~75 testes Feature/Unit) |
| RNF-09 | Manutenibilidade | Camada de serviços; policies Laravel; enums para estados |
| RNF-10 | Escalabilidade | Multi-tenant lógico; integrações via gateways (Asaas, WhatsApp) |

---

## 5. Matriz plano × funcionalidade

| Funcionalidade | Trial | Essencial | Premium | Clínica |
|----------------|-------|-----------|---------|---------|
| Pacientes / sessões / prontuário | ✓ (máx. 10) | ✓ (máx. 50) | ✓ (∞) | ✓ (∞) |
| IA clínica | ✓ | — | ✓ | ✓ |
| Multi-utilizador (equipa) | — | — | — | ✓ |
| Teleconsulta Jitsi | ✓ | ✓ | ✓ | ✓ |
| WhatsApp / conversas | ✓* | ✓* | ✓* | ✓* |
| Chatbot / suporte | ✓* | ✓* | ✓* | ✓* |

\*Depende de configuração externa (credenciais WhatsApp, Evolution, etc.).

---

## 6. Critérios de aceite (exemplos)

### Login social Google
- Dado utilizador não registado, quando autentica com Google, então vê ecrã de conclusão com termos e função (ou paciente automático).
- Dado e-mail já registado, quando autentica com Google, então entra sem duplicar conta e fica `social_accounts` vinculado.

### Pagamento paciente
- Dado cobrança pendente, quando paciente paga via PIX no portal, então webhook confirma e estado passa a pago.

### Assinatura expirada
- Dado trial expirado, quando profissional tenta criar paciente além do limite ou sem plano activo, então middleware `subscription.feature` bloqueia com mensagem clara.

### WhatsApp visitante
- Dado número não cadastrado, quando envia mensagem WhatsApp, então chatbot cria conversa de suporte e envia menu de opções.

---

## 7. Fora de âmbito (actual)

| Item | Nota |
|------|------|
| App nativo iOS/Android | Previsto via API paciente futura; portal web/PWA disponível |
| API para pacientes | Apenas profissionais na API v1 actual |
| Sign in with Apple (web) | Não implementado |
| Permissões granulares por membro da equipa | Todos os membros têm acesso clínico do titular |
| Prontuário no portal do paciente | Paciente não vê prontuário (por desenho) |

---

## 8. Rastreabilidade com testes

| Área | Pasta / ficheiros de teste |
|------|---------------------------|
| Auth + Social | `tests/Feature/Auth/SocialAuthTest.php` |
| Assinatura | `tests/Feature/SubscriptionTest.php` |
| Pagamentos | `tests/Feature/PatientPaymentPortalTest.php` |
| Chatbot / Suporte | `tests/Feature/SupportDeskTest.php`, `SupportWhatsAppTest.php` |
| WhatsApp | `tests/Feature/EvolutionWhatsAppTest.php` |
| Conversas | `tests/Feature/ConversationTest.php` |
| Teleconsulta | `tests/Feature/TherapySessionVideoCallTest.php` |
| LGPD | `tests/Feature/PatientLgpdTest.php`, `AdminLgpdTest.php` |
| API | `tests/Feature/ApiSanctumTest.php` |
| Notificações | `tests/Unit/NotificationPresenterTest.php` |

---

*Ver [README.md](README.md) para o índice completo da documentação.*

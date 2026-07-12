# Manual do Paciente — PsicoVita

> Guia de utilização do **portal do paciente**.  
> Versão: julho de 2026

---

## 1. O que é o portal

O portal PsicoVita permite-lhe, como paciente:

- Ver informações do seu profissional / consultório  
- Consultar e pagar cobranças (PIX ou cartão, quando disponível)  
- Entrar em **consultas online** (vídeo)  
- Enviar mensagens ao profissional (quando activo)  
- Exercer direitos de **privacidade (LGPD)**  

Área principal: `/area-paciente`

---

## 2. Como obter acesso

### 2.1 Convite do profissional

1. O profissional cadastra a sua ficha e envia o convite do portal.  
2. Recebe um e-mail com um link do tipo:  
   `https://seudominio/portal/activar/TOKEN`  
3. Define a senha e aceita os termos.  
4. Confirma o e-mail se for pedido.  
5. Faz login em **Entrar**.

### 2.2 Login

1. Aceda a **Entrar** (`/login`).  
2. Introduza o e-mail da ficha e a palavra-passe.  
3. Ou use **Continuar com Google** (se estiver activo no site).  
4. Após login, será direcionado para `/area-paciente`.

### 2.3 Esqueceu a senha

1. Em **Entrar**, clique em **Esqueceu a senha?**  
2. Informe o e-mail e siga as instruções da mensagem recebida.

---

## 3. Página inicial (`/area-paciente`)

Aqui encontra:

- Dados do seu terapeuta / profissional vinculado  
- Atalhos para pagamentos, consultas online e privacidade  
- Notificações (ícone no topo), quando existirem  

Use o menu do portal (não o menu clínico do profissional).

---

## 4. Pagamentos

**Caminho:** Área do paciente → **Pagamentos** (`/area-paciente/pagamentos`)

### 4.1 Lista de cobranças

Cada cobrança mostra normalmente:

- Valor e descrição  
- Estado (pendente, pago, atrasado, cancelado, etc.)  
- Data  

### 4.2 Pagar uma cobrança

1. Abra a cobrança.  
2. Se ainda não houver método definido, escolha **PIX** ou **cartão**.  
3. Clique em **Pagar** / iniciar pagamento.  
4. **PIX:** escaneie o QR Code ou copie o código.  
5. **Cartão:** conclua no ambiente seguro do gateway (quando Asaas estiver activo).  

O estado pode demorar alguns minutos a actualizar após o pagamento.

### 4.3 Problemas frequentes

| Situação | O que fazer |
|----------|-------------|
| Cobrança não aparece | Confirme com o profissional se foi gerada |
| PIX expirado | Peça nova cobrança ou actualize a página |
| Pagou e continua pendente | Aguarde confirmação automática; contacte o profissional se persistir |

---

## 5. Consultas online

**Caminho:** Área do paciente → **Consultas online** (`/area-paciente/consultas-online`)

1. Veja a lista de sessões com vídeo disponíveis.  
2. No horário indicado, clique em **Entrar**.  
3. Autorize microfone e câmara no browser.  
4. A consulta usa sala de vídeo (Jitsi).  

**Dicas:**

- Prefira Chrome ou Edge actualizados.  
- Use ligação estável (Wi‑Fi ou cabo).  
- Entre 2–5 minutos antes.  

Se receber um link directo de vídeo por e-mail/WhatsApp, pode abrir esse link (pode pedir consentimento na primeira vez).

---

## 6. Privacidade e LGPD

**Caminho:** Área do paciente → **Privacidade** (`/area-paciente/privacidade`)

Pode pedir:

| Tipo | Significado |
|------|-------------|
| Acesso | Confirmar / aceder aos seus dados |
| Correção | Pedir correção de dados incorrectos |
| Eliminação | Pedir eliminação (sujeito a regras legais) |
| Portabilidade | Exportar dados |
| Oposição | Opor-se a determinado tratamento |
| Revogação | Revogar consentimento |

Também pode **exportar** os seus dados (JSON/PDF), com limite de pedidos por hora.

O pedido será tratado pelo administrador / DPO da plataforma. Acompanhe o estado na mesma área.

---

## 7. Segurança e boas práticas

- Não partilhe a sua palavra-passe.  
- Faça logout em computadores partilhados.  
- Confirme que o site usa **HTTPS** (`https://`).  
- Desconfie de e-mails que peçam senha fora do site oficial.  

---

## 8. Onde pedir ajuda

1. Contacte o **seu profissional** (primeiro contacto clínico/financeiro).  
2. Use o **chatbot** do site (quando activo) para dúvidas gerais.  
3. Mesa de suporte da plataforma (quando disponível no site).  

---

## 9. Glossário rápido

| Termo | Significado |
|-------|-------------|
| Portal | Área `/area-paciente` |
| Cobrança | Pedido de pagamento gerado pelo consultório |
| Teleconsulta | Sessão por vídeo |
| LGPD | Lei Geral de Proteção de Dados |

---

*Em caso de dúvida sobre o tratamento clínico, fale sempre com o seu profissional.*

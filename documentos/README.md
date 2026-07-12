# PsicoVita / PsiConecta — Documentação de utilizador e técnica

> Manuais oficiais do projecto.  
> Última atualização: julho de 2026.  
> Marca comercial: **PsicoVita** · Código: **PsiConecta**

---

## Índice

| Documento | Público-alvo | Conteúdo |
|-----------|--------------|----------|
| [**MANUAL_PACIENTE.md**](MANUAL_PACIENTE.md) | Pacientes / utentes | Portal, pagamentos, consultas online, privacidade LGPD |
| [**MANUAL_ADMINISTRADOR.md**](MANUAL_ADMINISTRADOR.md) | Administrador da plataforma / DPO | LGPD, assinaturas, site, chatbot, suporte |
| [**MANUAL_TECNICO.md**](MANUAL_TECNICO.md) | DevOps / desenvolvedores | Instalação, `.env`, deploy HostGator, integrações, operações |

---

## Documentação técnica complementar

A pasta [`docs/`](../docs/README.md) contém a documentação de produto e engenharia:

| Ficheiro | Conteúdo |
|----------|----------|
| [docs/README.md](../docs/README.md) | Visão geral e índice |
| [docs/APLICACAO.md](../docs/APLICACAO.md) | Funcionalidades, ERD, rotas, serviços |
| [docs/REQUISITOS.md](../docs/REQUISITOS.md) | Requisitos e critérios de aceite |
| [docs/INSTALACAO.md](../docs/INSTALACAO.md) | Instalação local e checklist de produção |
| [docs/CONFIGURACAO.md](../docs/CONFIGURACAO.md) | Variáveis de ambiente |
| [docs/COMUNICACAO.md](../docs/COMUNICACAO.md) | WhatsApp, chatbot, notificações |

---

## Perfis do sistema

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   Paciente      │     │  Profissional    │     │ Administrador   │
│ /area-paciente  │     │ /dashboard       │     │ /admin/...      │
└─────────────────┘     └──────────────────┘     └─────────────────┘
```

| Perfil | Manual |
|--------|--------|
| Paciente | Este pacote → Manual do Paciente |
| Profissional / clínica | Ver [docs/APLICACAO.md](../docs/APLICACAO.md) secção área profissional |
| Administrador / DPO | Manual do Administrador |
| Equipa técnica | Manual Técnico |

---

## URLs típicas

| Ambiente | URL exemplo |
|----------|-------------|
| Local | `http://127.0.0.1:8080` |
| Produção | `https://psicovita.online` |

---

*Actualize estes manuais sempre que alterar fluxos de UI, permissões ou deploy.*

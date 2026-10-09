---
title: Autenticação e Perfil
parent: Módulos
nav_order: 15
---

# Autenticação e Perfil
{: .no_toc }

Login por e-mail e senha com sessão (cookie) do CodeIgniter Shield.
{: .fs-5 .fw-300 }

## Login (`/login`)

1. O usuário informa e-mail, senha e, se quiser, **Lembrar-me**.
2. O frontend chama `POST /api/v1/auth/login`.
3. O Shield valida e abre a sessão; o navegador guarda o cookie (as chamadas usam `credentials: 'include'`).
4. Ao abrir o sistema, `GET /api/v1/auth/me` diz se a sessão continua válida. Sem sessão, as rotas protegidas mandam para `/login`.

| Situação | Resposta |
|:--|:--|
| E-mail/senha inválidos | 401 com a mensagem do Shield |
| Sem sessão numa rota protegida | 401 `Não autenticado.` |
| Usuário banido | 403 com a mensagem de banimento (e a sessão é encerrada) |
| Conta não ativada | 403 `Conta não ativada.` |

A data do último acesso é registrada quando `Auth.recordActiveDate` está ligado.

## Sair

`POST /api/v1/auth/logout` encerra a sessão.

## Trocar a senha

`PUT /api/v1/perfil/senha` com:

| Campo | Regra |
|:--|:--|
| `current_password` | Obrigatório — é conferido antes da troca |
| `new_password` | Mínimo de 8 caracteres |
| `new_password_confirm` | Igual a `new_password` |

{: .nota }
O endpoint já existe, mas ainda não há tela de perfil no frontend para chamá-lo.

## Criar usuários

- **Primeiro administrador**: pela CLI — veja [Instalação → passo 7]({{ site.baseurl }}/instalacao#primeiro-usuario).
- **Demais usuários**: pelo cadastro da [Equipe](equipe) ou pela CLI do Shield (`php spark shield:user create`).

## Endpoints públicos

Só três grupos de rotas funcionam sem login:

- `api/v1/auth/*` (login, logout, me);
- `api/v1/publico/recibos/{id}/{hash}` — recibo protegido pelo hash aleatório da URL;
- `OPTIONS` (preflight de CORS).

# Ambiente de pairing técnico, Web/WordPress

Este repositório sobe um WordPress local com o site da INFLEET em versão reduzida. Ele existe para a sessão de pairing: você trabalha aqui, ao vivo, com tela compartilhada.

**Suba o ambiente antes da call** e confirme que ele funciona na sua máquina. A tarefa em si você recebe no começo da sessão, então não há nada para preparar além disso.

## Pré-requisitos

- **Docker** rodando (Docker Desktop, OrbStack ou equivalente). Confirme com `docker info`.
- **Node.js 20 ou superior**. Confirme com `node -v`.

## Subindo

```bash
npm install
npm start        # sobe WordPress e banco (na primeira vez baixa as imagens, leva alguns minutos)
npm run seed     # cria as páginas de demonstração
```

Os dois últimos comandos são separados de propósito, para você ver o que cada um faz. Se esquecer o `seed`, o admin avisa.

| | |
|---|---|
| Site | http://localhost:8888/ |
| Admin | http://localhost:8888/wp-admin/ |
| Usuário | `admin` |
| Senha | `password` |

## O que tem aqui

```
.wp-env.json      configuração do ambiente (WordPress 7.0.2, PHP 8.3)
theme/infleet/    o tema do site, ativo
mu-plugins/       ajustes carregados automaticamente pelo ambiente
seed/             conteúdo de demonstração e o script que o cria
```

O site tem uma home e duas landings de campanha, `/frota-conectada/` e `/gestao-de-combustivel/`.

## Comandos úteis

```bash
npm start                    # sobe (e re-sincroniza depois de mudar .wp-env.json)
npm stop                     # para os containers
npm run seed                 # recria o conteúdo de demonstração (pode rodar quantas vezes quiser)
npm run logs                 # logs do PHP e do servidor
npm run destroy              # apaga tudo, inclusive o banco

npm run cli -- plugin list             # WP-CLI: qualquer comando vai depois do --
npm run cli -- plugin install <slug> --activate
npm run cli -- post list --post_type=page
```

O ambiente tem acesso à internet, então instalar plugin pelo admin ou pelo WP-CLI funciona normalmente.

## Como trabalhamos na sessão

- **IA liberada**, do jeito que você usa no dia a dia. Copilot, Claude, Cursor, o que preferir. Isso não é trapaça aqui, é parte do trabalho.
- **É um pair, não uma prova.** Pergunte, pense em voz alta, discuta. Dar hint é normal.
- **Time-box de 45 a 60 minutos** de tarefa. Não é esperado que tudo fique pronto.
- Editor, terminal e ferramentas: use os seus.

## Se algo der errado

`npm start` reclamando de porta ocupada: algo já usa a 8888 ou a 8889. Pare o outro serviço, ou defina `WP_ENV_PORT` e `WP_ENV_TESTS_PORT`.

`npm start` falhando logo no começo: quase sempre é o Docker não estar rodando. Confirme com `docker info`.

Ambiente confuso depois de muitas mudanças: `npm run destroy && npm start && npm run seed` devolve tudo ao estado inicial.

Qualquer travada no setup, fale com a gente **antes** da call. Nenhum minuto da sessão deve ser gasto com ambiente.

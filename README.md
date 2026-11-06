# 🎮 Logic Gate

Este projeto é uma aplicação web para simulação de portas lógicas e desafios de lógica digital. O objetivo é permitir que o usuário entenda o comportamento de circuitos como AND, OR, NOT, NAND, NOR e XOR em uma interface simples e interativa.

## ✅ O que o sistema oferece

- Simulação de portas lógicas básicas.
- Navegação entre páginas de login, cadastro, projeto, ranking e administração.
- Estrutura organizada em PHP com roteamento e views separadas.
- Possibilidade de servir o projeto localmente com Docker.

## 🧰 Requisitos da máquina

Antes de rodar o projeto, certifique-se de ter instalado:

- Git
- Docker Desktop (ou Docker Engine + Docker Compose)
- Editor de código, como VS Code
- Navegador (Chrome, Edge, Firefox)

## ▶️ Como rodar na sua máquina

### 1) Clone o projeto

Abra o terminal e execute:

```bash
git clone https://github.com/mercuriohg/logic-gate.git
cd logic-gate
```

Se o projeto já estiver em sua máquina, basta entrar na pasta:

```bash
cd /caminho/para/logic-gate
```

### 2) Inicie o ambiente com Docker

Na raiz do projeto, execute:

```bash
docker compose up --build
```

Esse comando irá criar o container PHP/Apache e expor a aplicação na porta 8080.

### 3) Acesse no navegador

Depois que o container subir corretamente, abra no navegador:

```text
http://localhost:8080
```

A página inicial da aplicação será carregada pelo roteamento principal.

## 🧭 Rotas do sistema

O projeto usa roteamento dentro da pasta `src/public/index.php`. As principais rotas são:

- `/` → página inicial
- `/login` → tela de login
- `/cadastro` → tela de cadastro
- `/projeto` → página do projeto / simulação
- `/ranking` → ranking de usuários
- `/admin` → painel administrativo

Exemplo:

```text
http://localhost:8080/login
```

## 📁 Estrutura principal

```text
logic-gate/
├── src/
│   ├── api/
│   ├── config/
│   ├── controllers/
│   ├── model/
│   ├── public/
│   └── views/
├── database/
├── docker/
├── docker-compose.yml
├── Dockerfile
├── README.md
└── LICENSE
```

## 🛠️ Se quiser parar ou reiniciar

Para encerrar os containers:

```bash
docker compose down
```

Para subir novamente:

```bash
docker compose up
```

## ⚠️ Observações importantes

- O projeto foi configurado para rodar via Docker em PHP + Apache.
- A porta 8080 é a usada pelo sistema no host.
- Se a porta 8080 estiver ocupada, ajuste o arquivo `docker-compose.yml`.
- O arquivo `database/init.sql` indica a estrutura do banco MySQL esperada para o sistema.
- Caso o banco não esteja configurado ainda, a aplicação pode exigir ajustes adicionais na conexão de dados.

## 🚀 Dica para desenvolvimento local

Durante o desenvolvimento, é recomendado abrir a pasta do projeto no VS Code e rodar os comandos no terminal integrado, deixando o container ativo enquanto você trabalha nas páginas e funcionalidades.

## Funcionalidades esperadas

- Simulação de portas AND e OR.
- Simulação de porta NOT.
- Implementação de portas compostas como NAND, NOR e XOR.
- Testes simples de combinações de entrada.
- Fluxo de login, cadastro e ranking.

## 🔧 Backend (PHP + MySQL)

### Primeira execução
O MySQL só roda o `database/init.sql` na **primeira vez** que o volume é criado. Se você já tinha subido o projeto antes, recrie o banco:

```bash
docker compose down -v
docker compose up --build
```

O `init.sql` cria as tabelas, as 7 portas lógicas e um admin padrão: **login `admin` / senha `admin123`**. Troque a senha logo:

```bash
docker compose exec php php scripts/criar-admin.php admin MinhaSenhaNova
```

Esse mesmo comando cria novos admins (se o login não existir) ou troca a senha (se já existir).

### Atualizando um banco que já existe (estatísticas)
As estatísticas do admin e o tempo médio do perfil usam 3 colunas novas (`usuario.ultimo_acesso`, `progresso.data_inicio`, `progresso.tempo_segundos`). **Faça uma das duas opções antes de usar o site:**

- **Recriar o banco** (apaga os dados; serve se são só dados de teste):
  ```powershell
  docker compose down -v
  docker compose up -d --build
  ```
- **Migrar sem perder dados** (rode uma única vez, com os containers no ar):
  ```powershell
  Get-Content database/migracao-estatisticas.sql | docker compose exec -T mysql mysql -uroot -proot_password
  ```

### Como funciona
- **Página principal** (`/`): visitante vê a apresentação com *Entrar* / *Criar conta*; jogador logado vê o **mapa de fases em trilha** (concluídas em verde, a fase atual destacada, as bloqueadas apagadas); admin vê um atalho para o painel. `/fase` mostra o mesmo mapa.
- **Login** (`/login`): um único formulário. Primeiro tenta como admin (tabela `admin`); se não for, tenta como jogador pelo **nome de usuário ou e-mail**.
- **Cadastro** (`/cadastro`): valida os campos, a confirmação de senha (que **não** é salva) e se usuário/e-mail já existem. Senha com `password_hash`. Depois do cadastro o jogador já entra logado.
- **Perfil** (`/profile`): nome, nível, XP, pontos, fases concluídas e tempo médio por fase, tudo vindo do banco.
- **Ranking** (`/ranking`): soma dos pontos das fases concluídas (desempate por XP).
- **Fases** (`/fase?id=N`): enunciado, objetivo e portas disponíveis. Concluir uma fase libera a próxima (pela `ordem`).
- **Painel admin** (`/admin`): 4 cartões que levam a:
  - **Usuários** (`/admin/users`): tabela com exclusão;
  - **Fases** (`/admin/phases`): criar, editar e excluir (nome, ordem, dificuldade, enunciado, objetivo 0/1, XP, pontos e **quais portas e quantas de cada**);
  - **Ranking** (`/admin/ranking`): tabela com posição, nível, XP, pontos e fases concluídas de cada jogador;
  - **Estatísticas** (`/admin/stats`): jogadores ativos agora (últimos 5 min), pontos distribuídos, tempo médio e total no puzzle, e uma tabela por fase (quantos jogaram/concluíram, tentativas e tempo médio).
- **Tempo no puzzle**: começa a contar quando o jogador abre a fase e é gravado ao concluir (abrir a fase de novo reinicia a contagem; cada fase conta no máximo 1 hora, para uma aba esquecida aberta não distorcer a média).
- **Jogadores ativos**: o sistema marca o último acesso do jogador (no máximo 1x por minuto); sair da conta remove ele da contagem.
- **Segurança**: sessão regenerada no login, token CSRF em todos os formulários e na API, saída HTML escapada, páginas admin exigem login de administrador.

### API JSON para o jogo
| Método | Rota | Descrição |
|---|---|---|
| GET | `/api/fases` | mapa de fases do jogador logado |
| GET | `/api/fase?id=1` | enunciado, objetivo e portas da fase (se desbloqueada) |
| POST | `/api/fase/tentativa` | campos `id`, `saida` (0 ou 1) e `csrf_token` |

Na tela da fase, o editor de circuito deve chamar `LogicGate.enviarSaida(0 ou 1)` (em `assets/js/Fase.js`) quando o jogador terminar de montar o circuito. Se a saída for igual ao objetivo, o backend conclui a fase **uma única vez**: soma XP e pontos, recalcula o nível e libera a próxima fase.

Com `APP_ENV=dev` (já definido no `docker-compose.yml`) a tela da fase mostra botões para simular a saída e testar o backend. **Remova `APP_ENV` em produção.**

Nível = `floor(xp_total / XP_POR_NIVEL) + 1` (`XP_POR_NIVEL`, padrão 100, no `docker-compose.yml`).

### Limitações conhecidas
- O servidor compara a `saida` enviada pelo front com o `objetivo` da fase; ele não valida o circuito em si. Como o modelo de dados não guarda as entradas de cada fase, não há como simular o circuito no servidor.

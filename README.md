# Avaliação EAD

Projeto desenvolvido para o teste técnico da vaga de Developer PHP Pleno.

A aplicação consiste em uma plataforma simples de provas EAD, com área de professor, área de aluno, correção automática de provas, dashboard de desempenho e ranking de tentativas.

Este README descreve a configuração do ambiente de desenvolvimento e execução via Docker.

A documentação específica da aplicação Laravel, regras de negócio, API e decisões técnicas ficará em [`app/README.md`](app/README.md).

---
## Stack

- PHP 8.4
- Laravel 12
- Vue.js
- PostgreSQL
- Redis
- Nginx
- Docker
- Docker Compose
- Xdebug para cobertura de testes

---
## Estrutura geral

```text
.
├── app/                  # Aplicação Laravel
├── docker/
│   └── nginx/            # Configuração do Nginx
├── docker-compose.yml    # Orquestração dos serviços
├── Dockerfile            # Imagem PHP-FPM da aplicação
├── .env.example          # Variáveis usadas pelo Docker Compose
└── README.md             # Documentação do ambiente
```

---
## Serviços Docker

O ambiente é composto pelos seguintes serviços:

| Serviço    | Descrição                                                                |
| ---------- | ------------------------------------------------------------------------ |
| `app`      | Container PHP-FPM com PHP 8.4, Composer, Node.js e extensões necessárias |
| `nginx`    | Servidor HTTP responsável por expor a aplicação Laravel                  |
| `postgres` | Banco de dados PostgreSQL                                                |
| `redis`    | Cache Redis usado pela aplicação                                         |

Fluxo simplificado:

```text
Browser / API Client
        ↓
Nginx :8000
        ↓
PHP-FPM app:9000
        ↓
Laravel
        ↓
PostgreSQL / Redis
```

---
## Pré-requisitos

Antes de iniciar, é necessário ter instalado:
* Docker
* Docker Compose
* Git

Opcionalmente:
* `jq`, para visualizar respostas JSON no terminal

Em distribuições baseadas em Ubuntu/Debian:

```bash
sudo apt install jq
```

---
## Configuração inicial

Clone o repositório:

```bash
git clone https://github.com/deivisson-souza-84lab/avaliacao-ead.git
cd avaliacao-ead
```

Crie o arquivo `.env` da raiz a partir do exemplo:

```bash
cp .env.example .env
```

O arquivo `.env` da raiz é usado pelo Docker Compose para configurar os serviços de infraestrutura.

Exemplo:

```env
COMPOSER_VERSION="2.9.7"
XDEBUG_VERSION="3.5.1"
NODE_VERSION="25.9.0"
INSTALL_XDEBUG="false"
UID="1000"
GID="1000"
TZ="America/Sao_Paulo"

POSTGRES_DB=app
POSTGRES_USER=user
POSTGRES_PASSWORD=1234@5678A
POSTGRES_PORT=5432

REDIS_PORT=6379
```

---
## Sobre variáveis de ambiente

Este projeto usa uma separação entre variáveis de infraestrutura e variáveis da aplicação.

### `.env` da raiz

Usado pelo Docker Compose.

Responsável por configurar:
* versões de ferramentas;
* usuário/grupo do container;
* timezone;
* banco PostgreSQL;
* porta do Redis;
* instalação opcional do Xdebug.

### `app/.env`

Usado pelo Laravel.

As variáveis de infraestrutura da aplicação, como banco, Redis, cache, fila e sessão, são injetadas no container pelo `docker-compose.yml` através de `environment`.

Com isso, o Docker Compose fica como fonte principal da configuração de infraestrutura da aplicação.

---
## Subindo o ambiente

Construa e suba os containers:

```bash
docker compose up -d --build
```

Verifique se os serviços estão ativos:

```bash
docker compose ps
```

O esperado é que os serviços `app`, `nginx`, `postgres` e `redis` estejam em execução.

A aplicação ficará disponível em:

```text
http://localhost:8000
```

A API ficará disponível em:

```text
http://localhost:8000/api
```

---
## Instalando dependências da aplicação

Entre no container da aplicação para instalar as dependências PHP:

```bash
docker compose exec app composer install
```

Gere a chave da aplicação Laravel:

```bash
docker compose exec app php artisan key:generate
```

Instale as dependências JavaScript:

```bash
docker compose exec app npm install
```

---
## Banco de dados

Para rodar as migrations:

```bash
docker compose exec app php artisan migrate
```

Para recriar o banco do zero:

```bash
docker compose exec app php artisan migrate:fresh
```

Para recriar o banco e popular dados iniciais:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

O seeder cria uma prova inicial com questões e alternativas para facilitar testes manuais.

---
## Testando a API

Após rodar:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Teste a listagem de provas do professor:

```bash
curl -s http://localhost:8000/api/exams \
  -H "Accept: application/json" | jq
```

Teste o detalhe de uma prova:

```bash
curl -s http://localhost:8000/api/exams/1 \
  -H "Accept: application/json" | jq
```

Teste a visão do aluno, que não expõe o gabarito:

```bash
curl -s http://localhost:8000/api/student/exams/1 \
  -H "Accept: application/json" | jq
```

---
## Testes automatizados

Para rodar a suíte de testes:

```bash
docker compose exec app php artisan test
```

A suíte cobre os principais fluxos da aplicação:
* cadastro de provas;
* validação de questões e alternativas;
* edição e exclusão de provas;
* consulta de provas pelo aluno;
* submissão de respostas;
* correção automática;
* bloqueio de segunda tentativa;
* dashboard;
* ranking;
* cache do dashboard.

---
## Cobertura de testes

A geração de cobertura depende do Xdebug.

Para habilitar o Xdebug na imagem, ajuste no `.env` da raiz:

```env
INSTALL_XDEBUG="true"
```

Depois reconstrua os containers:

```bash
docker compose down
docker compose up -d --build
```

Confirme se o Xdebug está disponível:

```bash
docker compose exec app php -m | grep xdebug
```

Para rodar a cobertura:

```bash
docker compose exec -e XDEBUG_MODE=coverage app php artisan test --coverage
```

A suíte atual foi validada com cobertura acima de 80%.

---
## Redis

O Redis é usado como driver de cache da aplicação.

O cache é aplicado no dashboard, especialmente nos dados agregados de desempenho.

Para verificar se o Laravel está usando Redis como cache:

```bash
docker compose exec app php artisan tinker
```

Dentro do Tinker:

```php
config('cache.default');
```

O esperado é:

```php
"redis"
```

Também é possível testar escrita e leitura:

```php
Cache::put('teste:redis', 'ok', 60);
Cache::get('teste:redis');
```

O esperado é:

```php
"ok"
```

---
## Comandos úteis

Acessar o container da aplicação:

```bash
docker compose exec app bash
```

Listar rotas Laravel:

```bash
docker compose exec app php artisan route:list
```

Limpar cache de configuração:

```bash
docker compose exec app php artisan config:clear
```

Limpar cache da aplicação:

```bash
docker compose exec app php artisan cache:clear
```

Ver logs dos containers:

```bash
docker compose logs -f
```

Ver logs do Nginx:

```bash
docker compose logs -f nginx
```

Ver logs da aplicação:

```bash
docker compose logs -f app
```

Parar os containers:

```bash
docker compose down
```

Parar e remover volumes:

```bash
docker compose down -v
```

---
## Observações sobre o ambiente

* O serviço `app` executa PHP-FPM.
* O serviço `nginx` expõe a aplicação na porta `8000`.
* O serviço `postgres` expõe o banco na porta configurada por `POSTGRES_PORT`.
* O serviço `redis` expõe o Redis na porta configurada por `REDIS_PORT`.
* As variáveis de banco, Redis, cache, fila e sessão são injetadas no container da aplicação pelo `docker-compose.yml`.
* O arquivo `app/.env.example` mantém apenas configurações próprias da aplicação Laravel, evitando duplicidade com a infraestrutura.

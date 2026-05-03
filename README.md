# Avaliação EAD

Projeto desenvolvido para o teste técnico da vaga de Developer PHP Pleno.

A aplicação consiste em uma plataforma simples de provas EAD, com área de professor, área de aluno, correção automática de provas, dashboard de desempenho e ranking de tentativas.

Este README descreve a configuração do ambiente de desenvolvimento e execução via Docker.

A documentação específica da aplicação Laravel, regras de negócio, API e decisões técnicas fica em [`app/README.md`](app/README.md).

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
- PHPUnit / Laravel Test
- Xdebug para cobertura de testes

---

## Estrutura geral

```text
.
├── app/                         # Aplicação Laravel
├── docker/
│   └── nginx/                   # Configuração do Nginx
├── docker-compose.yml           # Orquestração dos serviços principais
├── docker-compose.override.yml  # Ajustes locais de desenvolvimento
├── Dockerfile                   # Imagem PHP-FPM da aplicação
├── entrypoint.sh                # Inicialização automática da aplicação
├── .env.example                 # Variáveis usadas pelo Docker Compose
└── README.md                    # Documentação do ambiente
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

A aplicação é acessada pelo Nginx em:

```text
http://localhost:8000
```

A porta `5173` é exposta apenas para o Vite durante o desenvolvimento.

---

## Pré-requisitos

Antes de iniciar, é necessário ter instalado:

- Docker
- Docker Compose
- Git

Opcionalmente:

- `jq`, para visualizar respostas JSON no terminal.

Em distribuições baseadas em Ubuntu/Debian:

```bash
sudo apt install jq
```

---

## Instalação limpa

Clone o repositório:

```bash
git clone https://github.com/deivisson-souza-84lab/avaliacao-ead.git
cd avaliacao-ead
```

Crie o arquivo `.env` da raiz a partir do exemplo:

```bash
cp .env.example .env
```

Suba o ambiente:

```bash
docker compose up -d --build
```

Verifique se os serviços estão ativos:

```bash
docker compose ps
```

O esperado é que os serviços `app`, `nginx`, `postgres` e `redis` estejam em execução, com o PostgreSQL saudável.

A aplicação ficará disponível em:

```text
http://localhost:8000
```

A API ficará disponível em:

```text
http://localhost:8000/api
```

---

## Variáveis de ambiente

Este projeto usa uma separação entre variáveis de infraestrutura e variáveis da aplicação.

### `.env` da raiz

O `.env` da raiz é usado pelo Docker Compose.

Ele configura:

- versões de ferramentas;
- usuário e grupo usados no container em ambiente local;
- timezone;
- banco PostgreSQL;
- porta do Redis;
- instalação opcional do Xdebug.

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

### `app/.env`

O `app/.env` é usado pelo Laravel.

Esse arquivo é criado automaticamente pelo `entrypoint.sh` a partir de `app/.env.example`, caso ainda não exista.

As variáveis de infraestrutura da aplicação, como banco, Redis, cache, fila e sessão, são injetadas no container pelo `docker-compose.yml` através de `environment`.

Com isso, o Docker Compose fica como fonte principal da configuração de infraestrutura da aplicação.

---

## Preparação automática da aplicação

Na primeira subida do container `app`, o `entrypoint.sh` prepara automaticamente a aplicação Laravel.

Quando necessário, ele executa:

```bash
cp app/.env.example app/.env
composer install
php artisan key:generate
npm ci
npm run build
```

Ou seja, após criar o `.env` da raiz e subir os containers, não é necessário instalar dependências manualmente:

```bash
cp .env.example .env
docker compose up -d --build
```

O entrypoint faz as seguintes verificações:

| Verificação | Ação automática |
|---|---|
| `app/.env` não existe | copia `app/.env.example` para `app/.env` |
| `vendor/autoload.php` não existe | executa `composer install` |
| `APP_KEY` não está gerada | executa `php artisan key:generate --force` |
| `node_modules` não existe | executa `npm ci` |
| `public/build/manifest.json` não existe | executa `npm run build` |

A chave `APP_KEY` é necessária mesmo sem autenticação real, pois o Laravel a usa em recursos internos como criptografia, cookies, sessão e segurança da aplicação.

---

## Comandos manuais opcionais

Os comandos abaixo são úteis para manutenção, diagnóstico ou execução manual de etapas que o entrypoint já realiza automaticamente quando necessário.

Reinstalar dependências PHP:

```bash
docker compose exec app composer install
```

Gerar novamente a chave da aplicação:

```bash
docker compose exec app php artisan key:generate
```

Instalar dependências JavaScript respeitando o lockfile:

```bash
docker compose exec app npm ci
```

Gerar build frontend:

```bash
docker compose exec app npm run build
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

Teste o detalhe de uma prova na visão do professor:

```bash
curl -s http://localhost:8000/api/exams/1 \
  -H "Accept: application/json" | jq
```

Teste a visão do aluno, que não expõe o gabarito:

```bash
curl -s http://localhost:8000/api/student/exams/1 \
  -H "Accept: application/json" | jq
```

Teste o dashboard:

```bash
curl -s http://localhost:8000/api/dashboard \
  -H "Accept: application/json" | jq
```

Teste o ranking:

```bash
curl -s http://localhost:8000/api/dashboard/ranking \
  -H "Accept: application/json" | jq
```

---

## Documentação da API

A documentação principal dos endpoints está descrita em Markdown no arquivo `app/README.md`.

Além disso, o projeto inclui uma especificação formal da API no padrão OpenAPI 3.0:

```text
app/docs/openapi.yaml
```

A opção por um arquivo OpenAPI estático foi intencional para esta entrega. Como a aplicação já está em fase final de validação, evitou-se adicionar um pacote Swagger ao Laravel apenas para servir a documentação, reduzindo risco de regressão, novas dependências e mudanças em rotas ou providers.

O arquivo pode ser aberto em ferramentas compatíveis com OpenAPI/Swagger, como Swagger Editor, Swagger UI, Postman, Insomnia, Stoplight ou Redoc.

---

## Exemplo de submissão de prova

Após executar o seeder, a prova inicial possui 3 questões.

Exemplo de submissão:

```bash
curl -s -X POST http://localhost:8000/api/student/exams/1/submit \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "student_identifier": "aluno1@email.com",
    "student_name": "Aluno 1",
    "answers": [
      {"question_id": 1, "alternative_id": 1},
      {"question_id": 2, "alternative_id": 6},
      {"question_id": 3, "alternative_id": 11}
    ]
  }' | jq
```

Resposta esperada:

```http
201 Created
```

A API retorna pontuação, percentual e indicação de acerto ou erro por resposta.

### Segunda tentativa

O mesmo aluno não pode realizar a mesma prova mais de uma vez.

Exemplo de resposta esperada:

```http
422 Unprocessable Content
```

```json
{
  "message": "Este aluno já realizou esta prova.",
  "errors": {
    "student_identifier": [
      "Este aluno já realizou esta prova."
    ]
  }
}
```

Para obter respostas JSON em erros de validação, envie sempre:

```http
Accept: application/json
```

---

## Testes automatizados

Para rodar a suíte de testes:

```bash
docker compose exec app php artisan test
```

A suíte cobre os principais fluxos da aplicação:

- cadastro de provas;
- validação de questões e alternativas;
- edição e exclusão de provas;
- consulta de provas pelo aluno;
- ocultação do gabarito na visão do aluno;
- submissão de respostas;
- correção automática;
- bloqueio de segunda tentativa;
- validações de respostas inválidas;
- dashboard;
- ranking;
- cache do dashboard.

Resultado validado:

```text
Tests: 25 passed (121 assertions)
```

---

## Cobertura de testes

Para rodar a cobertura:

```bash
docker compose exec -e XDEBUG_MODE=coverage app php artisan test --coverage
```

A suíte atual foi validada com cobertura acima de 80%.

Resultado validado:

```text
Total: 96.7 %
```

Caso seja necessário reconstruir a imagem garantindo Xdebug, ajuste no `.env` da raiz:

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

---

## Frontend

A interface Vue.js é integrada ao Laravel via Vite.

A tela inicial permite escolher entre:

- Área do Professor;
- Área do Aluno.

A aplicação deve ser acessada por:

```text
http://localhost:8000
```

### Área do Professor

Permite:

- visualizar dashboard;
- listar provas;
- visualizar detalhes da prova com gabarito;
- cadastrar prova;
- editar prova;
- excluir prova;
- consultar ranking.

### Área do Aluno

Permite:

- listar provas disponíveis;
- visualizar prova sem gabarito;
- informar identificador e nome;
- responder questões;
- submeter prova;
- visualizar resultado com pontuação, percentual e acertos/erros.

### Vite

Em desenvolvimento, a porta `5173` fica disponível para o Vite.

A aplicação principal continua sendo acessada por:

```text
http://localhost:8000
```

O build de produção pode ser executado manualmente com:

```bash
docker compose exec app npm run build
```

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

Ver informações da aplicação:

```bash
docker compose exec app php artisan about
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

## Validação rápida da instalação

Um fluxo completo de validação a partir de uma instalação limpa:

```bash
git clone https://github.com/deivisson-souza-84lab/avaliacao-ead.git
cd avaliacao-ead
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan test
docker compose exec -e XDEBUG_MODE=coverage app php artisan test --coverage
docker compose exec app npm run build
```

Validar frontend:

```bash
curl -i http://localhost:8000
```

Validar API:

```bash
curl -s http://localhost:8000/api/exams \
  -H "Accept: application/json" | jq
```

---

## Observações sobre o ambiente

- O serviço `app` executa PHP-FPM.
- O serviço `nginx` expõe a aplicação na porta `8000`.
- O serviço `postgres` expõe o banco na porta configurada por `POSTGRES_PORT`.
- O serviço `redis` expõe o Redis na porta configurada por `REDIS_PORT`.
- As variáveis de banco, Redis, cache, fila e sessão são injetadas no container da aplicação pelo `docker-compose.yml`.
- O arquivo `app/.env.example` mantém apenas configurações próprias da aplicação Laravel, evitando duplicidade com a infraestrutura.
- O `docker-compose.override.yml` aplica ajustes locais de desenvolvimento, incluindo o usuário `${UID}:${GID}` e a exposição da porta `5173`.
- Não há autenticação real por decisão de escopo; professor e aluno são separados pela interface.

---

## Status da entrega

Implementado:

- API REST de provas;
- área do professor;
- área do aluno;
- cadastro de provas;
- edição de provas;
- exclusão de provas;
- listagem e detalhe de provas;
- consulta de provas disponíveis para alunos;
- ocultação do gabarito na visão do aluno;
- submissão de respostas;
- correção automática;
- pontuação e percentual;
- bloqueio de segunda tentativa;
- dashboard;
- ranking paginado;
- cache com Redis;
- testes automatizados;
- cobertura acima de 80%;
- interface Vue.js consumindo a API;
- execução via Docker/Docker Compose;
- entrypoint de preparação automática da aplicação.

Não há pendências funcionais conhecidas para o escopo implementado.

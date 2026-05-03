# Avaliação EAD — Aplicação Laravel

Este README descreve a aplicação Laravel do projeto **Avaliação EAD**, suas regras de negócio, arquitetura, endpoints da API, testes e decisões técnicas.

A documentação de ambiente, Docker, Nginx, PostgreSQL, Redis e execução dos containers está no [`README.md`](../README.md) da raiz do projeto.

---

## Visão geral

O Avaliação EAD é uma aplicação para criação, realização e correção automática de provas.

O sistema possui dois fluxos principais:

- **Professor**
  - cadastra provas;
  - cadastra questões;
  - cadastra alternativas;
  - define uma alternativa correta por questão;
  - edita e exclui provas;
  - consulta dashboard e ranking.

- **Aluno**
  - visualiza provas disponíveis;
  - acessa uma prova sem visualizar o gabarito;
  - responde as questões;
  - submete a prova;
  - recebe pontuação e percentual de acertos.

Não há autenticação real nesta versão. O teste solicitava apenas dois acessos separados no front-end: professor e aluno.

---

## Stack da aplicação

- PHP 8.4
- Laravel 12
- PostgreSQL
- Redis
- Vue.js
- PHPUnit / Laravel Test
- Docker / Docker Compose

---

## Interface Vue.js

A aplicação possui uma interface Vue.js integrada ao Laravel via Vite.

A tela inicial permite escolher entre:

- área do professor;
- área do aluno.

A aplicação separa os fluxos de professor e aluno pela interface, sem camada de autenticação, conforme o escopo definido para esta entrega.

### Área do professor

A área do professor permite:

- visualizar dashboard com média geral, melhor pontuação e total de tentativas;
- listar provas cadastradas;
- visualizar detalhes de uma prova, incluindo questões, alternativas e gabarito;
- cadastrar novas provas;
- editar provas existentes;
- excluir provas;
- consultar ranking de tentativas.

### Área do aluno

A área do aluno permite:

- listar provas disponíveis;
- acessar detalhes de uma prova sem visualizar o gabarito;
- selecionar alternativas;
- informar identificador e nome;
- submeter respostas;
- visualizar pontuação, percentual e acertos/erros após a correção automática.

### Vite em desenvolvimento

Durante o desenvolvimento, o Vite roda na porta `5173`.

A aplicação deve ser acessada por:

```text
http://localhost:8000
```

A porta `5173` é usada apenas para servir os assets em modo desenvolvimento.

---

## Regras de negócio implementadas

### Provas

Uma prova possui título, descrição opcional, status de disponibilidade e uma ou mais questões.

### Questões

Cada questão pertence a uma prova e possui enunciado e duas ou mais alternativas.

### Alternativas

Cada alternativa pertence a uma questão e possui texto e indicação se é correta ou não.

Regra principal:

> Cada questão deve possuir exatamente uma alternativa correta.

Essa regra é validada tanto no cadastro quanto na edição de provas.

### Área do aluno

O aluno pode listar apenas provas disponíveis, visualizar questões e alternativas, submeter respostas e receber o resultado da correção.

A API do aluno **não expõe o campo `is_correct`** ao consultar uma prova.

### Submissão de prova

Ao submeter uma prova:

- a prova precisa estar disponível;
- o aluno deve informar um identificador;
- todas as questões da prova devem ser respondidas;
- cada questão deve ser respondida apenas uma vez;
- a questão respondida deve pertencer à prova;
- a alternativa escolhida deve pertencer à questão informada;
- o aluno não pode realizar a mesma prova mais de uma vez.

A correção é automática e gera pontuação, total de questões, percentual de acertos e respostas registradas com indicação de acerto ou erro.

### Dashboard

O dashboard apresenta média geral das tentativas, melhor pontuação, total de tentativas e ranking paginado.

O ranking é ordenado por maior percentual, maior pontuação e data de submissão.

### Cache

O Redis é usado como driver de cache.

O cache é aplicado no dashboard, especialmente no resumo de desempenho.

Quando uma prova é submetida, o cache do resumo do dashboard é invalidado para que as métricas sejam recalculadas.

---

## Modelagem principal

### `exams`

Representa uma prova.

Campos principais:

- `id`
- `title`
- `description`
- `is_available`
- `created_at`
- `updated_at`

Relacionamentos:

- possui muitas questões;
- possui muitas tentativas.

### `questions`

Representa uma questão da prova.

Campos principais:

- `id`
- `exam_id`
- `statement`
- `created_at`
- `updated_at`

Relacionamentos:

- pertence a uma prova;
- possui muitas alternativas.

### `alternatives`

Representa uma alternativa de uma questão.

Campos principais:

- `id`
- `question_id`
- `text`
- `is_correct`
- `created_at`
- `updated_at`

Relacionamentos:

- pertence a uma questão.

### `exam_attempts`

Representa uma tentativa de realização de prova por um aluno.

Campos principais:

- `id`
- `exam_id`
- `student_identifier`
- `student_name`
- `score`
- `total_questions`
- `percentage`
- `submitted_at`
- `created_at`
- `updated_at`

Regra importante:

- existe uma chave única para `exam_id` + `student_identifier`.

Isso impede que o mesmo aluno realize a mesma prova mais de uma vez.

### `exam_attempt_answers`

Representa cada resposta enviada em uma tentativa.

Campos principais:

- `id`
- `exam_attempt_id`
- `question_id`
- `alternative_id`
- `is_correct`
- `created_at`
- `updated_at`

Regra importante:

- existe uma chave única para `exam_attempt_id` + `question_id`.

Isso impede respostas duplicadas para a mesma questão dentro de uma tentativa.

---

## Estrutura de camadas

A aplicação evita concentrar regras de negócio nos controllers.

Estrutura principal:

```text
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── DashboardController.php
│   │       ├── ExamController.php
│   │       └── StudentExamController.php
│   ├── Requests/
│   │   ├── StoreExamRequest.php
│   │   ├── SubmitExamRequest.php
│   │   └── UpdateExamRequest.php
│   └── Resources/
│       ├── ExamAttemptResource.php
│       ├── ExamResource.php
│       └── StudentExamResource.php
├── Models/
│   ├── Alternative.php
│   ├── Exam.php
│   ├── ExamAttempt.php
│   ├── ExamAttemptAnswer.php
│   └── Question.php
└── Services/
    ├── DashboardService.php
    ├── ExamService.php
    └── ExamSubmissionService.php
```

### Controllers

Responsáveis por receber a requisição, acionar requests, services e resources, e retornar a resposta HTTP.

### Requests

Responsáveis por validação de entrada.

### Services

Responsáveis pelas regras de negócio principais.

### Resources

Responsáveis por padronizar a saída JSON da API.

---

## Decisões técnicas

### Separação entre professor e aluno

Foram criados resources e endpoints separados para professor e aluno.

Isso evita que a API do aluno receba dados sensíveis, como o campo `is_correct`.

### Uso de transações

O cadastro, edição e submissão de provas usam transações para evitar persistência parcial.

Se uma prova for criada, mas a criação de uma alternativa falhar, nada deve ser salvo parcialmente.

### Edição de provas

Na edição, as questões e alternativas antigas são removidas e recriadas a partir do payload enviado.

Essa decisão simplifica o fluxo para o MVP e assume que o payload representa o estado final da prova.

Em uma versão mais robusta, seria possível bloquear edição de provas já respondidas ou implementar versionamento de provas.

### Identificação do aluno

Como o teste não exige login, o aluno é identificado por `student_identifier`.

Esse identificador pode representar e-mail, matrícula ou outro valor textual.

### Redis no dashboard

O Redis foi usado no dashboard porque é uma área de leitura agregada e potencialmente acessada com frequência.

---

## Endpoints da API

Base URL local:

```text
http://localhost:8000/api
```

Todos os exemplos consideram o header:

```http
Accept: application/json
```

Para requisições com corpo JSON:

```http
Content-Type: application/json
```

---

# Professor

## Listar provas

```http
GET /api/exams
```

Exemplo:

```bash
curl -s http://localhost:8000/api/exams \
  -H "Accept: application/json" | jq
```

Resposta resumida:

```json
{
  "data": [
    {
      "id": 1,
      "title": "Fundamentos de PHP e Laravel",
      "description": "Prova introdutória sobre PHP, Composer, Laravel e APIs REST.",
      "is_available": true,
      "created_at": "2026-05-02T22:05:47.000000Z",
      "updated_at": "2026-05-02T22:05:47.000000Z"
    }
  ],
  "links": {},
  "meta": {}
}
```

---

## Detalhar prova

```http
GET /api/exams/{exam}
```

Exemplo:

```bash
curl -s http://localhost:8000/api/exams/1 \
  -H "Accept: application/json" | jq
```

Na visão do professor, o gabarito é retornado:

```json
{
  "data": {
    "id": 1,
    "title": "Fundamentos de PHP e Laravel",
    "questions": [
      {
        "id": 1,
        "statement": "Qual comando instala as dependências PHP de um projeto?",
        "alternatives": [
          {
            "id": 1,
            "text": "composer install",
            "is_correct": true
          }
        ]
      }
    ]
  }
}
```

---

## Criar prova

```http
POST /api/exams
```

Exemplo de payload:

```json
{
  "title": "Prova de Docker",
  "description": "Prova criada via API",
  "is_available": true,
  "questions": [
    {
      "statement": "Qual comando sobe os containers em segundo plano?",
      "alternatives": [
        {
          "text": "docker compose up -d",
          "is_correct": true
        },
        {
          "text": "composer install",
          "is_correct": false
        }
      ]
    }
  ]
}
```

Exemplo:

```bash
curl -s -X POST http://localhost:8000/api/exams \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d @/tmp/exam-payload.json | jq
```

Resposta esperada:

```http
201 Created
```

---

## Editar prova

```http
PUT /api/exams/{exam}
PATCH /api/exams/{exam}
```

Exemplo de payload:

```json
{
  "title": "Prova de Docker Atualizada",
  "description": "Prova atualizada via API",
  "is_available": true,
  "questions": [
    {
      "statement": "Qual comando lista os containers em execução?",
      "alternatives": [
        {
          "text": "docker ps",
          "is_correct": true
        },
        {
          "text": "docker images",
          "is_correct": false
        }
      ]
    }
  ]
}
```

Resposta esperada:

```http
200 OK
```

---

## Excluir prova

```http
DELETE /api/exams/{exam}
```

Resposta esperada:

```http
204 No Content
```

---

# Aluno

## Listar provas disponíveis

```http
GET /api/student/exams
```

Exemplo:

```bash
curl -s http://localhost:8000/api/student/exams \
  -H "Accept: application/json" | jq
```

Retorna apenas provas disponíveis.

---

## Detalhar prova disponível

```http
GET /api/student/exams/{exam}
```

Exemplo:

```bash
curl -s http://localhost:8000/api/student/exams/1 \
  -H "Accept: application/json" | jq
```

Na visão do aluno, o gabarito não é retornado:

```json
{
  "data": {
    "id": 1,
    "title": "Fundamentos de PHP e Laravel",
    "questions": [
      {
        "id": 1,
        "statement": "Qual comando instala as dependências PHP de um projeto?",
        "alternatives": [
          {
            "id": 1,
            "text": "composer install"
          },
          {
            "id": 2,
            "text": "npm install"
          }
        ]
      }
    ]
  }
}
```

---

## Submeter prova

```http
POST /api/student/exams/{exam}/submit
```

Exemplo de payload:

```json
{
  "student_identifier": "aluno1@email.com",
  "student_name": "Aluno 1",
  "answers": [
    {
      "question_id": 1,
      "alternative_id": 1
    },
    {
      "question_id": 2,
      "alternative_id": 6
    },
    {
      "question_id": 3,
      "alternative_id": 11
    }
  ]
}
```

Exemplo:

```bash
curl -s -X POST http://localhost:8000/api/student/exams/1/submit \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d @/tmp/submit-exam-payload.json | jq
```

Resposta esperada:

```http
201 Created
```

Exemplo de resposta:

```json
{
  "data": {
    "id": 1,
    "student_identifier": "aluno1@email.com",
    "student_name": "Aluno 1",
    "score": 3,
    "total_questions": 3,
    "percentage": 100,
    "submitted_at": "2026-05-02T23:54:10.000000Z",
    "exam": {
      "id": 1,
      "title": "Fundamentos de PHP e Laravel",
      "description": "Prova introdutória sobre PHP, Composer, Laravel e APIs REST."
    },
    "answers": [
      {
        "question_id": 1,
        "alternative_id": 1,
        "is_correct": true
      }
    ]
  }
}
```

---

# Dashboard

## Resumo

```http
GET /api/dashboard
```

Exemplo:

```bash
curl -s http://localhost:8000/api/dashboard \
  -H "Accept: application/json" | jq
```

Resposta:

```json
{
  "data": {
    "average_score": 66.67,
    "best_score": 100,
    "total_attempts": 2
  }
}
```

---

## Ranking paginado

```http
GET /api/dashboard/ranking
```

Parâmetros:

| Parâmetro | Descrição | Padrão |
|---|---|---|
| `page` | Página atual | `1` |
| `per_page` | Itens por página | `10` |

O valor de `per_page` é limitado entre `1` e `50`.

Exemplo:

```bash
curl -s "http://localhost:8000/api/dashboard/ranking?per_page=10" \
  -H "Accept: application/json" | jq
```

Resposta resumida:

```json
{
  "data": [
    {
      "student_identifier": "aluno1@email.com",
      "student_name": "Aluno 1",
      "score": 3,
      "total_questions": 3,
      "percentage": 100
    }
  ],
  "links": {},
  "meta": {}
}
```

---

## Validações principais

### Prova sem alternativa correta

Payload inválido:

```json
{
  "title": "Prova inválida",
  "questions": [
    {
      "statement": "Pergunta sem resposta correta?",
      "alternatives": [
        {
          "text": "Alternativa A",
          "is_correct": false
        },
        {
          "text": "Alternativa B",
          "is_correct": false
        }
      ]
    }
  ]
}
```

Resposta:

```http
422 Unprocessable Content
```

```json
{
  "message": "Cada questão deve possuir exatamente uma alternativa correta.",
  "errors": {
    "questions.0.alternatives": [
      "Cada questão deve possuir exatamente uma alternativa correta."
    ]
  }
}
```

### Segunda tentativa do mesmo aluno

Resposta:

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

---

## Seeders

O projeto possui um seeder inicial com uma prova de exemplo:

```text
Fundamentos de PHP e Laravel
```

Ela possui:

- 3 questões;
- 4 alternativas por questão;
- 1 alternativa correta por questão.

Para recriar o banco com dados iniciais:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

---

## Testes

Rodar a suíte:

```bash
docker compose exec app php artisan test
```

Rodar cobertura:

```bash
docker compose exec -e XDEBUG_MODE=coverage app php artisan test --coverage
```

A suíte cobre:

- cadastro de provas;
- validação de alternativa correta;
- listagem e detalhe de provas;
- edição e exclusão de provas;
- consulta de provas pelo aluno;
- bloqueio de gabarito na visão do aluno;
- submissão de respostas;
- correção automática;
- bloqueio de segunda tentativa;
- validações de respostas inválidas;
- dashboard;
- ranking;
- uso de cache no dashboard.

A cobertura validada está acima de 80%.

---

## Status atual da implementação

Implementado:

- API REST de provas;
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
- interface Vue.js para professor;
- interface Vue.js para aluno;
- cadastro de provas pela interface;
- edição de provas pela interface;
- exclusão de provas pela interface;
- visualização de detalhes da prova pela interface;
- submissão de provas pela interface do aluno;
- exibição de resultado da prova pela interface.

Ainda pendente:

- Não há pendências funcionais conhecidas para o escopo implementado.

Possíveis melhorias futuras:

- paginação visual avançada no frontend;
- documentação OpenAPI/Swagger;
- versionamento ou bloqueio de edição para provas que já possuem tentativas;
- refinamento visual da interface;
- filtros e busca na listagem de provas;
- filtros no ranking por prova ou aluno.
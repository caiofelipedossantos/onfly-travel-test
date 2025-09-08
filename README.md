# Onfly Travel Test

Este projeto gerencia pedidos de viagem. O sistema utiliza Laravel 11, MySQL, Redis e autenticação JWT.

---

## 1. Requisitos

- Docker & Docker Compose
- PHP 8.2 (via container)
- Composer (via container)
- MySQL
- Redis
- Mailtrap (para teste de e-mail)

---

## 2. Estrutura de Containers

| Serviço | Descrição |
|---------|-----------|
| `onfly_travel_app` | Container PHP/Laravel |
| `onfly_travel_nginx` | Servidor web Nginx |
| `onfly_travel_db` | MySQL |
| `onfly_travel_redis` | Redis (queues) |
| `onfly_travel_queue` | Worker Laravel para processar jobs |

---

## 3. Inicializando os containers

1. Construa e inicie todos os containers:

docker compose up -d --build

2. Acesse o container da aplicação para rodar comandos Artisan:

docker compose exec onfly_travel_app bash

---

## 4. Configuração do ambiente

1. Copie o arquivo `.env.example` para `.env`:

cp .env.example .env

2. Configure as variáveis de banco de dados (MySQL) e Redis:

DB_CONNECTION=mysql  
DB_HOST=onfly_travel_db  
DB_PORT=3306  
DB_DATABASE=travel_requests  
DB_USERNAME=root  
DB_PASSWORD=senha  

CACHE_DRIVER=redis  
QUEUE_CONNECTION=redis  
REDIS_HOST=onfly_travel_redis  
REDIS_PORT=6379  

3. Configure o Mail (para teste com Mailtrap):

MAIL_MAILER=smtp  
MAIL_HOST=smtp.mailtrap.io  
MAIL_PORT=2525  
MAIL_USERNAME=<SEU_USER_MAILTRAP>  
MAIL_PASSWORD=<SEU_PASS_MAILTRAP>  
MAIL_FROM_ADDRESS=no-reply@example.com  
MAIL_FROM_NAME="Travel Requests"  

4. Gere chave Laravel e JWT:

php artisan key:generate  
php artisan jwt:secret  

---

## 5. Banco de dados

1. Execute migrations e seeders:

php artisan migrate --seed

2. Verifique se as tabelas foram criadas corretamente.

---

## 6. Executando o Worker da fila

O container `onfly_travel_queue` já roda o worker Laravel automaticamente. Para monitorar:

docker compose logs -f onfly_travel_queue

> Este worker processa jobs de envio de e-mails quando um pedido é **aprovado ou cancelado**.

---

## 7. Autenticação JWT

O sistema utiliza **JWT** para autenticação API. Os endpoints disponíveis:

| Método | Endpoint | Payload / Observações |
|--------|----------|----------------------|
| POST   | `/api/v1/register` | `{ "name": "...", "email": "...", "password": "...", "password_confirmation": "..." }` |
| POST   | `/api/v1/login` | `{ "email": "...", "password": "..." }` |
| GET    | `/api/v1/me` | Retorna dados do usuário autenticado. Requer Bearer Token. |
| POST   | `/api/v1/logout` | Encerra a sessão JWT do usuário. Requer Bearer Token. |

> **Observação:** Ao registrar, a senha deve ser confirmada (`password_confirmation`).

---

## 8. Travel Requests

### Criar um pedido de viagem

**Endpoint:** POST `/api/v1/travel-requests`  
**Exemplo de payload:**

{
  "external_id": "ORSC3450",
  "requestor_name": "Caio Felipe",
  "destination": "Curitiba-PR",
  "departure_date": "2025-09-28 05:00:00",
  "return_date": "2025-09-28 24:00:00"
}

**Autenticação:** Bearer Token (JWT)

---

### Atualizar status de um pedido

**Endpoint:** PUT `/api/v1/travel-requests/{uuid}`  
**Payload:** apenas o status

{
  "status": "approved" // ou "canceled"
}

**Regras:**

- Usuário não pode alterar o status do próprio pedido.
- Pedido cancelado não pode ser alterado novamente.
- Pedido aprovado com data de partida no passado não pode ser cancelado.

---

### Listar pedidos de viagem

**Endpoint:** GET `/api/v1/travel-requests`  
**Parâmetros via URL (opcional):**

- `status` → filtra pelo status (`requested`, `approved`, `canceled`)
- `destination` → filtra pelo destino (case-insensitive)
- `start_date` → filtra pedidos com `departure_date >= start_date`  
- `end_date` → filtra pedidos com `return_date <= end_date`

**Exemplo:**

GET `/api/v1/travel-requests?status=approved&destination=Curitiba&start_date=2025-09-01&end_date=2025-09-30`

**Autenticação:** Bearer Token (JWT)

---

## 9. Testes

1. Execute os testes dentro do container:

docker compose exec onfly_travel_app php artisan test

2. Os testes cobrem:

- Criação de pedidos de viagem
- Aprovação/cancelamento por outro usuário
- Restrições de status
- Listagem com filtros
- Soft delete

---

## 10. Observações importantes

- Jobs de envio de e-mail recebem apenas o **UUID** da Travel Request.
- Exclusão de pedidos é **soft delete**, mantendo histórico.
- As datas devem estar no formato `Y-m-d H:i:s`.
- Todos os endpoints protegidos exigem JWT válido no header `Authorization: Bearer <TOKEN>`.

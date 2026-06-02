# ranqueamento

Sistema para ranqueamento das habilitações do curso de letras

Falta verificar para reranqueamento:

- Alunos com trancamento total podem ou não participarem
- pegar exemplo de alunos com mais de 8 créditos que não poderiam participar

## Instalação

### 1. Clone o repositório

```bash
git clone LINK

cd ranqueamento
```

### 2. Build da imagem Docker

```bash
docker build --no-cache -t ranqueamento .
```

### 3. Configure o ambiente

```bash
cp .env.example .env
```

Edite o `.env` e ajuste as variáveis necessárias, especialmente:

```env
DB_HOST=mariadb               # nome do serviço no docker-compose
DB_DATABASE=ranqueamento
DB_USERNAME=ranqueamento
DB_PASSWORD=ranqueamento

SENHAUNICA_KEY=               # credenciais da Senha Única USP
SENHAUNICA_SECRET=
SENHAUNICA_CALLBACK_ID=
SENHAUNICA_ADMINS=            # número(s) USP dos administradores
```

### 4. Suba os containers

```bash
docker compose up -d
```

### 5. Gere a chave da aplicação

```bash
docker exec -it ranqueamento php artisan key:generate
```

### 6. Execute as migrations

```bash
docker exec -it ranqueamento php artisan migrate
```

A aplicação estará disponível em **http://127.0.0.1:8000**.

---

## Testes com Dusk

### 1. Crie o banco de dados de testes

```bash
docker compose exec mariadb mariadb -u root -pranqueamento \
  -e "CREATE DATABASE IF NOT EXISTS ranqueamento_dusk;"

docker compose exec mariadb mariadb -u root -pranqueamento \
  -e "GRANT ALL PRIVILEGES ON ranqueamento_dusk.* TO 'ranqueamento'@'%'; FLUSH PRIVILEGES;"
```

### 2. Configure o ambiente Dusk

```bash
cp .env.dusk.local.example .env.dusk.local
```

Edite o `.env.dusk.local` e cole o valor de `APP_KEY` do seu `.env`:

```env
APP_KEY=    # copie do seu .env
```

### 3. Instale o Dusk

```bash
docker exec -it ranqueamento composer require --dev laravel/dusk
docker exec -it ranqueamento php artisan dusk:install
```

### 4. Execute os testes

```bash
docker exec -it ranqueamento php artisan dusk
```

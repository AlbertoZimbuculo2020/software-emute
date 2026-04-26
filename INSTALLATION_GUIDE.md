# Guia de Instalação e Configuração - Emute ERP

Este documento fornece as instruções necessárias para clonar, instalar e executar o projecto **Emute ERP** num novo ambiente.

## Pró-requisitos

Antes de começar, certifique-se de que tem as seguintes ferramentas instaladas:

1.  **PHP 8.2 ou superior**
2.  **Composer** (Gestor de dependências PHP)
3.  **Node.js & NPM** (Gestor de dependências JavaScript)
4.  **Servidor SQL Server** (MS SQL Server)
5.  **Drivers SQLSRV** instalados no PHP (necessário para conexão com a base de dados).

---

## Passos para Instalação

### 1. Clonar o Projecto
Abra o seu terminal e execute:
```bash
git clone <url-do-repositorio>
cd emute
```

### 2. Instalar Dependências PHP
```bash
composer install
```

### 3. Instalar Dependências JavaScript
```bash
npm install
```

### 4. Configurar Variáveis de Ambiente
Copie o ficheiro de exemplo `.env.example` para `.env`:
```bash
cp .env.example .env
```

Abra o ficheiro `.env` e configure as credenciais da sua base de dados (SQL Server):
```env
DB_CONNECTION=sqlsrv
DB_HOST=127.0.0.1
DB_PORT=1433
DB_DATABASE=emute
DB_USERNAME=sa
DB_PASSWORD=sua_senha
```

### 5. Gerar Chave da Aplicação
```bash
php artisan key:generate
```

### 6. Configurar a Base de Dados
Crie a base de dados no seu SQL Server com o nome definido no `.env` e execute as migrações:
```bash
php artisan migrate
```

*(Opcional) Se houver dados iniciais recomendados:*
```bash
php artisan db:seed
```

### 7. Povoar Dados Iniciais (Script de Teste)
Para configurar médicos, pacientes e exames base para teste, execute:
```bash
php populate_system.php
```

### 8. Compilar Assets Frontend
```bash
npm run build
# OU para desenvolvimento em tempo real:
npm run dev
```

### 9. Iniciar o Servidor Local
```bash
php artisan serve
```
A aplicação estará disponível em `http://127.0.0.1:8000`.

---

## Observações Adicionais

### Erros de Driver SQL Server
Se encontrar o erro `could not find driver`, certifique-se de que as extensões `pdo_sqlsrv` e `sqlsrv` estão habilitadas no seu `php.ini`.

### Permissões de Pasta
Em ambientes Linux, pode ser necessário dar permissão às pastas `storage` e `bootstrap/cache`:
```bash
chmod -R 775 storage bootstrap/cache
```

### Autenticação Legacy
O sistema utiliza uma criptografia **SHA-512** para compatibilidade com sistemas Desktop legados. Caso precise migrar utilizadores, utilize o formato SHA-512 exacto na coluna `SENHA`.

# Guia de Instalação e Configuração - Emute ERP

Este documento fornece as instruções necessárias para clonar, instalar e executar o projecto **Emute ERP** num novo ambiente.

## Pró-requisitos

Antes de começar, certifique-se de que tem as seguintes ferramentas instaladas. Pode descarregá-las através dos links oficiais:

1.  **[PHP 8.2 ou superior](https://www.php.net/downloads)**
2.  **[Composer](https://getcomposer.org/download/)** (Gestor de dependências PHP)
3.  **[Node.js & NPM](https://nodejs.org/)** (Gestor de dependências JavaScript)
4.  **[MS SQL Server Express](https://www.microsoft.com/en-us/sql-server/sql-server-downloads)** (Servidor de Base de Dados)
5.  **[Drivers Microsoft PHP para SQL Server](https://learn.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server)** (Obrigatório para conexão .env)

---

## Passos para Instalação

### 1. Clonar o Projecto
Abra o seu terminal e execute:
```bash
git clone https://github.com/AlbertoZimbuculo2020/software-emute.git
cd software-emute
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
Se encontrar o erro `could not find driver`, siga estes passos:
1. Descarregue os drivers (Link no topo).
2. Extraia os ficheiros `.dll` para a pasta `ext` do seu PHP.
3. No ficheiro `php.ini`, adicione ou retire o comentário das linhas:
   ```ini
   extension=php_pdo_sqlsrv_8x_nts_x64.dll
   extension=php_sqlsrv_8x_nts_x64.dll
   ```
4. Reinicie o servidor Apache/Nginx.

### Permissões de Pasta
Em ambientes Linux, pode ser necessário dar permissão às pastas `storage` e `bootstrap/cache`:
```bash
chmod -R 775 storage bootstrap/cache
```

### Autenticação Legacy
O sistema utiliza uma criptografia **SHA-512** para compatibilidade com sistemas Desktop legados. Caso precise migrar utilizadores, utilize o formato SHA-512 exacto na coluna `SENHA`.

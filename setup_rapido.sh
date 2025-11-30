#!/bin/bash

echo "🔧 SETUP RÁPIDO PREGÃO MARKETPLACE"
echo "======================================"

# 1. Instalar dependências
echo "📦 1. Instalando dependências..."
composer install --no-dev

# 2. Configurar .env com SQLite
echo "⚙️  2. Configurando ambiente..."
if [ ! -f ".env" ]; then
    cp .env.example .env
fi

# Configurar para SQLite
cat > .env << EOL
APP_NAME="PREGÃO Marketplace"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=$(pwd)/database/database.sqlite

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=public
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@pregao.ao"
MAIL_FROM_NAME="PREGÃO Marketplace"
EOL

# 3. Criar base de dados SQLite
echo "🗄️  3. Criando base de dados..."
touch database/database.sqlite
chmod 755 database/database.sqlite

# 4. Gerar chave da aplicação
echo "🔑 4. Gerando chave..."
php artisan key:generate

# 5. Executar migrações
echo "📊 5. Executando migrações..."
php artisan migrate:fresh

# 6. Popular base de dados
echo "🌱 6. Populando dados..."
php artisan db:seed

# 7. Criar link de storage
echo "📁 7. Configurando storage..."
php artisan storage:link

# 8. Limpar cache
echo "🧹 8. Limpando cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear

echo ""
echo "🎉 SETUP CONCLUÍDO!"
echo "===================="
echo ""
echo "📋 CREDENCIAIS:"
echo "   👑 Admin:    admin@pregao.ao / admin123"
echo "   🛍️  Vendedor: vendedor@pregao.ao / vendedor123"
echo "   👤 Cliente:  cliente@pregao.ao / cliente123"
echo "   🚚 Motorista: motorista@pregao.ao / motorista123"
echo ""
echo "🚀 PARA INICIAR:"
echo "   php artisan serve"
echo ""
echo "🌐 ACESSAR: http://localhost:8000"
echo "📚 API: http://localhost:8000/api"
echo ""
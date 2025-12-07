#!/bin/bash
echo "=== Teste COMPLETO da API PREGÃO ==="
echo "Servidor: http://localhost:8000"
echo ""

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m' # No Color

test_route() {
    local name=$1
    local method=$2
    local endpoint=$3
    local data=$4
    local headers=$5
    
    echo -n "Testando $name... "
    
    if [ "$method" = "POST" ] || [ "$method" = "PUT" ]; then
        response=$(curl -s -X $method "http://localhost:8000$endpoint" \
            -H "Content-Type: application/json" \
            $headers \
            -d "$data" 2>/dev/null)
    else
        response=$(curl -s -X $method "http://localhost:8000$endpoint" \
            $headers 2>/dev/null)
    fi
    
    if echo "$response" | grep -q "error\|Error"; then
        echo -e "${RED}✗ ERRO${NC}"
        echo "  Resposta: $response"
    else
        echo -e "${GREEN}✓ OK${NC}"
    fi
}

echo "1. Rotas públicas:"
test_route "Teste API" "GET" "/api/test"
test_route "Listar produtos" "GET" "/api/products"
test_route "Listar lojas" "GET" "/api/stores"
test_route "Ver produto 1" "GET" "/api/products/1"

echo -e "\n2. Autenticação:"
LOGIN_RESPONSE=$(curl -s -X POST "http://localhost:8000/api/login" \
    -H "Content-Type: application/json" \
    -d '{"email":"admin@pregao.ao","password":"admin123"}')

TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)
echo "Token obtido: ${TOKEN:0:20}..."

echo -e "\n3. Rotas autenticadas:"
test_route "Perfil utilizador" "GET" "/api/user" "" "-H \"Authorization: Bearer $TOKEN\""
test_route "Dashboard admin" "GET" "/api/admin/dashboard-stats" "" "-H \"Authorization: Bearer $TOKEN\""
test_route "Listar utilizadores" "GET" "/api/users" "" "-H \"Authorization: Bearer $TOKEN\""

echo -e "\n4. Carrinho e pedidos:"
test_route "Adicionar ao carrinho" "POST" "/api/cart/items" '{"product_id":1,"quantity":2,"store_id":1}' "-H \"Authorization: Bearer $TOKEN\""
test_route "Ver carrinho" "GET" "/api/cart" "" "-H \"Authorization: Bearer $TOKEN\""
test_route "Criar pedido" "POST" "/api/orders" '{"payment_method":"cash"}' "-H \"Authorization: Bearer $TOKEN\""
test_route "Listar pedidos" "GET" "/api/orders" "" "-H \"Authorization: Bearer $TOKEN\""

echo -e "\n5. Gestão Seller:"
test_route "Criar produto" "POST" "/api/products" '{"name":"Novo Produto","price":1000}' "-H \"Authorization: Bearer $TOKEN\""
test_route "Meus produtos" "GET" "/api/my-products" "" "-H \"Authorization: Bearer $TOKEN\""

echo -e "\n${GREEN}✅ Testes completos!${NC}"
echo "Agora pode testar todas as rotas no Postman!"

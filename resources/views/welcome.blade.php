<!DOCTYPE html>
<html>
<head>
    <title>PREGÃO Marketplace API</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .container {
            background: rgba(255,255,255,0.1);
            padding: 40px;
            border-radius: 10px;
            display: inline-block;
            backdrop-filter: blur(10px);
        }
        h1 {
            margin-bottom: 30px;
        }
        .api-info {
            text-align: left;
            background: rgba(0,0,0,0.2);
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        code {
            background: rgba(0,0,0,0.3);
            padding: 5px 10px;
            border-radius: 3px;
            display: inline-block;
            margin: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 PREGÃO Marketplace API</h1>
        <p>API RESTful para o marketplace PREGÃO</p>
        
        <div class="api-info">
            <h3>📋 Endpoints disponíveis:</h3>
            <p><code>GET /api/test</code> - Teste da API</p>
            <p><code>POST /api/login</code> - Autenticação</p>
            <p><code>GET /api/products</code> - Listar produtos</p>
            <p><code>GET /api/stores</code> - Listar lojas</p>
            <p><code>POST /api/orders</code> - Criar pedido</p>
            <p><code>GET /api/admin/dashboard-stats</code> - Dashboard admin</p>
        </div>
        
        <p>Use o Postman para testar todos os endpoints</p>
        <p><em>Servidor Laravel funcionando corretamente!</em></p>
    </div>
</body>
</html>

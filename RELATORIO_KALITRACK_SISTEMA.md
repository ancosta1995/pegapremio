# Relatório Técnico - Sistema Kalitrack
## Sistema Proxy de Tracking para Plataformas de Publicidade

---

## 📋 Índice

1. [Visão Geral](#1-visão-geral)
2. [Arquitetura do Sistema](#2-arquitetura-do-sistema)
3. [Estrutura de Banco de Dados](#3-estrutura-de-banco-de-dados)
4. [APIs e Endpoints](#4-apis-e-endpoints)
5. [Fluxo de Dados](#5-fluxo-de-dados)
6. [Integrações](#6-integrações)
7. [Painel Administrativo](#7-painel-administrativo)
8. [Sistema de Logs](#8-sistema-de-logs)
9. [Segurança](#9-segurança)
10. [Especificações Técnicas](#10-especificações-técnicas)
11. [Roadmap de Desenvolvimento](#11-roadmap-de-desenvolvimento)
12. [Exemplos de Uso](#12-exemplos-de-uso)

---

## 1. Visão Geral

### 1.1. Objetivo
O Kalitrack é um sistema proxy intermediário que centraliza o envio de eventos de tracking para múltiplas plataformas de publicidade (Kwai/AdsNebula, Facebook). Ele atua como uma camada de abstração entre aplicações clientes e as APIs de tracking, oferecendo:

- **Centralização**: Um único ponto de entrada para todos os eventos
- **Flexibilidade**: Suporte a múltiplas plataformas de tracking
- **Gerenciamento**: Painel administrativo para configurar pixels
- **Auditoria**: Sistema completo de logs

### 1.2. Casos de Uso
- Aplicações que precisam enviar eventos para múltiplas plataformas
- Sistemas que requerem logs centralizados de tracking
- Projetos que precisam de flexibilidade para adicionar novas plataformas
- Aplicações multi-tenant que gerenciam múltiplos pixels

### 1.3. Plataformas Suportadas
- **Kwai (AdsNebula)**: Plataforma de publicidade do Kwai
- **Facebook Pixel**: Sistema de tracking do Facebook/Meta

---

## 2. Arquitetura do Sistema

### 2.1. Diagrama de Arquitetura

```
┌─────────────────┐
│  Cliente App    │
│  (Laravel/Vue)  │
└────────┬────────┘
         │
         │ POST /api/tracking/events
         │ Bearer Token
         ▼
┌─────────────────────────────────┐
│   Kalitrack API Endpoint        │
│   /api/tracking_events.php      │
│                                 │
│   - Validação de autenticação   │
│   - Detecção de plataforma      │
│   - Busca de configuração       │
│   - Roteamento de eventos       │
└────────┬────────────────────────┘
         │
         ├─────────────────┬─────────────────┐
         │                 │                 │
         ▼                 ▼                 ▼
┌──────────────┐  ┌──────────────┐
│   Kwai       │  │   Facebook    │
│ AdsNebula    │  │  Graph API    │
└──────────────┘  └──────────────┘
```

### 2.2. Componentes Principais

#### 2.2.1. API Endpoint (`tracking_events.php`)
- Recebe eventos via POST
- Valida autenticação Bearer Token
- Detecta plataforma (Kwai ou Facebook)
- Busca configuração do pixel no banco
- Roteia evento para API correta
- Retorna resposta ao cliente

#### 2.2.2. Sistema de Logs (`logs/tracking.php`)
- `logTrackingEvent()`: Sistema de logs de eventos
- Funções auxiliares para formatação de dados
- Armazenamento em arquivos JSON por data

#### 2.2.3. Painel Administrativo (`admin/tracking.php`)
- CRUD de pixels (Kwai e Facebook)
- Interface web para gerenciamento
- Estatísticas de pixels cadastrados

#### 2.2.4. Banco de Dados
- Tabela `trackings`: Armazena configurações de pixels
- Tabela de logs (opcional): Histórico de eventos

---

## 3. Estrutura de Banco de Dados

### 3.1. Tabela `trackings`

```sql
CREATE TABLE `trackings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `source` VARCHAR(50) NOT NULL COMMENT 'kwai ou facebook',
  `pixel_id` VARCHAR(255) NOT NULL,
  `access_token` TEXT NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_pixel` (`source`, `pixel_id`),
  KEY `idx_source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Campos:
- **id**: ID único do registro
- **source**: Plataforma (`kwai` ou `facebook`)
- **pixel_id**: ID do pixel na plataforma
- **access_token**: Token de acesso da API
- **description**: Descrição opcional do pixel
- **created_at**: Data de criação
- **updated_at**: Data de atualização

### 3.2. Tabela de Logs (Opcional)

```sql
CREATE TABLE `tracking_logs` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `event_type` VARCHAR(50) NOT NULL,
  `pixel_id` VARCHAR(255) NOT NULL,
  `click_id` VARCHAR(255) NOT NULL,
  `source` VARCHAR(50) NOT NULL,
  `value` DECIMAL(10,2) DEFAULT NULL,
  `content_id` VARCHAR(255) DEFAULT NULL,
  `request_id` VARCHAR(100) NOT NULL,
  `status` ENUM('success', 'error') NOT NULL,
  `error_message` TEXT DEFAULT NULL,
  `response_data` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pixel_id` (`pixel_id`),
  KEY `idx_click_id` (`click_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_event_type` (`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 4. APIs e Endpoints

### 4.1. Endpoint Principal: `/api/tracking/events`

#### Método: `POST`
#### Autenticação: `Bearer Token` (Header: `Authorization: Bearer {token}`)

#### Headers:
```
Content-Type: application/json
Authorization: Bearer track123456
```

#### Request Body (Kwai):
```json
{
  "click_id": "abc123xyz",
  "pixel_id": "290714800540320",
  "event_name": "EVENT_COMPLETE_REGISTRATION",
  "value": 0,
  "currency": "BRL",
  "content_id": "user_123",
  "content_type": "user",
  "content_name": "Registration"
}
```

#### Request Body (Facebook):
```json
{
  "fbclid": "fb.1.1234567890.abc123",
  "pixel_id": "123456789012345",
  "event_name": "EVENT_COMPLETE_REGISTRATION",
  "email": "user@example.com",
  "phone": "+5511999999999",
  "value": 0,
  "currency": "BRL"
}
```

#### Response Success (200):
```json
{
  "success": true,
  "message": "Event sent successfully"
}
```

#### Response Error (400):
```json
{
  "error": "Missing click_id or fbclid"
}
```

#### Response Error (401):
```json
{
  "error": "Unauthorized"
}
```

#### Response Error (500):
```json
{
  "success": false,
  "error": "HTTP 500",
  "resp": "Error details"
}
```

### 4.2. Eventos Suportados

#### Kwai:
- `EVENT_CONTENT_VIEW`
- `EVENT_COMPLETE_REGISTRATION`
- `EVENT_ADD_TO_CART`
- `EVENT_PURCHASE`

#### Facebook:
- `EVENT_CONTENT_VIEW` → `ViewContent`
- `EVENT_COMPLETE_REGISTRATION` → `CompleteRegistration`
- `EVENT_PURCHASE` → `Purchase`

---

## 5. Fluxo de Dados

### 5.1. Fluxo Completo (Kwai)

```
1. Cliente envia POST /api/tracking/events
   ├─ Headers: Authorization: Bearer {token}
   └─ Body: { click_id, pixel_id, event_name, ... }

2. API valida autenticação
   ├─ Verifica Bearer Token
   └─ Se inválido → 401 Unauthorized

3. API detecta plataforma
   ├─ Se tem click_id → Kwai
   └─ Se tem fbclid → Facebook

4. API busca configuração
   ├─ SELECT access_token FROM trackings 
   │  WHERE source = 'kwai' AND pixel_id = ?
   └─ Se não encontrado → 400 Bad Request

5. API monta payload para Kwai
   ├─ access_token
   ├─ clickid
   ├─ event_name
   ├─ pixelId
   └─ properties (JSON string)

6. API envia para AdsNebula
   ├─ POST https://www.adsnebula.com/log/common/api
   └─ Headers: Content-Type: application/json

7. API retorna resposta
   ├─ Sucesso → 200 OK
   └─ Erro → 500 Internal Server Error
```

### 5.2. Fluxo de Logs

```
1. Evento é enviado com sucesso
   ├─ Sistema registra no log
   ├─ Formato: JSON com timestamp
   └─ Local: dino/logs/tracking_events_YYYY-MM-DD.log

2. Log contém:
   ├─ timestamp
   ├─ eventType
   ├─ pixelId
   ├─ clickId
   ├─ value (se houver)
   └─ request_id único
```

### 5.3. Fluxo Facebook

```
1. Cliente envia evento com fbclid
   └─ Body: { fbclid, pixel_id, event_name, email, phone, value }

2. API mapeia evento
   ├─ EVENT_COMPLETE_REGISTRATION → CompleteRegistration
   └─ EVENT_PURCHASE → Purchase

3. API prepara dados do usuário
   ├─ email → SHA256 hash
   ├─ phone → SHA256 hash
   └─ fbc → fbclid

4. API monta payload Facebook
   ├─ event_name
   ├─ event_time (timestamp)
   ├─ action_source: 'website'
   ├─ user_data (hashed)
   └─ custom_data (value, currency)

5. API envia para Facebook Graph API
   ├─ POST https://graph.facebook.com/v19.0/{pixel_id}/events
   └─ ?access_token={access_token}

6. API retorna resposta
```

---

## 6. Integrações

### 6.1. Kwai (AdsNebula)

#### Endpoint:
```
POST https://www.adsnebula.com/log/common/api
```

#### Payload:
```json
{
  "access_token": "RVLUO5lPW4vzx1BNP1ous2qPmozuVmuXaKt_BklSIXo",
  "clickid": "abc123xyz",
  "event_name": "EVENT_COMPLETE_REGISTRATION",
  "is_attributed": 1,
  "mmpcode": "PL",
  "pixelId": "290714800540320",
  "pixelSdkVersion": "9.9.9",
  "properties": "{\"content_id\":\"user_123\",\"content_type\":\"user\",\"value\":0,\"currency\":\"BRL\"}",
  "testFlag": false,
  "third_party": "shopline",
  "trackFlag": false
}
```

#### Headers:
```
Content-Type: application/json
```

### 6.2. Facebook Graph API

#### Endpoint:
```
POST https://graph.facebook.com/v19.0/{pixel_id}/events?access_token={access_token}
```

#### Payload:
```json
{
  "data": [{
    "event_name": "CompleteRegistration",
    "event_time": 1703123456,
    "action_source": "website",
    "event_id": "evt_abc123xyz",
    "event_source_url": "https://example.com",
    "user_data": {
      "fbc": "fb.1.1234567890.abc123",
      "em": ["sha256_hash_of_email"],
      "ph": ["sha256_hash_of_phone"],
      "client_ip_address": "192.168.1.1",
      "client_user_agent": "Mozilla/5.0..."
    },
    "custom_data": {
      "value": 0,
      "currency": "BRL"
    }
  }]
}
```

#### Headers:
```
Content-Type: application/json
```


---

## 7. Painel Administrativo

### 7.1. Funcionalidades

#### 7.1.1. Gerenciamento de Pixels
- **Cadastrar Pixel**: Adicionar novo pixel (Kwai ou Facebook)
- **Editar Pixel**: Atualizar configurações existentes
- **Remover Pixel**: Deletar pixel do sistema
- **Listar Pixels**: Visualizar todos os pixels cadastrados

#### 7.1.2. Interface
- **Dashboard**: Estatísticas de pixels
- **Filtros**: Separar por plataforma (Kwai/Facebook)
- **Formulários**: Interface para CRUD

### 7.2. Estrutura de Arquivos

```
admin/
├── tracking.php          # Página principal de gerenciamento
├── includes/
│   ├── auth.php         # Autenticação de admin
│   └── config.php       # Configurações do painel
└── css/
    └── tailwind.css     # Estilos
```

### 7.3. Formulário de Cadastro

#### Campos:
- **Plataforma**: Select (Kwai ou Facebook)
- **Pixel ID**: Text input
- **Access Token**: Textarea
- **Descrição**: Text input (opcional)

#### Validações:
- Plataforma obrigatória
- Pixel ID obrigatório
- Access Token obrigatório
- Pixel ID único por plataforma

### 7.4. Listagem de Pixels

#### Colunas:
- ID
- Pixel ID
- Descrição
- Data de Criação
- Ações (Editar/Remover)

#### Filtros:
- Por plataforma (Kwai/Facebook)
- Ordenação por data

---

## 8. Sistema de Logs

### 8.1. Logs de Arquivo

#### Localização:
```
logs/tracking_events_YYYY-MM-DD.log
```

#### Formato:
```json
{
  "timestamp": "2024-12-20 15:30:45",
  "eventType": "Purchase",
  "pixelId": "290714800540320",
  "clickId": "abc123xyz",
  "value": 50.00,
  "contentId": "deposito",
  "request_id": "track_abc123xyz"
}
```

### 8.2. Eventos Logados
- `CompleteRegistration`
- `AddToCart`
- `Purchase`

### 8.3. Logs de Banco de Dados (Opcional)

#### Tabela `tracking_logs`:
- Armazena todos os eventos enviados
- Status de sucesso/erro
- Resposta da API
- Timestamp completo

---

## 9. Segurança

### 9.1. Autenticação

#### Bearer Token
- Token fixo configurável: `track123456`
- Validado no header `Authorization`
- Se inválido → 401 Unauthorized

#### Recomendações:
- Usar token único por cliente
- Implementar rotação de tokens
- Armazenar tokens em variáveis de ambiente

### 9.2. Validação de Dados

#### Inputs Validados:
- JSON válido
- Campos obrigatórios presentes
- Tipos de dados corretos
- Eventos válidos para plataforma

### 9.3. CORS

#### Configuração:
```php
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    $u = parse_url($_SERVER['HTTP_ORIGIN']);
    $origin = $u['scheme'].'://'.$u['host'];
    if ($origin === $expected_origin) {
        header("Access-Control-Allow-Origin: $origin");
    }
}
```

### 9.4. Proteção de Dados

#### Facebook:
- Email e telefone são hasheados com SHA256
- Dados sensíveis não são armazenados em logs

#### Recomendações:
- HTTPS obrigatório
- Rate limiting
- Validação de IP (opcional)

---

## 10. Especificações Técnicas

### 10.1. Stack Tecnológica

#### Backend:
- **Linguagem**: PHP 7.4+
- **Banco de Dados**: MySQL 5.7+ / MariaDB 10.3+
- **PDO**: Para conexão com banco
- **cURL**: Para requisições HTTP

#### Frontend (Admin):
- **HTML5/CSS3**
- **JavaScript Vanilla**
- **Tailwind CSS** (opcional)

### 10.2. Requisitos do Servidor

#### PHP:
- PHP 7.4 ou superior
- Extensão `curl`
- Extensão `pdo_mysql`
- Extensão `json`
- Extensão `mbstring`

#### MySQL:
- MySQL 5.7+ ou MariaDB 10.3+
- InnoDB engine
- UTF8MB4 charset

### 10.3. Configurações

#### Timezone:
```php
date_default_timezone_set('America/Sao_Paulo');
```

#### Charset:
```php
$charset = 'utf8mb4';
```

#### Timeout cURL:
```php
CURLOPT_TIMEOUT => 10
CURLOPT_CONNECTTIMEOUT => 10
```

### 10.4. Estrutura de Diretórios

```
kalitrack/
├── api/
│   ├── tracking_events.php    # Endpoint principal
│   └── db.php                 # Conexão com banco
├── admin/
│   ├── tracking.php           # Painel admin
│   ├── includes/
│   │   ├── auth.php
│   │   └── config.php
│   └── css/
│       └── tailwind.css
├── logs/
│   ├── tracking.php           # Funções de logs
│   └── tracking_events_*.log   # Arquivos de log
└── index.php                   # Página inicial (opcional)
```

---

## 11. Roadmap de Desenvolvimento

### 11.1. Fase 1: Estrutura Base (2-3 dias)

#### Tarefas:
- [ ] Configurar banco de dados
- [ ] Criar tabela `trackings`
- [ ] Criar tabela `tracking_logs` (opcional)
- [ ] Configurar conexão PDO
- [ ] Criar estrutura de diretórios

#### Entregáveis:
- Banco de dados configurado
- Estrutura de arquivos criada

### 11.2. Fase 2: API Endpoint (2-3 dias)

#### Tarefas:
- [ ] Implementar endpoint `/api/tracking/events`
- [ ] Sistema de autenticação Bearer Token
- [ ] Validação de payload
- [ ] Detecção de plataforma (Kwai/Facebook)
- [ ] Busca de configuração no banco
- [ ] Integração com Kwai (AdsNebula)
- [ ] Integração com Facebook Graph API
- [ ] Tratamento de erros

#### Entregáveis:
- API funcional para Kwai
- API funcional para Facebook
- Documentação de endpoints

### 11.3. Fase 3: Sistema de Logs (1 dia)

#### Tarefas:
- [ ] Implementar `logTrackingEvent()` em `logs/tracking.php`
- [ ] Criar estrutura de diretórios para logs
- [ ] Sistema de rotação de logs (opcional)
- [ ] Formatação JSON dos logs
- [ ] Integração com endpoint principal

#### Entregáveis:
- Sistema de logs de arquivo funcional
- Logs sendo gerados corretamente após cada evento

### 11.4. Fase 4: Painel Administrativo (3-4 dias)

#### Tarefas:
- [ ] Criar página de listagem de pixels
- [ ] Formulário de cadastro
- [ ] Formulário de edição
- [ ] Sistema de exclusão
- [ ] Filtros por plataforma
- [ ] Estatísticas (dashboard)
- [ ] Autenticação de admin

#### Entregáveis:
- Painel admin completo
- CRUD de pixels funcional

### 11.5. Fase 5: Melhorias e Otimizações (1-2 dias)

#### Tarefas:
- [ ] Sistema de logs no banco (opcional)
- [ ] Rate limiting
- [ ] Cache de configurações
- [ ] Dashboard de estatísticas
- [ ] Testes de carga
- [ ] Documentação completa

#### Entregáveis:
- Sistema otimizado
- Documentação final

### 11.6. Total Estimado: 8-12 dias

---

## 12. Exemplos de Uso

### 12.1. Exemplo 1: Enviar Evento de Registro (Kwai)

#### Cliente (JavaScript):
```javascript
const response = await fetch('https://kalitrack.example.com/api/tracking/events', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer track123456'
  },
  body: JSON.stringify({
    click_id: 'abc123xyz',
    pixel_id: '290714800540320',
    event_name: 'EVENT_COMPLETE_REGISTRATION',
    value: 0,
    currency: 'BRL',
    content_id: 'user_123',
    content_type: 'user',
    content_name: 'Registration'
  })
});

const result = await response.json();
console.log(result);
```

#### PHP (Backend):
```php
// Enviar evento via API do Kalitrack
$ch = curl_init('https://kalitrack.example.com/api/tracking/events');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer track123456'
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'click_id' => 'abc123xyz',
        'pixel_id' => '290714800540320',
        'event_name' => 'EVENT_COMPLETE_REGISTRATION',
        'value' => 0,
        'currency' => 'BRL',
        'content_id' => 'user_123'
    ])
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "Evento enviado com sucesso!";
} else {
    echo "Erro: " . $response;
}
```

### 12.2. Exemplo 2: Enviar Evento de Compra (Facebook)

#### Cliente (JavaScript):
```javascript
const response = await fetch('https://kalitrack.example.com/api/tracking/events', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer track123456'
  },
  body: JSON.stringify({
    fbclid: 'fb.1.1234567890.abc123',
    pixel_id: '123456789012345',
    event_name: 'EVENT_PURCHASE',
    email: 'user@example.com',
    phone: '+5511999999999',
    value: 50.00,
    currency: 'BRL'
  })
});
```

### 12.3. Exemplo 3: Enviar Evento de Compra (Kwai)

#### PHP:
```php
$ch = curl_init('https://kalitrack.example.com/api/tracking/events');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer track123456'
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'click_id' => 'abc123xyz',
        'pixel_id' => '290714800540320',
        'event_name' => 'EVENT_PURCHASE',
        'value' => 50.00,
        'currency' => 'BRL',
        'content_id' => 'deposito',
        'content_type' => 'product',
        'content_name' => 'Depósito'
    ])
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "Evento de compra enviado com sucesso!";
} else {
    echo "Erro: " . $response;
}
```

### 12.4. Exemplo 4: Integração no Laravel

#### Controller:
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrackingController extends Controller
{
    public function sendEvent(Request $request)
    {
        $validated = $request->validate([
            'click_id' => 'required_without:fbclid',
            'fbclid' => 'required_without:click_id',
            'pixel_id' => 'required',
            'event_name' => 'required',
            'value' => 'nullable|numeric',
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer track123456',
                'Content-Type' => 'application/json',
            ])->post('https://kalitrack.example.com/api/tracking/events', $validated);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Event sent successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $response->body()
            ], $response->status());

        } catch (\Exception $e) {
            Log::error('Tracking error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to send event'
            ], 500);
        }
    }
}
```

#### Route:
```php
Route::post('/api/tracking/events', [TrackingController::class, 'sendEvent']);
```

---

## 13. Considerações Finais

### 13.1. Vantagens do Sistema
- ✅ Centralização de eventos
- ✅ Flexibilidade para múltiplas plataformas
- ✅ Fallback automático
- ✅ Logs centralizados
- ✅ Fácil manutenção

### 13.2. Limitações
- ⚠️ Token fixo (deve ser melhorado)
- ⚠️ Sem rate limiting nativo
- ⚠️ Logs em arquivo podem crescer muito
- ⚠️ Sem sistema de filas para alta carga
- ⚠️ Sem retry automático em caso de falha da API externa

### 13.3. Melhorias Futuras
- 🔄 Sistema de filas (Redis/RabbitMQ)
- 🔄 Rate limiting por IP/cliente
- 🔄 Dashboard de métricas
- 🔄 Webhooks para notificações
- 🔄 Suporte a mais plataformas (TikTok, Google Ads)
- 🔄 API versionada
- 🔄 Autenticação OAuth2
- 🔄 Retry automático com backoff exponencial
- 🔄 Sistema de fallback para múltiplos provedores (opcional)

---

## 14. Contato e Suporte

### 14.1. Documentação Adicional
- Documentação da API Kwai: [AdsNebula API Docs]
- Documentação Facebook Pixel: [Facebook Graph API]

### 14.2. Troubleshooting

#### Erro: "Unauthorized"
- Verificar Bearer Token no header
- Confirmar token configurado corretamente

#### Erro: "Unknown pixel_id"
- Verificar se pixel está cadastrado no banco
- Confirmar `source` correto (kwai/facebook)

#### Erro: "HTTP 500" da API externa
- Verificar access_token válido
- Confirmar formato do payload
- Verificar logs de erro

---

**Versão do Documento**: 1.0  
**Data de Criação**: 2024-12-20  
**Última Atualização**: 2024-12-20


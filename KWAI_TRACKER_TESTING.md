# 🎯 Como Testar o Tracker do Kwai

Este documento explica todas as formas de testar o envio de eventos para o Kwai Event API (AdsNebula).

## 📋 Eventos Disponíveis

O sistema envia 3 tipos de eventos para o Kwai:

1. **EVENT_COMPLETE_REGISTRATION** - Quando o usuário se registra
2. **EVENT_ADD_TO_CART** - Quando o QR code PIX é gerado (depósito criado)
3. **EVENT_PURCHASE** - Quando o pagamento é aprovado (PIX pago)

---

## 🛠️ Métodos de Teste

### 1. **Comando Artisan (Recomendado)**

O comando mais fácil para testar:

```bash
# Testa evento de registro
php artisan kwai:test registration

# Testa evento de adicionar ao carrinho (depósito gerado)
php artisan kwai:test add-to-cart --value=50.00

# Testa evento de compra (pagamento aprovado)
php artisan kwai:test purchase --value=50.00

# Com click_id específico
php artisan kwai:test registration --click-id=KWC.abc123...

# Usando click_id de um usuário específico
php artisan kwai:test add-to-cart --user-id=123 --value=100.00
```

**Exemplo completo:**
```bash
# 1. Verifica se há usuários com click_id
php artisan tinker
>>> User::whereNotNull('kwai_click_id')->first()

# 2. Testa o evento de registro
php artisan kwai:test registration --user-id=1

# 3. Testa o evento de depósito gerado
php artisan kwai:test add-to-cart --user-id=1 --value=50.00

# 4. Testa o evento de pagamento aprovado
php artisan kwai:test purchase --user-id=1 --value=50.00
```

---

### 2. **Teste Manual via Tinker**

```bash
php artisan tinker
```

```php
// Instancia o serviço
$kwaiService = new \App\Services\KwaiService();

// Testa evento de registro
$result = $kwaiService->sendEvent(
    clickId: 'KWC.abc123...',
    eventName: 'EVENT_COMPLETE_REGISTRATION',
    properties: [
        'content_type' => 'user',
        'content_name' => 'Registro de Usuário',
        'event_timestamp' => time() * 1000,
    ]
);
print_r($result);

// Testa evento de adicionar ao carrinho
$result = $kwaiService->sendEvent(
    clickId: 'KWC.abc123...',
    eventName: 'EVENT_ADD_TO_CART',
    properties: [
        'content_type' => 'product',
        'content_id' => 'deposito',
        'content_name' => 'Depósito',
        'quantity' => 1,
        'price' => 50.00,
        'event_timestamp' => time() * 1000,
    ],
    value: 50.00,
    currency: 'BRL'
);
print_r($result);

// Testa evento de compra
$result = $kwaiService->sendEvent(
    clickId: 'KWC.abc123...',
    eventName: 'EVENT_PURCHASE',
    properties: [
        'content_type' => 'product',
        'content_id' => 'test-123',
        'content_name' => 'Depósito - Compra Finalizada',
        'event_timestamp' => time() * 1000,
    ],
    value: 50.00,
    currency: 'BRL'
);
print_r($result);
```

---

### 3. **Teste via Código PHP**

Crie um arquivo temporário `test-kwai.php` na raiz:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\KwaiService;

$kwaiService = new KwaiService();

// Testa evento de registro
$result = $kwaiService->sendEvent(
    clickId: 'KWC.abc123...',
    eventName: 'EVENT_COMPLETE_REGISTRATION',
    properties: [
        'content_type' => 'user',
        'content_name' => 'Registro de Usuário',
        'event_timestamp' => time() * 1000,
    ]
);

echo "Resultado:\n";
print_r($result);
```

Execute:
```bash
php test-kwai.php
```

---

### 4. **Teste Real (Fluxo Completo)**

#### Passo 1: Configurar no Painel Admin
- Acesse o painel admin (Filament)
- Vá em "System Settings"
- Configure:
  - `kwai_pixel_id`
  - `kwai_access_token`
  - `kwai_test_token` (opcional - usado como click_id em modo de teste)
  - `kwai_mmpcode` (padrão: PL)
  - `kwai_pixel_sdk_version` (padrão: 9.9.9)
  - `kwai_is_test` (true para eventos de teste)

**Nota sobre Test Token**: O `kwai_test_token` pode ser usado como `click_id` quando estiver em modo de teste. Configure este campo com o token de teste fornecido pelo Kwai para facilitar os testes.

#### Passo 2: Capturar click_id
1. Acesse a landing page com o parâmetro: `?kwai_click_id=KWC.abc123...`
2. O `kwai.js` vai capturar e salvar no localStorage
3. Ao se registrar, o `kwai_click_id` será salvo no banco

#### Passo 3: Verificar Eventos
1. **Registro:** Ao criar conta, o evento `EVENT_COMPLETE_REGISTRATION` é enviado automaticamente
2. **Depósito Gerado:** Ao gerar QR code PIX, o evento `EVENT_ADD_TO_CART` é enviado
3. **Pagamento Aprovado:** Quando o webhook aprova o pagamento, o evento `EVENT_PURCHASE` é enviado

#### Passo 4: Verificar no Painel do Kwai
- Acesse o painel do Kwai Ads
- Vá em "Eventos" ou "Test Events" (se estiver em modo teste)
- Verifique se os eventos aparecem

---

## 🔍 Verificar Logs

Os eventos geram logs detalhados. Para ver:

```bash
# Ver logs em tempo real
tail -f storage/logs/laravel.log

# Ou no Windows (PowerShell)
Get-Content storage/logs/laravel.log -Wait -Tail 50
```

**O que procurar nos logs:**
- `Kwai Event API Request` - Evento sendo enviado
- `Kwai Event API Response` - Resposta da API
- `Kwai Event API Error` - Erro no envio
- `Kwai Event API Exception` - Exceção

**Exemplo de log:**
```
[2025-01-XX XX:XX:XX] local.INFO: Kwai Event API Request {"event_name":"EVENT_COMPLETE_REGISTRATION","click_id":"KWC.abc123...","pixel_id":"123456","value":null}
[2025-01-XX XX:XX:XX] local.INFO: Kwai Event API Response {"event_name":"EVENT_COMPLETE_REGISTRATION","click_id":"KWC.abc123...","http_code":200,"result":1,"success":true}
```

---

## 📊 Estrutura do Payload

O payload enviado para a API do Kwai (AdsNebula) tem esta estrutura:

```json
{
  "access_token": "seu_access_token",
  "clickid": "KWC.abc123...",
  "event_name": "EVENT_COMPLETE_REGISTRATION",
  "pixelId": "seu_pixel_id",
  "is_attributed": 1,
  "mmpcode": "PL",
  "pixelSdkVersion": "9.9.9",
  "testFlag": false,
  "trackFlag": true,
  "properties": "{\"content_type\":\"user\",\"content_name\":\"Registro de Usuário\"}",
  "value": "50.00",
  "currency": "BRL"
}
```

**Campos obrigatórios:**
- `access_token` - Token de acesso do Kwai
- `clickid` - ID do clique capturado
- `event_name` - Nome do evento
- `pixelId` - ID do pixel
- `is_attributed` - Sempre 1
- `mmpcode` - Código MMP (padrão: PL)
- `pixelSdkVersion` - Versão do SDK (padrão: 9.9.9)
- `testFlag` - Sempre false
- `trackFlag` - true se estiver em modo teste

**Campos opcionais:**
- `properties` - JSON string com propriedades adicionais
- `value` - Valor do evento (para eventos de e-commerce)
- `currency` - Moeda (padrão: BRL)

---

## ✅ Checklist de Teste

- [ ] Configurações do Kwai estão preenchidas no painel admin
- [ ] `kwai_click_id` está sendo capturado na landing page
- [ ] `kwai_click_id` está sendo salvo no banco ao registrar
- [ ] Evento de registro é enviado ao criar conta
- [ ] Evento AddToCart é enviado ao gerar QR code
- [ ] Evento Purchase é enviado ao aprovar pagamento
- [ ] Logs mostram requisições e respostas
- [ ] Resposta da API retorna `result: 1` (sucesso)
- [ ] Eventos aparecem no painel do Kwai (ou Test Events)

---

## 🐛 Troubleshooting

### Erro: "Kwai Event API não configurado"
**Solução:** Configure `kwai_pixel_id` e `kwai_access_token` no painel admin.

### Erro: "click_id é obrigatório"
**Solução:** 
- Verifique se o usuário tem `kwai_click_id` no banco
- Ou passe `--click-id=KWC.abc123...` no comando
- Ou configure `kwai_test_token` no painel admin (será usado automaticamente em modo de teste)

### Erro HTTP 401 ou 403
**Solução:** 
- Verifique se o `access_token` está correto
- Verifique se o `pixel_id` está correto
- Verifique se as credenciais estão ativas no painel do Kwai

### Eventos não aparecem no painel do Kwai
**Solução:**
- Verifique se `kwai_is_test` está como `true` (eventos aparecem em "Test Events")
- Aguarde alguns minutos (pode haver delay)
- Verifique os logs para ver se há erros
- Verifique se o `click_id` é válido e não expirou

### Resposta retorna `result: 0`
**Solução:**
- Verifique se o `click_id` é válido e não expirou
- Verifique se o evento está sendo enviado dentro do período de atribuição
- Verifique se o formato do payload está correto

---

## 📝 Exemplos de Uso

### Testar todos os eventos de uma vez

```bash
# 1. Busca um usuário com click_id
USER_ID=$(php artisan tinker --execute="echo \App\Models\User::whereNotNull('kwai_click_id')->first()->id;")

# 2. Testa registro
php artisan kwai:test registration --user-id=$USER_ID

# 3. Testa depósito gerado
php artisan kwai:test add-to-cart --user-id=$USER_ID --value=50.00

# 4. Testa pagamento aprovado
php artisan kwai:test purchase --user-id=$USER_ID --value=50.00
```

### Testar com click_id específico

```bash
php artisan kwai:test registration --click-id=KWC.abc123def456
php artisan kwai:test add-to-cart --click-id=KWC.abc123def456 --value=100.00
php artisan kwai:test purchase --click-id=KWC.abc123def456 --value=100.00
```

---

## 🚀 Próximos Passos

1. **Configure no painel admin:** Preencha todas as configurações do Kwai
2. **Teste com comando:** Use `php artisan kwai:test` para testar cada evento
3. **Teste fluxo real:** Crie uma conta, gere um depósito e aprove um pagamento
4. **Monitore logs:** Acompanhe os logs para verificar se tudo está funcionando
5. **Verifique no Kwai:** Confirme que os eventos aparecem no painel do Kwai

---

## 📚 Referências

- **Endpoint da API:** `https://www.adsnebula.com/log/common/api`
- **Documentação do Kwai:** Consulte a documentação oficial do Kwai Ads
- **Logs:** `storage/logs/laravel.log`

---

**Dúvidas?** Verifique os logs em `storage/logs/laravel.log` ou consulte a documentação do Kwai.


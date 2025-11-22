# Sistema de Proteção do Frontend

Este documento descreve as proteções implementadas para proteger o frontend contra clonagem e uso não autorizado.

## Proteções Implementadas

### 1. ✅ Domain Locking (Bloqueio de Domínio)
- **Arquivo**: `resources/js/utils/security.js`
- **Função**: `validateDomain()`
- **Descrição**: Verifica se o código está rodando em um domínio permitido
- **Configuração**: Edite `ALLOWED_DOMAINS` em `resources/js/utils/security.js`

```javascript
const ALLOWED_DOMAINS = [
    'seu-dominio.com',
    'www.seu-dominio.com',
    // Adicione outros domínios permitidos
];
```

### 2. ✅ Request Signing (Assinatura de Requisições)
- **Arquivo**: `resources/js/composables/useApi.js`
- **Função**: `generateRequestSignature()`
- **Descrição**: Assina todas as requisições POST/PUT/PATCH com um hash baseado em timestamp e dados
- **Backend**: Middleware `ValidateRequestSignature` (opcional, comentado por padrão)

### 3. ✅ Environment Detection (Detecção de Ambiente)
- **Arquivo**: `resources/js/utils/security.js`
- **Função**: `detectSuspiciousEnvironment()`
- **Descrição**: Detecta:
  - DevTools aberto
  - Navegadores headless
  - Execução em iframe
  - Extensões de desenvolvedor
  - Console modificado

### 4. ✅ Source Map Protection (Proteção de Source Maps)
- **Arquivo**: `vite.config.js`
- **Configuração**: `sourcemap: false` em produção
- **Descrição**: Remove source maps do build de produção para dificultar reverse engineering

### 5. ✅ CSP Headers (Content Security Policy)
- **Arquivo**: `app/Http/Middleware/SecurityHeaders.php` e `public/.htaccess`
- **Descrição**: Adiciona headers de segurança HTTP:
  - Content-Security-Policy
  - X-Content-Type-Options
  - X-Frame-Options
  - X-XSS-Protection
  - Referrer-Policy
  - Permissions-Policy

## Configuração

### 1. Configurar Domínios Permitidos

Edite `resources/js/utils/security.js`:

```javascript
const ALLOWED_DOMAINS = [
    'seu-dominio.com',
    'www.seu-dominio.com',
];
```

**⚠️ IMPORTANTE**: Em produção, remova `window.location.hostname` e adicione apenas seus domínios específicos.

### 2. Ativar Validação de Assinatura (Opcional)

Se quiser validar assinaturas no backend, descomente em `bootstrap/app.php`:

```php
$middleware->append(\App\Http\Middleware\ValidateRequestSignature::class);
```

**⚠️ ATENÇÃO**: Isso pode quebrar requisições legítimas se a assinatura não corresponder exatamente.

### 3. Ajustar CSP Headers

Edite `app/Http/Middleware/SecurityHeaders.php` para ajustar as políticas conforme necessário.

## Proteções Adicionais Já Implementadas

- ✅ Disable DevTools (via `disable-devtool`)
- ✅ Obfuscator no build (via `rollup-plugin-obfuscator`)
- ✅ Proteção contra cópia de texto
- ✅ Proteção contra seleção
- ✅ Desabilita botão direito
- ✅ Bloqueia atalhos de teclado (F12, Ctrl+Shift+I, etc.)

## Limitações

⚠️ **IMPORTANTE**: Nenhuma proteção frontend é 100% segura. JavaScript sempre pode ser inspecionado. Essas proteções:

- ✅ Tornam a clonagem mais difícil e trabalhosa
- ✅ Protegem contra usuários casuais
- ✅ Adicionam camadas de segurança
- ❌ NÃO protegem contra desenvolvedores experientes
- ❌ NÃO substituem proteção no backend

## Recomendações

1. **Sempre proteja a lógica de negócio no backend**
2. **Use autenticação e autorização adequadas**
3. **Valide todas as requisições no servidor**
4. **Monitore tentativas de acesso não autorizado**
5. **Mantenha as proteções atualizadas**

## Testes

Após implementar, teste:

1. ✅ Aplicação funciona no domínio permitido
2. ✅ Aplicação bloqueia em outros domínios
3. ✅ Requisições API funcionam corretamente
4. ✅ Headers de segurança estão presentes
5. ✅ Source maps não são gerados em produção

## Suporte

Em caso de problemas, verifique:

1. Console do navegador para erros
2. Network tab para requisições bloqueadas
3. Headers HTTP nas respostas
4. Logs do servidor Laravel


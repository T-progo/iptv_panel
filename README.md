# 🚀 IPTV Activation Panel & REST API

Painel web completo em PHP/MySQL para gerenciar, ativar e controlar o acesso de clientes no seu aplicativo Android TV / TV Box por **MAC Address** e **Device Key**.

---

## 📁 Estrutura do Projeto

```
iptv-panel/
├── config/
│   └── database.php       # Conexão PDO MySQL com o banco
├── api/
│   ├── check.php          # Endpoint chamado pelo App da TV para checar ativação e obter M3U
│   └── register.php       # Endpoint chamado pelo App na 1ª abertura para registrar o MAC
├── admin/
│   ├── auth.php           # Autenticação e proteção de sessão
│   ├── login.php          # Tela de Login Dark Mode
│   ├── logout.php         # Logout do painel
│   ├── index.php          # Dashboard com estatísticas, lista de clientes e ações
│   └── api_actions.php    # Ações AJAX (Salvar, Excluir, Renovar +30d)
├── database.sql           # Script para criar o banco de dados MySQL
└── index.php              # Redirecionamento para o login
```

---

## 🛠️ Como Instalar no seu Servidor Hostinger (VPS)

### Passo 1: Criar o Banco de Dados MySQL
1. Acesse o seu banco de dados MySQL no servidor (via phpMyAdmin, Coolify ou Terminal).
2. Execute o arquivo [`database.sql`](database.sql).
3. Isso vai criar as tabelas `devices` e `admins`, e o usuário padrão:
   - **Usuário:** `admin`
   - **Senha:** `admin123`

---

### Passo 2: Configurar a Conexão
Abra o arquivo [`config/database.php`](config/database.php) e ajuste suas credenciais:

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'iptv_panel');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
```

---

### Passo 3: Testar os Endpoints da API

#### 1. Registrar um dispositivo (Ping da TV ao abrir):
```http
GET https://seu-dominio.com/api/register.php?mac=00:1A:79:AB:CD:EF&key=123456&version=1.0
```
**Resposta JSON:**
```json
{
  "status": "success",
  "message": "Dispositivo registrado com sucesso e aguardando ativação.",
  "device_status": "pending"
}
```

#### 2. Verificar se está ativo (App checando se pode tocar canais):
```http
GET https://seu-dominio.com/api/check.php?mac=00:1A:79:AB:CD:EF&key=123456
```
**Resposta JSON (Ativo):**
```json
{
  "status": "active",
  "customer_name": "João da Silva",
  "playlist_url": "http://servidor.com/get.php?username=...&password=...&type=m3u_plus",
  "playlist_type": "m3u",
  "expires_at": "2026-09-23 23:59:59",
  "days_remaining": 30,
  "message": "Dispositivo ativo e autorizado."
}
```

---

## 🌟 Recursos do Painel:
- **Auto-registro:** O cliente liga a TV e já aparece na sua aba "Pendentes".
- **Ativação 1-Clique:** Cole a lista M3U dele e selecione a data de validade.
- **Renovação Expressa:** Botão `+30d` para adicionar 30 dias na hora.
- **Mensagem Pronta para WhatsApp:** Clique no botão do WhatsApp para copiar o texto pronto com MAC, Key e Validade formatados.

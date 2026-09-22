# 🤖 Guía: Servidor MCP de Instagram para Aura Adamo

Hemos integrado el servidor MCP de Instagram oficial en tu configuración global de Antigravity:
📁 **Archivo configurado:** `C:\Users\trabajo ia\.gemini\config\mcp_config.json`

---

## 1. Servidor MCP Seleccionado: `@mikusnuz/meta-mcp`

Buscamos en GitHub y npm la solución más completa, segura y actualizada del ecosistema MCP. Seleccionamos **`@mikusnuz/meta-mcp`** (versión 2.1+, Graph API v25.0).

### Herramientas que habilitará para manejar tu Instagram:
- 📸 **Publicación:** Crear posts en el feed, carruseles, reels e historias.
- 💬 **Comentarios:** Leer comentarios de tus publicaciones y responderlos con IA.
- 📩 **Mensajería Directa (DM):** Gestionar y responder consultas de clientes sobre tus apps.
- 📊 **Analítica & Métricas:** Consultar alcance, impresiones, mejores horarios y crecimiento de seguidores de tu startup **Aura**.

---

## 2. Configuración en tu sistema

En tu archivo [mcp_config.json](file:///C:/Users/trabajo%20ia/.gemini/config/mcp_config.json), ya quedó aplicada la siguiente estructura:

```json
"instagram": {
    "command": "npx",
    "args": [
        "-y",
        "@mikusnuz/meta-mcp"
    ],
    "env": {
        "INSTAGRAM_ACCESS_TOKEN": "TU_ACCESS_TOKEN_AQUI",
        "INSTAGRAM_USER_ID": "TU_USER_ID_AQUI"
    }
}
```

---

## 3. ¿Cómo obtener tu Token de Meta en 3 Pasos?

Dado que Instagram pertenece a Meta y protege la seguridad de las cuentas mediante OAuth:

### Paso 1: Convertir tu cuenta a Profesional (Gratis y al instante)
1. En tu app de Instagram, ve a tu perfil (`@aura_adamo_omar`).
2. Toca en el menú (tres líneas) > **Configuración y privacidad**.
3. Selecciona **Tipo de cuenta y herramientas** > **Cambiar a cuenta profesional** (elige "Creador" o "Empresa").

### Paso 2: Crear App en Meta for Developers
1. Ingresa con tu cuenta de Facebook/Instagram a [developers.facebook.com](https://developers.facebook.com/).
2. Haz clic en **Crear App** y selecciona el tipo **Empresa / Negocio (Business)**.
3. En productos, añade **Instagram Graph API**.

### Paso 3: Generar el Token y Obtener tu ID
1. Ve a **Herramientas > Explorador de Graph API** (Graph API Explorer).
2. Selecciona los permisos:
   - `instagram_basic`
   - `instagram_content_publish`
   - `instagram_manage_comments`
   - `instagram_manage_messages`
   - `instagram_manage_insights`
3. Haz clic en **Generate Access Token** y copia el token generado.
4. Para obtener tu `INSTAGRAM_USER_ID`, puedes consultar `me/accounts` en el explorador o usar la herramienta de consulta de perfil.
5. Pega ambos valores en [mcp_config.json](file:///C:/Users/trabajo%20ia/.gemini/config/mcp_config.json).

---

## 4. Alternativa Rápida para Mensajes (DMs sin App de Meta): `mcp-instagram-dm`

Si deseas que el asistente gestione **solamente tus Mensajes Directos (DMs)** de inmediato sin crear una App de desarrollador en Meta, también puedes usar **`mcp-instagram-dm`**, que se autentica mediante tus cookies de sesión del navegador:

```json
"instagram-dm": {
    "command": "npx",
    "args": [
        "-y",
        "mcp-instagram-dm"
    ],
    "env": {
        "INSTAGRAM_SESSION_ID": "tu_session_id_de_chrome",
        "INSTAGRAM_CSRF_TOKEN": "tu_csrf_token",
        "INSTAGRAM_DS_USER_ID": "tu_user_id"
    }
}
```
*(Se obtienen en Chrome abriendo instagram.com > F12 > Aplicación > Cookies).*

---

*Configurado para Omar - Startup AURA © 2026. Todos los derechos reservados.*

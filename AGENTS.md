# Agentes de IA - Normas Oficiales y Funciones MCP (Ecosistema Aura)

Este entorno y sus proyectos pertenecen a **Omar Adamo** (**Startup Aura**).

## 1. Normas Obligatorias de Usuario y Marca (Reglas Globales)
- **Idioma y Trato:** Hablar y mostrar todos los proyectos siempre en **castellano (español)**, dirigiéndose al usuario como **Omar**.
- **Footer y Copyright:** Incluir siempre en el footer de todas las aplicaciones y sitios web el copyright de la startup **Aura** (`© 2026 Aura. Todos los derechos reservados.`), salvo que Omar indique explícitamente lo contrario.
- **Inicio Limpio:** Empezar siempre limpio, sin ninguna carga residual de ningún programa ni aplicación previa.
- **Protección Multi-Dominio (DonWeb / Ferozo):** Respetar la separación estricta en el servidor entre `l1deres.site` (`/public_html`) y `aura-adamo.site` (`/public_html/aura-adamo`), manteniendo intacto el `.htaccess` raíz y sus cabeceras de seguridad anti-phishing, HSTS y Anti-XSS.

## 2. Servidores y Funciones MCP Activos (`mcp_config.json`)
- **`donweb` (DonWeb / Ferozo & Aura Ecosystem Manager v2.5):**
  - `deploy_project`: Despliega por nombre cualquier proyecto del ecosistema (`aura-landing`, `colsul`, `odonto`, `flota`, `aura-shield`, `aura-api`, `l1deres-landing`, `l1deres-admin`, `l1deres-cliente`, `l1deres-api`, `ecosistema-aura`, `ecosistema-l1deres`).
  - `deploy_file` / `deploy_folder`: Subida FTPS resiliente con reconexión automática y soporte recursivo de subcarpetas.
  - `ping_site` / `ping_api`: Monitoreo en vivo de todos los módulos (`aura`, `libros`, `colsul`, `odonto`, `flota`, `landing`, `admin`, `cliente`).
  - `trigger_backup`: Disparo y verificación del respaldo diario de las 18 tablas MySQL (`cron_backup.php`).
  - `audit_aura_compliance`: Auditoría en vivo de cabeceras de seguridad HTTP y presencia del copyright de **Aura** en el footer.
  - `list_files`, `create_directory`, `delete_file` (con protección de `.htaccess`), `get_config`, `set_config`, `setup_db`.
- **`github` (`@modelcontextprotocol/server-github`):** Gestión de repositorios, ramas, commits, issues y pull requests.
- **`graphify` (`graphify.serve`):** Consulta del grafo de arquitectura y nodos principales (`query_graph`, `god_nodes`, `graph_stats`).
- **`cloudrun` (`@google-cloud/cloud-run-mcp`):** Despliegue e inspección de servicios en Google Cloud Run.
- **`gmp-code-assist` (`@googlemaps/code-assist-mcp`):** Documentación y asistencia oficial de Google Maps Platform.

## 3. Metodología de Desarrollo (Superpowers & Skills)
Consultar y seguir las habilidades definidas cuando corresponda:
- **brainstorming**: Refinar ideas antes de escribir código.
- **writing-plans**: Desglosar tareas complejas.
- **test-driven-development (TDD)**: Ciclo RED-GREEN-REFACTOR.
- **systematic-debugging**: Corrección estructurada de errores.
- **frontend-design**: Interfaces de alta calidad estética con el sello de **Aura**.
- **security-review**: Auditoría de vulnerabilidades, secretos expuestos y fallos de seguridad (OWASP).

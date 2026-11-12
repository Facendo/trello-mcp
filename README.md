# mcp-trello

Servidor MCP (stdio) para Trello, en PHP plano con [`mcp/sdk`](https://github.com/modelcontextprotocol/php-sdk).
Lee y escribe en tus tableros, pero **no borra nada**: para eliminar se archiva (reversible desde Trello).

## Herramientas (23)

| Grupo | Herramientas |
|---|---|
| Tableros | `trello_list_boards`, `trello_get_board`, `trello_get_board_lists`, `trello_get_board_labels`, `trello_get_board_members` |
| Listas | `trello_create_list`, `trello_rename_list`, `trello_move_list`, `trello_archive_list` |
| Tarjetas | `trello_get_list_cards`, `trello_get_card`, `trello_create_card`, `trello_update_card`, `trello_move_card`, `trello_add_card_member`, `trello_add_card_label`, `trello_archive_card` |
| Colaboración | `trello_add_comment`, `trello_create_checklist`, `trello_add_checklist_item`, `trello_set_checklist_item_state`, `trello_attach_link` |
| Búsqueda | `trello_search_cards` |

## Para qué usarlo

Una vez conectado, le hablas a Claude en lenguaje natural y él usa las herramientas por ti:

- **Consultar**: ver tableros, listas y tarjetas; buscar tarjetas por texto, miembro, etiqueta o fecha (`due:week`, `@usuario`, `label:rojo`); resumir el estado de un tablero.
- **Organizar**: crear tarjetas y listas, moverlas, asignar miembros y etiquetas, poner fechas límite y archivar lo que ya no sirve.
- **Convertir texto en tareas**: pegarle notas de una reunión y que genere tarjetas con checklists.
- **Seguimiento**: comentar tarjetas, adjuntar enlaces (por ejemplo un PR), marcar elementos de checklist, preparar reportes semanales.
- **Junto a tu código (Claude Code)**: leer una tarjeta, implementarla y moverla a «Revisión» con un comentario; crear tarjetas a partir de los TODO del código.

**Límites (a propósito o por ahora):** no borra nada (solo archiva), no crea ni edita tableros ni etiquetas, no quita miembros ni etiquetas de una tarjeta, no sube archivos (solo enlaces) y no lee comentarios existentes.

## Cómo usarlo

No hay comandos que aprender: escribe lo que necesitas y Claude elige la herramienta. Normalmente empieza listando tableros para obtener los IDs, así que no necesitas dárselos.

Ejemplos de prompts:

```
Lista mis tableros
¿Qué tarjetas hay en la lista "Por hacer" del tablero Proyecto X?
Crea una tarjeta "Revisar contrato" en Por hacer, con fecha el viernes y asígnamela
Mueve la tarjeta "Revisar contrato" a Hecho y comenta que ya se firmó
Busca mis tarjetas que vencen esta semana
Con estas notas de la reunión, crea una tarjeta por acuerdo con su checklist: <pega las notas>
Archiva todas las tarjetas de la lista Hecho
Haz un resumen del tablero Proyecto X: tarjetas por lista y lo que está atrasado
```

Consejos:

- Sé específico con el nombre del tablero y de la lista si tienes varios parecidos.
- Para acciones masivas (mover o archivar muchas tarjetas), pídele primero que te **liste** lo que va a tocar y confirma después.
- En Claude Code puedes ver las herramientas conectadas con `/mcp`.
- Para fechas, di el día con claridad («el viernes 20 a las 18:00»); Claude las convierte a ISO 8601.

## Requisitos

PHP >= 8.1 (en el `PATH`) y Composer.

## Instalación

```bash
composer install
cp .env.example .env
```

### Obtener API key y token

Se usa el método clásico de Trello (API key + token). **No** hace falta OAuth 2.0 ni Power-Up.

1. Entra en <https://trello.com/power-ups/admin> y pulsa **Nuevo** para crear una aplicación.
2. En el formulario elige **«Mi aplicación no usa funciones de Power-Up»** (no se puede cambiar después). Pon un nombre (p. ej. `mcp-trello`), tu espacio de trabajo, tu correo y tu nombre como autor. Si no usas Power-Up, no pide URL de iframe.
3. Dentro de la aplicación, abre la pestaña **Clave de API** (*Autenticación Trello*). Pulsa **«Genera una nueva clave de la API de autenticación de Trello para esta aplicación»**. **Ignora el botón «Configurar OAuth 2.0»**: no lo necesitas.
4. Copia la **API Key** (cadena corta de ~32 caracteres). El «Secreto» no se usa.
5. Genera el token abriendo esta URL en el navegador, **sustituyendo `TU_API_KEY` por tu clave real** (si la dejas tal cual, Trello responde «App not found»):

   ```
   https://trello.com/1/authorize?expiration=never&scope=read,write&response_type=token&name=mcp-trello&key=TU_API_KEY
   ```

   Pulsa **Permitir** y copia el token que aparece. Con `expiration=never` no caduca; usa `30days` si prefieres que caduque.
6. Pega ambos valores en `.env`:

```
TRELLO_API_KEY=...
TRELLO_TOKEN=...
```

> ⚠️ **No compartas el token ni lo subas al repositorio.** Da acceso de lectura y escritura a tu cuenta. `.env` está en `.gitignore`. Si se filtra, revócalo en <https://trello.com/my/account>.

## Conectar con Claude Code

Usa **rutas absolutas**:

```bash
claude mcp add trello -- php /ruta/absoluta/a/mcp-trello/mcp-server.php
```

(Lee las credenciales de `.env`. Alternativa sin `.env`: añade `-e TRELLO_API_KEY=... -e TRELLO_TOKEN=...` antes del `--`.)

## Conectar con Claude Desktop

En `claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "trello": {
      "command": "php",
      "args": ["/ruta/absoluta/a/mcp-trello/mcp-server.php"],
      "env": { "TRELLO_API_KEY": "...", "TRELLO_TOKEN": "..." }
    }
  }
}
```

Reinicia Claude Desktop. Verifica preguntando: **«lista mis tableros»**.

## Solución de problemas

- **El servidor no arranca / "spawn php ENOENT"**: `php` no está en el `PATH` de la aplicación. Pon la ruta completa en `command` (`which php`).
- **No encuentra el archivo**: usa siempre rutas absolutas, nunca `~` ni rutas relativas.
- **`vendor/autoload.php` no existe**: falta ejecutar `composer install`.
- **«App not found» al autorizar**: dejaste `TU_API_KEY` en la URL; pon tu API key real.
- **401**: key o token incorrectos/expirados. **403**: el token no tiene permiso de escritura (regenéralo con `scope=read,write`). **404**: ID inexistente o sin acceso. **429**: límite de Trello; el cliente reintenta solo hasta 3 veces.
- Los mensajes de error del servidor salen por stderr y como resultado de la herramienta; stdout queda reservado al protocolo MCP.

## Desarrollo

```bash
vendor/bin/phpunit                                              # pruebas unitarias (respuestas simuladas)
npx @modelcontextprotocol/inspector php mcp-server.php          # inspeccionar el servidor
```

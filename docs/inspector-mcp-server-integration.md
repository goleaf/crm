# Inspector MCP Server Integration

## Overview
Inspector's MCP server lets coding agents fetch production errors and performance data directly from Inspector.dev. It exposes MCP tools over STDIO so agents like Claude Code or Cursor can query your live telemetry with full context.

## Installation
- Already added as a dev dependency: `inspector-apm/mcp-server`.
- Ensure dev dependencies are installed locally (`composer install`) so `vendor/inspector-apm/mcp-server/server.php` is available.

## Environment
Set your Inspector credentials (never commit real values):

```env
INSPECTOR_APP_ID=    # Application ID from Inspector dashboard → Application Settings
INSPECTOR_API_KEY=   # API key from https://app.inspector.dev/account/api
```

## Configure MCP Clients (STDIO)
Point your MCP-capable editor/agent to the server script in this project:

```json
{
  "mcpServers": {
    "inspector": {
      "command": "php",
      "args": [
        "/absolute/path/to/your/repo/vendor/inspector-apm/mcp-server/server.php"
      ],
      "env": {
        "INSPECTOR_API_KEY": "your-api-key",
        "INSPECTOR_APP_ID": "your-app-id"
      }
    }
  }
}
```

Claude CLI example (swap in your absolute path and secrets):

```bash
claude mcp add inspector \
  --env INSPECTOR_API_KEY=your-api-key \
  --env INSPECTOR_APP_ID=your-app-id \
  -- php /absolute/path/to/your/repo/vendor/inspector-apm/mcp-server/server.php
```

## Available MCP Tools
- `get_production_errors(hours = 24, limit = 10)` – recent production errors with frequency and context.
- `get_error_analysis(group_hash)` – deep dive into a specific error with stack and app file context.
- `worst_performing_transactions(hours = 24, limit = 10)` – slowest transactions over the window.
- `transaction_details(hours = 24)` – timeline of tasks/queries for a selected transaction.

## Tips
- This server only needs to run locally; the Inspector API key and app ID gate access to your telemetry.
- Use shorter lookback windows or lower limits if agents warn about too many results for their context window.

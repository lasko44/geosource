#!/usr/bin/env node

import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import { GeosourceClient } from "./client.js";
import { loadStoredToken, browserAuth } from "./auth.js";
import { registerScanTools } from "./tools/scans.js";
import { registerCitationTools } from "./tools/citations.js";
import { registerTokenTools } from "./tools/tokens.js";
import { registerDashboardTools } from "./tools/dashboard.js";

/**
 * Resolve API credentials in priority order:
 * 1. Environment variables (GEOSOURCE_API_TOKEN + GEOSOURCE_API_URL)
 * 2. Stored config from ~/.geosource-mcp/config.json (from previous browser login)
 * 3. Browser-based login flow (opens browser, user authorizes, token stored)
 */
async function resolveCredentials(): Promise<{
  apiUrl: string;
  apiToken: string;
}> {
  const defaultUrl = "https://geosource.ai/api/v1";

  // 1. Check environment variables
  const envToken = process.env.GEOSOURCE_API_TOKEN;
  if (envToken) {
    return {
      apiUrl: process.env.GEOSOURCE_API_URL ?? defaultUrl,
      apiToken: envToken,
    };
  }

  // 2. Check stored config
  const stored = await loadStoredToken();
  if (stored) {
    return {
      apiUrl: stored.api_url,
      apiToken: stored.api_token,
    };
  }

  // 3. Browser login flow
  const apiUrl = process.env.GEOSOURCE_API_URL ?? defaultUrl;

  process.stderr.write(
    "No API token found. Starting browser authentication...\n",
  );

  const config = await browserAuth(apiUrl);

  return {
    apiUrl: config.api_url,
    apiToken: config.api_token,
  };
}

const { apiUrl, apiToken } = await resolveCredentials();
const client = new GeosourceClient(apiUrl, apiToken);

const server = new McpServer({
  name: "geosource",
  version: "1.0.0",
});

// Register all tools
registerScanTools(server, client);
registerCitationTools(server, client);
registerTokenTools(server, client);
registerDashboardTools(server, client);

// Start the server with stdio transport
const transport = new StdioServerTransport();
await server.connect(transport);

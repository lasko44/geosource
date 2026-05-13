import { createServer, type IncomingMessage, type ServerResponse } from "node:http";
import { readFile, writeFile, mkdir } from "node:fs/promises";
import { homedir } from "node:os";
import { join } from "node:path";
import { randomBytes } from "node:crypto";
import { exec } from "node:child_process";

const CONFIG_DIR = join(homedir(), ".geosource-mcp");
const CONFIG_FILE = join(CONFIG_DIR, "config.json");

interface StoredConfig {
  api_url: string;
  api_token: string;
}

/**
 * Load stored token from ~/.geosource-mcp/config.json.
 */
export async function loadStoredToken(): Promise<StoredConfig | null> {
  try {
    const raw = await readFile(CONFIG_FILE, "utf-8");
    const config = JSON.parse(raw) as Partial<StoredConfig>;

    if (config.api_token && config.api_url) {
      return config as StoredConfig;
    }

    return null;
  } catch {
    return null;
  }
}

/**
 * Save token to ~/.geosource-mcp/config.json.
 */
async function saveConfig(config: StoredConfig): Promise<void> {
  await mkdir(CONFIG_DIR, { recursive: true });
  await writeFile(CONFIG_FILE, JSON.stringify(config, null, 2), {
    mode: 0o600,
  });
}

/**
 * Open a URL in the default browser.
 */
function openBrowser(url: string): void {
  const command =
    process.platform === "darwin"
      ? `open "${url}"`
      : process.platform === "win32"
        ? `start "${url}"`
        : `xdg-open "${url}"`;

  exec(command);
}

/**
 * Run the browser-based OAuth flow.
 *
 * 1. Start a temporary local HTTP server
 * 2. Open browser to the Geosource auth page
 * 3. Wait for the callback with the token
 * 4. Save token and return it
 */
export async function browserAuth(apiBaseUrl: string): Promise<StoredConfig> {
  // Strip /api/v1 to get the app URL
  const appUrl = apiBaseUrl.replace(/\/api\/v1\/?$/, "");

  const state = randomBytes(32).toString("hex");

  return new Promise<StoredConfig>((resolve, reject) => {
    const server = createServer(
      (req: IncomingMessage, res: ServerResponse) => {
        const url = new URL(req.url ?? "/", `http://localhost`);

        if (url.pathname === "/callback") {
          const token = url.searchParams.get("token");
          const returnedState = url.searchParams.get("state");

          if (returnedState !== state) {
            res.writeHead(400, { "Content-Type": "text/html" });
            res.end(errorPage("State mismatch. Please try again."));
            return;
          }

          if (!token) {
            res.writeHead(400, { "Content-Type": "text/html" });
            res.end(errorPage("No token received. Please try again."));
            return;
          }

          res.writeHead(200, { "Content-Type": "text/html" });
          res.end(successPage());

          const config: StoredConfig = {
            api_url: apiBaseUrl,
            api_token: token,
          };

          saveConfig(config)
            .then(() => {
              server.close();
              resolve(config);
            })
            .catch(reject);
        } else {
          res.writeHead(404);
          res.end("Not found");
        }
      },
    );

    server.listen(0, "127.0.0.1", () => {
      const addr = server.address();
      if (!addr || typeof addr === "string") {
        reject(new Error("Failed to start local server"));
        return;
      }

      const port = addr.port;
      const authUrl = `${appUrl}/auth/mcp?port=${port}&state=${state}`;

      // Log to stderr so it doesn't interfere with MCP stdio protocol
      process.stderr.write(
        `\nOpening browser for authentication...\n` +
          `If the browser doesn't open, visit: ${authUrl}\n\n`,
      );

      openBrowser(authUrl);
    });

    // Timeout after 5 minutes
    setTimeout(() => {
      server.close();
      reject(new Error("Authentication timed out after 5 minutes."));
    }, 5 * 60 * 1000);
  });
}

function successPage(): string {
  return `<!DOCTYPE html>
<html>
<head><title>Geosource MCP - Authorized</title></head>
<body style="font-family: system-ui, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #fafafa;">
  <div style="text-align: center; padding: 2rem;">
    <h1 style="color: #16a34a; font-size: 1.5rem;">Authorization Successful</h1>
    <p style="color: #666; margin-top: 0.5rem;">You can close this tab and return to your MCP client.</p>
  </div>
</body>
</html>`;
}

function errorPage(message: string): string {
  return `<!DOCTYPE html>
<html>
<head><title>Geosource MCP - Error</title></head>
<body style="font-family: system-ui, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #fafafa;">
  <div style="text-align: center; padding: 2rem;">
    <h1 style="color: #dc2626; font-size: 1.5rem;">Authorization Failed</h1>
    <p style="color: #666; margin-top: 0.5rem;">${message}</p>
  </div>
</body>
</html>`;
}

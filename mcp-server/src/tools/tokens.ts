import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import type { GeosourceClient } from "../client.js";
import type { TokenBalance } from "../types.js";

export function registerTokenTools(
  server: McpServer,
  client: GeosourceClient,
): void {
  server.tool(
    "get_token_balance",
    "Check your current token balance and usage statistics",
    {},
    async () => {
      const data = await client.get<TokenBalance>("/tokens/balance");

      return {
        content: [
          {
            type: "text",
            text: [
              `# Token Balance`,
              `Current balance: ${data.current_balance} tokens`,
              `Spent this month: ${data.spent_this_month} tokens`,
              `Transactions this month: ${data.transactions_this_month}`,
              `Total credited (all time): ${data.total_credited}`,
              `Total spent (all time): ${data.total_spent}`,
            ].join("\n"),
          },
        ],
      };
    },
  );
}

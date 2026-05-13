import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import type { GeosourceClient } from "../client.js";
import type { CitationQuery, CitationCheck } from "../types.js";

export function registerCitationTools(
  server: McpServer,
  client: GeosourceClient,
): void {
  server.tool(
    "list_citation_queries",
    "List your citation tracking queries and their current status",
    {
      team_id: z
        .number()
        .optional()
        .describe("Filter by team ID"),
    },
    async (params) => {
      const data = await client.get<{
        queries: CitationQuery[];
        usage: Record<string, unknown>;
      }>("/citations/queries", {
        team_id: params.team_id,
      });

      if (!data.queries.length) {
        return {
          content: [
            {
              type: "text",
              text: "No citation queries found. Use create_citation_query to start tracking.",
            },
          ],
        };
      }

      const lines = [`Found ${data.queries.length} citation queries:`, ""];

      for (const q of data.queries) {
        const status = q.latest_check
          ? `Last check: ${q.latest_check.platform} — ${q.latest_check.is_cited ? "CITED" : "NOT CITED"}`
          : "No checks yet";
        lines.push(
          `- [${q.uuid}] "${q.query}" on ${q.domain} (${q.frequency}) — ${status}`,
        );
      }

      const usage = data.usage as Record<string, number | string>;
      lines.push(
        "",
        `Queries: ${usage.queries_created ?? "?"}/${usage.max_queries ?? "?"}`,
        `Token balance: ${usage.token_balance ?? "?"}`,
      );

      return { content: [{ type: "text", text: lines.join("\n") }] };
    },
  );

  server.tool(
    "create_citation_query",
    "Create a new citation tracking query to monitor if your domain/brand is cited by AI platforms",
    {
      query: z
        .string()
        .describe("The search query to track (e.g., 'best SEO tools')"),
      domain: z
        .string()
        .describe("The domain to check for citations (e.g., 'example.com')"),
      brand: z
        .string()
        .optional()
        .describe("Brand name to also check for"),
      frequency: z
        .enum(["manual", "daily", "weekly"])
        .optional()
        .describe("How often to auto-check (default: manual)"),
      platforms: z
        .array(
          z.enum([
            "perplexity",
            "openai",
            "claude",
            "gemini",
            "deepseek",
            "google",
            "youtube",
            "facebook",
          ]),
        )
        .optional()
        .describe("Platforms to include in scheduled checks"),
      monthly_token_budget: z
        .number()
        .optional()
        .describe("Monthly token budget limit for automated checks"),
      team_id: z
        .number()
        .optional()
        .describe("Team ID to associate with"),
    },
    async (params) => {
      const result = await client.post<CitationQuery>("/citations/queries", {
        query: params.query,
        domain: params.domain,
        brand: params.brand,
        frequency: params.frequency ?? "manual",
        scheduled_platforms: params.platforms,
        monthly_token_budget: params.monthly_token_budget,
        team_id: params.team_id,
      });

      return {
        content: [
          {
            type: "text",
            text: [
              `Citation query created!`,
              `UUID: ${result.uuid}`,
              `Query: "${result.query}"`,
              `Domain: ${result.domain}`,
              `Frequency: ${result.frequency}`,
              "",
              `Use run_citation_check to check this query on a specific platform.`,
            ].join("\n"),
          },
        ],
      };
    },
  );

  server.tool(
    "run_citation_check",
    "Run a citation check for a query on a specific AI platform. Costs tokens based on the platform.",
    {
      query_uuid: z.string().describe("The citation query UUID"),
      platform: z
        .enum([
          "perplexity",
          "openai",
          "claude",
          "gemini",
          "deepseek",
          "google",
          "youtube",
          "facebook",
        ])
        .describe(
          "Platform to check. Token costs: deepseek=1, gemini/google/youtube/facebook=2, perplexity/claude=3, openai=5",
        ),
    },
    async (params) => {
      const result = await client.post<{
        uuid: string;
        platform: string;
        status: string;
      }>(`/citations/queries/${params.query_uuid}/check`, {
        platform: params.platform,
      });

      return {
        content: [
          {
            type: "text",
            text: [
              `Citation check started!`,
              `Check UUID: ${result.uuid}`,
              `Platform: ${result.platform}`,
              `Status: ${result.status}`,
              "",
              `The check runs asynchronously. Results will be available shortly.`,
            ].join("\n"),
          },
        ],
      };
    },
  );

  server.tool(
    "get_citation_trends",
    "Get citation trend data showing how your citations have changed over time across platforms",
    {
      days: z
        .number()
        .optional()
        .describe("Number of days to look back (7-90, default: 30)"),
      team_id: z
        .number()
        .optional()
        .describe("Filter by team ID"),
    },
    async (params) => {
      const data = await client.get<Record<string, unknown>>(
        "/citations/trends",
        {
          days: params.days,
          team_id: params.team_id,
        },
      );

      return {
        content: [
          {
            type: "text",
            text: JSON.stringify(data, null, 2),
          },
        ],
      };
    },
  );
}

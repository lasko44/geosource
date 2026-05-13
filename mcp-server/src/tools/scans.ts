import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import type { GeosourceClient } from "../client.js";
import type { Scan, ScanStatus, PaginatedResponse } from "../types.js";

export function registerScanTools(
  server: McpServer,
  client: GeosourceClient,
): void {
  server.tool(
    "list_scans",
    "List your GEO scans with optional filters for search, status, grade, and sorting",
    {
      search: z
        .string()
        .optional()
        .describe("Search by URL or title"),
      status: z
        .string()
        .optional()
        .describe("Filter by status: pending, processing, completed, failed, cancelled"),
      grade: z
        .string()
        .optional()
        .describe("Filter by grade: A, B, C, D, F"),
      sort: z
        .string()
        .optional()
        .describe("Sort by: created_at, score, grade, title, url"),
      direction: z
        .enum(["asc", "desc"])
        .optional()
        .describe("Sort direction"),
      per_page: z
        .number()
        .optional()
        .describe("Results per page (10-50)"),
      team_id: z
        .number()
        .optional()
        .describe("Filter by team ID"),
    },
    async (params) => {
      const data = await client.get<PaginatedResponse<Scan>>("/scans", {
        search: params.search,
        status: params.status,
        grade: params.grade,
        sort: params.sort,
        direction: params.direction,
        per_page: params.per_page,
        team_id: params.team_id,
      });

      const lines = [
        `Found ${data.total} scans (page ${data.current_page}/${data.last_page})`,
        "",
      ];

      for (const scan of data.data) {
        const score = scan.score !== null ? `${scan.score} (${scan.grade})` : scan.status;
        lines.push(`- [${scan.uuid}] ${scan.url} — ${score} — ${scan.created_at}`);
      }

      return { content: [{ type: "text", text: lines.join("\n") }] };
    },
  );

  server.tool(
    "get_scan",
    "Get full details of a GEO scan including score breakdown, pillar results, and recommendations",
    {
      uuid: z.string().describe("The scan UUID"),
    },
    async (params) => {
      const scan = await client.get<Scan>(`/scans/${params.uuid}`);

      const lines = [
        `# Scan: ${scan.url}`,
        `Title: ${scan.title ?? "N/A"}`,
        `Status: ${scan.status}`,
        `Score: ${scan.score ?? "N/A"} | Grade: ${scan.grade ?? "N/A"}`,
        `Tier: ${scan.requested_tier}`,
        `Created: ${scan.created_at}`,
      ];

      if (scan.results?.pillars) {
        lines.push("", "## Pillar Scores");
        for (const [key, pillar] of Object.entries(scan.results.pillars)) {
          lines.push(
            `- ${pillar.name}: ${pillar.score}/${pillar.max_score} (${pillar.grade})`,
          );
        }
      }

      if (scan.results?.recommendations?.length) {
        lines.push("", "## Recommendations");
        for (const rec of scan.results.recommendations) {
          lines.push(`- [${rec.priority}] ${rec.pillar}: ${rec.message}`);
        }
      }

      if (scan.results?.ai_suggestions?.length) {
        lines.push("", "## AI Suggestions");
        for (const sug of scan.results.ai_suggestions) {
          lines.push(`- **${sug.title}**: ${sug.suggestion}`);
        }
      }

      return { content: [{ type: "text", text: lines.join("\n") }] };
    },
  );

  server.tool(
    "run_scan",
    "Submit a URL for GEO (Generative Engine Optimization) scanning. Returns immediately with a UUID to poll for status.",
    {
      url: z.string().url().describe("The URL to scan"),
      tier: z
        .enum(["basic", "pro", "full"])
        .optional()
        .describe("Scan tier: basic (free, 5 pillars), pro (5 tokens, 8 pillars), full (10 tokens, 12 pillars)"),
      team_id: z
        .number()
        .optional()
        .describe("Team ID to associate the scan with"),
    },
    async (params) => {
      const result = await client.post<{
        uuid: string;
        url: string;
        status: string;
        requested_tier: string;
      }>("/scans", {
        url: params.url,
        tier: params.tier ?? "basic",
        team_id: params.team_id,
      });

      return {
        content: [
          {
            type: "text",
            text: [
              `Scan submitted successfully!`,
              `UUID: ${result.uuid}`,
              `URL: ${result.url}`,
              `Tier: ${result.requested_tier}`,
              `Status: ${result.status}`,
              "",
              `Use get_scan_status with UUID "${result.uuid}" to check progress.`,
            ].join("\n"),
          },
        ],
      };
    },
  );

  server.tool(
    "get_scan_status",
    "Check the current status and progress of a running scan",
    {
      uuid: z.string().describe("The scan UUID"),
    },
    async (params) => {
      const status = await client.get<ScanStatus>(
        `/scans/${params.uuid}/status`,
      );

      const lines = [
        `Status: ${status.status}`,
        `Progress: ${status.progress_percent ?? 0}%`,
      ];

      if (status.progress_step) {
        lines.push(`Step: ${status.progress_step}`);
      }

      if (status.score !== null) {
        lines.push(`Score: ${status.score} | Grade: ${status.grade}`);
      }

      if (status.error_message) {
        lines.push(`Error: ${status.error_message}`);
      }

      return { content: [{ type: "text", text: lines.join("\n") }] };
    },
  );

  server.tool(
    "cancel_scan",
    "Cancel a pending or processing scan",
    {
      uuid: z.string().describe("The scan UUID to cancel"),
    },
    async (params) => {
      const result = await client.post<{ success: boolean; status: string }>(
        `/scans/${params.uuid}/cancel`,
      );

      return {
        content: [
          {
            type: "text",
            text: result.success
              ? "Scan cancelled successfully."
              : "Could not cancel the scan.",
          },
        ],
      };
    },
  );
}

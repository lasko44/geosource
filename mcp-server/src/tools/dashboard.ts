import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import type { GeosourceClient } from "../client.js";
import type { DashboardData } from "../types.js";

export function registerDashboardTools(
  server: McpServer,
  client: GeosourceClient,
): void {
  server.tool(
    "get_dashboard",
    "Get a high-level overview of your GEO performance including recent scans, stats, and citation data",
    {
      team_id: z
        .number()
        .optional()
        .describe("Team ID for team-scoped data"),
    },
    async (params) => {
      const data = await client.get<DashboardData>("/dashboard", {
        team_id: params.team_id,
      });

      const lines = ["# Geosource Dashboard", ""];

      // Stats
      lines.push("## Scan Stats");
      lines.push(`Total scans: ${data.stats.total_scans}`);
      lines.push(`Average score: ${data.stats.avg_score}`);
      lines.push(`Best score: ${data.stats.best_score}`);
      lines.push(`Scans this week: ${data.stats.scans_this_week}`);

      if (data.current_team) {
        lines.push("", `Team: ${data.current_team.name}`);
      }

      // Recent scans
      if (data.recent_scans.length) {
        lines.push("", "## Recent Scans");
        for (const scan of data.recent_scans) {
          const score =
            scan.score !== null
              ? `${scan.score} (${scan.grade})`
              : scan.status;
          lines.push(`- ${scan.url} — ${score}`);
        }
      }

      // Citation data
      if (data.citation_data) {
        lines.push("", "## Citation Data");
        lines.push(JSON.stringify(data.citation_data, null, 2));
      }

      return { content: [{ type: "text", text: lines.join("\n") }] };
    },
  );
}

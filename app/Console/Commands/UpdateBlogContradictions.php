<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use Illuminate\Console\Command;

/**
 * Rewrites three blog posts whose claims contradict our published research line.
 *
 *   - topical-authority-matters-more-than-backlinks-in-ai-search → wholesale
 *     rewrite. The post's entire thesis was the hypothesis our research falsified.
 *
 *   - designing-content-for-ai-snippet-extraction → replace section 10
 *     "Link Internally to Build Topic Authority" with neutral guidance.
 *
 *   - why-some-content-becomes-ai-knowledge → drop the "Topical consistency"
 *     trust-signal bullet and the cross-link to the topical-authority post.
 *
 * Idempotent: each update is guarded by a unique sentinel string in the
 * rewritten content. Running the command twice is a no-op.
 *
 * Slugs and URLs are deliberately preserved so inbound links keep working.
 */
class UpdateBlogContradictions extends Command
{
    protected $signature = 'blog:update-research-contradictions';

    protected $description = 'Rewrite the 3 blog posts whose claims contradict the published research line';

    /**
     * Sentinel string used to mark posts that have already been updated.
     * Stored in `tags` so we can detect re-runs without inspecting content.
     */
    private const SENTINEL_TAG = 'research-aligned-2026-06';

    public function handle(): int
    {
        $this->update(
            slug: 'topical-authority-matters-more-than-backlinks-in-ai-search',
            label: 'Topical authority post (wholesale rewrite)',
            apply: fn (BlogPost $p) => $this->rewriteTopicalAuthorityPost($p),
        );

        $this->update(
            slug: 'designing-content-for-ai-snippet-extraction',
            label: 'Designing content post (section 10 replacement)',
            apply: fn (BlogPost $p) => $this->rewriteDesigningContentPost($p),
        );

        $this->update(
            slug: 'why-some-content-becomes-ai-knowledge',
            label: 'Why some content becomes AI knowledge post (bullet removal)',
            apply: fn (BlogPost $p) => $this->rewriteWhySomeContentPost($p),
        );

        $this->newLine();
        $this->info('Done.');
        return Command::SUCCESS;
    }

    /**
     * Locate a post, skip if it's already been updated, otherwise apply $apply
     * and tag the post so subsequent runs are no-ops.
     */
    private function update(string $slug, string $label, callable $apply): void
    {
        $post = BlogPost::where('slug', $slug)->first();
        if (! $post) {
            $this->warn("• {$label}: post not found ({$slug})");
            return;
        }

        if ($this->alreadyUpdated($post)) {
            $this->line("• {$label}: already up to date, skipping");
            return;
        }

        $apply($post);
        $tags = is_array($post->tags) ? $post->tags : [];
        $tags[] = self::SENTINEL_TAG;
        $post->tags = array_values(array_unique($tags));
        $post->save();

        $this->info("✓ {$label}: updated");
    }

    private function alreadyUpdated(BlogPost $post): bool
    {
        $tags = is_array($post->tags) ? $post->tags : [];
        return in_array(self::SENTINEL_TAG, $tags, true);
    }

    /**
     * Wholesale rewrite. The original post's title and thesis ("topical
     * authority is the lever") was the hypothesis our research falsified.
     * Slug stays the same so inbound links keep working; title and body change.
     */
    private function rewriteTopicalAuthorityPost(BlogPost $post): void
    {
        $post->title = 'Topical authority and AI search: what our research actually found';
        $post->excerpt = 'We spent six months testing the prevailing theory that topical authority drives AI citations. Across four studies, the data did not support it. Here is what we found instead.';
        $post->meta_title = 'Topical Authority and AI Search: What Our Research Actually Found';
        $post->meta_description = 'We tested the topical-authority theory across four studies and the data did not support it. Brand recognition and query phrasing matter more — here is the full read.';
        $post->content = $this->topicalAuthorityBody();
        $post->faq = null; // old FAQ schema reinforced the bad framing
        $post->schema_json = null;
    }

    private function topicalAuthorityBody(): string
    {
        return <<<MD
        The conventional wisdom in generative engine optimization has been that *topical authority* — covering a subject deeply across many connected pages — is what drives AI assistants to cite your site over a competitor.

        We spent six months testing that theory across four studies in our research line. The data did not support it.

        This post walks through what we expected, what we actually found, and what to focus on instead.

        ## What "topical authority" was supposed to do

        The hypothesis: AI assistants need to figure out which sites understand a subject deeply, and they do that by looking at content patterns — how many pages a site has on a topic, how those pages link to each other, how consistent the terminology is. A site with deep topic-cluster coverage signals comprehension. The AI rewards that with citations.

        It's intuitive. It also looks right at first glance — sites with strong topical depth do often get cited.

        ## What we found instead

        Across all four studies in our [research line](/research), the Topic Authority pillar (which measures topic depth, internal linking density, and keyword consistency) was a **weak or negative predictor** of whether AI actually cites a page.

        In our [ecommerce shopping-journey study](/blog/ecommerce-recommendation-survival), we tracked 40 brands across 12 product categories through a four-stage shopping conversation. Brands with the highest authority scores in our scoring model were recommended *less* often than brands with simpler, cleaner pages. The brands that made it all the way to a purchase recommendation tended to be familiar category-fit names — not the ones with the most topical depth on paper.

        ## What's really doing the work

        Two things matter far more than topical authority for whether AI cites your content:

        **1. Brand recognition.** Across every study, well-known brands got cited even with weak on-page signals; lesser-known brands struggled even with strong content. This is the biggest factor we measured, and it isn't something page-level SEO can fix.

        **2. Query phrasing.** Whether a person's question names your brand or not is the single biggest factor in whether you get cited. Our [E-E-A-T &amp; content type follow-up](/blog/eeat-content-type-study) showed that the same page can be cited or invisible depending only on how the question is phrased.

        ## What still matters on the page

        This isn't a "nothing on the page matters" finding. Three specific signals did predict citations in our research:

        - **Answerability** for informational queries — direct, declarative content that answers the question without making the reader dig
        - **Citation Quality** — citing authoritative external sources in your own content
        - **Definitions** — explicit "X is Y" statements near the top of a page

        These three drive our Citation Readiness Score, which uses only the pillars our research showed actually predict citations.

        For ecommerce and shopping queries the picture is different — over-optimization on these signals can actually hurt. We built a separate Recommendation Readiness Score for that context.

        ## What this means for your strategy

        The old advice — "build topical authority by covering a subject from many angles" — isn't wrong, but it isn't the lever it was made out to be. If your content is unclear, formatting like a wiki entry won't save it. If your content is clear, you don't need ten supporting articles for AI to find the one that answers a question.

        Focus on:

        - One page = one clear answer
        - Be the obvious result for the questions your audience naturally asks
        - Accept that brand familiarity is doing more work than any page-level signal, and prioritize your content investment accordingly

        You don't need to "build topical authority." You need to write the page that's worth citing.

        ---

        Want the full picture? [Read the cross-study synthesis &rarr;](/blog/what-predicts-ai-citations) or browse all of our [original research](/research).
        MD;
    }

    /**
     * Replace the "## 10. Link Internally to Build Topic Authority" section
     * with neutral internal-linking guidance. Leaves the rest of the post
     * untouched. The section numbering downstream stays at 10 → 11 → 12
     * because we keep the section number.
     */
    private function rewriteDesigningContentPost(BlogPost $post): void
    {
        $old = "## 10. Link Internally to Build Topic Authority\n\n"
            ."AI doesn't evaluate pages in isolation. It evaluates:\n\n"
            ."- Topical depth\n"
            ."- Semantic connections\n"
            ."- Internal consistency\n\n"
            ."Link related articles together using descriptive anchor text.\n\n"
            ."This strengthens: **Topical authority**, **Contextual relevance**, and **Long-form trust signals**.\n\n"
            ."A strong internal content cluster makes your site appear knowledge-dense — a major GEO advantage.";

        $new = "## 10. Use Internal Links Sparingly and Meaningfully\n\n"
            ."Internal links help readers and AI navigate related concepts. Use them when they genuinely add context — link to a definition page when you mention a key term, or to a deeper explainer when the current page only summarizes.\n\n"
            ."Don't overdo it. In our research, heavy internal linking and "
            ."\"topic-cluster\" depth did not measurably improve AI citation rates. The signal AI assistants reward is per-page clarity, not site-wide topic density. Each internal link should earn its place.";

        $post->content = str_replace($old, $new, $post->content);
    }

    /**
     * Drop the "Topical consistency" trust-signal bullet and the cross-link
     * sentence that points back at the (now-rewritten) topical-authority post.
     */
    private function rewriteWhySomeContentPost(BlogPost $post): void
    {
        // Remove the bullet
        $post->content = str_replace(
            "- **Topical consistency** — Does this site cover this subject deeply?\n",
            '',
            $post->content,
        );

        // Replace the cross-link sentence
        $oldCrossLink = "This is why [Topical Authority Matters More Than Backlinks in AI Search](/blog/topical-authority-matters-more-than-backlinks-in-ai-search) — consistency beats popularity in the generative era.";
        $newCrossLink = "Our [cross-study synthesis](/blog/what-predicts-ai-citations) walks through the full picture of what AI assistants actually reward — brand familiarity and query phrasing do far more of the work than depth of topic coverage alone.";
        $post->content = str_replace($oldCrossLink, $newCrossLink, $post->content);
    }
}

<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class TokenAnnouncementEmailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EmailTemplate::updateOrCreate(
            ['slug' => 'token-pricing-announcement'],
            [
                'name' => 'Token Pricing Announcement',
                'subject' => '🎉 {{ user_first_name }}, you just got 25 free tokens! Here\'s what you can do with them',
                'preview_text' => 'Pro scans, Full scans, citation checks — no subscription needed! Plus 25 tokens on us.',
                'type' => 'marketing',
                'is_active' => true,
                'content' => $this->getEmailContent(),
            ]
        );
    }

    private function getEmailContent(): string
    {
        return <<<'HTML'
<h2>Hey {{ user_first_name }}! 🎉</h2>

<p>We've got some exciting news that we just couldn't wait to share with you!</p>

<p>You can now unlock <strong>Pro</strong> and <strong>Full</strong> level GEO scans <em>without</em> a monthly subscription. Yep, you read that right!</p>

<h3>Say Hello to Token-Based Pricing 👋</h3>

<p>We heard you loud and clear — you wanted more flexibility. So we built something awesome: <strong>pay only for what you use, when you use it.</strong></p>

<p>No subscriptions. No commitments. Just powerful scans whenever you need them.</p>

<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
    <tr style="background-color: #f0fdf4;">
        <td style="padding: 12px; border: 1px solid #e5e7eb;">✅ <strong>Basic Scans</strong></td>
        <td style="padding: 12px; border: 1px solid #e5e7eb;">Always FREE — unlimited!</td>
    </tr>
    <tr>
        <td style="padding: 12px; border: 1px solid #e5e7eb;">⚡ <strong>Pro Scans</strong></td>
        <td style="padding: 12px; border: 1px solid #e5e7eb;">5 tokens each</td>
    </tr>
    <tr style="background-color: #faf5ff;">
        <td style="padding: 12px; border: 1px solid #e5e7eb;">🚀 <strong>Full Scans</strong></td>
        <td style="padding: 12px; border: 1px solid #e5e7eb;">10 tokens each</td>
    </tr>
    <tr>
        <td style="padding: 12px; border: 1px solid #e5e7eb;">🔍 <strong>Citation Checks</strong></td>
        <td style="padding: 12px; border: 1px solid #e5e7eb;">2-5 tokens per platform</td>
    </tr>
</table>

<h3>Here's Why You're Going to Love This 💜</h3>

<ul style="padding-left: 20px; line-height: 1.8;">
    <li><strong>Zero commitment</strong> — Grab tokens when you need them, use them whenever</li>
    <li><strong>They never expire</strong> — Your tokens are yours forever</li>
    <li><strong>Save your budget</strong> — Only pay for scans you actually run</li>
    <li><strong>Instant upgrades</strong> — Go from Basic to Full in one click</li>
</ul>

<h3>🎁 A Little Gift to Get You Started</h3>

<p>Because you're awesome and we appreciate you being part of our community, we've dropped <strong>25 free tokens</strong> into your account!</p>

<p>That's enough for <strong>5 Pro scans</strong> or <strong>2 Full scans</strong> (with tokens to spare). Not too shabby, right?</p>

<p style="text-align: center; margin: 30px 0;">
    <a href="{{ app_url }}/dashboard" class="cta-button">🎯 Run Your First Pro Scan</a>
</p>

<h3>Want Even More Power?</h3>

<p>Our <strong>Team plan</strong> ($99/month) is perfect if you're running an agency or need serious scanning power. You'll get 1,000 tokens monthly, team collaboration, and priority support.</p>

<p>But hey, no pressure — the token system is here so you can move at your own pace!</p>

<p>Got questions? Just hit reply — we'd love to hear from you. 💬</p>

<p>Happy scanning!</p>

<p><strong>— The {{ app_name }} Team</strong> ✨</p>
HTML;
    }
}

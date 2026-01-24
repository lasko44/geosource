<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GeoSource.ai - User Guide</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .prose h2 { scroll-margin-top: 80px; }
        .prose h3 { scroll-margin-top: 80px; }
        .sidebar { position: sticky; top: 1rem; max-height: calc(100vh - 2rem); overflow-y: auto; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center gap-4">
                        <h1 class="text-xl font-bold text-gray-900">User Guide</h1>
                    </div>
                    <a href="/dashboard" class="text-purple-600 hover:text-purple-700 font-medium">
                        Back to Dashboard &rarr;
                    </a>
                </div>
            </div>
        </header>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex gap-8">
                <!-- Sidebar Navigation -->
                <nav class="hidden lg:block w-64 flex-shrink-0">
                    <div class="sidebar bg-white rounded-lg border border-gray-200 p-4">
                        <h2 class="font-semibold text-gray-900 mb-4">Contents</h2>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#getting-started" class="text-gray-600 hover:text-purple-600">Getting Started</a></li>
                            <li><a href="#running-scans" class="text-gray-600 hover:text-purple-600">Running Scans</a></li>
                            <li><a href="#understanding-scores" class="text-gray-600 hover:text-purple-600">Understanding Your Score</a></li>
                            <li><a href="#score-pillars" class="text-gray-600 hover:text-purple-600">Score Pillars Explained</a></li>
                            <li><a href="#recommendations" class="text-gray-600 hover:text-purple-600">Using Recommendations</a></li>
                            <li><a href="#teams" class="text-gray-600 hover:text-purple-600">Managing Teams</a></li>
                            <li><a href="#citation-tracking" class="text-gray-600 hover:text-purple-600">Citation Tracking</a></li>
                            <li><a href="#billing" class="text-gray-600 hover:text-purple-600">Billing & Plans</a></li>
                            <li><a href="#exports" class="text-gray-600 hover:text-purple-600">Exporting Reports</a></li>
                            <li><a href="#faq" class="text-gray-600 hover:text-purple-600">FAQ</a></li>
                        </ul>
                    </div>
                </nav>

                <!-- Main Content -->
                <main class="flex-1 min-w-0">
                    <div class="bg-white rounded-lg border border-gray-200 p-8 prose prose-gray max-w-none">

                        <!-- Getting Started -->
                        <h2 id="getting-started" class="text-2xl font-bold text-gray-900 border-b pb-4">Getting Started</h2>

                        <p>Welcome to <strong>GeoSource.ai</strong> - your platform for optimizing content for AI search engines. GEO (Generative Engine Optimization) helps ensure your content is easily understood and cited by AI systems like ChatGPT, Perplexity, Claude, and Google AI Overviews.</p>

                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 my-4">
                            <h4 class="font-semibold text-purple-900 mb-2">What is GEO?</h4>
                            <p class="text-purple-800 text-sm">Generative Engine Optimization (GEO) is the practice of optimizing your web content so that AI-powered search engines can better understand, cite, and recommend your pages. Unlike traditional SEO which focuses on keywords and backlinks, GEO focuses on content structure, clarity, and machine readability.</p>
                        </div>

                        <h3 class="text-lg font-semibold mt-6">Quick Start</h3>
                        <ol class="list-decimal list-inside space-y-2">
                            <li>Navigate to your <strong>Dashboard</strong></li>
                            <li>Enter a URL in the scan input field</li>
                            <li>Click <strong>Scan</strong> and wait for analysis</li>
                            <li>Review your GEO score and recommendations</li>
                            <li>Implement suggested improvements</li>
                            <li>Re-scan to measure progress</li>
                        </ol>

                        <!-- Running Scans -->
                        <h2 id="running-scans" class="text-2xl font-bold text-gray-900 border-b pb-4 mt-12">Running Scans</h2>

                        <h3 class="text-lg font-semibold mt-6">Starting a New Scan</h3>
                        <p>To scan a webpage for GEO optimization:</p>
                        <ol class="list-decimal list-inside space-y-2 mt-2">
                            <li>Go to the <strong>Dashboard</strong></li>
                            <li>Enter the full URL (including https://) of the page you want to analyze</li>
                            <li>If you have teams, select which context to scan under (Personal or Team)</li>
                            <li>Click the <strong>Scan</strong> button</li>
                        </ol>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 my-4">
                            <h4 class="font-semibold text-blue-900 mb-2">Scan Tips</h4>
                            <ul class="text-blue-800 text-sm list-disc list-inside space-y-1">
                                <li>Scan individual pages, not entire websites</li>
                                <li>Use the canonical URL of the page</li>
                                <li>Ensure the page is publicly accessible</li>
                                <li>Wait for dynamic content to load before scanning</li>
                            </ul>
                        </div>

                        <h3 class="text-lg font-semibold mt-6">Scan Progress</h3>
                        <p>During scanning, you'll see progress updates:</p>
                        <ul class="list-disc list-inside space-y-1 mt-2">
                            <li><strong>Fetching webpage</strong> - Retrieving page content</li>
                            <li><strong>Analyzing structure</strong> - Examining HTML and content</li>
                            <li><strong>Checking llms.txt</strong> - Looking for AI-specific configurations</li>
                            <li><strong>Scoring content</strong> - Evaluating against GEO criteria</li>
                            <li><strong>Generating recommendations</strong> - Creating improvement suggestions</li>
                        </ul>

                        <h3 class="text-lg font-semibold mt-6">Re-scanning Pages</h3>
                        <p>After making improvements to your content, use the <strong>Rescan</strong> button on any completed scan to check your progress. Re-scans count against your monthly scan quota.</p>

                        <!-- Understanding Scores -->
                        <h2 id="understanding-scores" class="text-2xl font-bold text-gray-900 border-b pb-4 mt-12">Understanding Your Score</h2>

                        <h3 class="text-lg font-semibold mt-6">Overall GEO Score</h3>
                        <p>Your GEO score is a composite measure of how well your content is optimized for AI systems. The score is calculated from multiple "pillars" that evaluate different aspects of your content.</p>

                        <h3 class="text-lg font-semibold mt-6">Grade Scale</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 my-4">
                            <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-center">
                                <span class="text-2xl font-bold text-green-700">A+</span>
                                <p class="text-xs text-green-600 mt-1">90%+ Excellent</p>
                            </div>
                            <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-center">
                                <span class="text-2xl font-bold text-green-700">A/A-</span>
                                <p class="text-xs text-green-600 mt-1">80-89% Very Good</p>
                            </div>
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-center">
                                <span class="text-2xl font-bold text-blue-700">B+/B/B-</span>
                                <p class="text-xs text-blue-600 mt-1">65-79% Good</p>
                            </div>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-center">
                                <span class="text-2xl font-bold text-yellow-700">C+/C/C-</span>
                                <p class="text-xs text-yellow-600 mt-1">50-64% Needs Work</p>
                            </div>
                        </div>

                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 my-4">
                            <h4 class="font-semibold text-gray-900 mb-2">Score Details by Plan</h4>
                            <p class="text-sm text-gray-600">All plans use the same GEO scoring algorithm. The difference is in how results are presented:</p>
                            <ul class="text-sm text-gray-600 mt-2 space-y-1">
                                <li><strong>Free:</strong> Basic GEO score with top 3 recommendations</li>
                                <li><strong>Pro:</strong> Full score breakdown by pillar with all recommendations</li>
                                <li><strong>Agency:</strong> Full breakdown plus competitor benchmarking</li>
                            </ul>
                        </div>

                        <!-- Score Pillars -->
                        <h2 id="score-pillars" class="text-2xl font-bold text-gray-900 border-b pb-4 mt-12">Score Pillars Explained</h2>

                        <p>Your GEO score is calculated from multiple pillars. Each pillar measures a specific aspect of how well your content can be understood and cited by AI systems. Pro and Agency plans show the full breakdown by pillar.</p>

                        <div class="space-y-4 mt-6">
                            <div class="border rounded-lg p-4">
                                <h4 class="font-semibold">Definition Clarity</h4>
                                <p class="text-sm text-gray-600 mt-1">Measures how well your content defines key terms and concepts. AI systems look for clear definitions to provide accurate answers.</p>
                                <p class="text-sm text-gray-500 mt-2"><strong>Improve by:</strong> Adding clear definitions early in your content, using phrases like "X is defined as..." or "X refers to..."</p>
                            </div>

                            <div class="border rounded-lg p-4">
                                <h4 class="font-semibold">Content Structure</h4>
                                <p class="text-sm text-gray-600 mt-1">Evaluates your heading hierarchy and content organization. Well-structured content is easier for AI to parse and cite.</p>
                                <p class="text-sm text-gray-500 mt-2"><strong>Improve by:</strong> Using a single H1, multiple H2s, proper heading hierarchy, and bullet/numbered lists.</p>
                            </div>

                            <div class="border rounded-lg p-4">
                                <h4 class="font-semibold">Topic Authority</h4>
                                <p class="text-sm text-gray-600 mt-1">Measures content depth, comprehensiveness, and expertise signals.</p>
                                <p class="text-sm text-gray-500 mt-2"><strong>Improve by:</strong> Writing comprehensive content (800+ words), staying on topic, and linking to related internal content.</p>
                            </div>

                            <div class="border rounded-lg p-4">
                                <h4 class="font-semibold">Machine Readability</h4>
                                <p class="text-sm text-gray-600 mt-1">Checks technical accessibility for AI crawlers, including schema markup and semantic HTML.</p>
                                <p class="text-sm text-gray-500 mt-2"><strong>Improve by:</strong> Adding JSON-LD schema markup, using semantic HTML tags, and providing alt text for images.</p>
                            </div>

                            <div class="border rounded-lg p-4">
                                <h4 class="font-semibold">Answerability</h4>
                                <p class="text-sm text-gray-600 mt-1">Evaluates how easily AI can extract direct answers from your content.</p>
                                <p class="text-sm text-gray-500 mt-2"><strong>Improve by:</strong> Writing declarative statements, avoiding hedging words (maybe, perhaps), and including quotable snippets.</p>
                            </div>

                            <div class="border rounded-lg p-4">
                                <h4 class="font-semibold">E-E-A-T Signals</h4>
                                <p class="text-sm text-gray-600 mt-1">Experience, Expertise, Authoritativeness, and Trustworthiness signals that build credibility with both AI and users.</p>
                                <p class="text-sm text-gray-500 mt-2"><strong>Improve by:</strong> Adding author bios, credentials, contact information, and trust signals like reviews or testimonials.</p>
                            </div>

                            <div class="border rounded-lg p-4">
                                <h4 class="font-semibold">Citation Quality</h4>
                                <p class="text-sm text-gray-600 mt-1">Measures the quality and quantity of citations and references to authoritative sources.</p>
                                <p class="text-sm text-gray-500 mt-2"><strong>Improve by:</strong> Citing authoritative sources (.gov, .edu), including statistics with sources, and adding a references section.</p>
                            </div>

                            <div class="border rounded-lg p-4">
                                <h4 class="font-semibold">AI Accessibility</h4>
                                <p class="text-sm text-gray-600 mt-1">Checks if AI crawlers are allowed to access and index your content.</p>
                                <p class="text-sm text-gray-500 mt-2"><strong>Improve by:</strong> Allowing AI crawlers in robots.txt, having a sitemap, and checking for llms.txt configuration.</p>
                            </div>

                            <div class="border rounded-lg p-4">
                                <h4 class="font-semibold">Content Freshness</h4>
                                <p class="text-sm text-gray-600 mt-1">Evaluates how current and up-to-date your content appears to be.</p>
                                <p class="text-sm text-gray-500 mt-2"><strong>Improve by:</strong> Displaying publication and "last updated" dates, referencing current events or data.</p>
                            </div>

                            <div class="border rounded-lg p-4">
                                <h4 class="font-semibold">Readability</h4>
                                <p class="text-sm text-gray-600 mt-1">Measures reading level and sentence complexity for broad accessibility.</p>
                                <p class="text-sm text-gray-500 mt-2"><strong>Improve by:</strong> Targeting 8th-9th grade reading level, keeping sentences under 20 words, using simple language.</p>
                            </div>

                            <div class="border rounded-lg p-4">
                                <h4 class="font-semibold">Question Coverage</h4>
                                <p class="text-sm text-gray-600 mt-1">Checks if your content answers common questions about the topic.</p>
                                <p class="text-sm text-gray-500 mt-2"><strong>Improve by:</strong> Including FAQ sections, using question-format headings, covering what/how/why/when questions.</p>
                            </div>

                            <div class="border rounded-lg p-4">
                                <h4 class="font-semibold">Multimedia Optimization</h4>
                                <p class="text-sm text-gray-600 mt-1">Evaluates image usage, alt text quality, captions, and data tables.</p>
                                <p class="text-sm text-gray-500 mt-2"><strong>Improve by:</strong> Adding relevant images with descriptive alt text, using figure captions, including data tables where appropriate.</p>
                            </div>
                        </div>

                        <!-- Recommendations -->
                        <h2 id="recommendations" class="text-2xl font-bold text-gray-900 border-b pb-4 mt-12">Using Recommendations</h2>

                        <p>Each scan generates personalized recommendations to improve your GEO score. Recommendations are prioritized by impact and grouped by category.</p>

                        <h3 class="text-lg font-semibold mt-6">Recommendation Priorities</h3>
                        <div class="flex gap-4 my-4">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 bg-red-500 rounded-full"></span>
                                <span class="text-sm">High Priority - Fix first</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 bg-yellow-500 rounded-full"></span>
                                <span class="text-sm">Medium Priority</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                                <span class="text-sm">Low Priority - Nice to have</span>
                            </div>
                        </div>

                        <h3 class="text-lg font-semibold mt-6">Acting on Recommendations</h3>
                        <ol class="list-decimal list-inside space-y-2 mt-2">
                            <li>Review the recommendations in your scan results</li>
                            <li>Start with high-priority items for maximum impact</li>
                            <li>Make the suggested changes to your content</li>
                            <li>Re-scan the page to verify improvements</li>
                            <li>Repeat until you reach your target score</li>
                        </ol>

                        <!-- Teams -->
                        <h2 id="teams" class="text-2xl font-bold text-gray-900 border-b pb-4 mt-12">Managing Teams</h2>

                        <p>Teams allow you to collaborate with colleagues on GEO optimization. Team features are available on Pro and Agency plans.</p>

                        <h3 class="text-lg font-semibold mt-6">Team Roles</h3>
                        <div class="space-y-3 mt-4">
                            <div class="flex items-start gap-3">
                                <span class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-xs font-semibold">Owner</span>
                                <p class="text-sm text-gray-600">Full control - can manage members, billing, and all team settings. The team quota comes from the owner's subscription.</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs font-semibold">Admin</span>
                                <p class="text-sm text-gray-600">Can invite members, remove non-admin members, and manage team scans.</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs font-semibold">Member</span>
                                <p class="text-sm text-gray-600">Can run scans and view team reports, but cannot manage team settings.</p>
                            </div>
                        </div>

                        <h3 class="text-lg font-semibold mt-6">Inviting Team Members</h3>
                        <ol class="list-decimal list-inside space-y-2 mt-2">
                            <li>Go to <strong>Teams</strong> in the sidebar</li>
                            <li>Select your team</li>
                            <li>Click <strong>Invite Member</strong></li>
                            <li>Enter their email address and select a role</li>
                            <li>They'll receive an invitation email to join</li>
                        </ol>

                        <h3 class="text-lg font-semibold mt-6">Switching Team Context</h3>
                        <p>When running scans, you can choose whether to scan under your personal account or a team. Use the context switcher on the Dashboard to select where your scan will be saved and which quota to use.</p>

                        <!-- Citation Tracking -->
                        <h2 id="citation-tracking" class="text-2xl font-bold text-gray-900 border-b pb-4 mt-12">Citation Tracking</h2>

                        <div class="bg-purple-100 border border-purple-300 rounded-lg p-4 my-4 flex items-center gap-3">
                            <span class="bg-purple-600 text-white text-xs font-bold px-2 py-1 rounded">AGENCY ONLY</span>
                            <p class="text-purple-800 text-sm font-medium">Citation Tracking is an exclusive feature available only on the Agency plan.</p>
                        </div>

                        <p>Citation Tracking monitors whether AI search engines (like ChatGPT, Claude, and Perplexity) are citing your website when answering user questions.</p>

                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 my-4">
                            <h4 class="font-semibold text-orange-900 mb-2">Why Track Citations?</h4>
                            <p class="text-orange-800 text-sm">AI search engines are becoming a primary way people find information. When ChatGPT or Perplexity answers a question and cites your website, that's valuable traffic and credibility. Citation tracking helps you understand your AI search visibility.</p>
                        </div>

                        <h3 class="text-lg font-semibold mt-6">Getting Started with Citation Tracking</h3>
                        <ol class="list-decimal list-inside space-y-2 mt-2">
                            <li>Navigate to <strong>Citation Tracking</strong> in the sidebar</li>
                            <li>Click <strong>New Query</strong> to create your first tracking query</li>
                            <li>Enter a search query that relates to your content (e.g., "best project management tools")</li>
                            <li>Enter your domain (e.g., "example.com")</li>
                            <li>Optionally add a brand name if different from your domain</li>
                            <li>Select a check frequency (manual, weekly, or daily)</li>
                            <li>Save and run your first check</li>
                        </ol>

                        <h3 class="text-lg font-semibold mt-6">Creating Effective Queries</h3>
                        <p>The key to useful citation tracking is choosing the right queries to monitor:</p>

                        <div class="space-y-4 mt-4">
                            <div class="border rounded-lg p-4">
                                <h4 class="font-semibold text-green-700">Good Query Examples</h4>
                                <ul class="text-sm text-gray-600 mt-2 space-y-1 list-disc list-inside">
                                    <li>"What is the best CRM for small businesses?" (if you sell CRM software)</li>
                                    <li>"How to optimize content for AI search engines" (if you write about GEO)</li>
                                    <li>"Top restaurants in Austin Texas" (if you run a restaurant)</li>
                                    <li>"What are the benefits of solar panels?" (if you're in solar industry)</li>
                                </ul>
                            </div>

                            <div class="border rounded-lg p-4">
                                <h4 class="font-semibold text-red-700">Less Effective Queries</h4>
                                <ul class="text-sm text-gray-600 mt-2 space-y-1 list-disc list-inside">
                                    <li>"example.com" (too specific, AI won't search for your domain directly)</li>
                                    <li>"weather today" (not relevant unless you're a weather service)</li>
                                    <li>"buy shoes online" (too broad and transactional)</li>
                                </ul>
                            </div>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 my-4">
                            <h4 class="font-semibold text-blue-900 mb-2">Pro Tip</h4>
                            <p class="text-blue-800 text-sm">Think about what questions your ideal customers would ask an AI assistant. Those are the queries you should track. If your GEO-optimized content answers those questions well, AI systems are more likely to cite you.</p>
                        </div>

                        <h3 class="text-lg font-semibold mt-6">Available AI Platforms</h3>
                        <p>You can check citations across multiple AI platforms:</p>

                        <div class="grid md:grid-cols-2 gap-4 my-4">
                            <div class="border rounded-lg p-4 flex items-start gap-3">
                                <span class="w-3 h-3 rounded-full bg-teal-500 mt-1.5"></span>
                                <div>
                                    <h4 class="font-semibold">Perplexity</h4>
                                    <p class="text-sm text-gray-600">AI search engine that always cites sources. Great for tracking factual content.</p>
                                </div>
                            </div>
                            <div class="border rounded-lg p-4 flex items-start gap-3">
                                <span class="w-3 h-3 rounded-full bg-green-500 mt-1.5"></span>
                                <div>
                                    <h4 class="font-semibold">ChatGPT</h4>
                                    <p class="text-sm text-gray-600">OpenAI's popular assistant with web browsing capabilities.</p>
                                </div>
                            </div>
                            <div class="border rounded-lg p-4 flex items-start gap-3">
                                <span class="w-3 h-3 rounded-full bg-orange-500 mt-1.5"></span>
                                <div>
                                    <h4 class="font-semibold">Claude</h4>
                                    <p class="text-sm text-gray-600">Anthropic's AI assistant known for thoughtful, detailed responses.</p>
                                </div>
                            </div>
                        </div>

                        <h3 class="text-lg font-semibold mt-6">Running Citation Checks</h3>
                        <p>Once you've created a query, you can run checks to see if AI platforms cite your domain:</p>

                        <ol class="list-decimal list-inside space-y-2 mt-2">
                            <li>Open a query from the Citation Tracking dashboard</li>
                            <li>Click <strong>Run Check</strong> on any platform card</li>
                            <li>Wait for the check to complete (usually 10-30 seconds)</li>
                            <li>View the results showing whether you were cited</li>
                            <li>Click <strong>View Response</strong> to see the full AI response and citations</li>
                        </ol>

                        <h3 class="text-lg font-semibold mt-6">Understanding Citation Results</h3>
                        <p>Each check will show one of two outcomes:</p>

                        <div class="grid md:grid-cols-2 gap-4 my-4">
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span class="font-semibold text-green-700">Cited</span>
                                </div>
                                <p class="text-sm text-green-700">The AI mentioned or linked to your domain in its response. This is a win!</p>
                            </div>
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span class="font-semibold text-red-700">Not Cited</span>
                                </div>
                                <p class="text-sm text-red-700">Your domain wasn't mentioned. Review the response to see what sources were cited instead.</p>
                            </div>
                        </div>

                        <h3 class="text-lg font-semibold mt-6">Check History & Alerts</h3>
                        <p>Citation Tracking maintains a history of all checks and alerts you to changes:</p>

                        <ul class="list-disc list-inside space-y-2 mt-2">
                            <li><strong>Check History:</strong> View all past checks for a query, with timestamps and results</li>
                            <li><strong>New Citation Alert:</strong> Get notified when an AI starts citing your domain (great news!)</li>
                            <li><strong>Lost Citation Alert:</strong> Get notified if an AI stops citing you (may need attention)</li>
                        </ul>

                        <h3 class="text-lg font-semibold mt-6">Scheduled Checks</h3>
                        <p>Instead of running checks manually, you can schedule them to run automatically:</p>

                        <div class="space-y-3 mt-4">
                            <div class="flex items-start gap-3">
                                <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs font-semibold">Manual</span>
                                <p class="text-sm text-gray-600">Only runs when you click "Run Check". Best for occasional monitoring.</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs font-semibold">Weekly</span>
                                <p class="text-sm text-gray-600">Automatically checks once per week. Good for stable content.</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-xs font-semibold">Daily</span>
                                <p class="text-sm text-gray-600">Checks every day. Best for competitive or time-sensitive queries.</p>
                            </div>
                        </div>

                        <h3 class="text-lg font-semibold mt-6">Improving Your Citation Rate</h3>
                        <p>If AI platforms aren't citing your content, here are ways to improve:</p>

                        <div class="space-y-4 mt-4">
                            <div class="border-l-4 border-purple-500 pl-4">
                                <h4 class="font-semibold">Optimize Your Content</h4>
                                <p class="text-sm text-gray-600">Run a GEO scan on your relevant pages and implement the recommendations. Better-structured content is more likely to be cited.</p>
                            </div>
                            <div class="border-l-4 border-purple-500 pl-4">
                                <h4 class="font-semibold">Add Clear Definitions</h4>
                                <p class="text-sm text-gray-600">AI systems love content that clearly defines terms. If you rank for "what is X", make sure your content definitively answers that.</p>
                            </div>
                            <div class="border-l-4 border-purple-500 pl-4">
                                <h4 class="font-semibold">Include Statistics & Data</h4>
                                <p class="text-sm text-gray-600">Original research, statistics, and data points are highly citable. AI systems prefer content they can quote with confidence.</p>
                            </div>
                            <div class="border-l-4 border-purple-500 pl-4">
                                <h4 class="font-semibold">Build Authority</h4>
                                <p class="text-sm text-gray-600">Strong E-E-A-T signals (author credentials, citations to authoritative sources) help AI systems trust your content.</p>
                            </div>
                        </div>

                        <h3 class="text-lg font-semibold mt-6">Citation Tracking Limits (Agency Plan)</h3>
                        <p>As an Agency plan feature, citation tracking includes generous limits:</p>

                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 my-4">
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li><strong>Daily Checks:</strong> Up to 50 citation checks per day across all platforms</li>
                                <li><strong>Active Queries:</strong> Up to 25 tracking queries</li>
                                <li><strong>Per-Check Cost:</strong> Each platform check counts as 1 check (running on 3 platforms = 3 checks)</li>
                            </ul>
                            <p class="text-sm text-gray-500 mt-3 italic">Not on the Agency plan? <a href="/billing" class="text-purple-600 hover:underline">Upgrade now</a> to access Citation Tracking.</p>
                        </div>

                        <!-- Billing -->
                        <h2 id="billing" class="text-2xl font-bold text-gray-900 border-b pb-4 mt-12">Billing & Plans</h2>

                        <h3 class="text-lg font-semibold mt-6">Available Plans</h3>
                        <div class="grid md:grid-cols-3 gap-4 my-4">
                            <div class="border rounded-lg p-4">
                                <h4 class="font-bold text-lg">Free</h4>
                                <p class="text-2xl font-bold text-gray-900 mt-2">$0<span class="text-sm font-normal text-gray-500">/month</span></p>
                                <ul class="mt-4 space-y-2 text-sm text-gray-600">
                                    <li>3 scans per month</li>
                                    <li>Basic GEO score</li>
                                    <li>Top 3 recommendations</li>
                                    <li>7-day scan history</li>
                                </ul>
                            </div>
                            <div class="border border-blue-300 rounded-lg p-4 bg-blue-50/30">
                                <h4 class="font-bold text-lg text-blue-700">Pro</h4>
                                <p class="text-2xl font-bold text-gray-900 mt-2">$39<span class="text-sm font-normal text-gray-500">/month</span></p>
                                <ul class="mt-4 space-y-2 text-sm text-gray-600">
                                    <li>50 scans per month</li>
                                    <li>Full GEO score breakdown</li>
                                    <li>All recommendations</li>
                                    <li>90-day scan history</li>
                                    <li>1 team with 5 members</li>
                                    <li>PDF export</li>
                                    <li>Priority support</li>
                                </ul>
                            </div>
                            <div class="border border-purple-300 rounded-lg p-4 bg-purple-50/30 relative">
                                <span class="absolute -top-2 right-2 bg-purple-500 text-white text-xs px-2 py-0.5 rounded">Best Value</span>
                                <h4 class="font-bold text-lg text-purple-700">Agency</h4>
                                <p class="text-2xl font-bold text-gray-900 mt-2">$99<span class="text-sm font-normal text-gray-500">/month</span></p>
                                <ul class="mt-4 space-y-2 text-sm text-gray-600">
                                    <li>Unlimited scans</li>
                                    <li>Unlimited scan history</li>
                                    <li>3 teams (5 members each)</li>
                                    <li>White-label reports</li>
                                    <li>Scheduled & bulk scans</li>
                                    <li>Citation Tracking</li>
                                </ul>
                            </div>
                        </div>

                        <h3 class="text-lg font-semibold mt-6">Managing Your Subscription</h3>
                        <p>Go to <strong>Billing</strong> in the sidebar to:</p>
                        <ul class="list-disc list-inside space-y-1 mt-2">
                            <li>View your current plan and usage</li>
                            <li>Upgrade or downgrade your plan</li>
                            <li>Update payment methods</li>
                            <li>View billing history</li>
                            <li>Cancel your subscription</li>
                        </ul>

                        <!-- Exports -->
                        <h2 id="exports" class="text-2xl font-bold text-gray-900 border-b pb-4 mt-12">Exporting Reports</h2>

                        <p>Export your scan results to share with clients or team members.</p>

                        <h3 class="text-lg font-semibold mt-6">Export Formats</h3>
                        <div class="space-y-3 mt-4">
                            <div class="flex items-start gap-3">
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-semibold">PDF</span>
                                <div>
                                    <p class="text-sm text-gray-600">Professional report format perfect for sharing with clients or stakeholders. Available on Pro and Agency plans.</p>
                                </div>
                            </div>
                        </div>

                        <h3 class="text-lg font-semibold mt-6">How to Export</h3>
                        <ol class="list-decimal list-inside space-y-2 mt-2">
                            <li>Open a completed scan from the Reports page</li>
                            <li>Click the <strong>Export PDF</strong> button</li>
                            <li>The file will download to your device</li>
                        </ol>

                        <!-- FAQ -->
                        <h2 id="faq" class="text-2xl font-bold text-gray-900 border-b pb-4 mt-12">Frequently Asked Questions</h2>

                        <div class="space-y-6 mt-6">
                            <div>
                                <h4 class="font-semibold">How often should I scan my pages?</h4>
                                <p class="text-sm text-gray-600 mt-1">Scan after making significant content changes. For actively maintained content, monthly scans are recommended to track progress.</p>
                            </div>

                            <div>
                                <h4 class="font-semibold">Why is my score different from before?</h4>
                                <p class="text-sm text-gray-600 mt-1">Scores can change if your content changed, if you upgraded your plan (unlocking more pillars), or if the page's technical setup changed (e.g., robots.txt updates).</p>
                            </div>

                            <div>
                                <h4 class="font-semibold">Can I scan competitor pages?</h4>
                                <p class="text-sm text-gray-600 mt-1">Yes! You can scan any publicly accessible webpage. This is great for benchmarking your content against competitors.</p>
                            </div>

                            <div>
                                <h4 class="font-semibold">What is llms.txt?</h4>
                                <p class="text-sm text-gray-600 mt-1">llms.txt is a proposed standard (similar to robots.txt) that provides AI systems with guidance on how to interact with your site. Our scanner checks for its presence and quality.</p>
                            </div>

                            <div>
                                <h4 class="font-semibold">Do re-scans count against my quota?</h4>
                                <p class="text-sm text-gray-600 mt-1">Yes, each scan (including re-scans) counts against your monthly quota. Plan your scans strategically.</p>
                            </div>

                            <div>
                                <h4 class="font-semibold">How do team quotas work?</h4>
                                <p class="text-sm text-gray-600 mt-1">Team scans use the team owner's subscription quota. All team members share this pool. Personal scans use your own quota.</p>
                            </div>

                            <div>
                                <h4 class="font-semibold">Can I get a refund?</h4>
                                <p class="text-sm text-gray-600 mt-1">Please contact support for refund requests. We handle these on a case-by-case basis.</p>
                            </div>

                            <div>
                                <h4 class="font-semibold">How do I contact support?</h4>
                                <p class="text-sm text-gray-600 mt-1">Email us at <a href="mailto:support@geosource.ai" class="text-purple-600 hover:underline">support@geosource.ai</a> for any questions or issues.</p>
                            </div>

                            <div>
                                <h4 class="font-semibold">What's the difference between GEO scanning and Citation Tracking?</h4>
                                <p class="text-sm text-gray-600 mt-1">GEO scanning analyzes your content to see how well it's optimized for AI systems. Citation tracking monitors whether AI platforms are actually citing your content when users ask questions. Think of GEO scanning as "input" (how good is my content?) and citation tracking as "output" (is it working?).</p>
                            </div>

                            <div>
                                <h4 class="font-semibold">Why wasn't I cited even though my GEO score is high?</h4>
                                <p class="text-sm text-gray-600 mt-1">A high GEO score means your content is well-structured for AI systems, but citation depends on many factors: competition, query specificity, recency of content, and whether your content directly answers the question being asked. Keep optimizing and try different query variations.</p>
                            </div>

                            <div>
                                <h4 class="font-semibold">How accurate is citation tracking?</h4>
                                <p class="text-sm text-gray-600 mt-1">Citation tracking queries real AI platforms in real-time, so results reflect actual AI behavior at that moment. However, AI responses can vary based on phrasing, timing, and other factors. Track consistently over time for the most reliable insights.</p>
                            </div>

                            <div>
                                <h4 class="font-semibold">Can I track my competitors' citations?</h4>
                                <p class="text-sm text-gray-600 mt-1">Yes! When viewing citation check results, you can see all the sources the AI cited, including competitors. This helps you understand who you're competing against for AI visibility.</p>
                            </div>
                        </div>

                        <div class="mt-12 pt-8 border-t text-center">
                            <p class="text-gray-500 text-sm">Need more help? Contact us at <a href="mailto:support@geosource.ai" class="text-purple-600 hover:underline">support@geosource.ai</a></p>
                            <a href="/dashboard" class="inline-block mt-4 px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                                Return to Dashboard
                            </a>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
</body>
</html>

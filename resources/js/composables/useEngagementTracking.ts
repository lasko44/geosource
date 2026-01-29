/**
 * Track page engagement - mark page view as engaged after user stays on page for a few seconds.
 * This filters out bots, accidental visits, and bounce traffic from analytics.
 */

const ENGAGEMENT_DELAY_MS = 3000; // 3 seconds

let engagementTimer: ReturnType<typeof setTimeout> | null = null;
let currentPath: string | null = null;

export function trackEngagement(): void {
    // Clear any existing timer
    if (engagementTimer) {
        clearTimeout(engagementTimer);
        engagementTimer = null;
    }

    // Get current path
    currentPath = window.location.pathname;

    // Set timer to mark as engaged after delay
    engagementTimer = setTimeout(async () => {
        if (!currentPath) return;

        try {
            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (!csrfToken) {
                console.warn('CSRF token not found for engagement tracking');
                return;
            }

            // Send engagement beacon
            await fetch('/analytics/engaged', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ path: currentPath }),
                keepalive: true, // Allow request to complete even if page is closed
            });
        } catch {
            // Silently fail - engagement tracking is not critical
        }
    }, ENGAGEMENT_DELAY_MS);
}

export function initEngagementTracking(): void {
    // Track on initial page load
    if (document.readyState === 'complete') {
        trackEngagement();
    } else {
        window.addEventListener('load', trackEngagement);
    }

    // Track on Inertia navigation
    document.addEventListener('inertia:navigate', () => {
        trackEngagement();
    });

    // Clear timer if user leaves page
    window.addEventListener('beforeunload', () => {
        if (engagementTimer) {
            clearTimeout(engagementTimer);
            engagementTimer = null;
        }
    });

    // Handle visibility changes (tab switching)
    document.addEventListener('visibilitychange', () => {
        if (document.hidden && engagementTimer) {
            clearTimeout(engagementTimer);
            engagementTimer = null;
        } else if (!document.hidden && currentPath === window.location.pathname) {
            // Resume tracking if same page
            trackEngagement();
        }
    });
}

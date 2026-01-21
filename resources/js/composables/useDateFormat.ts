import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useDateFormat() {
    const page = usePage();

    const userTimezone = computed(() => {
        return page.props.auth?.user?.timezone ?? 'UTC';
    });

    const formatDate = (dateString: string, options?: Intl.DateTimeFormatOptions) => {
        if (!dateString) return '';

        const defaultOptions: Intl.DateTimeFormatOptions = {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            timeZone: userTimezone.value,
        };

        return new Date(dateString).toLocaleString('en-US', {
            ...defaultOptions,
            ...options,
        });
    };

    const formatDateShort = (dateString: string) => {
        if (!dateString) return '';

        return new Date(dateString).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            timeZone: userTimezone.value,
        });
    };

    const formatTime = (dateString: string) => {
        if (!dateString) return '';

        return new Date(dateString).toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            timeZone: userTimezone.value,
        });
    };

    const formatRelative = (dateString: string) => {
        if (!dateString) return '';

        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now.getTime() - date.getTime();
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;

        return formatDateShort(dateString);
    };

    return {
        userTimezone,
        formatDate,
        formatDateShort,
        formatTime,
        formatRelative,
    };
}

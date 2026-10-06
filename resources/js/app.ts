import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'WCCIK Membership Portal';

createInertiaApp({
    title: (title) => {
        if (!title) {
            return appName;
        }

        if (title === appName) {
            return appName;
        }

        return `${title} · ${appName}`;
    },
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
            case name === 'Apply':
            case name === 'ApplyConfirmation':
            case name === 'PortalSignIn':
            case name === 'PortalVerify':
            case name === 'PortalDashboard':
            case name === 'PortalApply':
            case name === 'PortalRenew':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();

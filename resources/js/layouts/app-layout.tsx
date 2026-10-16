import { usePage } from '@inertiajs/react';
import { ErrorBoundary } from '@/components/error-boundary';
import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';

export default function AppLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    const url = usePage().url;

    return (
        <AppLayoutTemplate breadcrumbs={breadcrumbs}>
            {/* este key = {url}  hace que el cartel de error se limpie solo al navegar a otra página*/}
            <ErrorBoundary key={url}>{children}</ErrorBoundary>
        </AppLayoutTemplate>
    );
}

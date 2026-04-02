import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Bookmark, Building2, Check, ExternalLink, Eye, EyeOff, MessageSquare, X } from 'lucide-react';

interface JobPostingItem {
    id: string;
    title: string;
    location: string | null;
    department: string | null;
    url: string;
    first_seen_at: string;
    status: string;
    company: { id: string; name: string };
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface DashboardProps {
    jobPostings: {
        data: JobPostingItem[];
        links: PaginationLink[];
    };
    companies: { id: string; name: string }[];
    filters: { status: string; company: string | null };
}

const statusColors: Record<string, string> = {
    new: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
    bookmarked: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300',
    submitted: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    interview: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
    dismissed: 'bg-stone-100 text-stone-500 dark:bg-stone-800 dark:text-stone-400',
};

export default function Dashboard({ jobPostings, companies, filters }: DashboardProps) {
    const [animating, setAnimating] = useState<Record<string, string>>({});
    const { t } = useTranslation();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('dashboard.title'), href: '/dashboard' },
    ];

    const statusTabs = [
        { value: 'all', label: t('dashboard.all') },
        { value: 'new', label: t('dashboard.new') },
        { value: 'bookmarked', label: t('dashboard.bookmarked') },
        { value: 'submitted', label: t('dashboard.submitted') },
        { value: 'interview', label: t('dashboard.interview') },
        { value: 'dismissed', label: t('dashboard.dismissed') },
    ];

    const navigate = (params: Record<string, string | null>) => {
        const query: Record<string, string> = {};
        const merged = { ...filters, ...params };
        if (merged.status && merged.status !== 'all') query.status = merged.status;
        if (merged.company) query.company = merged.company;
        router.get('/dashboard', query, { preserveState: true, preserveScroll: true });
    };

    const changeStatus = (jobPostingId: string, status: string) => {
        setAnimating((prev) => ({ ...prev, [jobPostingId]: status }));
        setTimeout(() => setAnimating((prev) => { const next = { ...prev }; delete next[jobPostingId]; return next; }), 300);
        router.patch(`/job-postings/${jobPostingId}/status`, { status }, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('dashboard.title')} />
            <div className="mx-auto w-full max-w-5xl p-6">
                <h1 className="text-2xl font-semibold">{t('dashboard.title')}</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    {t('dashboard.description')}
                </p>

                {/* Filters */}
                <div className="mt-6 flex flex-wrap items-center gap-3">
                    {/* Status tabs */}
                    <div className="flex flex-wrap gap-1 rounded-lg bg-muted p-1">
                        {statusTabs.map((tab) => (
                            <button
                                key={tab.value}
                                onClick={() => navigate({ status: tab.value })}
                                className={`rounded-md px-3 py-1.5 text-sm font-medium transition-colors ${
                                    filters.status === tab.value
                                        ? 'bg-background text-foreground shadow-sm'
                                        : 'text-muted-foreground hover:text-foreground'
                                }`}
                            >
                                {tab.label}
                            </button>
                        ))}
                    </div>

                    {/* Company filter */}
                    {companies.length > 0 && (
                        <select
                            value={filters.company ?? ''}
                            onChange={(e) => navigate({ company: e.target.value || null })}
                            className="rounded-md border border-input bg-background px-3 py-1.5 text-sm"
                        >
                            <option value="">{t('dashboard.all_companies')}</option>
                            {companies.map((c) => (
                                <option key={c.id} value={c.id}>{c.name}</option>
                            ))}
                        </select>
                    )}
                </div>

                {/* Job postings list */}
                {jobPostings.data.length === 0 ? (
                    <div className="mt-16 text-center text-muted-foreground">
                        <Building2 className="mx-auto size-12 opacity-30" />
                        <p className="mt-4">
                            {companies.length === 0
                                ? t('dashboard.empty_no_subscriptions')
                                : t('dashboard.empty_no_results')}
                        </p>
                        {companies.length === 0 && (
                            <Link href="/companies" className="mt-2 inline-block text-sm text-orange-600 hover:underline dark:text-orange-400">
                                {t('dashboard.go_to_companies')}
                            </Link>
                        )}
                    </div>
                ) : (
                    <div className="mt-6 space-y-3">
                        {jobPostings.data.map((jp, index) => (
                            <div
                                key={jp.id}
                                className="flex items-start justify-between gap-4 rounded-lg border border-border bg-card p-4 animate-fade-slide-up"
                                style={{ animationDelay: `${Math.min(index * 50, 500)}ms` }}
                            >
                                <div className="min-w-0 flex-1">
                                    <div className="flex items-center gap-2">
                                        <a
                                            href={jp.url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="font-medium hover:underline"
                                        >
                                            {jp.title}
                                            <ExternalLink className="ml-1 inline size-3 opacity-50" />
                                        </a>
                                        <span className={`inline-block rounded-full px-2 py-0.5 text-xs font-medium ${statusColors[jp.status] ?? ''}`}>
                                            {jp.status}
                                        </span>
                                    </div>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {jp.company.name}
                                        {jp.location && ` · ${jp.location}`}
                                        {jp.department && ` · ${jp.department}`}
                                        {` · ${jp.first_seen_at}`}
                                    </p>
                                </div>
                                <div className="flex shrink-0 items-center gap-1">
                                    {jp.status !== 'bookmarked' && (
                                        <Button variant="ghost" size="icon" onClick={() => changeStatus(jp.id, 'bookmarked')} title={t('dashboard.bookmark')}>
                                            <Bookmark className={`size-4 text-amber-500 transition-transform duration-200 ${animating[jp.id] === 'bookmarked' ? 'scale-125' : ''}`} />
                                        </Button>
                                    )}
                                    {jp.status !== 'submitted' && (
                                        <Button variant="ghost" size="icon" onClick={() => changeStatus(jp.id, 'submitted')} title={t('dashboard.mark_submitted')}>
                                            <Check className={`size-4 text-green-600 transition-transform duration-300 ${animating[jp.id] === 'submitted' ? '-translate-y-1' : ''}`} />
                                        </Button>
                                    )}
                                    {jp.status !== 'interview' && (
                                        <Button variant="ghost" size="icon" onClick={() => changeStatus(jp.id, 'interview')} title={t('dashboard.mark_interview')}>
                                            <MessageSquare className={`size-4 text-purple-600 transition-transform duration-300 ${animating[jp.id] === 'interview' ? '-translate-y-1' : ''}`} />
                                        </Button>
                                    )}
                                    {jp.status !== 'dismissed' ? (
                                        <Button variant="ghost" size="icon" onClick={() => changeStatus(jp.id, 'dismissed')} title={t('dashboard.dismiss')}>
                                            <EyeOff className={`size-4 text-stone-400 transition-opacity duration-200 ${animating[jp.id] === 'dismissed' ? 'opacity-30' : ''}`} />
                                        </Button>
                                    ) : (
                                        <Button variant="ghost" size="icon" onClick={() => changeStatus(jp.id, 'new')} title={t('dashboard.restore')}>
                                            <Eye className="size-4 text-orange-500" />
                                        </Button>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {/* Pagination */}
                {jobPostings.links.length > 3 && (
                    <div className="mt-6 flex justify-center gap-1">
                        {jobPostings.links.map((link, i) => (
                            <button
                                key={i}
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true })}
                                className={`rounded-md px-3 py-1.5 text-sm ${
                                    link.active
                                        ? 'bg-foreground text-background'
                                        : 'text-muted-foreground hover:bg-muted'
                                } ${!link.url ? 'opacity-30' : ''}`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

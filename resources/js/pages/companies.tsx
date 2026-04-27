import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/hooks/use-translation';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, router } from '@inertiajs/react';
import { Bell, BellOff, Building2, Download, Filter, Plus, RefreshCw, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { type ContinentGroup, type JobFilter, FilterForm, emptyFilter } from '@/components/filters/filter-components';

interface CompanySubscription {
    id: string;
    name: string;
    provider: string;
    provider_slug: string;
    is_active: boolean;
    job_postings_count: number;
    email_notifications: boolean;
    last_synced_at: string | null;
    can_sync: boolean;
}

interface CompanyFilter extends JobFilter {
    company: {
        id: string;
        name: string;
    } | null;
}

interface CompaniesProps {
    companies: CompanySubscription[];
    companyFilters: CompanyFilter[];
    departments: string[];
    countries: ContinentGroup[];
    demoCompaniesTotal: number;
    demoCompaniesFollowed: number;
}

export default function Companies({ companies, companyFilters, departments, countries, demoCompaniesTotal, demoCompaniesFollowed }: CompaniesProps) {
    const { t } = useTranslation();
    const [filterDialogCompany, setFilterDialogCompany] = useState<CompanySubscription | null>(null);
    const [syncingCompanyId, setSyncingCompanyId] = useState<string | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('companies.title'), href: '/companies' },
    ];

    const providers = [
        { value: '', label: t('companies.auto_detect') },
        { value: 'workable', label: 'Workable' },
        { value: 'lever', label: 'Lever' },
        { value: 'ashby', label: 'Ashby' },
        { value: 'greenhouse', label: 'Greenhouse' },
        { value: 'teamtailor', label: 'Teamtailor' },
        { value: 'factorial', label: 'Factorial' },
        { value: 'personio', label: 'Personio' },
    ];

    const form = useForm({ slug: '', provider: '' });

    const handleFollow = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/companies/follow', {
            preserveScroll: true,
            onSuccess: () => form.reset('slug', 'provider'),
        });
    };

    const handleUnfollow = (companyId: string) => {
        if (!confirm(t('companies.unfollow_confirm'))) return;
        router.delete(`/companies/${companyId}/unfollow`, { preserveScroll: true });
    };

    const handleSync = (companyId: string) => {
        setSyncingCompanyId(companyId);
        router.post(`/companies/${companyId}/sync`, {}, {
            preserveScroll: true,
            onFinish: () => setSyncingCompanyId(null),
        });
    };

    const handleToggleNotifications = (companyId: string) => {
        router.patch(`/companies/${companyId}/notifications`, {}, { preserveScroll: true });
    };

    const handleLoadDefaults = () => {
        router.post('/companies/load-defaults', {}, { preserveScroll: true });
    };

    const getCompanyFilter = (companyId: string): CompanyFilter | undefined => {
        return companyFilters.find((f) => f.company_id === companyId);
    };

    const handleFilterSubmit = (companyId: string, existingFilter: CompanyFilter | undefined, data: Omit<JobFilter, 'id' | 'company_id'>) => {
        if (existingFilter) {
            router.put(`/filters/${existingFilter.id}`, data, {
                preserveScroll: true,
                onSuccess: () => setFilterDialogCompany(null),
            });
        } else {
            router.post('/filters', { ...data, company_id: companyId }, {
                preserveScroll: true,
                onSuccess: () => setFilterDialogCompany(null),
            });
        }
    };

    const handleFilterDelete = (filterId: string) => {
        if (confirm(t('settings.filters.delete_override_confirm'))) {
            router.delete(`/filters/${filterId}`, {
                preserveScroll: true,
                onSuccess: () => setFilterDialogCompany(null),
            });
        }
    };

    const activeFilter = filterDialogCompany ? getCompanyFilter(filterDialogCompany.id) : undefined;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('companies.title')} />
            <div className="w-full p-6">
                <h1 className="text-2xl font-semibold">{t('companies.title')}</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    {t('companies.description')}
                </p>

                {/* Add company form */}
                <form onSubmit={handleFollow} className="mt-6 flex gap-3">
                    <Input
                        placeholder={t('companies.url_placeholder')}
                        value={form.data.slug}
                        onChange={(e) => form.setData('slug', e.target.value)}
                        className="flex-1"
                    />
                    <select
                        value={form.data.provider}
                        onChange={(e) => form.setData('provider', e.target.value)}
                        className="rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                    >
                        {providers.map((p) => (
                            <option key={p.value} value={p.value}>
                                {p.label}
                            </option>
                        ))}
                    </select>
                    <Button type="submit" disabled={form.processing || !form.data.slug.trim()}>
                        <Plus className="mr-1 size-4" />
                        {t('companies.follow')}
                    </Button>
                </form>
                {(form.errors.slug || form.errors.provider) && (
                    <p className="mt-2 text-sm text-red-600 dark:text-red-400">{form.errors.slug || form.errors.provider}</p>
                )}

                {/* Demo companies banner */}
                {demoCompaniesFollowed < demoCompaniesTotal && (
                    <div className="mt-4 flex items-center justify-between rounded-lg border border-dashed border-border bg-muted/30 px-4 py-3">
                        <p className="text-sm text-muted-foreground">
                            {t('companies.demo_progress')
                                .replace(':followed', String(demoCompaniesFollowed))
                                .replace(':total', String(demoCompaniesTotal))}
                        </p>
                        <Button variant="outline" size="sm" onClick={handleLoadDefaults}>
                            <Download className="mr-1 size-4" />
                            {t('companies.load_defaults')}
                        </Button>
                    </div>
                )}

                {/* Companies list */}
                {companies.length === 0 ? (
                    <div className="mt-12 text-center text-muted-foreground">
                        <Building2 className="mx-auto size-12 opacity-30" />
                        <p className="mt-4">{t('companies.empty')}</p>
                    </div>
                ) : (
                    <div className="mt-6 space-y-3">
                        {companies.map((company) => {
                            const hasOverride = companyFilters.some((f) => f.company_id === company.id);
                            return (
                                <div
                                    key={company.id}
                                    className="flex items-center justify-between rounded-lg border border-border bg-card p-4"
                                >
                                    <div className="min-w-0 flex-1">
                                        <h3 className="font-medium">{company.name}</h3>
                                        <p className="text-sm text-muted-foreground">
                                            {company.provider_slug} · {company.job_postings_count} jobs · Last sync: {company.last_synced_at ?? 'Never synced'}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => setFilterDialogCompany(company)}
                                            title={t('settings.filters.title')}
                                        >
                                            <Filter className={`size-4 ${hasOverride ? 'text-primary' : 'text-muted-foreground'}`} />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => handleSync(company.id)}
                                            disabled={!company.can_sync || syncingCompanyId !== null}
                                            title={company.can_sync ? 'Sync jobs' : 'Sync unavailable (max 2/day, 1h interval)'}
                                        >
                                            <RefreshCw className={`size-4 ${syncingCompanyId === company.id ? 'animate-spin' : ''} ${!company.can_sync ? 'opacity-30' : ''}`} />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => handleToggleNotifications(company.id)}
                                            title={company.email_notifications ? t('companies.disable_notifications') : t('companies.enable_notifications')}
                                        >
                                            {company.email_notifications ? (
                                                <Bell className="size-4 text-teal-600" />
                                            ) : (
                                                <BellOff className="size-4 text-muted-foreground" />
                                            )}
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => handleUnfollow(company.id)}
                                            title={t('companies.unfollow')}
                                        >
                                            <Trash2 className="size-4 text-red-500" />
                                        </Button>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>

            {/* Filter dialog */}
            <Dialog open={!!filterDialogCompany} onOpenChange={(open) => !open && setFilterDialogCompany(null)}>
                <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>{filterDialogCompany?.name}</DialogTitle>
                        <DialogDescription>
                            {activeFilter
                                ? t('settings.filters.company_overrides_description')
                                : t('settings.filters.global_description')}
                        </DialogDescription>
                    </DialogHeader>
                    {filterDialogCompany && (
                        <FilterForm
                            key={filterDialogCompany.id}
                            filter={activeFilter ?? emptyFilter}
                            departments={departments}
                            countries={countries}
                            onSubmit={(data) => handleFilterSubmit(filterDialogCompany.id, activeFilter, data)}
                            onDelete={activeFilter ? () => handleFilterDelete(activeFilter.id) : undefined}
                            submitLabel={activeFilter ? t('common.save') : t('settings.filters.create_override')}
                            t={t}
                        />
                    )}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

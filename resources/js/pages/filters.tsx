import { useTranslation } from '@/hooks/use-translation';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import {
    type ContinentGroup,
    type JobFilter,
    FilterForm,
    emptyFilter,
} from '@/components/filters/filter-components';

export type { JobFilter, ContinentGroup } from '@/components/filters/filter-components';
export { TagInput, ChipSelect, CountrySelector, FilterForm, emptyFilter } from '@/components/filters/filter-components';

interface FiltersPageProps {
    globalFilter: JobFilter | null;
    departments: string[];
    countries: ContinentGroup[];
}

export default function Filters({ globalFilter, departments, countries }: FiltersPageProps) {
    const { t } = useTranslation();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('settings.filters.title'),
            href: '/filters',
        },
    ];

    const handleGlobalSubmit = (data: Omit<JobFilter, 'id' | 'company_id'>) => {
        if (globalFilter) {
            router.put(`/filters/${globalFilter.id}`, data, { preserveScroll: true });
        } else {
            router.post('/filters', { ...data, company_id: null }, { preserveScroll: true });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('settings.filters.title')} />

            <div className="w-full p-6">
                <h1 className="text-2xl font-semibold">{t('settings.filters.global_heading')}</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    {t('settings.filters.global_description')}
                </p>

                <div className="mt-6 max-w-2xl">
                    <FilterForm
                        filter={globalFilter ?? emptyFilter}
                        departments={departments}
                        countries={countries}
                        onSubmit={handleGlobalSubmit}
                        t={t}
                    />
                </div>
            </div>
        </AppLayout>
    );
}

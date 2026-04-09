import { WizardConfirm } from '@/components/explore/wizard-confirm';
import { emptyExploreFilters, type ExploreFilters, WizardFilters } from '@/components/explore/wizard-filters';
import { type ExploreCompany, WizardResults } from '@/components/explore/wizard-results';
import { useTranslation } from '@/hooks/use-translation';
import AppLayout from '@/layouts/app-layout';
import { type ContinentGroup } from '@/components/filters/filter-components';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

type Step = 'filters' | 'results' | 'confirm';

interface ExplorePageProps {
    departments: string[];
    countries: ContinentGroup[];
}

async function getCsrfToken(): Promise<string> {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

export default function Explore({ departments, countries }: ExplorePageProps) {
    const { t } = useTranslation();
    const [step, setStep] = useState<Step>('filters');
    const [filters, setFilters] = useState<ExploreFilters>(emptyExploreFilters);
    const [selectedIds, setSelectedIds] = useState<string[]>([]);
    const [selectedCompanies, setSelectedCompanies] = useState<ExploreCompany[]>([]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('explore.title'), href: '/explore' },
    ];

    const handleFiltersNext = (f: ExploreFilters) => {
        setFilters(f);
        setStep('results');
    };

    const handleResultsNext = (ids: string[], companies: ExploreCompany[]) => {
        setSelectedIds(ids);
        setSelectedCompanies(companies);
        setStep('confirm');
    };

    const handleConfirm = async () => {
        const token = await getCsrfToken();
        await fetch('/explore/follow-many', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': token,
            },
            body: JSON.stringify({ company_ids: selectedIds, filters }),
        });
        router.visit('/dashboard');
    };

    const stepTitles: Record<Step, string> = {
        filters: t('explore.step1_title'),
        results: t('explore.step2_title'),
        confirm: t('explore.step3_title'),
    };

    const stepDescriptions: Record<Step, string> = {
        filters: t('explore.step1_description'),
        results: t('explore.step2_title'),
        confirm: t('explore.step3_description'),
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('explore.title')} />
            <div className="w-full p-6">
                <h1 className="text-2xl font-semibold">{stepTitles[step]}</h1>
                <p className="mt-1 text-sm text-muted-foreground">{stepDescriptions[step]}</p>

                <div className="mt-8 max-w-2xl">
                    {step === 'filters' && (
                        <WizardFilters
                            departments={departments}
                            countries={countries}
                            initialFilters={filters}
                            onNext={handleFiltersNext}
                        />
                    )}

                    {step === 'results' && (
                        <WizardResults
                            filters={filters}
                            onNext={handleResultsNext}
                            onBack={() => setStep('filters')}
                        />
                    )}

                    {step === 'confirm' && (
                        <WizardConfirm
                            filters={filters}
                            selectedCompanies={selectedCompanies}
                            countries={countries}
                            onBack={() => setStep('results')}
                            onConfirm={() => void handleConfirm()}
                        />
                    )}
                </div>
            </div>
        </AppLayout>
    );
}

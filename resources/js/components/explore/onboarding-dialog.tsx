import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTranslation } from '@/hooks/use-translation';
import { type ContinentGroup } from '@/components/filters/filter-components';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import { emptyExploreFilters, type ExploreFilters, WizardFilters } from './wizard-filters';
import { type ExploreCompany, WizardResults } from './wizard-results';
import { WizardConfirm } from './wizard-confirm';

type Step = 'filters' | 'results' | 'confirm';

interface OnboardingDialogProps {
    departments: string[];
    countries: ContinentGroup[];
}

async function getCsrfToken(): Promise<string> {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

async function skipOnboarding(): Promise<void> {
    const token = await getCsrfToken();
    await fetch('/explore/skip-onboarding', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': token,
        },
        body: JSON.stringify({}),
    });
}

export function OnboardingDialog({ departments, countries }: OnboardingDialogProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(true);
    const [step, setStep] = useState<Step>('filters');
    const [filters, setFilters] = useState<ExploreFilters>(emptyExploreFilters);
    const [selectedIds, setSelectedIds] = useState<string[]>([]);
    const [selectedCompanies, setSelectedCompanies] = useState<ExploreCompany[]>([]);

    const handleSkip = async () => {
        setOpen(false);
        await skipOnboarding();
        router.reload();
    };

    const handleOpenChange = async (isOpen: boolean) => {
        if (!isOpen) {
            setOpen(false);
            await skipOnboarding();
            router.reload();
        }
    };

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
        setOpen(false);
        router.reload();
    };

    const stepTitles: Record<Step, string> = {
        filters: t('explore.step1_title'),
        results: t('explore.step2_title'),
        confirm: t('explore.step3_title'),
    };

    const stepDescriptions: Record<Step, string> = {
        filters: t('explore.step1_description'),
        results: t('explore.step2_description_found').replace(':count', ''),
        confirm: t('explore.step3_description'),
    };

    return (
        <Dialog open={open} onOpenChange={(isOpen) => void handleOpenChange(isOpen)}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>{stepTitles[step]}</DialogTitle>
                    <DialogDescription>
                        {step !== 'results' ? stepDescriptions[step] : t('explore.step2_title')}
                    </DialogDescription>
                </DialogHeader>

                {step === 'filters' && (
                    <WizardFilters
                        departments={departments}
                        countries={countries}
                        initialFilters={filters}
                        onNext={handleFiltersNext}
                        onSkip={() => void handleSkip()}
                    />
                )}

                {step === 'results' && (
                    <WizardResults
                        filters={filters}
                        onNext={handleResultsNext}
                        onBack={() => setStep('filters')}
                        onSkip={() => void handleSkip()}
                    />
                )}

                {step === 'confirm' && (
                    <WizardConfirm
                        filters={filters}
                        selectedCompanies={selectedCompanies}
                        countries={countries}
                        onBack={() => setStep('results')}
                        onConfirm={() => void handleConfirm()}
                        onSkip={() => void handleSkip()}
                    />
                )}
            </DialogContent>
        </Dialog>
    );
}

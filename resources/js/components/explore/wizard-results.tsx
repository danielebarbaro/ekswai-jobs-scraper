import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { useTranslation } from '@/hooks/use-translation';
import { Building2, Loader2 } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { type ExploreFilters } from './wizard-filters';

export interface ExploreCompany {
    id: string;
    name: string;
    provider: string;
    provider_slug: string;
    description: string | null;
    matched_jobs_count: number;
    is_followed: boolean;
}

interface WizardResultsProps {
    filters: ExploreFilters;
    onNext: (selectedIds: string[], companies: ExploreCompany[]) => void;
    onBack: () => void;
    onSkip?: () => void;
}

function buildQueryString(filters: ExploreFilters): string {
    const params = new URLSearchParams();

    for (const kw of filters.title_include) {
        params.append('title_include[]', kw);
    }
    for (const kw of filters.title_exclude) {
        params.append('title_exclude[]', kw);
    }
    for (const c of filters.countries) {
        params.append('countries[]', c);
    }
    if (filters.remote_only) {
        params.set('remote_only', '1');
    }
    for (const d of filters.departments) {
        params.append('departments[]', d);
    }

    return params.toString();
}

export function WizardResults({ filters, onNext, onBack, onSkip }: WizardResultsProps) {
    const { t } = useTranslation();
    const [companies, setCompanies] = useState<ExploreCompany[]>([]);
    const [loading, setLoading] = useState(true);
    const [selected, setSelected] = useState<Set<string>>(new Set());

    const fetchCompanies = useCallback(async () => {
        setLoading(true);
        try {
            const qs = buildQueryString(filters);
            const response = await fetch(`/explore/companies${qs ? `?${qs}` : ''}`, {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) throw new Error('Failed to fetch');
            const json = (await response.json()) as { data: ExploreCompany[] };
            setCompanies(json.data);
            // Pre-select already-followed companies but keep them disabled
            const initialSelected = new Set(
                json.data.filter((c) => !c.is_followed).map((c) => c.id),
            );
            setSelected(initialSelected);
        } catch {
            setCompanies([]);
        } finally {
            setLoading(false);
        }
    }, [filters]);

    useEffect(() => {
        void fetchCompanies();
    }, [fetchCompanies]);

    const unfollowedCompanies = companies.filter((c) => !c.is_followed);
    const allSelected = unfollowedCompanies.length > 0 && unfollowedCompanies.every((c) => selected.has(c.id));

    const toggleAll = () => {
        if (allSelected) {
            setSelected(new Set());
        } else {
            setSelected(new Set(unfollowedCompanies.map((c) => c.id)));
        }
    };

    const toggleCompany = (id: string) => {
        setSelected((prev) => {
            const next = new Set(prev);
            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }
            return next;
        });
    };

    const selectedCount = selected.size;
    const selectedCompanies = companies.filter((c) => selected.has(c.id));

    const handleNext = () => {
        onNext(Array.from(selected), selectedCompanies);
    };

    return (
        <div className="space-y-4">
            {loading ? (
                <div className="flex items-center justify-center py-12">
                    <Loader2 className="size-6 animate-spin text-muted-foreground" />
                </div>
            ) : companies.length === 0 ? (
                <div className="py-12 text-center text-muted-foreground">
                    <Building2 className="mx-auto size-10 opacity-30" />
                    <p className="mt-3 text-sm">{t('explore.no_matching_companies')}</p>
                </div>
            ) : (
                <>
                    <div className="flex items-center justify-between border-b pb-2">
                        <p className="text-sm text-muted-foreground">
                            {t('explore.step2_description_found').replace(':count', String(companies.length))}
                        </p>
                        {unfollowedCompanies.length > 0 && (
                            <button
                                type="button"
                                onClick={toggleAll}
                                className="text-xs font-medium text-primary hover:underline"
                            >
                                {allSelected ? t('explore.selected') : t('explore.select_all')}
                            </button>
                        )}
                    </div>

                    <div className="max-h-80 space-y-2 overflow-y-auto pr-1">
                        {companies.map((company) => {
                            const isFollowed = company.is_followed;
                            const isChecked = isFollowed || selected.has(company.id);

                            return (
                                <label
                                    key={company.id}
                                    className={`flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors ${
                                        isFollowed
                                            ? 'border-border bg-muted/40 opacity-60'
                                            : selected.has(company.id)
                                              ? 'border-primary/40 bg-primary/5'
                                              : 'border-border bg-card hover:bg-muted/30'
                                    }`}
                                >
                                    <Checkbox
                                        checked={isChecked}
                                        disabled={isFollowed}
                                        onCheckedChange={() => !isFollowed && toggleCompany(company.id)}
                                        className="mt-0.5 shrink-0"
                                    />
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center gap-2">
                                            <span className="font-medium">{company.name}</span>
                                            <span className="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                                                {company.provider}
                                            </span>
                                            {isFollowed && (
                                                <span className="text-xs text-muted-foreground">
                                                    {t('explore.already_followed')}
                                                </span>
                                            )}
                                        </div>
                                        {company.description && (
                                            <p className="mt-1 line-clamp-2 text-xs text-muted-foreground">
                                                {company.description}
                                            </p>
                                        )}
                                        {company.matched_jobs_count > 0 && (
                                            <p className="mt-1 text-xs text-primary">
                                                {t('companies.jobs_count').replace(':count', String(company.matched_jobs_count))}
                                            </p>
                                        )}
                                    </div>
                                </label>
                            );
                        })}
                    </div>
                </>
            )}

            <div className="flex items-center justify-between border-t pt-4">
                <div className="flex items-center gap-2">
                    <Button type="button" variant="outline" onClick={onBack}>
                        {t('common.back')}
                    </Button>
                    {onSkip && (
                        <Button type="button" variant="ghost" size="sm" onClick={onSkip}>
                            {t('common.skip')}
                        </Button>
                    )}
                </div>
                <Button
                    type="button"
                    onClick={handleNext}
                    disabled={loading || selectedCount === 0}
                >
                    {selectedCount > 0
                        ? t('explore.follow_selected').replace(':count', String(selectedCount))
                        : t('common.continue')}
                </Button>
            </div>
        </div>
    );
}

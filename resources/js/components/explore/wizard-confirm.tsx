import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { type ContinentGroup } from '@/components/filters/filter-components';
import { Building2 } from 'lucide-react';
import { useMemo } from 'react';
import { type ExploreCompany } from './wizard-results';
import { type ExploreFilters } from './wizard-filters';

interface WizardConfirmProps {
    filters: ExploreFilters;
    selectedCompanies: ExploreCompany[];
    countries: ContinentGroup[];
    onBack: () => void;
    onConfirm: () => void;
    onSkip?: () => void;
}

export function WizardConfirm({ filters, selectedCompanies, countries, onBack, onConfirm, onSkip }: WizardConfirmProps) {
    const { t } = useTranslation();

    const countryNameById = useMemo(() => {
        const map = new Map<string, string>();
        for (const continent of countries) {
            for (const country of continent.countries) {
                map.set(country.id, country.name);
            }
        }
        return map;
    }, [countries]);

    const hasFilters =
        filters.title_include.length > 0 ||
        filters.title_exclude.length > 0 ||
        filters.countries.length > 0 ||
        filters.remote_only ||
        filters.departments.length > 0;

    return (
        <div className="space-y-6">
            <div>
                <h3 className="text-sm font-semibold">{t('explore.your_filters')}</h3>
                {hasFilters ? (
                    <div className="mt-2 flex flex-wrap gap-1.5">
                        {filters.title_include.map((kw) => (
                            <Badge key={`inc-${kw}`} variant="secondary">
                                +{kw}
                            </Badge>
                        ))}
                        {filters.title_exclude.map((kw) => (
                            <Badge key={`exc-${kw}`} variant="outline" className="text-muted-foreground line-through">
                                {kw}
                            </Badge>
                        ))}
                        {filters.countries.map((c) => (
                            <Badge key={`c-${c}`} variant="secondary">
                                {countryNameById.get(c) ?? c}
                            </Badge>
                        ))}
                        {filters.departments.map((d) => (
                            <Badge key={`d-${d}`} variant="secondary">
                                {d}
                            </Badge>
                        ))}
                        {filters.remote_only && (
                            <Badge variant="secondary">{t('settings.filters.remote_only_label')}</Badge>
                        )}
                    </div>
                ) : (
                    <p className="mt-1 text-xs text-muted-foreground">{t('settings.filters.global_description')}</p>
                )}
            </div>

            <div>
                <h3 className="text-sm font-semibold">
                    {t('explore.companies_to_follow').replace(':count', String(selectedCompanies.length))}
                </h3>
                <div className="mt-2 max-h-60 space-y-2 overflow-y-auto">
                    {selectedCompanies.map((company) => (
                        <div key={company.id} className="flex items-center gap-3 rounded-lg border border-border bg-card p-3">
                            <Building2 className="size-4 shrink-0 text-muted-foreground" />
                            <div className="min-w-0 flex-1">
                                <span className="font-medium">{company.name}</span>
                                <span className="ml-2 rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                                    {company.provider}
                                </span>
                            </div>
                        </div>
                    ))}
                </div>
            </div>

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
                <Button type="button" onClick={onConfirm}>
                    {t('explore.confirm_follow').replace(':count', String(selectedCompanies.length))}
                </Button>
            </div>
        </div>
    );
}

import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { useTranslation } from '@/hooks/use-translation';
import { ChipSelect, CountrySelector, TagInput, type ContinentGroup } from '@/pages/filters';
import { type FormEvent, useState } from 'react';

export interface ExploreFilters {
    title_include: string[];
    title_exclude: string[];
    countries: string[];
    remote_only: boolean;
    departments: string[];
}

export const emptyExploreFilters: ExploreFilters = {
    title_include: [],
    title_exclude: [],
    countries: [],
    remote_only: false,
    departments: [],
};

interface WizardFiltersProps {
    departments: string[];
    countries: ContinentGroup[];
    initialFilters?: ExploreFilters;
    onNext: (filters: ExploreFilters) => void;
    onSkip?: () => void;
}

export function WizardFilters({ departments, countries, initialFilters, onNext, onSkip }: WizardFiltersProps) {
    const { t } = useTranslation();
    const init = initialFilters ?? emptyExploreFilters;

    const [titleInclude, setTitleInclude] = useState<string[]>(init.title_include);
    const [titleExclude, setTitleExclude] = useState<string[]>(init.title_exclude);
    const [selectedCountries, setSelectedCountries] = useState<string[]>(init.countries);
    const [remoteOnly, setRemoteOnly] = useState(init.remote_only);
    const [selectedDepartments, setSelectedDepartments] = useState<string[]>(init.departments);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        onNext({
            title_include: titleInclude,
            title_exclude: titleExclude,
            countries: selectedCountries,
            remote_only: remoteOnly,
            departments: selectedDepartments,
        });
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <div className="grid gap-2">
                <Label>{t('settings.filters.title_include_label')}</Label>
                <p className="text-xs text-muted-foreground">{t('settings.filters.title_include_description')}</p>
                <TagInput value={titleInclude} onChange={setTitleInclude} placeholder={t('settings.filters.tag_placeholder')} />
            </div>

            <div className="grid gap-2">
                <Label>{t('settings.filters.title_exclude_label')}</Label>
                <p className="text-xs text-muted-foreground">{t('settings.filters.title_exclude_description')}</p>
                <TagInput value={titleExclude} onChange={setTitleExclude} placeholder={t('settings.filters.tag_placeholder')} />
            </div>

            {countries.length > 0 && (
                <div className="grid gap-2">
                    <Label>{t('settings.filters.countries_label')}</Label>
                    <p className="text-xs text-muted-foreground">{t('settings.filters.countries_description')}</p>
                    <CountrySelector continents={countries} value={selectedCountries} onChange={setSelectedCountries} />
                </div>
            )}

            <div className="flex items-center gap-3">
                <Switch id="explore-remote" checked={remoteOnly} onCheckedChange={setRemoteOnly} />
                <div>
                    <Label htmlFor="explore-remote">{t('settings.filters.remote_only_label')}</Label>
                    <p className="text-xs text-muted-foreground">{t('settings.filters.remote_only_description')}</p>
                </div>
            </div>

            {departments.length > 0 && (
                <div className="grid gap-2">
                    <Label>{t('settings.filters.departments_label')}</Label>
                    <p className="text-xs text-muted-foreground">{t('settings.filters.departments_description')}</p>
                    <ChipSelect options={departments} value={selectedDepartments} onChange={setSelectedDepartments} />
                </div>
            )}

            <div className="flex items-center justify-between pt-2">
                {onSkip ? (
                    <Button type="button" variant="ghost" size="sm" onClick={onSkip}>
                        {t('common.skip')}
                    </Button>
                ) : (
                    <span />
                )}
                <Button type="submit">{t('explore.find_companies')}</Button>
            </div>
        </form>
    );
}

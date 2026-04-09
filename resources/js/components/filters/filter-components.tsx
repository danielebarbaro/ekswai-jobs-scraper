import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { X } from 'lucide-react';
import { type FormEvent, type KeyboardEvent, useCallback, useState } from 'react';

export interface JobFilter {
    id: string;
    company_id: string | null;
    title_include: string[];
    title_exclude: string[];
    countries: string[];
    remote_only: boolean;
    departments: string[];
}

export interface Country {
    id: string;
    name: string;
}

export interface ContinentGroup {
    name: string;
    countries: Country[];
}

export const emptyFilter: JobFilter = {
    id: '',
    company_id: null,
    title_include: [],
    title_exclude: [],
    countries: [],
    remote_only: false,
    departments: [],
};

export function TagInput({
    value,
    onChange,
    placeholder,
}: {
    value: string[];
    onChange: (tags: string[]) => void;
    placeholder: string;
}) {
    const [input, setInput] = useState('');

    const addTag = useCallback(() => {
        const tag = input.trim().toLowerCase();
        if (tag && !value.includes(tag)) {
            onChange([...value, tag]);
        }
        setInput('');
    }, [input, value, onChange]);

    const removeTag = useCallback(
        (tag: string) => {
            onChange(value.filter((t) => t !== tag));
        },
        [value, onChange],
    );

    const handleKeyDown = (e: KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            addTag();
        }
        if (e.key === 'Backspace' && input === '' && value.length > 0) {
            removeTag(value[value.length - 1]);
        }
    };

    return (
        <div className="space-y-2">
            <div className="flex flex-wrap gap-1.5">
                {value.map((tag) => (
                    <Badge key={tag} variant="secondary" className="gap-1 pr-1">
                        {tag}
                        <button
                            type="button"
                            onClick={() => removeTag(tag)}
                            className="rounded-full p-0.5 hover:bg-muted-foreground/20"
                        >
                            <X className="h-3 w-3" />
                        </button>
                    </Badge>
                ))}
            </div>
            <Input
                value={input}
                onChange={(e) => setInput(e.target.value)}
                onKeyDown={handleKeyDown}
                onBlur={addTag}
                placeholder={placeholder}
            />
        </div>
    );
}

export function ChipSelect({
    options,
    value,
    onChange,
}: {
    options: string[];
    value: string[];
    onChange: (selected: string[]) => void;
}) {
    const toggle = (option: string) => {
        if (value.includes(option)) {
            onChange(value.filter((v) => v !== option));
        } else {
            onChange([...value, option]);
        }
    };

    return (
        <div className="flex flex-wrap gap-1.5">
            {options.map((option) => (
                <button
                    key={option}
                    type="button"
                    onClick={() => toggle(option)}
                    className={`rounded-md border px-2.5 py-1 text-xs font-medium transition-colors ${
                        value.includes(option)
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-input bg-background text-foreground hover:bg-muted'
                    }`}
                >
                    {option}
                </button>
            ))}
        </div>
    );
}

export function CountrySelector({
    continents,
    value,
    onChange,
}: {
    continents: ContinentGroup[];
    value: string[];
    onChange: (selected: string[]) => void;
}) {
    const toggle = (countryId: string) => {
        if (value.includes(countryId)) {
            onChange(value.filter((v) => v !== countryId));
        } else {
            onChange([...value, countryId]);
        }
    };

    return (
        <div className="max-h-64 space-y-3 overflow-y-auto rounded-md border p-3">
            {continents.map((continent) => (
                <div key={continent.name}>
                    <p className="mb-1.5 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        {continent.name}
                    </p>
                    <div className="flex flex-wrap gap-1.5">
                        {continent.countries.map((country) => (
                            <button
                                key={country.id}
                                type="button"
                                onClick={() => toggle(country.id)}
                                className={`rounded-md border px-2 py-0.5 text-xs font-medium transition-colors ${
                                    value.includes(country.id)
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'border-input bg-background text-foreground hover:bg-muted'
                                }`}
                            >
                                {country.name}
                            </button>
                        ))}
                    </div>
                </div>
            ))}
        </div>
    );
}

export function FilterForm({
    filter,
    departments,
    countries,
    companyName,
    onSubmit,
    onDelete,
    submitLabel,
    t,
}: {
    filter: JobFilter;
    departments: string[];
    countries: ContinentGroup[];
    companyName?: string;
    onSubmit: (data: Omit<JobFilter, 'id' | 'company_id'>) => void;
    onDelete?: () => void;
    submitLabel?: string;
    t: (key: string) => string;
}) {
    const [titleInclude, setTitleInclude] = useState<string[]>(filter.title_include);
    const [titleExclude, setTitleExclude] = useState<string[]>(filter.title_exclude);
    const [selectedCountries, setSelectedCountries] = useState<string[]>(filter.countries);
    const [remoteOnly, setRemoteOnly] = useState(filter.remote_only);
    const [selectedDepartments, setSelectedDepartments] = useState<string[]>(filter.departments);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        onSubmit({
            title_include: titleInclude,
            title_exclude: titleExclude,
            countries: selectedCountries,
            remote_only: remoteOnly,
            departments: selectedDepartments,
        });
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            {companyName && (
                <div className="flex items-center justify-between">
                    <h4 className="text-sm font-semibold">{companyName}</h4>
                    {onDelete && (
                        <Button type="button" variant="ghost" size="sm" onClick={onDelete} className="text-destructive hover:text-destructive">
                            {t('settings.filters.delete_override')}
                        </Button>
                    )}
                </div>
            )}

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
                <Switch id={`remote-${filter.id || 'global'}`} checked={remoteOnly} onCheckedChange={setRemoteOnly} />
                <div>
                    <Label htmlFor={`remote-${filter.id || 'global'}`}>{t('settings.filters.remote_only_label')}</Label>
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

            <Button type="submit">{submitLabel ?? t('common.save')}</Button>
        </form>
    );
}

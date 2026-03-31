import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, router } from '@inertiajs/react';
import { Bell, BellOff, Building2, Plus, Trash2 } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Companies', href: '/companies' },
];

interface CompanySubscription {
    id: string;
    name: string;
    workable_account_slug: string;
    is_active: boolean;
    job_postings_count: number;
    email_notifications: boolean;
}

interface CompaniesProps {
    companies: CompanySubscription[];
}

export default function Companies({ companies }: CompaniesProps) {
    const form = useForm({ slug: '' });

    const handleFollow = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/companies/follow', {
            preserveScroll: true,
            onSuccess: () => form.reset('slug'),
        });
    };

    const handleUnfollow = (companyId: string) => {
        if (!confirm('Are you sure you want to unfollow this company?')) return;
        router.delete(`/companies/${companyId}/unfollow`, { preserveScroll: true });
    };

    const handleToggleNotifications = (companyId: string) => {
        router.patch(`/companies/${companyId}/notifications`, {}, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Companies" />
            <div className="mx-auto w-full max-w-3xl p-6">
                <h1 className="text-2xl font-semibold">My Companies</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Add Workable companies to track their job postings.
                </p>

                {/* Add company form */}
                <form onSubmit={handleFollow} className="mt-6 flex gap-3">
                    <Input
                        placeholder="Enter Workable slug (e.g. laravel)"
                        value={form.data.slug}
                        onChange={(e) => form.setData('slug', e.target.value)}
                        className="flex-1"
                    />
                    <Button type="submit" disabled={form.processing || !form.data.slug.trim()}>
                        <Plus className="mr-1 size-4" />
                        Follow
                    </Button>
                </form>
                {form.errors.slug && (
                    <p className="mt-2 text-sm text-red-600 dark:text-red-400">{form.errors.slug}</p>
                )}

                {/* Companies list */}
                {companies.length === 0 ? (
                    <div className="mt-12 text-center text-muted-foreground">
                        <Building2 className="mx-auto size-12 opacity-30" />
                        <p className="mt-4">No companies yet. Add one above to start tracking jobs.</p>
                    </div>
                ) : (
                    <div className="mt-6 space-y-3">
                        {companies.map((company) => (
                            <div
                                key={company.id}
                                className="flex items-center justify-between rounded-lg border border-border bg-card p-4"
                            >
                                <div className="min-w-0 flex-1">
                                    <h3 className="font-medium">{company.name}</h3>
                                    <p className="text-sm text-muted-foreground">
                                        {company.workable_account_slug} · {company.job_postings_count} jobs
                                    </p>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => handleToggleNotifications(company.id)}
                                        title={company.email_notifications ? 'Disable notifications' : 'Enable notifications'}
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
                                        title="Unfollow"
                                    >
                                        <Trash2 className="size-4 text-red-500" />
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

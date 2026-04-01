import { dashboard, login, register } from '@/routes';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

const providers = [
    {
        name: 'Workable',
        type: 'API',
        example: 'apply.workable.com/laravel',
        description: 'REST API integration with Workable widget endpoints.',
    },
    {
        name: 'Lever',
        type: 'API',
        example: 'jobs.lever.co/scaleway',
        description: 'REST API integration with Lever job board postings.',
    },
    {
        name: 'Teamtailor',
        type: 'Scraper',
        example: 'weroad.teamtailor.com/jobs',
        description: 'HTML scraper with configurable CSS selectors and health checks.',
    },
    {
        name: 'Factorial',
        type: 'Scraper',
        example: 'shippypro.factorialhr.com',
        description: 'HTML scraper with configurable CSS selectors and health checks.',
    },
];

const steps = [
    {
        number: '1',
        title: 'Add companies',
        description: 'Select a provider and enter the company slug. The system validates and starts tracking.',
    },
    {
        number: '2',
        title: 'Auto-sync daily',
        description: 'New job postings are fetched every day and you get notified by email.',
    },
    {
        number: '3',
        title: 'Track your pipeline',
        description: 'Bookmark, mark as submitted, track interviews, or dismiss from your dashboard.',
    },
];

export default function Welcome({ canRegister = true }: { canRegister?: boolean }) {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="ekswai">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>
            <div className="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:p-8 dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                {/* Header nav */}
                <header className="mb-6 w-full max-w-4xl text-sm">
                    <nav className="flex items-center justify-end gap-4">
                        {auth.user ? (
                            <Link
                                href={dashboard()}
                                className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={login()}
                                    className="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                                >
                                    Log in
                                </Link>
                                {canRegister && (
                                    <Link
                                        href={register()}
                                        className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                                    >
                                        Register
                                    </Link>
                                )}
                            </>
                        )}
                    </nav>
                </header>

                {/* Hero */}
                <section className="mt-12 mb-16 flex max-w-4xl flex-col items-center text-center lg:mt-24">
                    <h1 className="mb-4 text-5xl font-semibold tracking-tight lg:text-6xl">ekswai</h1>
                    <p className="mb-8 max-w-2xl text-lg text-[#706f6c] dark:text-[#A1A09A]">
                        Track job postings from multiple job boards, manage your application pipeline.
                    </p>
                    {auth.user ? (
                        <Link
                            href={dashboard()}
                            className="rounded-sm bg-[#1b1b18] px-8 py-2.5 text-sm font-medium text-white hover:bg-black dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white"
                        >
                            Go to Dashboard
                        </Link>
                    ) : (
                        <Link
                            href={register()}
                            className="rounded-sm bg-[#1b1b18] px-8 py-2.5 text-sm font-medium text-white hover:bg-black dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white"
                        >
                            Get Started
                        </Link>
                    )}
                </section>

                {/* Supported Providers */}
                <section className="mb-16 w-full max-w-4xl">
                    <h2 className="mb-8 text-center text-2xl font-semibold">Supported Providers</h2>
                    <div className="grid gap-4 sm:grid-cols-2">
                        {providers.map((provider) => (
                            <Card key={provider.name}>
                                <CardHeader>
                                    <div className="flex items-center justify-between">
                                        <CardTitle>{provider.name}</CardTitle>
                                        <Badge variant={provider.type === 'API' ? 'default' : 'secondary'}>
                                            {provider.type}
                                        </Badge>
                                    </div>
                                    <CardDescription>{provider.description}</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <code className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                        {provider.example}
                                    </code>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </section>

                {/* How it works */}
                <section className="mb-16 w-full max-w-4xl">
                    <h2 className="mb-8 text-center text-2xl font-semibold">How it works</h2>
                    <div className="grid gap-6 sm:grid-cols-3">
                        {steps.map((step) => (
                            <div key={step.number} className="text-center">
                                <div className="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-[#1b1b18] text-sm font-medium text-white dark:bg-[#eeeeec] dark:text-[#1C1C1A]">
                                    {step.number}
                                </div>
                                <h3 className="mb-2 font-medium">{step.title}</h3>
                                <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">{step.description}</p>
                            </div>
                        ))}
                    </div>
                </section>
            </div>
        </>
    );
}

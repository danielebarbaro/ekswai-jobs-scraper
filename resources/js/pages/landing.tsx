import { useAppearance } from '@/hooks/use-appearance';
import { Head, Link } from '@inertiajs/react';
import { Bell, Bookmark, Building2, Check, Github, Mail, Monitor, Moon, Sun, ArrowRight } from 'lucide-react';

interface LandingTranslations {
    meta: {
        title: string;
        description: string;
        og_title: string;
        og_description: string;
    };
    nav: {
        login: string;
        register: string;
        dashboard: string;
    };
    hero: {
        headline: string;
        subtitle: string;
        cta: string;
    };
    steps_heading: string;
    steps: Record<string, { title: string; description: string }>;
    preview_heading: string;
    preview: Record<string, { title: string; description: string }>;
    features_heading: string;
    features: Record<string, { title: string; description: string }>;
    cta_final: {
        headline: string;
        cta: string;
    };
    footer: {
        opensource_by: string;
        plincode: string;
    };
}

interface LandingConfig {
    repo_url: string;
    umami: {
        enabled: boolean;
        script_url: string | null;
        website_id: string | null;
    };
}

interface LandingProps {
    locale: string;
    alternateLocale: string;
    translations: LandingTranslations;
    config: LandingConfig;
    baseUrl: string;
    auth: { user: unknown | null };
}

export default function Landing({ locale, alternateLocale, translations: t, config, baseUrl, auth }: LandingProps) {
    const { appearance, updateAppearance } = useAppearance();

    const toggleTheme = () => {
        const isDark = appearance === 'dark' || (appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        updateAppearance(isDark ? 'light' : 'dark');
    };

    return (
        <>
            <Head title={t.meta.title}>
                <meta name="description" content={t.meta.description} />
                <meta property="og:title" content={t.meta.og_title} />
                <meta property="og:description" content={t.meta.og_description} />
                <meta property="og:type" content="website" />
                <meta property="og:locale" content={locale === 'it' ? 'it_IT' : 'en_US'} />
                <meta property="og:image" content={`${baseUrl}/images/og-landing.png`} />
                <meta property="og:url" content={`${baseUrl}/${locale}`} />
                <link rel="canonical" href={`${baseUrl}/${locale}`} />
                <link rel="alternate" hrefLang="en" href={`${baseUrl}/en`} />
                <link rel="alternate" hrefLang="it" href={`${baseUrl}/it`} />
                {config.umami.enabled && config.umami.script_url && config.umami.website_id && (
                    <script defer src={config.umami.script_url} data-website-id={config.umami.website_id} />
                )}
                <script type="application/ld+json">
                    {JSON.stringify({
                        '@context': 'https://schema.org',
                        '@type': 'WebApplication',
                        name: 'ekswai',
                        description: t.meta.description,
                        url: config.repo_url,
                        applicationCategory: 'Utilities',
                        operatingSystem: 'All',
                        offers: { '@type': 'Offer', price: '0', priceCurrency: 'EUR' },
                    })}
                </script>
            </Head>

            <div className="min-h-screen bg-white font-sans text-stone-900 opacity-100 transition-opacity duration-750 dark:bg-stone-950 dark:text-stone-100 starting:opacity-0">
                {/* Header */}
                <header className="mx-auto flex max-w-5xl items-center justify-between px-6 py-6">
                    <div className="flex items-center gap-2">
                        <img src="/images/logo.png" alt="ekswai" className="size-7" />
                        <span className="text-lg font-semibold" style={{ fontFamily: "'Leckerli One', cursive" }}>ekswai</span>
                    </div>
                    <nav className="flex items-center gap-3 text-sm">
                        <button
                            onClick={toggleTheme}
                            className="rounded-md p-1.5 text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-stone-100"
                            aria-label="Toggle theme"
                        >
                            <Sun className="size-4 dark:hidden" />
                            <Moon className="hidden size-4 dark:block" />
                        </button>
                        <Link
                            href={`/${alternateLocale}`}
                            className="rounded-md px-3 py-1.5 font-medium uppercase text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-stone-100"
                        >
                            {alternateLocale}
                        </Link>
                        {auth.user ? (
                            <Link
                                href="/dashboard"
                                className="rounded-md bg-orange-500 px-4 py-1.5 font-medium text-white hover:bg-orange-600 dark:bg-orange-500 dark:hover:bg-orange-400"
                            >
                                {t.nav.dashboard}
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href="/login"
                                    className="rounded-md px-4 py-1.5 text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-stone-100"
                                >
                                    {t.nav.login}
                                </Link>
                                <Link
                                    href="/register"
                                    className="rounded-md bg-orange-500 px-4 py-1.5 font-medium text-white hover:bg-orange-600 dark:bg-orange-500 dark:hover:bg-orange-400"
                                >
                                    {t.nav.register}
                                </Link>
                            </>
                        )}
                    </nav>
                </header>

                {/* Hero */}
                <section className="bg-gradient-to-b from-orange-50 to-amber-50 px-6 py-24 dark:from-orange-950 dark:to-amber-950">
                    <div className="mx-auto flex max-w-5xl flex-col items-center gap-12 lg:flex-row">
                        <div className="flex-1 text-center lg:text-left">
                            <h1 className="text-4xl font-semibold tracking-tight text-stone-900 lg:text-5xl dark:text-stone-100">
                                {t.hero.headline}
                            </h1>
                            <p className="mt-4 text-lg text-stone-600 dark:text-stone-400">
                                {t.hero.subtitle}
                            </p>
                            <Link
                                href="/register"
                                className="mt-8 inline-flex items-center gap-2 rounded-md bg-orange-500 px-6 py-3 text-base font-medium text-white hover:bg-orange-600 dark:bg-orange-500 dark:hover:bg-orange-400"
                            >
                                {t.hero.cta}
                                <ArrowRight className="size-4" />
                            </Link>
                        </div>
                        <div className="flex-1">
                            <img
                                src="/images/hero.png"
                                alt="ekswai app preview"
                                className="mx-auto max-w-md rounded-xl"
                            />
                        </div>
                    </div>
                </section>

                {/* How it works */}
                <section className="px-6 py-24">
                    <div className="mx-auto max-w-5xl">
                        <h2 className="text-center text-2xl font-semibold lg:text-3xl">
                            {t.steps_heading}
                        </h2>
                        <div className="mt-12 grid gap-8 lg:grid-cols-3">
                            {Object.entries(t.steps).map(([key, step]) => {
                                const icons = [Building2, Monitor, Mail];
                                const Icon = icons[Number(key) - 1] ?? Building2;
                                return (
                                    <div key={key} className="rounded-lg border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                                        <div className="mb-4 flex size-10 items-center justify-center rounded-lg bg-orange-50 dark:bg-orange-950">
                                            <Icon className="size-5 text-orange-500 dark:text-orange-400" />
                                        </div>
                                        <h3 className="font-semibold">{step.title}</h3>
                                        <p className="mt-2 text-sm text-stone-600 dark:text-stone-400">{step.description}</p>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </section>

                {/* App Preview */}
                <section className="bg-stone-50 px-6 py-24 dark:bg-stone-900">
                    <div className="mx-auto max-w-5xl">
                        <h2 className="text-center text-2xl font-semibold lg:text-3xl">
                            {t.preview_heading}
                        </h2>
                        <div className="mt-12 grid gap-8 lg:grid-cols-2">
                            {/* My Companies mockup */}
                            <div className="overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                                <div className="border-b border-stone-200 bg-stone-50 px-4 py-3 text-sm font-medium dark:border-stone-700 dark:bg-stone-900">
                                    {t.preview.companies.title}
                                </div>
                                <div className="space-y-3 p-4">
                                    {['Laravel', 'Spotify', 'Stripe'].map((name) => (
                                        <div key={name} className="flex items-center justify-between rounded-lg border border-stone-100 bg-stone-50 px-3 py-2 dark:border-stone-700 dark:bg-stone-900">
                                            <div>
                                                <div className="text-sm font-medium">{name}</div>
                                                <div className="text-xs text-stone-500">{name.toLowerCase()} · 5 jobs</div>
                                            </div>
                                            <Bell className="size-4 text-orange-500" />
                                        </div>
                                    ))}
                                </div>
                                <div className="px-4 pb-3 text-xs text-stone-500 dark:text-stone-400">{t.preview.companies.description}</div>
                            </div>

                            {/* Dashboard mockup */}
                            <div className="overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                                <div className="border-b border-stone-200 bg-stone-50 px-4 py-3 text-sm font-medium dark:border-stone-700 dark:bg-stone-900">
                                    {t.preview.dashboard.title}
                                </div>
                                <div className="space-y-3 p-4">
                                    {[
                                        { title: 'Senior Engineer', company: 'Laravel', status: 'new', color: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300' },
                                        { title: 'Product Designer', company: 'Spotify', status: 'bookmarked', color: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300' },
                                        { title: 'Backend Lead', company: 'Stripe', status: 'submitted', color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' },
                                    ].map((job) => (
                                        <div key={job.title} className="flex items-center justify-between rounded-lg border border-stone-100 bg-stone-50 px-3 py-2 dark:border-stone-700 dark:bg-stone-900">
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <span className="text-sm font-medium">{job.title}</span>
                                                    <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${job.color}`}>{job.status}</span>
                                                </div>
                                                <div className="text-xs text-stone-500">{job.company}</div>
                                            </div>
                                            <div className="flex gap-1">
                                                <Bookmark className="size-3.5 text-stone-400" />
                                                <Check className="size-3.5 text-stone-400" />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                                <div className="px-4 pb-3 text-xs text-stone-500 dark:text-stone-400">{t.preview.dashboard.description}</div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Features */}
                <section className="px-6 py-24">
                    <div className="mx-auto max-w-5xl">
                        <h2 className="text-center text-2xl font-semibold lg:text-3xl">
                            {t.features_heading}
                        </h2>
                        <div className="mt-12 grid gap-8 lg:grid-cols-2">
                            {Object.entries(t.features).map(([key, feature]) => {
                                const iconMap: Record<string, typeof Mail> = {
                                    notifications: Mail,
                                    workable: Monitor,
                                    pipeline: Building2,
                                    opensource: Github,
                                };
                                const Icon = iconMap[key] ?? Building2;
                                const isOpensource = key === 'opensource';
                                return (
                                    <div key={key} className="flex gap-4">
                                        <div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-950">
                                            <Icon className="size-5 text-amber-500 dark:text-amber-400" />
                                        </div>
                                        <div>
                                            <h3 className="font-semibold">
                                                {feature.title}
                                                {isOpensource && (
                                                    <span className="ml-2 inline-block rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-300">
                                                        OSS
                                                    </span>
                                                )}
                                            </h3>
                                            <p className="mt-1 text-sm text-stone-600 dark:text-stone-400">
                                                {feature.description}
                                                {isOpensource && (
                                                    <>
                                                        {' '}
                                                        <a
                                                            href={config.repo_url}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="text-orange-600 hover:underline dark:text-orange-400"
                                                        >
                                                            GitHub &rarr;
                                                        </a>
                                                    </>
                                                )}
                                            </p>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </section>

                {/* Final CTA */}
                <section className="bg-gradient-to-b from-amber-50 to-orange-50 px-6 py-24 dark:from-amber-950 dark:to-orange-950">
                    <div className="mx-auto max-w-3xl text-center">
                        <h2 className="text-2xl font-semibold lg:text-3xl">
                            {t.cta_final.headline}
                        </h2>
                        <Link
                            href="/register"
                            className="mt-8 inline-flex items-center gap-2 rounded-md bg-orange-500 px-6 py-3 text-base font-medium text-white hover:bg-orange-600 dark:bg-orange-500 dark:hover:bg-orange-400"
                        >
                            {t.cta_final.cta}
                            <ArrowRight className="size-4" />
                        </Link>
                    </div>
                </section>

                {/* Footer */}
                <footer className="border-t border-stone-200 px-6 py-8 dark:border-stone-800">
                    <div className="mx-auto flex max-w-5xl flex-col items-center justify-between gap-4 text-sm text-stone-600 sm:flex-row dark:text-stone-400">
                        <div className="flex items-center gap-2">
                            <img src="/images/logo.png" alt="ekswai" className="size-5" />
                            <p>
                                {t.footer.opensource_by}{' '}
                                <a
                                    href={config.repo_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="font-medium text-orange-600 hover:underline dark:text-orange-400"
                                >
                                    {t.footer.plincode}
                                </a>
                            </p>
                        </div>
                        <div className="flex items-center gap-4">
                            <Link
                                href={`/${alternateLocale}`}
                                className="uppercase hover:text-stone-900 dark:hover:text-stone-100"
                            >
                                {alternateLocale}
                            </Link>
                            <a
                                href={config.repo_url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="hover:text-stone-900 dark:hover:text-stone-100"
                            >
                                GitHub
                            </a>
                            <Link href="/login" className="hover:text-stone-900 dark:hover:text-stone-100">
                                {t.nav.login}
                            </Link>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}

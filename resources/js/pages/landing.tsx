import { Head, Link } from '@inertiajs/react';
import { Building2, Mail, Monitor, Github, ArrowRight } from 'lucide-react';

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
    auth: { user: unknown | null };
}

export default function Landing({ locale, alternateLocale, translations: t, config, auth }: LandingProps) {
    return (
        <>
            <Head title={t.meta.title}>
                <meta name="description" content={t.meta.description} />
                <meta property="og:title" content={t.meta.og_title} />
                <meta property="og:description" content={t.meta.og_description} />
                <meta property="og:type" content="website" />
                <meta property="og:locale" content={locale === 'it' ? 'it_IT' : 'en_US'} />
                <meta property="og:image" content="/images/og-landing.png" />
                <meta property="og:url" content={`/${locale}`} />
                <link rel="canonical" href={`/${locale}`} />
                <link rel="alternate" hrefLang="en" href="/en" />
                <link rel="alternate" hrefLang="it" href="/it" />
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
                {config.umami.enabled && config.umami.script_url && config.umami.website_id && (
                    <script defer src={config.umami.script_url} data-website-id={config.umami.website_id} />
                )}
                <script type="application/ld+json">
                    {JSON.stringify({
                        '@context': 'https://schema.org',
                        '@type': 'WebApplication',
                        name: 'EksWai Position Scraper',
                        description: t.meta.description,
                        url: config.repo_url,
                        applicationCategory: 'Utilities',
                        operatingSystem: 'All',
                        offers: { '@type': 'Offer', price: '0', priceCurrency: 'EUR' },
                    })}
                </script>
            </Head>

            <div className="min-h-screen bg-white font-[family-name:Instrument_Sans] text-slate-900 dark:bg-slate-950 dark:text-slate-100">
                {/* Header */}
                <header className="mx-auto flex max-w-5xl items-center justify-between px-6 py-6">
                    <span className="text-lg font-semibold">EksWai</span>
                    <nav className="flex items-center gap-4 text-sm">
                        <Link
                            href={`/${alternateLocale}`}
                            className="rounded-md px-3 py-1.5 font-medium uppercase text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100"
                        >
                            {alternateLocale}
                        </Link>
                        {auth.user ? (
                            <Link
                                href="/dashboard"
                                className="rounded-md bg-teal-600 px-4 py-1.5 font-medium text-white hover:bg-teal-700 dark:bg-teal-500 dark:hover:bg-teal-600"
                            >
                                {t.nav.dashboard}
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href="/login"
                                    className="rounded-md px-4 py-1.5 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100"
                                >
                                    {t.nav.login}
                                </Link>
                                <Link
                                    href="/register"
                                    className="rounded-md bg-teal-600 px-4 py-1.5 font-medium text-white hover:bg-teal-700 dark:bg-teal-500 dark:hover:bg-teal-600"
                                >
                                    {t.nav.register}
                                </Link>
                            </>
                        )}
                    </nav>
                </header>

                {/* Hero */}
                <section className="bg-gradient-to-b from-sky-100 to-cyan-50 px-6 py-24 dark:from-sky-950 dark:to-cyan-950">
                    <div className="mx-auto max-w-3xl text-center">
                        <h1 className="text-4xl font-semibold tracking-tight text-slate-900 lg:text-5xl dark:text-slate-100">
                            {t.hero.headline}
                        </h1>
                        <p className="mt-4 text-lg text-slate-600 dark:text-slate-400">
                            {t.hero.subtitle}
                        </p>
                        <Link
                            href="/register"
                            className="mt-8 inline-flex items-center gap-2 rounded-md bg-teal-600 px-6 py-3 text-base font-medium text-white hover:bg-teal-700 dark:bg-teal-500 dark:hover:bg-teal-600"
                        >
                            {t.hero.cta}
                            <ArrowRight className="size-4" />
                        </Link>
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
                                    <div key={key} className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                                        <div className="mb-4 flex size-10 items-center justify-center rounded-lg bg-cyan-50 dark:bg-cyan-950">
                                            <Icon className="size-5 text-cyan-500 dark:text-cyan-400" />
                                        </div>
                                        <h3 className="font-semibold">{step.title}</h3>
                                        <p className="mt-2 text-sm text-slate-600 dark:text-slate-400">{step.description}</p>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </section>

                {/* Features */}
                <section className="bg-slate-50 px-6 py-24 dark:bg-slate-900">
                    <div className="mx-auto max-w-5xl">
                        <h2 className="text-center text-2xl font-semibold lg:text-3xl">
                            {t.features_heading}
                        </h2>
                        <div className="mt-12 grid gap-8 lg:grid-cols-2">
                            {Object.entries(t.features).map(([key, feature]) => {
                                const iconMap: Record<string, typeof Mail> = {
                                    notifications: Mail,
                                    workable: Monitor,
                                    admin: Building2,
                                    opensource: Github,
                                };
                                const Icon = iconMap[key] ?? Building2;
                                const isOpensource = key === 'opensource';
                                return (
                                    <div key={key} className="flex gap-4">
                                        <div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-teal-50 dark:bg-teal-950">
                                            <Icon className="size-5 text-teal-500 dark:text-teal-400" />
                                        </div>
                                        <div>
                                            <h3 className="font-semibold">
                                                {feature.title}
                                                {isOpensource && (
                                                    <span className="ml-2 inline-block rounded-full bg-lime-100 px-2.5 py-0.5 text-xs font-medium text-lime-800 dark:bg-lime-900 dark:text-lime-300">
                                                        OSS
                                                    </span>
                                                )}
                                            </h3>
                                            <p className="mt-1 text-sm text-slate-600 dark:text-slate-400">
                                                {feature.description}
                                                {isOpensource && (
                                                    <>
                                                        {' '}
                                                        <a
                                                            href={config.repo_url}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="text-cyan-600 hover:underline dark:text-cyan-400"
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
                <section className="bg-gradient-to-b from-teal-50 to-lime-50 px-6 py-24 dark:from-teal-950 dark:to-lime-950">
                    <div className="mx-auto max-w-3xl text-center">
                        <h2 className="text-2xl font-semibold lg:text-3xl">
                            {t.cta_final.headline}
                        </h2>
                        <Link
                            href="/register"
                            className="mt-8 inline-flex items-center gap-2 rounded-md bg-teal-600 px-6 py-3 text-base font-medium text-white hover:bg-teal-700 dark:bg-teal-500 dark:hover:bg-teal-600"
                        >
                            {t.cta_final.cta}
                            <ArrowRight className="size-4" />
                        </Link>
                    </div>
                </section>

                {/* Footer */}
                <footer className="border-t border-slate-200 px-6 py-8 dark:border-slate-800">
                    <div className="mx-auto flex max-w-5xl flex-col items-center justify-between gap-4 text-sm text-slate-600 sm:flex-row dark:text-slate-400">
                        <p>
                            {t.footer.opensource_by}{' '}
                            <a
                                href={config.repo_url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="font-medium text-cyan-600 hover:underline dark:text-cyan-400"
                            >
                                {t.footer.plincode}
                            </a>
                        </p>
                        <div className="flex items-center gap-4">
                            <Link
                                href={`/${alternateLocale}`}
                                className="uppercase hover:text-slate-900 dark:hover:text-slate-100"
                            >
                                {alternateLocale}
                            </Link>
                            <a
                                href={config.repo_url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="hover:text-slate-900 dark:hover:text-slate-100"
                            >
                                GitHub
                            </a>
                            <Link href="/login" className="hover:text-slate-900 dark:hover:text-slate-100">
                                {t.nav.login}
                            </Link>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}

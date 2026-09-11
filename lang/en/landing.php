<?php

declare(strict_types=1);

return [
    'meta' => [
        'title' => 'ekswai — Track job postings, manage your applications',
        'description' => 'Follow companies across job boards, get daily notifications for new positions, and manage your application pipeline. Free and open source.',
        'og_title' => 'ekswai — Job Tracking Made Simple',
        'og_description' => 'Follow companies across job boards, get daily notifications, manage your application pipeline.',
    ],

    'nav' => [
        'login' => 'Log in',
        'register' => 'Register',
        'dashboard' => 'Dashboard',
    ],

    'hero' => [
        'headline' => 'Track job postings, manage your applications',
        'subtitle' => 'Follow companies across job boards, get notified about new positions, and track your entire application pipeline in one place.',
        'cta' => 'Get started',
    ],

    'steps_heading' => 'How it works',
    'steps' => [
        '1' => [
            'title' => 'Sign up and add companies',
            'description' => 'Create your free account. Then go to My Companies, enter a company slug (e.g. "laravel" from apply.workable.com/laravel for Workable), and we validate it instantly.',
        ],
        '2' => [
            'title' => 'We sync every day',
            'description' => 'Each morning we check all your companies for new positions. Toggle email notifications per company, or check your dashboard anytime.',
        ],
        '3' => [
            'title' => 'Manage your pipeline',
            'description' => 'Bookmark interesting roles, mark jobs where you sent your CV, track interviews. Dismiss what you do not need. Everything in one clean dashboard.',
        ],
    ],

    'preview_heading' => 'See it in action',
    'preview' => [
        'companies' => [
            'title' => 'My Companies',
            'description' => 'Add companies from Workable, Lever, Ashby, Greenhouse, Teamtailor, or Factorial',
        ],
        'dashboard' => [
            'title' => 'Your Dashboard',
            'description' => 'Track every application in one place',
        ],
    ],

    'features_heading' => 'Features',
    'features' => [
        'notifications' => [
            'title' => 'Daily email notifications',
            'description' => 'Get notified about new positions. Toggle notifications per company so you only hear about what matters.',
        ],
        'providers' => [
            'title' => 'Job board integration',
            'description' => 'Supports Workable, Lever, Ashby, Greenhouse, Teamtailor, and Factorial. Add any company by their provider slug and we start syncing automatically.',
        ],
        'pipeline' => [
            'title' => 'Personal job pipeline',
            'description' => 'Bookmark, submit, interview, dismiss. Track every application status in one place.',
        ],
        'opensource' => [
            'title' => 'Open source',
            'description' => 'Free and open source, built with Laravel. Contribute on GitHub.',
        ],
    ],

    'connectors' => [
        'badge' => 'Early · v0.1.x',
        'heading' => 'Open source connectors',
        'description' => 'Free, MIT licensed PHP connectors for 7 ATS job boards. PSR-18 compatible, they work in Laravel, Symfony, or plain PHP. ekswai runs six of them in production.',
        'core' => [
            'title' => 'job-boards-core',
            'description' => 'The shared contract every connector builds on: a common interface, DTOs, and a PSR-18 HTTP helper.',
        ],
        'skeleton' => [
            'title' => 'job-boards-skeleton',
            'description' => 'Your job board is missing? Clone the skeleton, rename it, implement the interface.',
        ],
        'scrapers_note' => 'Teamtailor and Factorial are handled by ekswai\'s own HTML scrapers, not packages.',
        'cta' => 'Explore on GitHub',
    ],

    'cta_final' => [
        'headline' => 'Start tracking jobs today',
        'cta' => 'Get started',
    ],

    'footer' => [
        'opensource_by' => 'An open source project by',
        'plincode' => 'Plincode',
    ],
];

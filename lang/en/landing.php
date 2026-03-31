<?php

declare(strict_types=1);

return [
    'meta' => [
        'title' => 'EksWai Position Scraper — Never miss a job posting',
        'description' => 'Monitor Workable job postings and get notified daily when new positions open. Free, open source, built with Laravel.',
        'og_title' => 'EksWai Position Scraper',
        'og_description' => 'Track job postings from Workable and receive daily email notifications for new positions.',
    ],

    'nav' => [
        'login' => 'Log in',
        'register' => 'Register',
        'dashboard' => 'Dashboard',
    ],

    'hero' => [
        'headline' => 'Track job postings, manage your applications',
        'subtitle' => 'Follow companies on Workable, get notified about new positions, and track your application pipeline.',
        'cta' => 'Get started',
    ],

    'steps_heading' => 'How it works',
    'steps' => [
        '1' => [
            'title' => 'Sign up and add companies',
            'description' => 'Register and add Workable company slugs you want to track.',
        ],
        '2' => [
            'title' => 'We sync daily',
            'description' => 'Every day we fetch new positions. You get notified for companies you follow.',
        ],
        '3' => [
            'title' => 'Manage your pipeline',
            'description' => 'Bookmark jobs, mark applications as submitted, track your interviews.',
        ],
    ],

    'features_heading' => 'Features',
    'features' => [
        'notifications' => [
            'title' => 'Daily email notifications',
            'description' => 'Get notified about new positions. Toggle notifications per company.',
        ],
        'workable' => [
            'title' => 'Workable integration',
            'description' => 'Add any company by their Workable slug. We validate and sync automatically.',
        ],
        'admin' => [
            'title' => 'Personal job pipeline',
            'description' => 'Bookmark, submit, interview, dismiss. Track every application in one place.',
        ],
        'opensource' => [
            'title' => 'Open source',
            'description' => 'Free and open source, built with Laravel. Contribute on GitHub.',
        ],
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

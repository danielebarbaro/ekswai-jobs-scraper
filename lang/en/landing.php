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
        'headline' => 'Never miss a job posting',
        'subtitle' => 'Track companies on Workable and get daily email alerts when new positions open.',
        'cta' => 'Get started',
    ],

    'steps_heading' => 'How it works',
    'steps' => [
        '1' => [
            'title' => 'Add companies',
            'description' => 'Enter the Workable account slug of any company you want to monitor.',
        ],
        '2' => [
            'title' => 'We check daily',
            'description' => 'Every day we fetch the latest job postings from Workable automatically.',
        ],
        '3' => [
            'title' => 'Get notified',
            'description' => 'Receive an email with all the new positions, grouped by company.',
        ],
    ],

    'features_heading' => 'Features',
    'features' => [
        'notifications' => [
            'title' => 'Daily email notifications',
            'description' => 'New job postings delivered to your inbox every morning.',
        ],
        'workable' => [
            'title' => 'Workable integration',
            'description' => 'Automatic sync with Workable public API, no API key required.',
        ],
        'admin' => [
            'title' => 'Admin panel',
            'description' => 'Manage companies, job postings, and users from a clean dashboard.',
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

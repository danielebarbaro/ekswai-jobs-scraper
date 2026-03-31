<?php

declare(strict_types=1);

return [
    'meta' => [
        'title' => 'EksWai Position Scraper — Non perdere mai un\'offerta di lavoro',
        'description' => 'Monitora le offerte di lavoro su Workable e ricevi notifiche giornaliere quando si aprono nuove posizioni. Gratuito, open source, costruito con Laravel.',
        'og_title' => 'EksWai Position Scraper',
        'og_description' => 'Tieni traccia delle offerte di lavoro su Workable e ricevi email giornaliere per le nuove posizioni.',
    ],

    'nav' => [
        'login' => 'Accedi',
        'register' => 'Registrati',
        'dashboard' => 'Dashboard',
    ],

    'hero' => [
        'headline' => 'Monitora le offerte di lavoro, gestisci le tue candidature',
        'subtitle' => 'Segui le aziende su Workable, ricevi notifiche sulle nuove posizioni e gestisci la tua pipeline di candidature.',
        'cta' => 'Inizia ora',
    ],

    'steps_heading' => 'Come funziona',
    'steps' => [
        '1' => [
            'title' => 'Registrati e aggiungi le aziende',
            'description' => 'Crea un account e aggiungi gli slug Workable delle aziende che vuoi monitorare.',
        ],
        '2' => [
            'title' => 'Sincronizziamo ogni giorno',
            'description' => 'Ogni giorno recuperiamo le nuove posizioni. Ricevi notifiche per le aziende che segui.',
        ],
        '3' => [
            'title' => 'Gestisci la tua pipeline',
            'description' => 'Salva le offerte, segna le candidature inviate, monitora i colloqui.',
        ],
    ],

    'features_heading' => 'Funzionalità',
    'features' => [
        'notifications' => [
            'title' => 'Notifiche email giornaliere',
            'description' => 'Ricevi notifiche sulle nuove posizioni. Attiva o disattiva le notifiche per ogni azienda.',
        ],
        'workable' => [
            'title' => 'Integrazione Workable',
            'description' => 'Aggiungi qualsiasi azienda con il suo slug Workable. Validiamo e sincronizziamo automaticamente.',
        ],
        'admin' => [
            'title' => 'Pipeline personale',
            'description' => 'Salva, candidati, colloquio, scarta. Monitora ogni candidatura in un unico posto.',
        ],
        'opensource' => [
            'title' => 'Open source',
            'description' => 'Gratuito e open source, costruito con Laravel. Contribuisci su GitHub.',
        ],
    ],

    'cta_final' => [
        'headline' => 'Inizia a monitorare le offerte oggi',
        'cta' => 'Inizia ora',
    ],

    'footer' => [
        'opensource_by' => 'Un progetto open source di',
        'plincode' => 'Plincode',
    ],
];

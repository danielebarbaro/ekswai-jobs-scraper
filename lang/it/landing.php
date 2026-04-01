<?php

declare(strict_types=1);

return [
    'meta' => [
        'title' => 'ekswai — Monitora le offerte di lavoro, gestisci le tue candidature',
        'description' => 'Segui le aziende sulle job board, ricevi notifiche giornaliere per le nuove posizioni e gestisci la tua pipeline di candidature. Gratuito e open source.',
        'og_title' => 'ekswai — Job Tracking semplice',
        'og_description' => 'Segui le aziende sulle job board, ricevi notifiche giornaliere, gestisci la tua pipeline di candidature.',
    ],

    'nav' => [
        'login' => 'Accedi',
        'register' => 'Registrati',
        'dashboard' => 'Dashboard',
    ],

    'hero' => [
        'headline' => 'Monitora le offerte di lavoro, gestisci le tue candidature',
        'subtitle' => 'Segui le aziende sulle job board, ricevi notifiche sulle nuove posizioni e gestisci tutta la tua pipeline di candidature in un unico posto.',
        'cta' => 'Inizia ora',
    ],

    'steps_heading' => 'Come funziona',
    'steps' => [
        '1' => [
            'title' => 'Registrati e aggiungi le aziende',
            'description' => 'Crea il tuo account gratuito. Poi vai su Le Mie Aziende, inserisci uno slug aziendale (come "laravel" da apply.workable.com/laravel per Workable) e lo validiamo subito.',
        ],
        '2' => [
            'title' => 'Sincronizziamo ogni giorno',
            'description' => 'Ogni mattina controlliamo tutte le tue aziende per nuove posizioni. Attiva le notifiche email per azienda, o controlla la dashboard quando vuoi.',
        ],
        '3' => [
            'title' => 'Gestisci la tua pipeline',
            'description' => 'Salva i ruoli interessanti, segna dove hai inviato il CV, monitora i colloqui. Scarta quello che non ti serve. Tutto in una dashboard pulita.',
        ],
    ],

    'preview_heading' => 'Guarda come funziona',
    'preview' => [
        'companies' => [
            'title' => 'Le Mie Aziende',
            'description' => 'Aggiungi aziende da Workable, Lever, Teamtailor o Factorial',
        ],
        'dashboard' => [
            'title' => 'La Tua Dashboard',
            'description' => 'Monitora ogni candidatura in un unico posto',
        ],
    ],

    'features_heading' => 'Funzionalità',
    'features' => [
        'notifications' => [
            'title' => 'Notifiche email giornaliere',
            'description' => 'Ricevi notifiche sulle nuove posizioni. Attiva o disattiva le notifiche per ogni azienda.',
        ],
        'providers' => [
            'title' => 'Integrazione job board',
            'description' => 'Supporta Workable, Lever, Teamtailor e Factorial. Aggiungi qualsiasi azienda con il suo slug e sincronizziamo automaticamente.',
        ],
        'pipeline' => [
            'title' => 'Pipeline personale',
            'description' => 'Salva, candidati, colloquio, scarta. Monitora ogni stato di candidatura in un unico posto.',
        ],
        'opensource' => [
            'title' => 'Open source',
            'description' => 'Gratuito e open source, costruito con Laravel. Contribuisci su GitHub.',
        ],
    ],

    'extensibility' => [
        'heading' => 'Progettato per essere estensibile',
        'description' => 'ekswai ha un\'architettura provider-agnostic. Supporta integrazioni API (Workable, Lever) e scraper HTML con selettori configurabili (Teamtailor, Factorial). Vuoi vedere la tua job board preferita? I contributi sono benvenuti.',
        'steps' => [
            '1' => 'Aggiungi un nuovo case all\'enum JobBoardProvider',
            '2' => 'Crea una classe che implementa l\'interfaccia JobBoardClient',
            '3' => 'Registrala nella JobBoardClientFactory',
            '4' => 'Per gli scraper HTML: aggiungi una ScraperConfig con i selettori CSS',
        ],
        'cta' => 'Contribuisci su GitHub',
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

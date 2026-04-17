<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 */

return [
    'ai' => [
        'enabled' => env('ISSUETRACKER_AI_ENABLED', false),
        'model' => env('ISSUETRACKER_AI_MODEL', 'gpt-4o-mini'),
        'queue' => env('ISSUETRACKER_AI_QUEUE', 'default'),
        'candidate_limit' => (int) env('ISSUETRACKER_AI_CANDIDATE_LIMIT', 25),
        'min_body_chars' => (int) env('ISSUETRACKER_AI_MIN_BODY_CHARS', 40),
    ],

    'statuses' => [
        'enabled' => env('ISSUETRACKER_STATUSES_ENABLED', true),
        'prefix' => 'status:',
        'values' => [
            'received', 'investigating', 'implementing', 'pending', 'deployed',
        ],
        'default' => 'received',
        'email_on_sync_transition' => env('ISSUETRACKER_EMAIL_ON_SYNC', true),
        'transition_dedupe_seconds' => 600,
    ],
];

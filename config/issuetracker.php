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

    /*
    |--------------------------------------------------------------------------
    | Mail
    |--------------------------------------------------------------------------
    |
    | Reporter-facing mail templates. The reporter is an end-user, not a
    | developer — they cannot reply to the From address and they do not have
    | a GitHub account. The defaults below produce plain-English messages
    | that direct them back into your Filament app to add a note.
    |
    | issue_route               Filament route name for the issue edit page.
    |                           When the route exists in your app, the email
    |                           includes a deep link; otherwise it falls back
    |                           to a generic "open the Issues page" message.
    | issue_route_record_param  The route parameter name for the issue id.
    |                           Default ('record') is what Filament uses.
    | strip_dev_only_blocks     Strip <!--dev-only-->...<!--/dev-only--> from
    |                           comment bodies before they reach the reporter,
    |                           so developer breadcrumbs on a GitHub issue do
    |                           not leak into the email.
    | from_team_name            Used in the sign-off ("The Team at <X>").
    |
    */
    'mail' => [
        'issue_route' => env('ISSUETRACKER_ISSUE_ROUTE', 'filament.admin.resources.issues.edit'),
        'issue_route_record_param' => env('ISSUETRACKER_ISSUE_RECORD_PARAM', 'record'),
        'strip_dev_only_blocks' => filter_var(env('ISSUETRACKER_STRIP_DEV_ONLY', true), FILTER_VALIDATE_BOOLEAN),
        'from_team_name' => env('ISSUETRACKER_MAIL_TEAM', 'the Development Team'),
    ],
];

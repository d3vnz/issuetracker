<!--@formatter:off-->
@php
    $route = config('issuetracker.mail.issue_route', 'filament.admin.resources.issues.edit');
    $param = config('issuetracker.mail.issue_route_record_param', 'record');
    $issueUrl = null;
    try {
        $issueUrl = \Illuminate\Support\Facades\Route::has($route) ? route($route, [$param => $issue->id]) : null;
    } catch (\Throwable $e) {}
    $kind = isset($issue->labels['name']) ? strtolower($issue->labels['name']) : 'request';
    $kindDisplay = isset($issue->labels['name']) ? ucwords($issue->labels['name']) : 'Request';
    $team = config('issuetracker.mail.from_team_name', 'the Development Team');
@endphp
<x-mail::message>
# We've received your {{ $kindDisplay }}

Hi {{ $user->first_name }},

Thanks for letting us know about **{{ $issue->title }}**. Your {{ $kind }} has been logged and our team will pick it up shortly.

@if($kind === 'bug')
We'll prioritise this and email you again as soon as we start working on it.
@else
We'll email you again once we've looked at it and have a plan.
@endif

@if($issueUrl)
If you need to add anything — more detail, a screenshot, or a follow-up note — please log in and open this issue: [{{ $issue->title }}]({{ $issueUrl }}).
@else
If you need to add anything — more detail, a screenshot, or a follow-up note — please log in and open this issue from the Issues page.
@endif

Thanks,<br>
The Team at {{ $team }}
</x-mail::message>
<!--@formatter::on-->

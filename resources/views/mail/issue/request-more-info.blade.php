<!--@formatter:off-->
@php
    $route = config('issuetracker.mail.issue_route', 'filament.admin.resources.issues.edit');
    $param = config('issuetracker.mail.issue_route_record_param', 'record');
    $issueUrl = null;
    try {
        $issueUrl = \Illuminate\Support\Facades\Route::has($route) ? route($route, [$param => $issue->id]) : null;
    } catch (\Throwable $e) {}
    $team = config('issuetracker.mail.from_team_name', 'the Development Team');
@endphp
<x-mail::message>
# We need a bit more information

Hi {{ $user->first_name ?? $user->name }},

Thanks for logging **{{ $issue->title }}**. Before our team can action it, we'd appreciate a little more detail.

@if(!empty($reason))
{{ $reason }}
@endif

If you could let us know:

- What you clicked or typed when the problem happened
- What you expected to happen and what actually happened
- A screenshot or short screen recording if possible

@if($duplicate)
---

This also looks similar to an existing request: **{{ $duplicate->title }}**. If it's the same problem, just let us know and we'll link them together.
@endif

@if($issueUrl)
To add this information, please log in and open the issue here: [{{ $issue->title }}]({{ $issueUrl }}). Add your reply as a note.
@else
To add this information, please log in and open the issue from the Issues page, then add your reply as a note.
@endif

Thanks,<br>
The Team at {{ $team }}
</x-mail::message>
<!--@formatter::on-->

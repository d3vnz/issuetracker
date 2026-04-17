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
# Your issue has been closed

Hi {{ $user->first_name }},

Your issue **{{ $issue->title }}** has now been closed.

If you're happy with the resolution, there's nothing more you need to do. If you think it's been closed by mistake, or the problem has come back, please let us know.

@if($issueUrl)
To re-open it or add a note, please log in and open this issue: [{{ $issue->title }}]({{ $issueUrl }}).
@else
To re-open it or add a note, please log in and open this issue from the Issues page.
@endif

Thanks,<br>
The Team at {{ $team }}
</x-mail::message>
<!--@formatter::on-->

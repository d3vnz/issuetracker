<!--@formatter:off-->
@php
    $body = (string) ($comment->body ?? '');
    if (config('issuetracker.mail.strip_dev_only_blocks', true)) {
        $body = preg_replace('/<!--\s*dev-only\s*-->.*?<!--\s*\/dev-only\s*-->/is', '', $body);
    }
    $clean = trim(strip_tags($body));

    $route = config('issuetracker.mail.issue_route', 'filament.admin.resources.issues.edit');
    $param = config('issuetracker.mail.issue_route_record_param', 'record');
    $issueUrl = null;
    try {
        $issueUrl = \Illuminate\Support\Facades\Route::has($route) ? route($route, [$param => $request->id]) : null;
    } catch (\Throwable $e) {}
    $team = config('issuetracker.mail.from_team_name', 'the Development Team');
@endphp
<x-mail::message>
# Update on your issue

Hi {{ $to_name }},

{{ $origin }} added a note to your issue **{{ $request->title }}**:

{!! nl2br(e($clean)) !!}

@if($issueUrl)
To add your own note, please log in and open this issue: [{{ $request->title }}]({{ $issueUrl }}).
@else
To add your own note, please log in and open this issue from the Issues page.
@endif

Thanks,<br>
The Team at {{ $team }}
</x-mail::message>
<!--@formatter::on-->

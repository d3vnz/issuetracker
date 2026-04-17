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
# Update on your issue

Hi {{ $user->first_name }},

We have an update on **{{ $issue->title }}**.

@if($status === 'received')
Thank you for raising this. We've received your issue and our team will look into it shortly. We'll email you when we start work on it.
@elseif($status === 'investigating')
Our team is now looking into this. We'll update you again once we know more.
@elseif($status === 'implementing')
We've identified the cause and are now working on a fix. We'll email you again once the fix is ready to deploy.
@elseif($status === 'pending')
A fix has been prepared and is ready to go out in the next deployment. We'll email you once it's live so you can confirm things are working as expected.
@elseif($status === 'deployed')
The fix is now live. Please take a moment to check that everything is working as you expected.
@else
The status of your issue has been updated to **{{ ucfirst($status) }}**.
@endif

@if(!empty($note))
---

{!! $note !!}
@endif

@if($issueUrl)
If you'd like to add a note, ask a question, or share more detail, please log in and open this issue: [{{ $issue->title }}]({{ $issueUrl }}).
@else
If you'd like to add a note, ask a question, or share more detail, please log in and open this issue from the Issues page.
@endif

Thanks,<br>
The Team at {{ $team }}
</x-mail::message>
<!--@formatter::on-->

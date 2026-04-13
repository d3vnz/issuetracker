<!--@formatter:off--><x-mail::message>
# Issue Status Update

Dear {{ $user->first_name }},

Your issue **{{ $issue->title }}** has been marked as **{{ ucfirst($status) }}**.

@if($status === 'investigating')
Our development team is currently investigating this issue and will update you as progress is made.
@elseif($status === 'implementing')
Good news — we are now working on implementing a fix / change for this issue.
@elseif($status === 'pending')
This issue is currently **pending** — we may need further information or are waiting on an external dependency. We will be in touch if we need anything from you.
@endif

@if(!empty($note))
**Note from the developer:**

{!! $note !!}
@endif

Thanks,<br>
D3V Services Limited Development Team
</x-mail::message>
<!--@formatter::on-->

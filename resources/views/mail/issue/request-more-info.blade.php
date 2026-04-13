<!--@formatter:off--><x-mail::message>
# We need a bit more information

Dear {{ $user->first_name ?? $user->name }},

Thanks for logging **{{ $issue->title }}**. Before our development team can action it, we'd appreciate some extra context.

@if(!empty($reason))
{{ $reason }}
@else
Could you please reply with any steps to reproduce, what you expected to happen, and a screenshot or screen recording if possible?
@endif

@if($duplicate)
---

This issue also looks similar to an existing open request: **{{ $duplicate->title }}** (#{{ $duplicate->number ?? $duplicate->id }}). If it's the same problem, please let us know and we'll close this one as a duplicate.
@endif

Thanks,<br>
D3V Services Limited Development Team
</x-mail::message>
<!--@formatter::on-->

<!--@formatter:off--><x-mail::message>
# We need a bit more information

Dear {{ $user->first_name ?? $user->name }},

Thanks for logging **{{ $issue->title }}**. Before our development team can action it, we'd appreciate some extra context.

@if(!empty($reason))
**Review notes from our triage assistant:**

> {{ $reason }}
@endif

Could you please reply with:

- Steps to reproduce the issue (what you clicked, what you typed)
- What you expected to happen versus what actually happened
- A screenshot or short screen recording if possible

@if($duplicate)
---

This issue also looks similar to an existing open request: **{{ $duplicate->title }}** (#{{ $duplicate->number ?? $duplicate->id }}). If it's the same problem, please let us know and we'll close this one as a duplicate.
@endif

Thanks,<br>
D3V Services Limited Development Team
</x-mail::message>
<!--@formatter::on-->

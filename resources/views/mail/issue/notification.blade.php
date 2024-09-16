<!--@formatter:off--><x-mail::message>
# New Issue Created

{{ $user->name }} has created a new issue for {{ config('app.name')}} {{ $issue->title }}.

<x-mail::button :url="$url">
View Issue
</x-mail::button>

Thanks,<br>
D3V Services Limited Development Team
</x-mail::message>
<!--@formatter::on-->

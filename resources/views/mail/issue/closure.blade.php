<!--@formatter:off--><x-mail::message>
# Issue Closed
Dear {{ $user->first_name }},
This email is to let you know issue **{{ $issue->title }}** has been closed.
Please feel free to rest this and review, and if you feel this is in error, you can re-open and comment as to why.

Thanks,<br>
D3V Services Limited Development Team
</x-mail::message>
<!--@formatter::on-->

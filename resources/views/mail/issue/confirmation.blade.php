<!--@formatter:off--><x-mail::message>
# Issue Received
Dear {{ $user->first_name }},
This email is to let you know we have received your {{ isset($issue->labels['name']) ? ucwords($issue->labels['name']) : 'Request'  }}.
D3V will be in touch with you if required as soon as possible.
@if(isset($issue->labels['name']) && $issue->labels['name'] == 'bug')
As this is a bug, we will get our developers to address this asap and respond accordingly.
@endif


Thanks,<br>
D3V Services Limited Development Team
</x-mail::message>
<!--@formatter::on-->

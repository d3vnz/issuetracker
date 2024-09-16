<!--@formatter:off--><x-mail::message>
# New Comment Added
Dear {{ $to_name }},

{{ $origin }} has added a new comment to the issue {{ $request->title }}.
    {{ strip_tags($comment->body) }}




Thanks,<br>
The D3V Services Limited Development Team
</x-mail::message>
<!--@formatter::on-->

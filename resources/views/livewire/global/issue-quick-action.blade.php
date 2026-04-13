<div
    x-data
    x-on:click.capture.window="
        const anchor = $event.target.closest('a[href*=&quot;#d3vnz-create-&quot;]');
        if (!anchor) return;
        $event.preventDefault();
        $event.stopPropagation();
        const hash = anchor.getAttribute('href').split('#').pop();
        const type = decodeURIComponent(hash.replace('d3vnz-create-', ''));
        $wire.mountAction('createIssue', { type: type });
    "
>
    <x-filament-actions::modals />
</div>

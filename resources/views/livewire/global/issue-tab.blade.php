<div>
    @if(auth()->check() && auth()->user()->isAdmin())
        <div class="fi-dropdown-list p-1 issue-tracker-menu-group">
            <div class="issue-tracker-menu-label">Issue Tracker</div>

            <a href="{{ \D3vnz\IssueTracker\Filament\Resources\IssueResource::getUrl('index') }}"
               class="fi-dropdown-list-item flex w-full items-center gap-2 whitespace-nowrap rounded-md p-2 text-sm outline-none transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                <x-filament::icon icon="heroicon-m-rectangle-stack" class="h-5 w-5 text-gray-400 dark:text-gray-500"/>
                <span class="text-gray-700 dark:text-gray-200">View Issues</span>
            </a>

            <button type="button"
                    wire:click.prevent="mountAction('createQuickIssue',{'type' : 'bug'})"
                    class="fi-dropdown-list-item flex w-full items-center gap-2 whitespace-nowrap rounded-md p-2 text-sm outline-none transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                <x-filament::icon icon="heroicon-m-bug-ant" class="h-5 w-5 text-danger-500"/>
                <span class="text-gray-700 dark:text-gray-200">Report a Bug</span>
            </button>

            <button type="button"
                    wire:click.prevent="mountAction('createQuickIssue',{'type' : 'enhancement'})"
                    class="fi-dropdown-list-item flex w-full items-center gap-2 whitespace-nowrap rounded-md p-2 text-sm outline-none transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                <x-filament::icon icon="heroicon-m-pencil-square" class="h-5 w-5 text-warning-500"/>
                <span class="text-gray-700 dark:text-gray-200">Request a Change</span>
            </button>

            <button type="button"
                    wire:click.prevent="mountAction('createQuickIssue',{'type' : 'feature request'})"
                    class="fi-dropdown-list-item flex w-full items-center gap-2 whitespace-nowrap rounded-md p-2 text-sm outline-none transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                <x-filament::icon icon="heroicon-m-sparkles" class="h-5 w-5 text-success-500"/>
                <span class="text-gray-700 dark:text-gray-200">Request a Feature</span>
            </button>

            <div class="issue-tracker-menu-divider"></div>
        </div>

        <x-filament-actions::modals/>

        @once
            <style>
                .issue-tracker-menu-group { display: flex; flex-direction: column; gap: 2px; }
                .issue-tracker-menu-label {
                    padding: 6px 8px 4px;
                    font-size: 11px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                    color: rgb(107 114 128);
                }
                .dark .issue-tracker-menu-label { color: rgb(156 163 175); }
                .issue-tracker-menu-divider {
                    height: 1px;
                    background-color: rgb(229 231 235);
                    margin: 4px 0;
                }
                .dark .issue-tracker-menu-divider { background-color: rgb(255 255 255 / 0.05); }
                .issue-tracker-menu-group button { cursor: pointer; background: transparent; border: 0; text-align: left; }
            </style>
        @endonce
    @endif
</div>

<div>
    <div x-data="{ isOpen: false }" class="issue-engage" id="kt_app_engage">
        <div class="issue-engage-content">
            <div x-show="isOpen" x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-y-full"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-300"
                 x-transition:leave-start="opacity-100 transform translate-y-0"
                 x-transition:leave-end="opacity-0 transform translate-y-full">
                <a href="{{ \App\Filament\Resources\IssueResource::getUrl('index') }}"
                   class="issue-engage-btn hover-dark">
                    <i class="las la-info-circle fs-1 pt-1 mb-2"></i>
                    Issues
                </a>

                <a href="" class="issue-engage-btn hover-primary"
                   wire:click.prevent="mountAction('createQuickIssue',{'type' : 'bug'})">
                    <i class="las la-bug fs-1 pt-1 mb-2"></i>
                    Bug
                </a>
                <a wire:click.prevent="mountAction('createQuickIssue',{'type' : 'enhancement'})"
                   class="issue-engage-btn hover-primary">
                    <i class="las la-pen fs-1 pt-1 mb-2"></i>
                    Change
                </a>

                <a wire:click.prevent="mountAction('createQuickIssue',{'type' : 'feature request'})"
                   class="issue-engage-btn hover-success">
                    <i class="las la-grin-stars fs-1 pt-1 mb-2"></i>
                    Feature
                </a>
            </div>
        </div>

        <a href="#" @click.prevent="isOpen = !isOpen" class="issue-engage-btn-toggle text-hover-primary p-0">
            <i x-bind:class="isOpen ? 'las la-times' : 'las la-question-circle'" class="fs-2x"></i>
        </a>
    </div>

    <x-filament-actions::modals/>
    <link rel="stylesheet"
          href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">
</div>

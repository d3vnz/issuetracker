<div>
    <div x-data="{ isOpen: false }" class="issue-engage" id="kt_app_engage">
        <div class="issue-engage-content">
            <div x-show="isOpen" x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-y-full"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-300"
                 x-transition:leave-start="opacity-100 transform translate-y-0"
                 x-transition:leave-end="opacity-0 transform translate-y-full">
                <a href="{{ \D3vnz\IssueTracker\Filament\Resources\IssueResource::getUrl('index') }}"
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
    @once
      <style>
          .issue-engage {
              position: fixed;
              right: 12px;
              bottom: 50px;
              display: flex;
              flex-direction: column;
              align-items: flex-end;
              z-index: 5;
          }

          .issue-engage-content {
              position: absolute;
              bottom: 100%;
              right: 0;
              margin-bottom: 8px;
          }

          .issue-engage .issue-engage-btn {
              display: flex;
              align-items: center;
              justify-content: center;
              flex-direction: column;
              box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.15);
              border: 1px solid #E4E6EF;
              font-size: 12px;
              font-weight: 600;
              margin-bottom: 8px;
              border-radius: 6px;
              width: 66px;
              height: 70px;
              color: #5E6278;
              background-color: #ffffff;
              transition: all 0.3s ease;
          }

          .issue-engage .issue-engage-btn:hover {
              background-color: #F5F8FA;
              font-weight: bold;
          }

          .issue-engage .issue-engage-btn-toggle {
              display: flex;
              align-items: center;
              justify-content: center;
              width: 35px;
              height: 35px;
              border-radius: 6px;
              background-color: #ffffff;
              box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.15);
              transition: all 0.3s ease;
              border: 1px solid #E4E6EF;
          }

          .issue-engage .issue-engage-btn-toggle:hover {
              background-color: #F5F8FA;
          }

          .issue-engage .issue-engage-btn i,
          .issue-engage .issue-engage-btn-toggle i {
              font-size: 1.5rem;
          }
      </style>
          @endonce
</div>

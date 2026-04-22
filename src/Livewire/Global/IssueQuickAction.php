<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 */

namespace D3vnz\IssueTracker\Livewire\Global;

use D3vnz\IssueTracker\Filament\Actions\CreateIssueAction;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;

class IssueQuickAction extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public function createIssueAction(): Action
    {
        // Single source of truth — see CreateIssueAction.
        return CreateIssueAction::make();
    }

    public function render()
    {
        return view('d3vnz-issuetracker::livewire.global.issue-quick-action');
    }
}

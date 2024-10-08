<?php
/*
 *
 *  * Copyright (c) D3V Services Limited on behalf of their client.
 *  * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 *
 */

namespace D3vnz\IssueTracker\Filament\Resources\IssueResource\Pages;

use D3vnz\IssueTracker\Filament\Resources\IssueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIssues extends ListRecords
{
    protected static string $resource = IssueResource::class;

    protected function getHeaderActions(): array
    {
        return [
          //  CreateAction::make(),
        ];
    }
}

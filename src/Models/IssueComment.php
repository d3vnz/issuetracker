<?php
/*
 *
 *  * Copyright (c) D3V Services Limited on behalf of their client.
 *  * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 *
 */

namespace D3vnz\IssueTracker\Models;

use D3vnz\IssueTracker\Traits\GithubTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;


class IssueComment extends Model
{
    use SoftDeletes;
    use GithubTrait;

    protected $guarded = [];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    public function issue(): BelongsTo
    {
        return $this->belongsTo(\D3vnz\IssueTracker\Models\Issue::class);
    }
}

<?php
/*
 *
 *  * Copyright (c) D3V Services Limited on behalf of their client.
 *  * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 *
 */

namespace App\Traits;

use GrahamCampbell\GitHub\Facades\GitHub;

trait GithubTrait
{

    public function createIssue($data): array|null
    {

        $data['state'] = 'open';
        $data['labels'] = [$data['labels']];
        return GitHub::issues()->create(config('services.github.owner'), config('services.github.repo'), $data);
    }

    public function getIssues($status = null): array|null
    {
        if ($status == null) {
            $status = ['open', 'closed'];
        }

        return GitHub::issues()->all(config('services.github.owner'), config('services.github.repo'), $status);
    }

    public function updateIssue($id, $data)
    {
        return GitHub::issue()->update(config('services.github.owner'), config('services.github.repo'), 1, $data);
    }

    public function removeIssue($issue)
    {
        return GitHub::issue()->update(config('services.github.owner'), config('services.github.repo'), $issue['number'], array('state' => 'closed'));
    }

    public function getComments($issue)
    {

        return GitHub::issues()->comments()->all(config('services.github.owner'), config('services.github.repo'), $issue['number']);
    }

    public function setComment($issue, $data, $id = null)
    {
        if ($id == null)
            return GitHub::issues()->comments()->create(config('services.github.owner'), config('services.github.repo'), $issue->number, $data);
        else
            return GitHub::issues()->comments()->update(config('services.github.owner'), config('services.github.repo'), $id, $data);


    }

    public function removeComment($id)
    {
        return GitHub::issues()->comments()->remove(config('services.github.owner'), config('services.github.repo'), $id);

    }

    public function getLabels()
    {
        return cache()->remember('github_labels', now()->addDays(10), function () {
            return GitHub::issues()->labels()->all(config('services.github.owner'), config('services.github.repo'));

        });

    }
}


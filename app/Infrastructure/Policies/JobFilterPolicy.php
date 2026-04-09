<?php

declare(strict_types=1);

namespace App\Infrastructure\Policies;

use App\Domain\User\User;

class JobFilterPolicy
{
    public function update(User $user, JobFilter $jobFilter): bool
    {
        return $jobFilter->user_id === $user->id;
    }

    public function delete(User $user, JobFilter $jobFilter): bool
    {
        return $jobFilter->user_id === $user->id;
    }
}

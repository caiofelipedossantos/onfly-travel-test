<?php

namespace App\Policies;

use App\Models\TravelRequest;
use App\Models\User;

class TravelRequestPolicy
{
    public function view(User $user, TravelRequest $travelRequest)
    {
        return $user->id === $travelRequest->user_id;
    }

    public function changeStatus(User $user, TravelRequest $travelRequest)
    {
        return $user->role === 'approver' && $user->id !== $travelRequest->user_id;
    }

    public function delete(User $user, TravelRequest $travelRequest)
    {
        return $user->id === $travelRequest->user_id;
    }
}

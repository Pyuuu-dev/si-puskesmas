<?php

namespace App\Policies;

use App\Models\ArsipLink;
use App\Models\User;

class ArsipLinkPolicy
{
    private function isAdmin(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'kepala'], true);
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ArsipLink $link): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, ArsipLink $link): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, ArsipLink $link): bool
    {
        return $this->isAdmin($user);
    }

    /** Toggle favorite — semua user login boleh */
    public function favorite(User $user, ArsipLink $link): bool
    {
        return true;
    }

    /** Track open count — semua user login boleh */
    public function track(User $user, ArsipLink $link): bool
    {
        return true;
    }
}

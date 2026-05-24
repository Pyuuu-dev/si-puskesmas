<?php

namespace App\Policies;

use App\Models\ArsipFolder;
use App\Models\User;

class ArsipFolderPolicy
{
    private function isAdmin(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'kepala'], true);
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ArsipFolder $folder): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, ArsipFolder $folder): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, ArsipFolder $folder): bool
    {
        return $this->isAdmin($user);
    }

    public function move(User $user, ArsipFolder $folder): bool
    {
        return $this->isAdmin($user);
    }
}

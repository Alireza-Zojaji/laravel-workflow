<?php

namespace Amir\Workflow\Support;

class Models
{
    public static function roleModel(): string
    {
        $cls = config('workflow.models.role');
        return is_string($cls) && $cls !== '' ? $cls : \Spatie\Permission\Models\Role::class;
    }

    public static function userModel(): string
    {
        $cls = config('workflow.models.user');
        if (is_string($cls) && $cls !== '') return $cls;
        $authUser = config('auth.providers.users.model');
        return is_string($authUser) && $authUser !== '' ? $authUser : \App\Models\User::class;
    }
}


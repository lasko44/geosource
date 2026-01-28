<?php

namespace Geosource\UserActions;

use Laravel\Nova\ResourceTool;

class UserActions extends ResourceTool
{
    public function name(): string
    {
        return 'User Actions';
    }

    public function component(): string
    {
        return 'user-actions';
    }

    public function isVerified(bool $verified): self
    {
        return $this->withMeta(['extraAttributes' => ['isVerified' => $verified]]);
    }
}

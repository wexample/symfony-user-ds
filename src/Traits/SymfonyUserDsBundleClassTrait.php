<?php

namespace Wexample\SymfonyUserDs\Traits;

use Wexample\SymfonyHelpers\Traits\BundleClassTrait;
use Wexample\SymfonyUserDs\WexampleSymfonyUserDsBundle;

trait SymfonyUserDsBundleClassTrait
{
    use BundleClassTrait;

    public static function getBundleClassName(): string
    {
        return WexampleSymfonyUserDsBundle::class;
    }
}

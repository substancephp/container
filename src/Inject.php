<?php

declare(strict_types=1);

namespace SubstancePHP\Container;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
readonly class Inject
{
    public function __construct(public string $id)
    {
    }
}

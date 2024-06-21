<?php

declare(strict_types=1);

namespace SubstancePHP\Container\Exception;

use Psr\Container\NotFoundExceptionInterface;

class DependencyNotFoundException extends \RuntimeException implements NotFoundExceptionInterface
{
}

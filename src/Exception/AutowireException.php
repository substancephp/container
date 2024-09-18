<?php

declare(strict_types=1);

namespace SubstancePHP\Container\Exception;

use Psr\Container\ContainerExceptionInterface;

class AutowireException extends \RuntimeException implements ContainerExceptionInterface
{
}

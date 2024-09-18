<?php

namespace TestUtil\Fixtures;

use SubstancePHP\Container\Inject;

readonly class DummyServiceB
{
    public function __construct(
        public DummyService $dummyService,
        #[Inject('abc.xyz')] public int $length,
        public int $width = 50,
    ) {
    }
}

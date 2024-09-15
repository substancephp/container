<?php

namespace TestUtil\Fixtures;

readonly class DummyServiceB
{
    public function __construct(
        public readonly DummyService $dummyService,
    ) {
    }
}

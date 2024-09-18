<?php

declare(strict_types=1);

namespace Test;

use Psr\Container\ContainerInterface;
use SubstancePHP\Container\Container;
use PHPUnit\Framework\TestCase;
use SubstancePHP\Container\Exception\AutowireException;
use SubstancePHP\Container\Exception\DependencyNotFoundException;
use SubstancePHP\Container\Inject;
use TestUtil\Fixtures\DummyCustomInject;
use TestUtil\Fixtures\DummyService;
use TestUtil\Fixtures\DummyServiceB;
use TestUtil\Fixtures\DummyServiceC;
use TestUtil\Fixtures\DummyServiceD;
use TestUtil\Fixtures\DummyServiceE;
use TestUtil\Fixtures\DummyServiceF;

class ContainerTest extends TestCase
{
    private static function makeSampleParentContainer(): ContainerInterface
    {
        return new class implements ContainerInterface {
            public function get(string $id): string
            {
                if ($this->has($id)) {
                    return "parent dummy value for $id";
                }
                throw new DependencyNotFoundException();
            }

            public function has(string $id): bool
            {
                return \strtoupper($id) === $id;
            }
        };
    }

    /** @return array<string, \Closure(Container, string): mixed> */
    private static function makeSampleFactories(): array
    {
        return [
            'W' => fn () => 'child dummy value for W',
            'x' => fn () => 'dummy value for x',
            'y' => fn ($c) => 'dummy value for y, not ' . $c->get('x'),
            'dummy-name' => fn () => 'Max',
            'abc.xyz' => fn () => 9,
            DummyService::class => fn ($c) => new DummyService($c->get('dummy-name')),
            DummyServiceB::class => Container::autowire(...),
        ];
    }

    public function testExtend(): void
    {
        $parentContainer = self::makeSampleParentContainer();
        $childContainer = Container::extend($parentContainer, self::makeSampleFactories());
        $this->assertInstanceOf(Container::class, $childContainer);
        $this->assertTrue($childContainer->has('x'));
        $this->assertSame('dummy value for x', $childContainer->get('x'));
        $this->assertTrue($childContainer->has('y'));
        $this->assertSame('dummy value for y, not dummy value for x', $childContainer->get('y'));
        $this->assertFalse($childContainer->has('z'));
        $this->assertTrue($childContainer->has('Z'));
        $this->assertSame('parent dummy value for Z', $childContainer->get('Z'));
        $this->expectException(DependencyNotFoundException::class);
        $childContainer->get('w');
    }

    public function testRunHappyPath(): void
    {
        $happyClosure = fn(
            DummyService $param1,
            #[Inject('x')] string $param2,
            #[DummyCustomInject('hi')] string $param3,
            DummyServiceB $param4,
            int $param5 = 50,
        ) => ['a' => $param1, 'b' => $param2, 'c' => $param3, 'd' => $param4, 'e' => $param5];

        $container = Container::from(self::makeSampleFactories());
        $result = $container->run($happyClosure);

        $this->assertIsArray($result);
        $this->assertCount(5, $result);
        $this->assertInstanceOf(DummyService::class, $result['a']);
        $this->assertSame('Max', $result['a']->name);
        $this->assertSame('dummy value for x', $result['b']);
        $this->assertSame('hi|hi', $result['c']);
        $this->assertInstanceOf(DummyServiceB::class, $result['d']);
        $this->assertEquals(50, $result['e']);
    }

    public function testRunUnhappyPathMultipleInjectionAttributes(): void
    {
        $unhappyClosure = fn (
            DummyService $param1,
            #[DummyCustomInject('x')] #[DummyCustomInject('y')] string $param2,
        ) => 'will not reach';
        $this->expectException(DependencyNotFoundException::class);
        $this->expectExceptionMessage("Unexpected plurality of injection attributes on parameter param2");
        $container = Container::from(self::makeSampleFactories());
        $container->run($unhappyClosure);
    }

    public function testRunUnhappyPathUnnamedTypeWithNoInjectionAttribute(): void
    {
        $unhappyClosure = fn ($param1) => 'will not reach';
        $this->expectException(DependencyNotFoundException::class);
        $container = Container::from(self::makeSampleFactories());
        $container->run($unhappyClosure);
    }

    public function testAutowireHappyPath(): void
    {
        $container = Container::from(self::makeSampleFactories());

        $result = Container::autowire($container, DummyServiceC::class);
        $this->assertInstanceOf(DummyServiceC::class, $result);
    }

    public function testAutowireCannotResolveConstructorParam(): void
    {
        $container = Container::from(self::makeSampleFactories());
        $this->expectException(AutowireException::class);
        Container::autowire($container, DummyService::class);
    }

    public function testAutowireNotClassName(): void
    {
        $container = Container::from(self::makeSampleFactories());
        $this->expectException(AutowireException::class);
        Container::autowire($container, 'dummy-name');
    }

    public function testAutowireConstructorProtected(): void
    {
        $container = Container::from(self::makeSampleFactories());
        $this->expectException(AutowireException::class);
        Container::autowire($container, DummyServiceD::class);
    }

    public function testAutowireConstructorPrivate(): void
    {
        $container = Container::from(self::makeSampleFactories());
        $this->expectException(AutowireException::class);
        Container::autowire($container, DummyServiceF::class);
    }

    public function testAutowireWhenClassIsAbstract(): void
    {
        $container = Container::from(self::makeSampleFactories());
        $this->expectException(AutowireException::class);
        Container::autowire($container, DummyServiceE::class);
    }

    public function testFromAndGet(): void
    {
        $container = Container::from(self::makeSampleFactories());
        $this->assertSame('child dummy value for W', $container->get('W'));
        $this->assertInstanceOf(DummyService::class, $container->get(DummyService::class));
        $this->assertSame('Max', $container->get(DummyService::class)->name);
        $this->assertInstanceOf(DummyServiceB::class, $container->get(DummyServiceB::class));

        $a = $container->get(DummyService::class);
        $b = $container->get(DummyService::class);
        $this->assertSame($a, $b);
        $this->expectException(DependencyNotFoundException::class);
        $container->get('xyz');
    }

    public function testHas(): void
    {
        $container = Container::from(self::makeSampleFactories());
        $this->assertTrue($container->has('W'));
        $this->assertTrue($container->has(DummyService::class));
        $this->assertTrue($container->has(DummyServiceB::class));
        $this->assertFalse($container->has('xyz'));
    }
}

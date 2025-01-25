<?php

namespace Tests\Feature\Traits;

use BadMethodCallException;
use Error;
use InvalidArgumentException;
use Mockery;
use PhpDecorator\Attributes\Decorate;
use PhpDecorator\Contracts\Decorateable;
use PhpDecorator\Traits\Decorates;
use ReflectionAttribute;
use Tests\Stubs\ParentDecorated;
use Tests\Stubs\TestDecoratorOne;
use Tests\Stubs\TestDecoratorTwo;

test('it can\'t execute restricted methods without a decorator', function () {
    // Collect
    $testClass = new class () implements Decorateable {
        use Decorates;

        protected function ogMethod(): int
        {
            return 123;
        }
    };

    // Act
    $testClass->ogMethod();
})->throws(BadMethodCallException::class, "Call to protected method PhpDecorator\\Contracts\\Decorateable");

test('it handles missing methods', function () {
    // Collect
    $testClass = new class () implements Decorateable {
        use Decorates;
    };

    // Act
    $testClass->test();
})->throws(Error::class, "Call to undefined method PhpDecorator\\Contracts\\Decorateable");

test('it propagates to parent magic method __call', function () {
    // Collect
    $testClass = new class () extends ParentDecorated implements Decorateable {
        use Decorates;
    };

    // Act
    $result = $testClass->test();

    // Assert
    expect($result)->toBe(321);
});

test('it handles missing decorator class', function () {
    // Collect
    $testClass = new class () extends ParentDecorated implements Decorateable {
        use Decorates;

        #[Decorate('NonExistentDecorator')]
        protected function ogMethod(): int
        {
            return 123;
        }
    };

    // Act
    $testClass->ogMethod();
})->throws(InvalidArgumentException::class, "Decorator NonExistentDecorator does not exist.");

test('it can execute single decorator', function () {
    // Collect
    $decoratorSpy = new class () extends TestDecoratorOne {
        public array $methodCalls = [];

        public function handle(object $instance, string $methodName, array $arguments, ?callable $next): mixed
        {
            $this->methodCalls['handle'] = $this->methodCalls['handle'] ?? 0;
            $this->methodCalls['handle']++;

            return parent::handle($instance, $methodName, $arguments, $next);
        }
    };

    $reflectionAttribute = Mockery::mock(ReflectionAttribute::class)
        ->makePartial()
        ->shouldReceive('getArguments')
        ->once()
        ->andReturn([$decoratorSpy]);

    $testClass = new class () implements Decorateable {
        use Decorates;

        #[Decorate(TestDecoratorOne::class)]
        protected function ogMethod(): int
        {
            return 123;
        }
    };
    $testClass->setDecoratorAttributes(decoratorAttributes: [$reflectionAttribute->getMock()]);

    // Act
    $result = $testClass->ogMethod();

    // Assert
    expect($decoratorSpy->methodCalls['handle'])->toBe(1)
        ->and($result)->toBe(123);
});

test('it can execute multiple decorators', function () {
    // Collect
    $decoratorOneSpy = new class () extends TestDecoratorOne {
        public array $methodCalls = ['handle' => 0];

        public function handle(object $instance, string $methodName, array $arguments, ?callable $next): mixed
        {
            $this->methodCalls['handle']++;

            return parent::handle($instance, $methodName, $arguments, $next);
        }
    };

    $decoratorTwoSpy = new class () extends TestDecoratorTwo {
        public array $methodCalls = ['handle' => 0];

        public function handle(object $instance, string $methodName, array $arguments, ?callable $next): mixed
        {
            $this->methodCalls['handle']++;

            return parent::handle($instance, $methodName, $arguments, $next);
        }
    };

    $reflectionAttributeOne = Mockery::mock(ReflectionAttribute::class)
        ->makePartial()
        ->shouldReceive('getArguments')
        ->once()
        ->andReturn([$decoratorOneSpy]);

    $reflectionAttributeTwo = Mockery::mock(ReflectionAttribute::class)
        ->makePartial()
        ->shouldReceive('getArguments')
        ->once()
        ->andReturn([$decoratorTwoSpy]);

    $testClass = new class () implements Decorateable {
        use Decorates;

        #[Decorate(TestDecoratorOne::class)]
        #[Decorate(TestDecoratorTwo::class)]
        protected function ogMethod(): int
        {
            return 123;
        }
    };
    $testClass->setDecoratorAttributes(decoratorAttributes: [
        $reflectionAttributeOne->getMock(),
        $reflectionAttributeTwo->getMock()
    ]);

    // Act
    $result = $testClass->ogMethod();

    // Assert
    expect($decoratorOneSpy->methodCalls['handle'])->toBe(1)
        ->and($decoratorTwoSpy->methodCalls['handle'])->toBe(1)
        ->and($result)->toBe(123);
});

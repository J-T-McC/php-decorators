<?php

namespace PhpDecorator\Traits;

use BadMethodCallException;
use Error;
use InvalidArgumentException;
use PhpDecorator\Attributes\Decorate;
use PhpDecorator\Contracts\Decorator;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

trait Decorates
{
    /** @var ReflectionAttribute[] */
    private array $decoratorAttributes = [];

    /**
     * @throws ReflectionException
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (!method_exists($this, $name)) {
            return $this->handleParentCall($name, $arguments);
        }

        $this->setDecoratorAttributes($name);

        if (empty($this->decoratorAttributes) && method_exists($this, $name)) {
            throw new BadMethodCallException(sprintf('Call to protected method %s::%s()', static::class, $name));
        }

        $callable = fn () => $this->{$name}(...$arguments);
        $callable = $this->applyDecorators($name, $arguments, $callable);

        return $callable();
    }

    /**
     * @throws ReflectionException
     */
    public function setDecoratorAttributes(string $method = '', array $decoratorAttributes = []): void
    {
        if (!empty($decoratorAttributes)) {
            $this->decoratorAttributes = $decoratorAttributes;
        }

        if (empty($this->decoratorAttributes)) {
            $reflection = new ReflectionMethod($this, $method);

            $this->decoratorAttributes = $reflection->getAttributes(Decorate::class);
        }
    }

    /**
     * @throws ReflectionException
     */
    private function handleParentCall(string $name, array $arguments): mixed
    {
        while ($parentClass = get_parent_class($this)) {
            $parentReflection = new ReflectionClass($parentClass);

            if ($parentReflection->hasMethod('__call')) {
                /** @phpstan-ignore-next-line */
                return parent::__call($name, $arguments);
            }
        }

        throw new Error(sprintf('Call to undefined method %s::%s()', static::class, $name));
    }

    /**
     * @param string $name
     * @param array $arguments
     * @param callable $callable
     * @return callable
     */
    private function applyDecorators(
        string $name,
        array $arguments,
        callable $callable
    ): callable {
        foreach ($this->decoratorAttributes as $attribute) {
            /** @var Decorator $decorator */
            $decorator = $attribute->getArguments()[0];

            if (is_string($decorator)) {
                if (!class_exists($decorator)) {
                    throw new InvalidArgumentException("Decorator {$decorator} does not exist.");
                }

                $decorator = new $decorator();
            }

            $callable = $decorator->handle($this, $name, $arguments, $callable);
        }

        return $callable;
    }
}

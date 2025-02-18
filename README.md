# jtmcc/php-decorator

This project is an experiment to replicate a basic typescript-like decorator experience in PHP. It abuses class encapsulation
paired with magic methods and attributes to achieve this functionality.

### Note: This project is intended for experimental purposes only and is not recommended for production use.

## Usage

In the example below, we define a class `MyClass` with a method `doSomeAction`.
Our goal is to validate the argument passed to this method before its execution.
This is accomplished using the `ValidateAction` decorator.
Multiple decorators can be applied to a single method. Decorators are executed in the order they are defined.

```php
<?php

use PhpDecorator\Attributes\Decorate;
use PhpDecorator\Contracts\Decorator;
use PhpDecorator\Contracts\Decorateable;
use PhpDecorator\Traits\Decorates;
use Some\Logger;

class ValidateAction implements Decorator
{
    public function handle($instance, $methodName, $arguments, $next): mixed
    {
        if ($arguments[0] <= 0) {
            throw new \Exception('Invalid argument');
        }

        return $next();
    }
}

class LogMethodInvoke implements Decorator
{
    public function handle($instance, $methodName, $arguments, $next): mixed
    {
        Logger::log("Invoking method {$methodName}", $arguments);

        return $next();
    }
}

class MyClass implements Decorateable {
    use Decorates;

    #[Decorate(ValidateAction::class)]
    #[Decorate(LogMethodInvoke::class)]
    protected function doSomeAction(int $value) {
       // Usage of accessible methods inside/outside the object do not invoke the __call magic method.
       // This decorated method is protected allowing us to handle decorators via the __call method when invoked externally.
       // Decorators are ignored when invoked internally since the method is accessible and __call is not triggered.
    }
}
```

### Examples

```php
$myClass = new MyClass();
$myClass->doSomeAction(-1);
```

**Result:**

1. Invoke the method doSomeAction with arguments, -1
2. ValidateAction decorator throws an exception preventing further execution
3. LogMethodInvoke decorator is **not** executed
4. Original method doSomeAction is **not** executed

```php
$myClass = new MyClass();
$myClass->doSomeAction(10);
```

**Result:**

1. Invoke the method doSomeAction with arguments, 10
2. ValidateAction decorator succeeds
3. LogMethodInvoke decorator executed
4. Original method doSomeAction is executed

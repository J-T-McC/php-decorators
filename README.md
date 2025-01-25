# php-decorators

This project is an experiment to replicate a Node.js-like decorator experience in PHP. It abuses class encapsulation paired with magic methods and attributes to achieve this functionality.

### Note: This project is intended for experimental purposes only and is not recommended for production use.

## Usage

In the example below, we define a class `MyClass` with a method `doSomeAction`.
Our goal is to validate the argument passed to this method before its execution.
This is accomplished using the `ValidateAction` decorator.

```php
<?php

use PhpDecorator\Contracts\Decorator;

class ValidateAction implements Decorator {
    public function handle($instance, $methodName, $arguments, $next) {
       if ($arguments[0] <= 0) {
          throw new Exception('Invalid argument');
       }
       
       if ($next) {
           $next();
       }
    }
}

use PhpDecorator\Attributes\Decorate;
use PhpDecorator\Contracts\Decorateable;
use PhpDecorator\Traits\Decorates;

class MyClass implements Decorateable {
    use Decorates;

    #[Decorate(ValidateAction::class)]
    protected method doSomeAction(int $value) {
        // ...
    }
}

$myClass = new MyClass();
$myClass->doSomeAction(10);
```
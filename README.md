# Nex Framework
> A simple and efficient web framework written with PHP.

## Table of Contents

* [Requirements](#requirements)
* [Installation](#installation)
* [Usage](#usage)
    * [Container](#container)
    * [Router](#router)
* [License](#license)

## Requirements

You're going to need:
- ***[composer](https://getcomposer.org/)***
- ***[php](https://secure.php.net/manual/en/install.php) >= 7.1***
- ***[super powers](https://hourofcode.com/)***

## Installation

In your terminal execute:
```bash
composer require newpoow/nex-framework
```

## Usage

All you need to start using the framework is to instantiate an application and define the access routes.
```php
$app = new \Nex\Application();

$app->drawRoutes(function () {

    $this->get('/', function () {
        return "Welcome!";
    });

});

$app->run();
```

### Container

Dependency injection allows you to standardize and centralize the way objects are constructed in your application.
```php
$app = new \Nex\Application();

$app->configure(function () {

    $this->singleton(Bar::class, function () {
        return new Bar();
    });

    $this->bind(Foo::class, function (Bar $barInstance) {
        return new Foo($barInstance);
    });

});

$app->run();
```

### Router
A fast and powerful router that maps route callbacks to specific HTTP request methods and URIs. It supports parameters and pattern matching.
```php
$app->drawRoutes(function () {

    $this->map('GET|POST', '/', function (ServerRequestInterface $request) {
        return $request->getMethod();
    });

    $this->get('/{s:username}', function ($username) {
        return "Welcome {$username}!";
    });

    $this->post('/auth', 'AuthController@onlyUsers')->middleware(new AuthMiddleware());

});
```

## License

This project is licensed under the terms of the **[MIT](https://github.com/newpoow/nex-framework/blob/master/LICENSE)** license.

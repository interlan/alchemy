Alchemy framework
=================

Fast and clean PHP micro framework to build websites and not only. Alchemy focus to be simple and yet
gives you the way to build your application faster than writing from scratch. 

What differs this framework from others:
- It does not trying force on you to use predefined dir structure you may not like or don't want to have.
- It does not mixing framework files with your application files
- Requires no configuration


List of contents
----------------

[Installation](#installation)
- [Server Requirements](#server-requirements)
- [Basics](#basics)
- [Creating bootstrap file](#creating-bootstrap-file)

[Routing](#routing)
- [Resource](#resource)
- [Route types](#route-types)
- [Advanced routing](#advanced-routing)


Installation
============

Server requirements
-------------------

- PHP 5.4.x or newer.
- Curl extension on
- PDO with MySQL (to make DB working)

Basics
------

In the repository there are two folders one of them is named `app` and this is example structure
for your application code.
Dir structure looks like this:
- `cache`
- `controller`
- `model`
- `view`
- `plugins` (not required)
- `public` (server's root directory have to point at this one)

Of course you can use totally different structure but you should follow some conventions:
- Dirnames must be lower case
- Every file containing class which should be loaded dynamically must have the same name as the class. 
- The namespace of given class must corresponds to the dirname. 

Assume we willing to create `HelloWorld` class which will be one of controllers for our application, we should
end with path similar to: `/myapp/mycontroller/HelloWorld.php`, and therefore file must containing contents
similar to below ones

```php
<?php
namespace myapp\mycontroller;
class HelloWorld extends \alchemy\app\Controller
{
  //here goes your methods and properties
}
```

The other one is a framework package (the `alchemy` dir)- it holds all classes that simplify your work.
Framework has been sharded into packages, every package has its own role in framework;

- `app` FW's core classes which setup all application and controlls flow
- `event` event package wich uses Observer pattern to make framework elastic 
- `file` file manipullation classes (images, xls, etc... goes here)
- `http` classes connected with http protocol and request handling
- `object`
- `security` acl and validation class
- `storage` package which focuses on persisting data
- `ui` views and views helpers
- `vendor` vendor classes and external API helpers (paypal, payu, ups, facebook, g+, etc...)

Creating bootstrap file
-----------------------

In order to create your first application point you server's root directory to `public` dir and put there an `index.php` file

```php
<?php
require_once $VALID_PATH_TO . '/alchemy/app/Application.php';
use alchemy\app\Application;

$app = new Application($PATH_TO_APPLICATION_ROOT);

//add routes here...
$app->addRoute('*', function(){ 
  echo 'Hello World!';
});

//run application
$app->run();
```

`$PATH_TO_APPLICATION_ROOT` must be valid dirname pointing to the application root directory (the one that holds whole app's
direcotry structure)


Now if you go to `http://localhost/` url you should see:

    Hello World

Routing
=======

Resource
--------

Each route need to point to a specific resource (closure function, class' method, object's method)
Framework supports three variations of resources
- closures, eg:

        $app->addRoute('*', function(){
            //handle request here
        });

- class' method, eg:

        $app->addRoute('*', 'your\controller\MyController::index');

- object's method, eg:

        $app->addRoute('*', 'your\controller\MyController->index');

The difference between using class' method and object's method is when you are using operator `->` 
framework will automaticaly create an instance of given class and call a method. Otherhand if you use `::` operator
framework will search for a static method and instead creating an instace it will just call that method

Route Types
-----------

Alchemy supports to types of routing:
- static
- dynamic 

Static routing means you point given uri to desired resource;

- Closure example:
```php
$app->addRoute('hello/world', function(){
    echo 'Hello World!';
});
```
- Object's method example
```php
$app->addRoute('hello/world', 'app\controller\Hello->world');
```
- Class' method example
```php
$app->addRoute('hello/world', 'app\controller\Hello::world');
```

Dynamic routing allows you to dynamically point to resource, lets asume we are willing to handle
various methods on a one object, so we can build route like:
```php
$app->addRoute('/{$controller}/{$method}', 'app\controller\{$controller}->{$method}');
```
Right now if someone goes to `http://localhost/world/hello` the `app\controller\World->hello` resource
will be executed if exists.

Advanced routing
----------------

You can define various resources to be executed by various request types like GET, POST, PUT, DELETE
Just simply put the request type before URI path, for example:
```php
$app->addRoute('GET /{$controller}/{$method}', 'app\controller\{$controller}->{$method}');
$app->addRoute('POST /{$controller}/{$method}', 'app\controller\PostHandler->{$controller}{$method}');
```


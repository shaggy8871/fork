# Fork
Fork makes it simple to fork and run multiple child processes from PHP.

[![Build Status](https://travis-ci.org/shaggy8871/fork.svg?branch=master)](https://travis-ci.org/shaggy8871/fork)

### Installation

Make sure [PCNTL](http://php.net/manual/en/book.pcntl.php) is installed. This library only works via [php-cli](http://php.net/manual/en/features.commandline.php).

In composer.json:
```
"require": {
    "shaggy8871/fork": "dev-master"
}
```

Then run:
```
composer install
```

### Callback mode:

```php
<?php

include_once("vendor/autoload.php");

Fork\Fork::createChildren(['test1', 'test2'], function(Fork\ChildProcess $child) {

    // Wait 1 second to allow the broadcast to come through
    sleep(1);

    $child->sendToParent('Hello parent, I got ' . $child->getKey() . ' and "' . $child->receivedFromParent() . '" from you');

    // Wait a random amount of time
    $r = rand(1, 10);
    sleep($r);

    $child->sendToParent('Still here after ' . $r . ' seconds?');

    //... do more work

})->then(function(Fork\ParentProcess $parent) {

    $parent->broadcast('Hello children');

    // Wait for all children to finish running and handle messages
    $parent->waitForChildren(function($message, Fork\Child $child) {
        echo "Got message " . $message . " from child " . $child->getPid() . "\n";
    });

    // Display remaining output from buffer (if any)
    print_r($parent->receivedFromChildren());

    // Ask the parent to clean up after itself
    $parent->cleanup();

});
```

### Normal mode:

```php
<?php

include_once("vendor/autoload.php");

$ps = Fork::createChildren(['test1', 'test2']);

if ($ps->isParent()) {

    $ps->broadcast('Hello children');

    // Wait a second to ensure children have had a chance to fork
    sleep(1);

    // Display output from buffer
    print_r($ps->receivedFromChildren());

    // Wait for all children to finish running...
    $ps->waitForChildren();

    // Display remaining output from buffer (if any)
    print_r($ps->receivedFromChildren());

    // Ask the parent to clean up after itself
    $ps->cleanup();

    exit(0);

} else {

    $ps->sendToParent('Hello parent, I got ' . $ps->getKey() . ' and "' . $ps->receivedFromParent() . '" from you');

    sleep(5);

    $ps->sendToParent('Still here?');

    //... do work

    // Child must shut itself down properly
    $ps->shutdown();

}
```

### Contact
* Twitter: http://twitter.com/johnginsberg

### License
Fork may be freely distributed under the MIT license.

<?php

include_once("vendor/autoload.php");

use Fork\Fork;
use Fork\ChildProcess;

/**
 * Examples:
 * Callback model -----------------------
 */

$parent = Fork::createChildren(['test1', 'test2'], function(ChildProcess $child) {

    $child->sendToParent('Hello parent, I got ' . $child->getKey() . ' and "' . $child->receivedFromParent() . '" from you');

    sleep(5);

    $child->sendToParent('Still here?');

    //... do work

});

$parent->broadcast('Hello children');

// Wait a second to ensure children have had a chance to fork
sleep(1);

// Display output from buffer
print_r($parent->receivedFromChildren());

// Wait for all children to finish running...
$parent->waitForChildren();

// Display output from buffer
print_r($parent->receivedFromChildren());

// Ask the parent to clean up after itself
$parent->cleanup();

/**
 * Freeflow model -----------------------
 */

$ps = Fork::createChildren(['test1', 'test2']);

if ($ps->isParent()) {

    $ps->broadcast('Hello children');

    // Wait a second to ensure children have had a chance to fork
    sleep(1);

    // Display output from buffer
    print_r($ps->receivedFromChildren());

    // Wait for all children to finish running...
    $ps->waitForChildren();

    // Display output from buffer
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

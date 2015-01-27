<?php

include_once("vendor/autoload.php");

use Fork\Fork;
use Fork\ChildProcess;
use Fork\Child;

/**
 * Examples:
 * Callback model -----------------------
 */

$parent = Fork::createChildren(['test1', 'test2'], function(ChildProcess $child) {

    // Wait 1 second to allow the broadcast to come through
    sleep(1);

    $child->sendToParent('Hello parent, I got ' . $child->getKey() . ' and "' . $child->receivedFromParent() . '" from you');

    // Wait a random amount of time
    $r = rand(1, 10);
    sleep($r);

    $child->sendToParent('Still here after ' . $r . ' seconds?');

    //... do work

});

// Add a listener to get messages from children immediately
$parent->addEventListener('onMessageWaiting', function(Child $child, $message) {
    echo "Got message " . $message . " from child " . $child->getPid() . "\n";
});

$parent->broadcast('Hello children');

// Wait for all children to finish running...
$parent->waitForChildren();

// Display remaining output from buffer
print_r($parent->receivedFromChildren());

// Ask the parent to clean up after itself
$parent->cleanup();

/**
 * Freeflow model -----------------------
 */

/*
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
*/

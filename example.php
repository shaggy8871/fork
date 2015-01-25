<?php

use Fork\Fork;
use Fork\ChildProcess;

//**** Examples:
//---
//--- Callback model -----------------------

$parent = new Fork::createChildren(['test1', 'test2'], function(ChildProcess $child) {

    $child->notifyParent('Got param ' . $child->getKey());
    $child->notifyParent('Got param ' . $child->getKey());

});

// Wait for all children to finish running...
$parent->waitForChildren();

// Display output
print_r($parent->receive());

// Ask the parent to clean up after itself
$parent->cleanup();

//--- Freeflow model -----------------------

$ps = new Fork::createChildren(['test1', 'test2']);

if ($ps->isParent()) {

    // Wait for all children to finish running...
    while ($ps->hasChildrenRunning()) {
        sleep(1);
    }

    print_r($ps->receive());

    $ps->cleanup();
    die();
}

$ps->notifyParent('Starting up with param ' . $ps->getKey());

//... do work

$ps->shutdown();

<?php

namespace Fork\Tests;

class BasicTest extends \PHPUnit_Framework_TestCase
{

    public function testBasicFork()
    {

        $this->expectOutputString(json_encode((object) [
            'key' => 'test1',
            'message' => 'Hello world'
        ]));

        \Fork\Fork::createChildren(['test1'], function(\Fork\ChildProcess $child) {

            // Wait 1 second to allow the broadcast to come through
            sleep(1);

            $child->sendToParent([
                'key' => $child->getKey(),
                'message' => $child->receivedFromParent()
            ]);

        })->then(function(\Fork\ParentProcess $parent) {

            $parent->broadcast('Hello world');

            // Wait for all children to finish running and output messages
            $parent->waitForChildren(function($message, \Fork\Child $child) {
                echo json_encode($message);
            });

            // Ask the parent to clean up after itself
            $parent->cleanup();

        });

    }

    /**
     * Test two forked processes
     */
    public function testSplitFork()
    {

        $this->expectOutputString(json_encode([
            'test1' => [(object) [
                'key' => 'test1',
                'message' => 'Hello world'
            ]],
            'test2' => [(object) [
                'key' => 'test2',
                'message' => 'Hello world'
            ]],
        ]));

        \Fork\Fork::createChildren(['test1', 'test2'], function(\Fork\ChildProcess $child) {

            // Wait 1 second to allow the broadcast to come through
            sleep(1);

            $child->sendToParent([
                'key' => $child->getKey(),
                'message' => $child->receivedFromParent()
            ]);

        })->then(function(\Fork\ParentProcess $parent) {

            $parent->broadcast('Hello world');

            // Wait for all children to finish running and output messages
            $parent->waitForChildren();

            echo json_encode($parent->receivedFromChildren());

            // Ask the parent to clean up after itself
            $parent->cleanup();

        });

    }

}

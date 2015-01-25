<?php

namespace Fork;

class Fork
{

    /*
     * Fork the specified number of children. If $children is an array of objects,
     * each child will be passed its own object as a parameter.
     * Array must contain numeric keys.
     */
    public static function createChildren($children, callable $callback = null)
    {

        if (!function_exists('pcntl_fork')) {
            throw new \Exception('Cannot spawn children; pcntl_fork method is required.');
        }

        if (is_array($children)) {
            $childCount = count($children);
        } else {
            $childCount = (int) $children;
        }

        $parent = null;

        for ($x = 0; $x < $childCount; $x++) {
            $pid = pcntl_fork();
            $key = isset($children[$x]) ? $children[$x] : $x;
            // Create a socket pair so parent and child can communicate
            list($parentSocket, $childSocket) = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
            switch ($pid) {
                case -1:
                    throw new \Exception('Unable to fork');
                case 0:
                    // Child doesn't need parentSocket
                    fclose($parentSocket);
                    // Create the child process object and send in the startup parameters
                    $child = new ChildProcess($key, $childSocket);
                    // Two modes of operation here - callback or freeflow
                    if ($callable) {
                        // Call the user defined function and send in the process object
                        call_user_func($callable, $child);
                        // Kill the child process
                        $child->shutdown();
                    } else {
                        // Send the child back, calling script must shut down manually
                        return $child;
                    }
                default:
                    // Parent doesn't need childSocket
                    fclose($childSocket);
                    // Initialized here to prevent children from having an instance
                    if (!($parent instanceof ParentProcess)) {
                        $parent = new ParentProcess();
                    }
                    $parent->addChild(new Child($key, $pid, $parentSocket));
                    break;
            }
        }

        return $parent;

    }

}

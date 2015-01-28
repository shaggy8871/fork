<?php

namespace Fork;

class ParentProcess implements ProcessInterface
{

    protected $children = [];

    /*
     * Returns true if I am a child process
     */
    public function isChild()
    {

        return false;

    }

    /*
     * Returns true if I am a parent process
     */
    public function isParent()
    {

        return true;

    }

    /*
     * Adds to the children array
     */
    public function addChild(Child $child)
    {

        $this->children[] = $child;

    }

    /*
     * Returns an array of Child objects for the children
     */
    public function getChildren()
    {

        return $this->children;

    }

    /*
     * Sends a message to all children
     */
    public function broadcast($message)
    {

        foreach($this->children as $child) {
            if ($child->isRunning()) {
                $written = @fwrite($child->getSocket(), $message);
                // If we can't write, assume it's finished
                if ($written === false) {
                    $child->setRunningStatus(false);
                }
            }
        }

    }

    /*
     * Collect all received content and send it back
     */
    public function receivedFromChildren($maxLength = -1)
    {

        $output = [];

        foreach($this->children as $child) {
            if ($child->isRunning()) {
                $key = $child->getKey();
                $contents = stream_get_contents($child->getSocket(), $maxLength);
                if ($contents !== false) {
                    $output[$key] = $contents;
                } else {
                    // If we can't read, assume it's finished
                    $child->setRunningStatus(false);
                }
            }
        }

        return $output;

    }

    /*
     * Wait for all children to stop running
     */
    public function waitForChildren(callable $callback = null)
    {

        $childrenRunning = count($this->children);

        while ($childrenRunning) {

            foreach($this->children as $child) {
                if (!$child->isRunning()) {
                    $childrenRunning--;
                    continue;
                }
                $res = pcntl_waitpid($child->getPid(), $status, WNOHANG);
                if (($res == -1) || ($res > 0)) {
                    $child->setRunningStatus(false, pcntl_wexitstatus($status));
                    $childrenRunning--;
                } else {
                    // Check for any messages waiting
                    $output = stream_get_contents($child->getSocket());
                    if (($output) && (isset($callback))) {
                        call_user_func($callback, $output, $child);
                    }
                }
            }

            if ($childrenRunning > 0) {
                // Sleep for 0.1 seconds
                usleep(Fork::WAIT_TIMEOUT);
                // Then check again
                $childrenRunning = count($this->children);
            }

        }

    }

    /*
     * Returns true if any child process is still running
     */
    public function hasChildrenRunning()
    {

        $childrenRunning = 0;

        foreach($this->children as $child) {
            $res = pcntl_waitpid($child->getPid(), $status, WNOHANG);
            if ($res == 0) {
                $childrenRunning++;
            }
        }

        return $childrenRunning > 0;

    }

    /*
     * Clean up any open parent socket connections; not strictly necessary, but still...
     */
    public function cleanup()
    {

        foreach($this->children as $child) {
            fclose($child->getSocket());
        }

    }

    /*
     * Syntactic sugar method that allows for a then() call after forking
     */
    public function then(callable $callback)
    {

        call_user_func($callback, $this);

    }

}

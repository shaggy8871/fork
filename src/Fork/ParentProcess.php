<?php

namespace Fork;

class ParentProcess implements ProcessInterface
{

    protected $children = array();

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
            fwrite($child->getSocket(), $message);
        }

    }

    /*
     * Collect all received content and send it back
     */
    public function receivedFromChildren($maxLength = -1)
    {

        $output = [];

        foreach($this->children as $child) {
            $key = $child->getKey();
            $output[$key] = stream_get_contents($child->getSocket(), $maxLength);
        }

        return $output;

    }

    /*
     * Wait for all children to stop running
     */
    public function waitForChildren()
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
                }
            }

            sleep(1);

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

}

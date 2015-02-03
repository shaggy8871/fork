<?php

namespace Fork;

class ParentProcess implements ProcessInterface
{

    protected $children;
    protected $messageQueue = [];

    public function __construct()
    {

        $this->children = new \SplObjectStorage();

    }

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

        $this->children->attach($child);

    }

    /*
     * Returns an SplObjectStorage object of Child objects for the children
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

        // Reset...
        $this->children->rewind();

        while($this->children->valid()) {

            $child = $this->children->current();

            if ($child->isRunning()) {
                $written = @fwrite($child->getSocket(), serialize($message));
                // If we can't write, assume it's finished
                if ($written === false) {
                    $child->setRunningStatus(false);
                }
            }

            // Iterate...
            $this->children->next();

        }

    }

    /*
     * Collect all received content and send it back
     */
    public function receivedFromChildren($maxLength = -1)
    {

        // Start with the queue
        $output = $this->getMessageQueue();

        // Clear it before we process what's waiting now
        $this->clearMessageQueue();

        // Reset...
        $this->children->rewind();

        while($this->children->valid()) {

            $child = $this->children->current();

            if ($child->isRunning()) {
                $key = $child->getKey();
                $contents = stream_get_contents($child->getSocket(), $maxLength);
                if (trim($contents) != '') {
                    $output[$key] = unserialize($contents);
                } else {
                    // If we can't read, assume it's finished
                    $child->setRunningStatus(false);
                }
            }

            // Iterate...
            $this->children->next();

        }

        return $output;

    }

    /*
     * Wait for all children to stop running
     */
    public function waitForChildren(callable $callback = null)
    {

        $childrenRunning = $this->children->count();

        while ($childrenRunning) {

            // Reset...
            $this->children->rewind();

            while($this->children->valid()) {

                $child = $this->children->current();

                if (!$child->isRunning()) {
                    $childrenRunning--;
                    $this->children->next();
                    continue;
                }
                $res = pcntl_waitpid($child->getPid(), $status, WNOHANG);
                if (($res == -1) || ($res > 0)) {
                    $child->setRunningStatus(false, pcntl_wexitstatus($status));
                    $childrenRunning--;
                } else {
                    // Check for any messages waiting
                    $contents = stream_get_contents($child->getSocket());
                    if ((trim($contents) != '') && (false !== ($contents = unserialize($contents)))) {
                        if ($callback != null) {
                            call_user_func($callback, $contents, $child);
                        } else {
                            $this->addToMessageQueue($child, $contents);
                        }
                    }
                }

                // Iterate...
                $this->children->next();

            }

            if ($childrenRunning > 0) {
                // Sleep for 0.1 seconds
                usleep(Fork::WAIT_TIMEOUT);
                // Then check again
                $childrenRunning = $this->children->count();
            }

        }

    }

    /*
     * Returns true if any child process is still running
     */
    public function hasChildrenRunning()
    {

        $childrenRunning = 0;

        // Reset...
        $this->children->rewind();

        while($this->children->valid()) {

            $child = $this->children->current();

            $res = pcntl_waitpid($child->getPid(), $status, WNOHANG);
            if ($res == 0) {
                $childrenRunning++;
            }

            // Iterate...
            $this->children->next();

        }

        return $childrenRunning > 0;

    }

    /*
     * Clean up any open parent socket connections; not strictly necessary, but still...
     */
    public function cleanup()
    {

        // Reset...
        $this->children->rewind();

        while($this->children->valid()) {

            fclose($this->children->current()->getSocket());

            // Iterate...
            $this->children->next();

        }

    }

    /*
     * Syntactic sugar method that allows for a then() call after forking
     */
    public function then(callable $callback)
    {

        call_user_func($callback, $this);

    }

    /*
     * Add a message to the message queue
     */
    protected function addToMessageQueue(Child $child, $message)
    {

        $childKey = $child->getKey();
        $this->messageQueue[$childKey][] = $message;

    }

    /*
     * Return the message queue for a specific child, or all children
     */
    protected function getMessageQueue(Child $child = null)
    {

        if ($child) {
            $childKey = $child->getKey();
            return (isset($this->messageQueue[$childKey]) ? $this->messageQueue[$childKey] : null);
        } else {
            return $this->messageQueue;
        }

    }

    /*
     * Clear the message queue for a specific child or all children
     */
    protected function clearMessageQueue(Child $child = null)
    {

        if ($child) {
            unset($this->messageQueue[$childKey]);
        } else {
            reset($this->messageQueue);
        }

    }

}

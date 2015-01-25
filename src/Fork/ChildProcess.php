<?php

namespace Fork;

class ChildProcess implements ProcessInterface
{

    protected $key;
    protected $socket;

    public function __construct($key = null, $socket)
    {

        $this->key = $key;
        $this->socket = $socket;

    }

    /*
     * Returns true if I am a child process
     */
    public function isChild()
    {

        return true;

    }

    /*
     * Returns true if I am a parent process
     */
    public function isParent()
    {

        return false;

    }

    /*
     * Return key if this is a child process
     */
    public function getKey()
    {

        return $this->key;

    }

    /*
     * Returns the current process id
     */
    public function getPid()
    {

        return getmypid();

    }

    /*
     * Sends a message to the parent
     */
    public function sendToParent($message)
    {

        fwrite($this->socket, $message);

    }

    /*
     * Returns any content broadcast by the parent process
     */
    public function receivedFromParent($maxLength = -1)
    {

        return stream_get_contents($this->socket, $maxLength);

    }

    /*
     * Disable the socket connection to indicate that we're finished
     */
    public function shutdown()
    {

        fclose($this->socket);

        exit(0);

    }

}

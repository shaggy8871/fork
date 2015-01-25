<?php

namespace Fork;

class Child
{

    protected $key;
    protected $pid;
    protected $socket;
    protected $isRunning = true;
    protected $exitCode;

    public function __construct($key, $pid, $socket)
    {

        $this->key = $key;
        $this->pid = $pid;
        $this->socket = $socket;

    }

    /*
     * Returns the child's key/parameter
     */
    public function getKey()
    {

        return $this->key;

    }

    /*
     * Returns the child's process id
     */
    public function getPid()
    {

        return $this->pid;

    }

    /*
     * Returns the child's socket
     */
    public function getSocket()
    {

        return $this->socket;

    }

    /*
     * Sets whether the child is currently running or not
     */
    public function setRunningStatus($isRunning, $exitCode = null)
    {

        $this->isRunning = $isRunning;
        if ($exitCode) {
            $this->exitCode = $exitCode;
        }

    }

    /*
     * Returns true if the child is still running
     */
    public function isRunning()
    {

        return $this->isRunning;

    }

    /*
     * If the child has stopped, return the exit code reported
     */
    public function getExitCode()
    {

        return $this->exitCode;

    }

}

<?php

namespace Fork;

interface ProcessInterface
{

    /*
     * Returns true if I am a child process
     */
    public function isChild();

    /*
     * Returns true if I am a parent process
     */
    public function isParent();

}

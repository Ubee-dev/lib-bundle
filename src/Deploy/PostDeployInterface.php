<?php

namespace UbeeDev\LibBundle\Deploy;

interface PostDeployInterface
{
    public function execute(): void;
}
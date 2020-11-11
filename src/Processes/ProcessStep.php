<?php

namespace Truust\Processes;

interface ProcessStep
{
	public function next(ProcessStep $step);

    public function process($data);
}
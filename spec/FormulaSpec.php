<?php

namespace spec;

use Formula;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class FormulaSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Formula::class);
    }
}

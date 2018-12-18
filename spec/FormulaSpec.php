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

// somehow, i'm only able to match latin letters.
// not sure what the issue with unicode is.
// tried a bare preg_match and it's still behaving
// badly. might be the php version (5.6). 
//$formula = '併せて(2,10)'; // :-(
//$formula = 'הוסף(2,10)'; // :-(
//$formula = 'dodaća(2,10)'; // :-(
//$formula = '3 + 4-floor(5.5+max(0, 2)) * plus_1(5)'; // works
//$formula = 'sqrt(5) + floor(1.5)';

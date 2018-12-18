<?php

namespace spec\Monoj\Formula;

use Monoj\Formula\Parser;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ParserSpec extends ObjectBehavior
{
    function it_does_basic_math()
    {
        $this('1+1')->shouldEqual('2');
        $this('3 * 1')->shouldEqual('3');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(Parser::class);
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

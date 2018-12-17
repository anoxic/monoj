<?php
namespace Monoj\Formula;
use Parco\Combinator\RegexParsers;
use Parco\ParseException;

include __DIR__ . '/../../vendor/autoload.php';

class Formula
{
    use RegexParsers;

    const numberRegex = '/[-$]{0,2}\d+(\.\d+)?([eE][+-]?\d+)?/';
    const wordRegex   = '/(*UTF8)\p{L}[\p{L}0-9_]*/';
    // matches a letter followed by letters or numbers
    // doesn't seem to work with non-ascii on my machine rn

    public $operators = ['+','-','*','/','%','^'];

    public function formulaNumber()
    {
        return $this->regex($this::numberRegex)->map(function ($x) {
            return new Token("number", $x);
        });
    }

    public function formulaWord()
    {
        return $this->regex($this::wordRegex);
    }

    public function formulaFunction()
    {
        return $this->seq(
            $this->formulaWord,
            $this->char('(')->seqR(
                $this->seq(
                    $this->formulaExpr,
                    $this->rep($this->char(',')->seqR($this->formulaExpr))
                )
            )->seqL($this->char(')'))
        );
    }

    public function formulaOperator($o)
    {
        return $this->seq(
            $this->opt($this->whitespace),
            $this->elem($o),
            $this->opt($this->whitespace)
        );
    }

    public function formulaInfix()
    {
        $return = [];
        foreach ($this->operators as $o) {
            $return[] = $this->chainl(
                $this->alt($this->formulaNumber, $this->formulaFunction),
                $this->formulaOperator($o)->withResult(function ($left, $right) use ($o) {
                    switch ($o) {
                        case '+': return bcadd($left->value, $right->value);
                        case '-': return bcsub($left->value, $right->value);
                        case '*': return bcmul($left->value, $right->value);
                        case '/': return bcdiv($left->value, $right->value);
                        case '%': return bcmod($left->value, $right->value);
                        case '^': return bcpow($left->value, $right->value);
                    }
                })
            );
        }
        return call_user_func_array([$this, "alt"], $return);
    }

    public function formulaExpr()
    {
        return $this->alt(
            $this->formulaInfix,
            $this->formulaNumber,
            $this->formulaFunction
        );
    }

    /**
     * @throws ParseException
     */
    public function __invoke($input)
    {
        return $this->parseAll($this->formulaExpr, $input)->get();
    }
}

$decoder = new Formula();

// somehow, i'm only able to match latin letters.
// not sure what the issue with unicode is.
// tried a bare preg_match and it's still behaving
// badly. might be the php version (5.6). 
$formula = '併せて(2,10)'; // :-(
$formula = 'הוסף(2,10)'; // :-(
$formula = 'dodaća(2,10)'; // :-(
$formula = '3 + 4-floor(5.5+max(0, 2)) * plus_1(5)'; // works
$formula = '3 + 4';

try {
    var_export($decoder($formula));
} catch (ParseException $e) {
    $lines = explode("\n", $formula);
    $line = $e->getInputLine($lines);
    $column = $e->getInputColumn($lines);
    echo 'Syntax Error: ' . $e->getMessage() . ' on line ' . $line . ' column ' . $column . PHP_EOL;
    if ($line > 0) {
        echo $lines[$line - 1] . PHP_EOL;
        echo str_repeat('-', $column - 1) . '^';
    }
}

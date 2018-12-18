<?php
namespace Monoj\Formula;
use Parco\Combinator\RegexParsers;
use Parco\ParseException;

class Parser
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
        )->map(function($x) {
            $fn = $x[0];
            switch ($x[0]) {
                case 'floor': return new Token("number", floor($x[1][0]->value)); // XXX: use bc
                case 'ceil':  return new Token("number", ceil($x[1][0]->value));  // XXX: use bc
                case 'sqrt':  return new Token("number", bcsqrt($x[1][0]->value));

                default:
                    throw new \Exception("unknown function: $x");
            }
            return $x;
        });
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


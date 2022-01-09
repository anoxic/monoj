<?php
namespace Monoj\Formula;
use Parco\Combinator\RegexParsers;
use Parco\ParseException;

class Parser
{
    use RegexParsers;

    const numberRegex = '/[-$]{0,2}\d+(\.\d+)?([eE][+-]?\d+)?/';
    const opRegex     = '/(*UTF8)[\p{L}_%^\->=!#~\\\\\+-\/\*|]+/';
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

    public function formulaOperator()
    {
        return $this->seq(
            $this->opt($this->whitespace),
            $this->regex($this::opRegex),
            $this->opt($this->whitespace)
        );
    }

    public function mapInfix($m, $dex = "")
    {
        foreach ($m as $val) {
            //re-order to $val[1][0], $val[0], $val[1][1]
            if ($val instanceof Token) {
                echo($dex.$val."\n");
            } elseif (is_array($val)) {
                $dex .= ".";
                $this->mapInfix($val, $dex);
            } else {
                echo "not token or array: $val";
            }
        }
    }

    public function formulaInfix()
    {
        $infixExpr = $this->alt($this->formulaNumber, $this->formulaFunction);
        $infixOperator = $this->formulaOperator->map(function ($x) {
            return new Token("operator", $x[1]);
        });
        return $this->seq(
            $infixExpr,
            $this->rep($this->seq($infixOperator, $infixExpr))
        )->map(function($m) {
            $this->mapInfix($m);
            //var_export($m);
            //var_export([
                //"operator" => $m[1][0][0]->value,
                //"left" => $m[0]->value,
                //"right" => $m[1][0][1]->value
            //]);
            //var_export(count($m[1]));
            //exit;
        });
        //$return = [];
        //foreach ($this->operators as $o) {
        //    $return[] = $this->chainl(
        //        $this->alt($this->formulaNumber, $this->formulaFunction),
        //        $this->formulaOperator($o)->withResult(function ($left, $right) use ($o) {
        //            switch ($o) {
        //                case '+': return bcadd($left->value, $right->value);
        //                case '-': return bcsub($left->value, $right->value);
        //                case '*': return bcmul($left->value, $right->value);
        //                case '/': return bcdiv($left->value, $right->value);
        //                case '%': return bcmod($left->value, $right->value);
        //                case '^': return bcpow($left->value, $right->value);
        //                default:
        //                    throw new \Exception("unknown operator: $x");
        //            }
        //        })
        //    );
        //}
        //return call_user_func_array([$this, "alt"], $return);
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


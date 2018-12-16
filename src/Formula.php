<?php
namespace Monoj;
use Parco\Combinator\RegexParsers;
use Parco\ParseException;

include __DIR__ . '/../vendor/autoload.php';

class Formula
{
    use RegexParsers;

    public function formulaNumber()
    {
        return $this->regex('/[-$]{0,2}\d+(\.\d+)?([eE][+-]?\d+)?/')->map(function ($x) {
            return floatval($x);
        });
    }

    public function formulaWord()
    {
        // TODO: allow non-{whitespace,puncuation,digit}
        // unicode and a whitelist of symbols?
        return $this->regex('/[a-zA-Z][a-zA-Z0-9_]*/');
    }

    // TODO: does nothing
    public function formulaOperator()
    {
        return $this->alt(
            $this->elem('+'),
            $this->elem('-'),
            $this->elem('*'),
            $this->elem('/'),
            $this->elem('%'),
            $this->elem('^'),
            $this->elem('&')
        );
    }

    public function formulaFunction()
    {
        return $this->seq(
            $this->formulaWord,
            $this->char('(')->seqR(
                $this->seq(
                    $this->formulaExpr,
                    $this->rep($this->elem(',')->seqR($this->formulaExpr))
                )
            )->seqL($this->char(')'))
        );
    }

    public function formulaExpr()
    {
        return $this->alt(
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

$json = 'min(3,max(3,3))';

try {
    var_dump($decoder($json));
} catch (\Parco\ParseException $e) {
    $lines = explode("\n", $json);
    $line = $e->getInputLine($lines);
    $column = $e->getInputColumn($lines);
    echo 'Syntax Error: ' . $e->getMessage() . ' on line ' . $line . ' column ' . $column . PHP_EOL;
    if ($line > 0) {
        echo $lines[$line - 1] . PHP_EOL;
        echo str_repeat('-', $column - 1) . '^';
    }
}

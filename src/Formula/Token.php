<?php
namespace Monoj\Formula;
use Parco\Position;
use Parco\Positional;

class Token implements Positional {
    use Position;
    
    public $type;
    public $value;
    
    public function __construct($type, $value)
    {
        $this->type = $type;
        $this->value = $value;
    }
    
    public function __toString()
    {
        return "$this->type($this->value)";
    }
}

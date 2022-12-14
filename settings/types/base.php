<?
namespace PackTheSettings\Settings\Types;

class Base
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function __toString()
    {
        return $this->data;
    }
}
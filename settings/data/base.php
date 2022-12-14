<?
namespace PackTheSettings\Settings\Data;

use \PackTheSettings\Settings\Printer;
use \PackTheSettings\Data\IBase as IBaseData;

abstract class Base implements IBase
{
    protected $ID = false;
    protected $langID = false;
    protected $data;

    static $settings = [];

    public function getID()
    {
        return $this->ID;
    }

    public function getLangID()
    {
        return $this->langID;
    }

    public function getData(): IBaseData
    {
        return $this->data;
    }

    protected function isExcluded(Printer $printer = null)
    {
        return !is_null($printer) && $printer->isExcluded($this);
    }

    public function isPreparedForPrinter(Printer $printer): bool
    {
        return !$this->isExcluded($printer);
    }

    public function finishPrinting(Printer $printer)
    {
        return true;
    }
}
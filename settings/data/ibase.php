<?
namespace PackTheSettings\Settings\Data;

use \PackTheSettings\Settings\Printer;
use \PackTheSettings\Data\IBase as IBaseData;

interface IBase
{
    const SETTINGS_CODE = '';

    public function getID();
    public function getLangID();
    public function getData(): IBaseData;
    public function isPreparedForPrinter(Printer $printer): bool;
    public function finishPrinting(Printer $printer);
    public function getConstantValues(Printer $printer = null): array;
    public function getLangValues(Printer $printer = null): array;
    public function getShortSettings(Printer $printer = null): array;
    public function getComment(int $commentType, Printer $printer = null): string;
}
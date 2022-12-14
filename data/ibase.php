<?
namespace PackTheSettings\Data;

interface IBase
{
    public static function init(array $data);
    public static function get(array $filter);
    public static function getDefaultValues(): array;
    public function getMainParams(): array;
    public function setParams(array $params);
    public function getErrorText(): string;
    public function getChangedValues(): array;
    public function getNormalizedValues(): array;
    public function save();
}
<?
namespace PackTheSettings\Data;

interface IBase
{
    public static function init(array $data): ?IBase;
    public static function get(array $filter): ?IBase;
    public static function getDefaultParams(): array;
    public function getMainParams(): array;
    public function setParams(array $params): IBase;
    public function getErrorText(): string;
    public function getChangedParams(): array;
    public function getNormalizedParams(): array;
    public function save();
    public static function remove(array $filter): bool;
}
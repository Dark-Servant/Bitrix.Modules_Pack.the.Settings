<?
namespace PackTheSettings\Data\IBlock;

use \PackTheSettings\Data\Base;
use \Bitrix\Main\Loader;
use \Bitrix\Iblock\TypeLanguageTable;

class Type extends Base
{
    const LANG_ID = 'RU';
    const DEFAULT_EXIST_CODES = ['lists', 'bitrix_processes', 'structure'];

    protected $name;
    protected $code;
    protected $exists = false;

    public function __construct(string $name, string $code)
    {
        $this->name = $name;
        $this->code = $code;
        $this->exists = in_array($code, static::DEFAULT_EXIST_CODES);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function isExists(): bool
    {
        return $this->exists;
    }

    public function isDefault(): bool
    {
        return in_array($this->code, static::DEFAULT_EXIST_CODES);
    }

    public static function init(array $data)
    {
        $unit = new static($data['NAME'], $data['ID']);
        return $unit->setParams($data);
    }

    public static function get(array $filter)
    {
        $typeID = trim(strval($filter['ID']));
        if (empty($typeID)) return;

        Loader::includeModule('iblock');
        
        $data = \CIBlockType::GetByID($typeID)->Fetch();
        if (!$data) return;

        $lang = TypeLanguageTable::Getlist([
                    'filter' => [
                        'LANGUAGE_ID' => strtolower(static::LANG_ID),
                        'IBLOCK_TYPE_ID' => $typeID
                    ]
                ])->Fetch() ?? ['NAME' => ''];

        $unit = static::init($data + ['NAME' => $lang['NAME']]);
        $unit->exists = true;
        return $unit;
    }

    public static function getDefaultValues(): array
    {
        return [
            'SECTIONS' => 'Y',
            'EDIT_FILE_BEFORE' => '',
            'EDIT_FILE_AFTER' => '',
            'IN_RSS' => 'N',
            'SORT' => 80
        ];
    }

    public function getMainParams(): array
    {
        return [
            'ID' => $this->code,
            'LANG' => [
                static::LANG_ID => [
                    'NAME' => $this->name
                ]
            ]
        ];
    }

    public function save()
    {
        if ($this->exists) return true;

        Loader::includeModule('iblock');

        $type = new \CIBlockType;
        $this->exists = !!$type->Add($this->getNormalizedValues());
        if (!$this->exists) $this->errorText = $type->LAST_ERROR;

        return $this->exists;
    }
}
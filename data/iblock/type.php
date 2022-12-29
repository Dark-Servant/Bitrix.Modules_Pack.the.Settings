<?
namespace PackTheSettings\Data\IBlock;

use \PackTheSettings\Arguments\ClassName;
use \PackTheSettings\Data\{
    IBase,
    Base as DataBase
};
use \Bitrix\Main\{
    Loader,
    Localization\Loc
};
use \Bitrix\IBlock\TypeLanguageTable;

class Type extends DataBase
{
    const LANG_ID = 'RU';
    const DEFAULT_EXIST_CODES = ['lists', 'bitrix_processes', 'structure'];

    protected $name;
    protected $code;
    protected $exists = false;

    public function __construct(string $name, string $code)
    {
        /**
         * В коде для типов инфоблоков есть своя проверка на то,
         * указаны ли название или сивольный код. свою проверку
         * писать не надо
         */
        $this->name = trim($name);
        $this->code = trim($code);
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

    public static function init(array $data): ?IBase
    {
        $unit = new static($data['NAME'], $data['ID']);
        return $unit->setParams($data);
    }

    public static function get(array $filter): ?IBase
    {
        $typeID = trim(strval($filter['ID']));
        if (empty($typeID)) return null;

        Loader::includeModule('iblock');
        
        $data = \CIBlockType::GetByID($typeID)->Fetch();
        if (!$data) return null;

        $lang = TypeLanguageTable::Getlist([
                    'filter' => [
                        'LANGUAGE_ID' => strtolower(static::LANG_ID),
                        'IBLOCK_TYPE_ID' => $typeID
                    ]
                ])->Fetch() ?? ['NAME' => ''];

        $unit = static::init($data + ['NAME' => $lang['NAME'] ?? '']);
        $unit->exists = true;
        return $unit;
    }

    public static function getDefaultParams(): array
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

    public function IBlocks(string $iblockClassName = IBlock::class)
    {
        if (!$this->code) return;

        Loader::includeModule('iblock');

        $iblockClassName = ClassName::getLastRelative(IBlock::class, $iblockClassName);
        $iblocks  = \CIBlock::GetLIst(['ID' => 'ASC'], ['CHECK_PERMISSIONS' => 'N', 'IBLOCK_TYPE_ID' => $this->ID]);
        while ($iblock = $iblocks ->Fetch()) {
            $result = $iblockClassName::init($iblock + ['TYPE_ID_CLASS' => $this]);
            $special_result = (new \ReflectionProperty($result, 'ID'));
            $special_result->setAccessible(true);
            $special_result->setValue($result, $iblock['ID']);
            yield $result;
        }
    }

    public function getIBlocks(string $iblockClassName = IBlock::class)
    {
        return iterator_to_array($this->IBlocks($iblockClassName));
    }

    public function getNewIBlock(string $name, string $code, string $iblockClassName = IBlock::class): ?IBlock
    {
        if (!$this->code) return null;

        return ClassName::getInstance(IBlock::class, $iblockClassName, $name, $code, $this);
    }

    public function save()
    {
        if ($this->exists) return true;

        Loader::includeModule('iblock');

        $type = new \CIBlockType;
        $this->exists = !!$type->Add($this->getNormalizedParams());
        if (!$this->exists) $this->errorText = $type->LAST_ERROR;

        return $this->exists;
    }

    public static function remove(array $filter): bool
    {
        $typeID = static::get($filter);
        if (!$typeID) return false;

        \CIBlockType::Delete($typeID->code);
        return true;
    }
}
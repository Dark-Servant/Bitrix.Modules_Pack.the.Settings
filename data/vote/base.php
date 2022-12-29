<?
namespace PackTheSettings\Data\Vote;

use \PackTheSettings\Data\{
    IBase,
    Base as DataBase
};
use \Bitrix\Main\{
    Localization\Loc,
    Loader
};

class Base extends DataBase
{
    protected $ID = 0;
    protected $name;
    protected $code;

    public function __construct(string $name, string $code)
    {
        $this->name = $name;
        $this->code = $code;
    }

    public function getID()
    {
        return $this->ID;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getCode()
    {
        return $this->code;
    }

    public static function init(array $data): ?IBase
    {
        if (!is_string($data['TITLE'])) return null;

        $unit = new static($data['TITLE'], $data['SYMBOLIC_NAME'] ?: '');
        return $unit->setParams($data);
    }

    public static function get(array $filter): ?IBase
    {
        $ID = intval($filter['ID']);
        if ($ID < 1) return null;
        
        Loader::includeModule('vote');

        $data = \CVoteChannel::GetByID($ID)->Fetch();
        if (!$data) return null;

        $unit = static::init($data);
        $unit->ID = $ID;
        return $unit;
    }

    public static function getDefaultParams(): array
    {
        return [
            'C_SORT' => 100,
            'HIDDEN' => 'N',
            'VOTE_SINGLE' => 'Y',
            'USE_CAPTCHA' => 'N',
            'SITE' => [\CSite::GetDefSite()]
        ];
    }

    public function getMainParams(): array
    {
        return [
            'TITLE' => $this->name,
            'ACTIVE' => 'Y',
            'SYMBOLIC_NAME' => $this->code,
        ];
    }

    public function setGroupPermissions(array $permissions = [])
    {
        if (!$this->ID) return $this;

        Loader::includeModule('vote');

        \CVoteChannel::SetAccessPermissions($this->ID, $permissions);
        return $this;
    }

    public function save()
    {
        global $APPLICATION;

        if (!empty($this->errorText)) return 0;
        if ($this->ID) return $this->ID;

        Loader::includeModule('vote');

        $this->ID = \CVoteChannel::Add($this->getNormalizedParams());
        if (!$this->ID) {
            $this->errorText = $APPLICATION->GetException();
            return 0;
        }

        return $this->ID;
    }

    public static function remove(array $filter): bool
    {
        $vode = static::get($filter);
        if (!$vode) return false;

        Loader::includeModule('vote');

        \CVoteChannel::Delete($vode->ID);
        return true;
    }
}
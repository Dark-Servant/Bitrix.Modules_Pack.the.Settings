<?
namespace PackTheSettings\Data\UF;

use \PackTheSettings\Data\{
    IBase,
    Base as DataBase,
    Vote\Base as VoteData,
    IBlock\IBlock
};
use \PackTheSettings\Data\UF\Helpers\{
    InitParamsTrait,
    ChangedParamsTrait
};

class Base extends DataBase
{
    use InitParamsTrait;
    use ChangedParamsTrait;

    protected const TITLE_CODES = [
        'EDIT_FORM_LABEL', 'LIST_COLUMN_LABEL',
        'LIST_FILTER_LABEL', 'ERROR_MESSAGE',
        'HELP_MESSAGE'
    ];

    protected $ID = 0;
    protected $name;
    protected $code;
    protected $areaID;

    public function __construct(string $name, string $code, string $areaID)
    {
        $this->name = $name;
        $this->code = strtoupper($code);
        $this->areaID = strtoupper($areaID);

        if (!preg_match('/^uf_/i', $this->code))
            $this->code = 'UF_' . $this->code;
    }

    public function getID(): int
    {
        return $this->ID;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getAreaID(): string
    {
        return $this->areaID;
    }

    public static function init(array $data): ?IBase
    {
        $name = '';
        if (is_string($data['NAME']) && !empty($data['NAME'])) {
            $name = $data['NAME'];

        } else {
            foreach (static::TITLE_CODES as $labelUnit) {
                if (is_string($data[$labelUnit]['ru']) && !empty($data[$labelUnit]['ru'])) {
                    $name = $data[$labelUnit]['ru'];
                    break;
                }
            }
        }

        $unit = new static($name, $data['FIELD_NAME'], $data['ENTITY_ID']);
        return $unit->setParams($data);
    }

    public static function get(array $filter): ?IBase
    {
        $ID = intval($filter['ID']);
        if ($ID < 1) return null;

        $data = \CUserTypeEntity::GetByID($ID);
        if (!$data) return null;

        $unit = static::init($data);
        $unit->ID = $ID;
        return $unit;
    }

    public static function getDefaultParams(): array
    {
        return [
            'USER_TYPE_ID' => 'string',
            'XML_ID' => '',
            'SORT' => 100,
            'MULTIPLE' => 'N',
            'MANDATORY' => 'N',
            'SHOW_FILTER' => 'N',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'N',
            'SETTINGS' => []
        ];
    }

    public function getMainParams(): array
    {
        $result = [
            'ENTITY_ID' => $this->areaID,
            'FIELD_NAME' => $this->code
        ];
        foreach (static::TITLE_CODES as $labelUnit) {
            if (
                is_array($this->params[$labelUnit])
                && !empty($this->params[$labelUnit])
            ) {
                $result[$labelUnit] = $this->params[$labelUnit];

            } else {
                $result[$labelUnit] = ['ru' => $this->name, 'en' => ''];
            }
        }
        return $result;
    }

    public function save()
    {
        global $APPLICATION;

        if (!empty($this->errorText)) return 0;
        if ($this->ID) return $this->ID;

        $this->ID = (new \CUserTypeEntity)->Add($this->getNormalizedParams());
        if (!$this->ID) {
            $this->errorText = $APPLICATION->GetException();
            return 0;
        }

        return $this->ID;
    }

    public static function remove(array $filter): bool
    {
        $data = static::get($filter);
        if (!$data) return false;

        (new \CUserTypeEntity)->Delete($data->ID);
        return true;
    }
}
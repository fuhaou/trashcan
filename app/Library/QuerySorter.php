<?php


namespace App\Library;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class QuerySorter
{
    const ASC = 'ASC';
    const DESC = 'DESC';

    protected $_orderBys = [];
    public $supportFields = null;
    public $fieldTransforms = [];

    /**
     * QuerySorter constructor.
     * @param $fieldName
     * @param string $mode
     * @param null|array $supportFields
     * @param array $fieldTransforms
     */
    public function __construct($fieldName, $mode = self::ASC, $supportFields = null, $fieldTransforms = [])
    {
        $this->supportFields = $supportFields;
        $this->fieldTransforms = $fieldTransforms;

        $this->addOrderBy($fieldName, $mode);
    }

    /**
     * Init with array order
     * * Ex:    [
     *              ['name' => 'asc' ],
     *              ['id'   => 'desc'],
     *              ['date'          ],
     *          ]
     * @param array $_orderByArray
     * @param null|array $supportFields
     * @param array $fieldTransforms
     * @return QuerySorter|null
     */
    public static function withArrayOrderBy(array $_orderByArray, $supportFields = null, $fieldTransforms = [])
    {
        if (!$_orderByArray) return null;

        $sorter = new self(null, null, $supportFields, $fieldTransforms);
        $sorter->_orderBys = $_orderByArray;

        return $sorter;
    }

    public static function withRequest(Request $request, $supportFields = null, $fieldTransforms = [])
    {
        $orderField = $request->input('order_by');
        $orderMode = $request->input('order_mode');

        return new self($orderField, $orderMode, $supportFields, $fieldTransforms);
    }

    /**
     * @param $fieldName
     * @param string $mode
     * @return bool
     */
    public function addOrderBy($fieldName, $mode = self::ASC)
    {
        if (!$fieldName) return false;

        $mode = strtoupper($mode);
        if ($mode != self::ASC && $mode != self::DESC) $mode = self::ASC;

        $this->_orderBys[$fieldName] = $mode;

        return true;
    }

    public function removeOrderBy($fieldName)
    {
        if (!isset($this->_orderBys[$fieldName])) return false;

        unset($this->_orderBys[$fieldName]);
        return true;
    }

    /**
     * @param \Illuminate\Database\Query\Builder|Builder $query
     * @return Builder
     */
    public function applyQuery($query)
    {
        $transform = $this->fieldTransforms ? $this->fieldTransforms : [];
        foreach ($this->_orderBys as $fieldName => $mode) {
            if ($this->supportFields !==null && !in_array($fieldName, $this->supportFields)) continue;
            if (isset($transform[$fieldName])) $fieldName = $transform[$fieldName];

            if (is_numeric($fieldName)) {
                $query = $query->orderBy($mode, self::ASC);
            } else {
                $query = $query->orderBy($fieldName, $mode);
            }
        }

        return $query;
    }

    public function getOrders(){
        return $this->_orderBys;
    }
}

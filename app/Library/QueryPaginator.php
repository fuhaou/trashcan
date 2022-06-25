<?php

namespace App\Library;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class QueryPaginator
{
    const NO_LIMIT = -1;

    protected $_limit;
    protected $_offset = 0;
    protected $_page;
    protected $_data = null;
    protected $_itemCount = null;
    protected $_pageCount = null;
    protected $_dataFilterCallback = null;
    protected $_itemTransformCallback = null;

    /** @var Builder */
    protected $_query;

    /**
     * QueryPaginator constructor.
     * @param $limit
     * @param $page
     */
    public function __construct($limit, $page)
    {
        $this->setLimit($limit);
        $this->setPage($page);
    }

    public static function withRequest(Request $request)
    {
        return new self($request->input('limit'), $request->input('page'));
    }

    /**
     * @param mixed $limit
     */
    public function setLimit($limit)
    {
        $limitConfig = config('pagination.limit');
        $this->_limit = $limit === null || $limit === 0 ? intval($limitConfig) : intval($limit);
    }

    /**
     * @return mixed
     */
    public function getLimit()
    {
        return intval($this->_limit);
    }

    public function isNoLimit()
    {
        return $this->_limit == self::NO_LIMIT;
    }

    /**
     * @return int
     */
    public function getPage()
    {
        return $this->_page;
    }

    /**
     * @param int $page
     */
    public function setPage($page)
    {
        $this->_page = $page ? intval($page) : 1;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->_offset;
    }

    /**
     * @param int $offset
     */
    public function setOffset($offset)
    {
        $this->_offset = $offset > -1 ? intval($offset) : 0;
    }

    /**
     * @return mixed
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * @param Builder $query
     */
    public function setQuery($query)
    {
        $this->_data = null;
        $this->_query = $query;
    }

    /**
     * @param $query
     * @return Builder|\Illuminate\Database\Query\Builder
     */
    public function applyQuery($query)
    {
        $this->setQuery($query);
        $this->process();

        return $this->_query;
    }

    /**
     * @return Collection
     */
    public function getData()
    {
        if ($this->_data !== null) return $this->_data;

        $this->process();

        return $this->_data;
    }

    /**
     * @return int
     */
    public function getItemCount()
    {
        if ($this->_itemCount !== null) return $this->_itemCount;

        $this->process();

        return $this->_itemCount;
    }

    /**
     * @return int
     */
    public function getPageCount()
    {
        if ($this->_pageCount !== null) return $this->_pageCount;

        $this->process();

        return $this->_pageCount;
    }

    public function process()
    {
        if (!$this->_query) return false;

        if ($this->isNoLimit()) {
            $this->_data = $this->_query->get();
            $this->_itemCount = count($this->_data);
            $this->_pageCount = 1;
        } else {
            // clear orders before call count()
            $this->_itemCount = $this->_query->getQuery()->getCountForPagination();
            $this->_pageCount = ceil($this->_itemCount / $this->getLimit());
            $offset = ($this->getPage() - 1) * $this->getLimit();
            $this->_data = $this->_query->offset($offset)->limit($this->getLimit())->get();
        }

        if ($this->_dataFilterCallback) {
            /** @var callable $closure */
            $closure = $this->_dataFilterCallback;
            $this->_data = $closure($this->_data);
        }

        if ($this->_itemTransformCallback) {
            /** @var callable $closure */
            $closure = $this->_itemTransformCallback;
            $this->_data = $this->_data->map($closure);
        }

        return true;
    }

    function setDataFilter(callable $callback)
    {
        $this->_dataFilterCallback = is_callable($callback) ? $callback : null;
    }

    function setItemTransform(callable $callback)
    {
        $this->_itemTransformCallback = is_callable($callback) ? $callback : null;
    }
}

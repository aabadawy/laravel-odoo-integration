<?php

namespace Aabadawy\LaravelOdooIntegration;

use Illuminate\Support\{Arr,Collection,Str};
use JetBrains\PhpStorm\ArrayShape;

class OdooBuilder
{

    protected array $allowedFilterOperators = [
        '=',
        '!=',
        '>',
        '<',
        '>=',
        '<=',
        '=?',
        'like',
        'like',
        '=like', //
        'not like',
        'ilike',
        'not ilike',
        'in',
        'not in',
        'child_of', //
        'parent_of', //
    ];

    protected $allowedLogics = [
        '&',
        '|',
        '!',
    ];

    /**
     * number of arity for each logical criteria
     * @var array
     */
    protected array  $arity = [
        '&' => 2,
        '|' => 2,
        '!' => 1,
    ];

    protected int $latestLogicalIndex = -1;

    protected array $wheres = [];

    protected array $includes = [];

    protected array $excludes = [];

    protected array $holdQueries = [];

    protected OdooConnection $connection;

    protected int|null $limit = null;

    protected string $connectionName;

    protected array $orders = [];

    protected int|null $offset = null;

    protected bool $only_count = false;

    public function __construct()
    {
        $this->setConnection();
    }

    public function setConnection(OdooConnection | null $connection = null,string $connection_name = 'default')
    {
        $this->connection = ! is_null($connection) ? $connection: (new OdooConnection($connection_name));
    }

    public function setModule(string $module)
    {
        $this->odooModule = $module;

        return $this;
    }

    /**
     * get collection of data from odoo
     * @param string|array|null $columns
     * @return Collection
     * @throws \Throwable
     */
    public function get(string | array | null $columns = null): Collection
    {
        if(! is_null($columns) && empty($this->includes))
            $this->include($columns);

        return $this->connection
            ->setModule($this->odooModule)
            ->setQueryParams($this->queryParam())
            ->get();
    }

    public function paginate(int $offset = 4250, string | array | null $columns = null)
    {
        if(! is_null($columns))
            $this->include($columns);

        $this->offset = $offset;

        return $this->connection
            ->setModule($this->odooModule)
            ->setQueryParams($this->queryParam())
            ->paginate();
    }

    public function find($id)
    {
        return $this->connection
            ->setModule($this->odooModule)
            ->setQueryParams($this->queryParam())
            ->find($id);
    }

//    public function count()
//    {
//        $this->only_count = true;
//
//        $params = $this->queryParam();
//
//        return $this->connection->get($this->odooModule,$params);
//    }

    public function limit(int $limit = 0): static
    {
        $this->limit = $this->limitIsValid($limit) ? $limit : $this->limit;

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function where(string $column, string $operator = '=', $value = null): static
    {
        if(func_num_args() == 2) {
            $this->bindWhere($column, '=', $operator);
            return $this;
        }
        $this->bindWhere($column,$operator,$value,',');

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function logicalNot(string $column, string $operator = '=', $value = null): static
    {
        $this->logicalWhere([$column,$operator,$value],'!');

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function logicalAnd(array $first_condition, array $second_condition): static
    {
        return $this->logicalWhere($first_condition,$second_condition);
    }

    /**
     * @throws \Exception
     */
    public function logicalOr(array $first_condition, array $second_condition): static
    {
        return $this->logicalWhere($first_condition,$second_condition,'|');
    }

    /**
     * @param array $first_condition
     * @param array|string $second_condition
     * @param string $logical_operator
     * @return $this
     * @throws \Exception
     */
    protected function logicalWhere(array $first_condition,array|string $second_condition = [],string $logical_operator = '&'): static
    {
        if(is_string($second_condition))
            return $this->logicalWhere($first_condition,[],$second_condition);

        $params = array_values(array_slice($first_condition,0,3));

        $params[] = $logical_operator;

        $params[] = ++$this->latestLogicalIndex;

        $this->bindWhere(...$params);

        if(empty($second_condition))
            return $this;

        $params = array_values(array_slice($second_condition,0,3));

        $params[] = $logical_operator;

        $params[] = $this->latestLogicalIndex;

        $this->bindWhere(...$params);

        return $this;
    }

    public function orderBy(string $column,string $dir = 'asc'): static
    {
        $this->orders[] = "$column $dir";

        return $this;
    }

    public function include(string | array $columns = "id"): static
    {
        return $this->select($columns);
    }

    public function exclude(string | array $columns = []): static
    {
        return $this->select($columns,'excludes');
    }

    protected function select(string | array $columns = "id",string $for = 'includes'): static
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        $includes = $columns;

        $this->$for =  array_map(function($value) use($includes) {
            $for_relation = is_array($value) ? array_search($value,$includes): null;

            return $this->bindSelect($value,$for_relation);
        }
            ,$includes);


        return $this;
    }

    /**
     * @return array
     * @throws \Throwable
     */
    #[ArrayShape(['filters' => "string", 'include_fields' => "string", 'exclude_fields' => "string",'order' => 'string','limit' => 'int','offset' => 'int','count' => 'bool'])]
    public function queryParam():array
    {
        $this->builQueryFilter($this->wheres);

        $filters = implode(',',$this->holdQueries);

        $this->resetQueries();

        $this->buildSelectQuery($this->includes);

        $includes = implode(',',$this->holdQueries);

        $this->resetQueries();

        $this->buildSelectQuery($this->excludes);

        $excludes = implode(',',$this->holdQueries);

        $this->resetQueries();

        $queryParams = [
            'filters' => Str::of($filters)->start('[')->append(']'),
            'include_fields' => Str::of($includes)->start('[')->append(']'),
            'exclude_fields' => Str::of($excludes)->start('[')->append(']'),
            'order'          => implode(',',$this->orders),
            'limit'          => $this->limit ?: null,
            'count'          =>  $this->only_count ?: null
        ];
        if(! is_null($this->offset))
            $queryParams['offset'] = $this->offset;

        return $queryParams;
    }

    protected function bindSelect(array|string &$includes,string|int $for_relation = null)
    {
        // that will bind all the current module entities which
        // doesn't belong to any nested relation
        if(is_string($includes) && is_null($for_relation)){
            return  "'{$includes}'";
        }

        // start with looping on all relational in current selection
        foreach ($includes as $key => &$include) {
            if(is_string($include)){
                //overwrite current entity of a relation to be ready for bind
                $include = "'{$include}'";
                continue;
            }
            if(is_string($key) && is_array($include)) {
                //start bind current  module's related modules
                $this->bindSelect($include, $key);
            }
        }

        $nested_selected = $includes;

        $number_of_nested_relations = 0;

        foreach ($nested_selected as $key => $nested) {
            if(is_array($nested)) {
                // count total number of nested relations
                // to be used when close the brackets ')'
                ++$number_of_nested_relations;
                //remove all relational selected as it's will be ambiguous
                unset($nested_selected[$key]);
            }
        }

        unset($nested_selected['group_by']);

        // build full query for nested relation entities
        $query = implode(',',$nested_selected);

        $includes['group_by'] = "('$for_relation',(";

        // set default brackets when only has one nested relation
        $query_end = $number_of_nested_relations == 0 ? "))": "";

        // append current nested relation for includes
        // to be used when build the selection query param
        $includes['nested_result'] = "{$includes['group_by']}{$query}{$query_end}";

        // append current total number of nested relations
        // to be used when build the selection query param
        $includes['number_of_nested_relations'] = $number_of_nested_relations;

        return $includes;
    }

    protected function bindWhere(string $column,string $operator,$value,string $logic_operation = ',',int|null $logic_index = null)
    {
        /*
         * first we make sure the passed operation and logic_operation are valid
         */
        throw_unless(in_array($operator,$this->allowedFilterOperators),new \Exception("this operator '$operator' is invalid"));

        if(in_array($operator,['in','not in'])) {
            throw_unless(is_array($value), new \Exception("value must be array when use operator '$operator'"));

            $values = array_map(fn($element) => "'{$element}'",$value);

            $value = "(" . implode(',',$values) . ")";
        }
        else
            $value = "'$value'";

        // build current passed query param
        $query = "('$column', '$operator', $value)";

        $index = $logic_operation.$logic_index;

        /*
         * here we check current logical operation is valid
         * which means every logical operation is equals its max arity number
         */
        if(array_key_exists($index,$this->wheres) && is_array($this->wheres[$index]) && array_key_exists('group_by',$this->wheres[$index]) && $this->arityReachedMax($this->wheres[$index]))
            throw new \Exception('arity reached the max for this');

        $this->wheres[$index][] = $query;

        /*
         * when found any logic operation except ',' that's mean it will need custom
         * cast when bind the query param
         * so we started catch it to be handled
         */
        if($logic_operation != ',')
            $this->wheres[$index]['group_by']= "'$logic_operation' ";

        return $this->wheres;
    }



    public function buildSelectQuery(array $selects)
    {
        foreach ($selects as $key => $select) {
            if (is_string($select) && !array_key_exists('nested_result',$selects)) {
                $this->holdQueries[] = $select;
                continue;
            }
            if(is_array($select)){
                if(array_key_exists('nested_result',$select))
                    $this->holdQueries[] = $select['nested_result'];
                $this->buildSelectQuery($select);
            }

            if(is_array($select) && array_key_exists('number_of_nested_relations',$select) && $num_of_nested_relations = $select['number_of_nested_relations']) {
                $last_index = array_key_last($this->holdQueries);
                $this->holdQueries[$last_index] = $this->holdQueries[$last_index] . str_repeat(")", $num_of_nested_relations + 1);
            }
        }
    }

    /**
     * @param array $wheres
     * @return void
     * @throws \Throwable
     */
    protected function builQueryFilter(array $wheres)
    {
        foreach ($wheres as $where) {
            if(is_string($where)){
                $this->holdQueries[] = $where;
                continue;
            }
            if(is_array($where) && ! array_key_exists('group_by',$where)){
                $this->builQueryFilter($where);
                continue;
            }

            $nested_wheres = $where;

            unset($nested_wheres['group_by']);

            $query = implode(',',$nested_wheres);

            $this->holdQueries[] = "{$where['group_by']},{$query}";
        }
    }

    /**
     * @param array $where
     * @return bool
     */
    protected function arityReachedMax(array $where)
    {
        $logical_operator = Str::of($where['group_by'])->remove(["'", " "])->toString();

        return count(Arr::except($where, 'group_by')) == $this->arity[$logical_operator];
    }

    protected function resetQueries()
    {
        $this->holdQueries = [];
    }

    protected function limitIsValid(int $limit):bool
    {
        return $limit >= 0;
    }

    public function __toString(): string
    {
        return json_encode($this->queryParam());
    }
}

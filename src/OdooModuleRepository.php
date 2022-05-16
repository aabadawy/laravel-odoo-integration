<?php

namespace Aabadawy\LaravelOdooIntegration;

use Illuminate\Support\Collection;

class OdooModuleRepository
{
    protected OdooModule $module;

    protected string $odooModuleName;

    public function __construct(protected OdooBuilder $odooBuilder)
    {

    }

    /**
     * @throws \Throwable
     */
    public function get(string | array $columns = ['id']): Collection
    {
        $response = $this->odooBuilder
            ->setModule($this->odooModuleName)
            ->get($columns);

        return $this->getModules($response);
    }

    /**
     * @param $id
     * @return null|OdooModule
     */
    public function find($id): ?OdooModule
    {
        $response = $this->odooBuilder->setModule($this->odooModuleName)->find($id);

        return $this->module->newInstance($response->toArray());
    }

    public function create(array $data = [])
    {
        $module = $this->module->newInstance($data);

        $module->id = $this->odooBuilder->setModule($this->odooModuleName)->create($data);

        $module->setWasRecentlyCreated(true);

        // todo fire events when start creating and when creat finished
        return $module;
    }

    public function update(array $data = []):bool
    {
        $result = $this->odooBuilder->setModule($this->odooModuleName)->update($this->module->id,$data);

        $this->module->fill($data);
        
        return $result;
    }

    /**
     * @throws \Exception
     */
    public function where(string $column, $operator, $value = null): static
    {
        $this->odooBuilder->where(...func_get_args());

        return $this;
    }


    public function whereNot(string $column,$value = null): static
    {
        return $this->where($column,'!=',$value);
    }

    public function whereIn(string $column,array $value): static
    {
        return $this->where($column,'in',$value);
    }

    public function whereNotIn(string $column,array $value): static
    {
        return $this->where($column,'not in',$value);
    }

    public function whereGreaterThan(string $column,$value = null): static
    {
        return $this->where($column,'>',$value);
    }

    public function whereSmallerThan(string $column,$value = null): static
    {
        return $this->where($column,'<',$value);
    }

    public function whereGreaterThanOrEqual(string $column,$value = null): static
    {
        return $this->where($column,'>=',$value);
    }

    public function whereSmallerThanOrEqual(string $column,$value = null): static
    {
        return $this->where($column,'<=',$value);
    }

    public function whereUnsetOrEquals(string $column, $value = null): static
    {
        return $this->where($column,'=?',$value);
    }

    public function whereLike(string $column,array $value): static
    {
        return $this->where($column,'like',$value);
    }

    public function whereNotLike(string $column,array $value): static
    {
        return $this->where($column,'not like',$value);
    }

    public function whereIlike(string $column,$value = null): static
    {
        return $this->where($column,'ilike',$value);
    }

    public function whereNotIlike(string $column,$value = null): static
    {
        return $this->where($column,'not ilike',$value);
    }

    public function include(string | array $columns = ['id']): static
    {
        $this->odooBuilder->include($columns);

        return $this;
    }

    public function exclude(string | array $columns = ['id']): static
    {
        $this->odooBuilder->exclude($columns);

        return $this;
    }

    public function limit(int $limit): static
    {
        $this->odooBuilder->limit($limit);

        return $this;
    }

    public function orderBy(string $column = 'id', string $dir = 'asc'): static
    {
        $this->odooBuilder->orderBy($column,$dir);

        return $this;
    }

    public function orderByDesc(string $column = 'id'): static
    {
        return $this->orderBy($column,'desc');
    }

    /**
     * @param OdooModule $module
     * @return self
     */
    public function setModule(OdooModule $module): static
    {
        $this->module = $module;

        return $this;
    }

    /**
     * @param string $odooModuleName
     * @return self
     */
    public function setOdooModuleName(string $odooModuleName): static
    {
        $this->odooModuleName = $odooModuleName;

        return $this;
    }

    public function getModules(Collection $collection):Collection
    {
        return collect($collection['results'])->map(fn($module) => $this->module->newInstance($module))->values();
    }
}

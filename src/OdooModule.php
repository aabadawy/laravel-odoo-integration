<?php

namespace Aabadawy\LaravelOdooIntegration;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Traits\ForwardsCalls;

abstract class OdooModule implements Arrayable
{
    use ForwardsCalls;

    // TODO MAKE odoo collection

    protected string $odooModuleName;

    protected OdooModuleRepository | null $odooRepo = null;

    protected array $attributes = [];

    /**
     * detremine current module had just been created
     * @var bool
     */
    protected bool $wasRecentlyCreated = false;

    public function __construct()
    {
        $wasReacntr;
        $this->odooModuleName = $this->moduleName();
    }

    /**
     * define the odoo module name
     * @return string
     */
    abstract public function moduleName():string;

    public static function all(string | array $columns = 'id')
    {
        return static::query()->get($columns);
    }

    public function setWasRecentlyCreated(bool $justCreated = false)
    {
        $this->wasRecentlyCreated = $justCreated;
    }

    public function justCreated():bool
    {
        return $this->wasRecentlyCreated;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function newQuery()
    {
        return $this->getOdooRepo();
    }

    /**
     * @param OdooModuleRepository|null $odooRepo
     */
    public function setOdooRepo(?OdooModuleRepository $odooRepo): void
    {
        $this->odooRepo = $odooRepo;
    }

    /**
     * @return OdooModuleRepository|null
     */
    public function getOdooRepo(): ?OdooModuleRepository
    {
        if(is_null($this->odooRepo))
            $this->odooRepo = (new OdooModuleRepository((new OdooBuilder())))
                ->setModule($this)
            ->setOdooModuleName($this->odooModuleName);

        return $this->odooRepo
            ->setModule($this)
            ->setOdooModuleName($this->odooModuleName);
    }

    public function update(array $data):bool
    {
        if(! $this->existInOdoo())
            return false;

        return $this->newQuery()->update($data);
    }

    protected function existInOdoo()
    {
        return ! is_null($this->id);
    }

    /**
     * @return OdooModuleRepository|null
     */
    public static function query()
    {
        return (new static())->newQuery();
    }

    public function newInstance(array $attributes):static
    {
        $instance = (new static());

        $instance->fill($attributes);

        return $instance;
    }

    /**
     * fill current entity attributes
     * @return void
     */
    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $attribute) {
            $this->__set($key,$attribute);
        }
    }

    public function toArray()
    {
        return $this->attributes;
    }

    /**
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->forwardCallTo($this->newQuery(),$method,$parameters);
    }

    /**
     * Handle dynamic static method calls into the model.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public static function __callStatic(string $method, array $parameters)
    {
        return (new static)->$method(...$parameters);
    }

    public function __set(string $name, $value): void
    {
        // todo prevent client from fill id for new instance
        $this->attributes[$name] = $value;
    }

    /**
     * @param string $name
     * @return mixed|void
     */
    public function __get(string $name)
    {
        if(! array_key_exists($name,$this->attributes))
            return ;
        return $this->attributes[$name];
    }
}

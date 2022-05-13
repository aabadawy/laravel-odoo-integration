<?php

namespace Aabadawy\LaravelOdooIntegration;

use App\Common\Services\Odoo\Exceptions\ObjectNotFoundException;
use App\Common\Services\Odoo\Exceptions\QueryParamConflictException;
use App\Common\Services\Odoo\Exceptions\ServerOdooException;
use App\Domain\User\Entities\User;
use \Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class OdooConnection
{

    protected string $url;

    protected string $user_id;

    protected string $token;

    protected int $default_limit = 25; // TODO set default limit in config file

    protected string $module;

    protected array $queryParams = [];

    /**
     * @throws \Exception
     */
    public function __construct(string $connection_name)
    {
        $configuration = config("odoo-integration.$connection_name");

        if(! $this->configurationValid($configuration))
            throw new \Exception('invalid configuration for connection name '. $connection_name);

        $this->setToken($configuration['token']);
        $this->setUrl($configuration['url']);
        $this->setUserId($configuration['user_id']);
    }

    /**
     * @throws \Exception
     */
    public function get(): Collection
    {
        $response = $this->connect()
            ->get($this->module,$this->queryParams);

        return $this->parseResponse($response);
    }

    /**
     * @param $id
     * @return Collection
     * @throws \Exception
     */
    public function find($id)
    {
        $response = $this->connect()
            ->get($this->module ."/$id",$this->queryParams);

        return $this->parseResponse($response);
    }

    public function paginate()
    {
        $response = $this->connect()
            ->get($this->module,$this->queryParams);

        return $response;
    }

    public function post()
    {

    }

    public function put()
    {

    }

    public function delete()
    {

    }
    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @param string $user_id
     */
    public function setUserId(string $user_id): void
    {
        $this->user_id = $user_id;
    }


    /**
     * @param string $token
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * @param string $module
     * return static
     */
    public function setModule(string $module): static
    {
        $this->module = $module;
        return $this;
    }

    /**
     * @param array $queryParams
     * return static
     */
    public function setQueryParams(array $queryParams): static
    {
        $this->queryParams = $queryParams;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getUserId(): string
    {
        return $this->user_id;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function getModule(): string
    {
        return $this->module;
    }

    /**
     * @return array
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * @param int $default_limit
     */
    public function setDefaultLimit(int $default_limit): void
    {
        $this->default_limit = $default_limit;
    }

    /**
     * @return int
     */
    public function getDefaultLimit(): int
    {
        return $this->default_limit;
    }

    protected function connect()
    {
        return Http::withHeaders(
            [
                'Content-Type' => 'text/html',
                'Access-Token' => $this->token,
            ]
        )->withOptions(['cookies' => false])
            ->baseUrl($this->url);
    }

    protected function limitIsValid(int $limit):bool
    {
        return $limit > 0;
    }

    protected function configurationValid(array | null $data):bool
    {
        return is_array($data) &&
            $this->configureKeyIsValid('url',$data) &&
            $this->configureKeyIsValid('token',$data) &&
            $this->configureKeyIsValid('user_id',$data);
    }

    protected function configureKeyIsValid(string $key,array $data)
    {
        return array_key_exists($key,$data) && ! empty($data[$key]);
    }

    protected function parseResponse(Response $response)
    {
        if($response->clientError()){
            $this->throwException($response->status(),$response->collect()['error_descrip']);
        }

        if($response->serverError()){
            $this->throwException($response->status());
        }

        return $response->collect();
    }

    /**
     * @throws ObjectNotFoundException
     * @throws QueryParamConflictException
     * @throws ServerOdooException
     */
    protected function throwException(int $odoo_exception_status = 500, string $odo_exception_message = 'odoo internal server error')
    {
        $inputs = [$odoo_exception_status,$odo_exception_message,$this->module,$this->url,$this->queryParams];

        throw match($odoo_exception_status) {
            ResponseAlias::HTTP_CONFLICT            => (new QueryParamConflictException(...$inputs)),
            ResponseAlias::HTTP_NOT_FOUND           => (new ObjectNotFoundException(...$inputs)),
            default                                 => (new ServerOdooException(...$inputs)),
        };
    }
}

<?php

namespace Aabadawy\LaravelOdooIntegration\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\{Request,Response};

abstract class OdooException extends \Exception
{
    public function __construct(
        protected int $odooExceptionStatus,
        protected string $odooExceptionMessage,
        protected string $module,
        protected string $mainUrl,
        protected array $queryParams,
    )
    {
        $message = empty($message) ? $this->buildMessage() : $message;

        parent::__construct($message);
    }


    /**
     * Render the exception into an HTTP response.
     *
     * @param  Request  $request
     * @return Response | JsonResponse
     */
    abstract public function render(Request $request): Response|JsonResponse;

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

    public function getOdooExceptionStatus():int
    {
        return $this->odooExceptionStatus;
    }

    /**
     * @return string
     */
    protected function getQueryParamsToString(): string
    {
        return (string) json_encode($this->getQueryParams());
    }

    public function buildMessage(): string
    {
        return "Odoo Exception : ({$this->getOdooExceptionStatus()}) Message:{$this->odooExceptionMessage} happen in module '{$this->getModule()}' when send {$this->mainUrl} with queries: {$this->getQueryParamsToString()}";
    }
}

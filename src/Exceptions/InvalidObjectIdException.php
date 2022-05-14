<?php

namespace aabadawy\LaravelOdooIntegration\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InvalidObjectIdException extends OdooException
{

    public function render(Request $request): Response|JsonResponse
    {
        $status = Response::HTTP_BAD_REQUEST;

        if ($request->wantsJson()) {
            return response()->json(['message'  => "passed id doesn't compatible wit odoo id definition, try to pass int instead ?"], $status);
        }

        return response()->view("errors.400", [], $status);
    }
}

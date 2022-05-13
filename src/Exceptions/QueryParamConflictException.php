<?php

namespace Aabadawy\LaravelOdooIntegration\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class QueryParamConflictException extends OdooException
{
    /**
     * Render the exception into an HTTP response.
     *
     * @param  Request  $request
     * @return Response | \Illuminate\Http\JsonResponse
     */
    public function render(Request $request):Response | JsonResponse
    {
        $status = Response::HTTP_CONFLICT;

        if ($request->wantsJson()) {
            return response()->json(['message'  => $this->message], $status);
        }

        return response()->view("errors.400", [], $status);
    }
}

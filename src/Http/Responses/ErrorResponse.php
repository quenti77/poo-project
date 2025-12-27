<?php

namespace Tuto\Http\Responses;

use JsonException;
use ReflectionException;
use Tuto\Error\ErrorDetails;

class ErrorResponse extends AbstractResponse
{
    /**
     * @param ErrorDetails $errorDetails
     * @param string $httpVersion
     * @throws JsonException
     * @throws ReflectionException
     */
    public function __construct(
        ErrorDetails $errorDetails,
        string $httpVersion = 'HTTP/2',
    ) {
        $response = request()->isJson()
            ? $this->renderJson($errorDetails)
            : $this->renderHtml($errorDetails);

        parent::__construct($response->getBody(), $errorDetails->httpCode, $response->getHeaders(), $httpVersion);
    }

    /**
     * @param ErrorDetails $errorDetails
     * @return JsonResponse
     * @throws JsonException
     */
    private function renderJson(ErrorDetails $errorDetails): JsonResponse
    {
        $data = [
            'error' => true,
            'httpCode' => $errorDetails->httpCode->value,
            'message' => 'An error occurred during this request',
        ];

        if (container()->getWithoutError('app.debug', true)) {
            $data = array_merge($data, $errorDetails->toArray());
        }

        return json($data);
    }

    /**
     * @param ErrorDetails $errorDetails
     * @return ViewResponse
     * @throws ReflectionException
     */
    private function renderHtml(ErrorDetails $errorDetails): ViewResponse
    {
        $isDebug = container()->getWithoutError('app.debug', true);
        $data = ['isDebug' => $isDebug, 'error' => $errorDetails];

        return view('errors/internal', $data, null);
    }
}
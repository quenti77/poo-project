<?php

namespace Tuto\Http\Responses;

use ReflectionException;

class ViewResponse extends AbstractResponse
{
    /**
     * @throws ReflectionException
     */
    public function __construct(
        string $viewPath,
        array $data = [],
        HttpCode $code = HttpCode::OK,
        string|null $layoutPath = null,
        array $headers = [],
        string $httpVersion = 'HTTP/2',
    ) {
        $body = $this->renderView($viewPath, $data, $layoutPath);

        parent::__construct($body, $code, $headers, $httpVersion);
    }

    /**
     * @param string $viewPath
     * @param array $data
     * @param string|null $layoutPath
     * @return string
     * @throws ReflectionException
     */
    private function renderView(string $viewPath, array $data, string|null $layoutPath): string
    {
        extract($data);

        $auth = $_SESSION['auth'] ?? null;
        // TODO: un-comment this line
        // $router = router();

        ob_start();
        require ROOT . "/views/{$viewPath}.php";
        $content = ob_get_clean();

        if ($layoutPath) {
            ob_start();
            require ROOT . "/views/layouts/{$layoutPath}.php";
            $content = ob_get_clean();
        }

        return $content;
    }
}
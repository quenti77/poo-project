<?php

namespace Tuto\Http\Response;

use ReflectionException;

class ViewResponse extends AbstractResponse
{
    /**
     * @throws ReflectionException
     */
    public function __construct(
        int $code,
        string $viewPath,
        array $data,
        string|null $layoutPath = null,
        string $httpVersion = 'HTTP/1.1',
        array $headers = [],
    ) {
        $body = $this->renderView($viewPath, $data, $layoutPath);

        parent::__construct($code, $httpVersion, $headers, $body);
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
        $router = router();

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

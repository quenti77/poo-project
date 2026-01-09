<?php
/**
 * Vue pour l'affichage du dump de variables
 *
 * Variables disponibles:
 * @var array $render Tableau avec 'header' et 'items'
 * @var bool $withScript Si true, inclut le CSS et JS
 */
if ($withScript) {
    /**
     * @param array $item
     * @param int $depth
     * @return string
     */
    function renderDumpItem(array $item, int $depth = 0): string
    {
        $type = strtolower($item['type'] ?? 'unknown');

        return match ($type) {
            'null' => renderNull($item),
            'boolean' => renderBoolean($item),
            'integer', 'double' => renderNumber($item),
            'string' => renderString($item),
            'array' => renderArray($item, $depth),
            'object' => renderObject($item, $depth),
            'enum' => renderEnum($item),
            'closure', 'function', 'method', 'invokable' => renderCallable($item, $depth),
            default => renderUnknown($item),
        };
    }

    function renderNull(array $item): string
    {
        return '<span class="sf-dump-type sf-dump-type-null">null</span>';
    }

    function renderBoolean(array $item): string
    {
        $value = $item['value'] ? 'true' : 'false';
        return '<span class="sf-dump-type sf-dump-type-boolean">bool</span>' .
                '<span class="sf-dump-boolean">' . $value . '</span>';
    }

    function renderNumber(array $item): string
    {
        $type = strtolower($item['type']);
        $typeLabel = $type === 'integer' ? 'int' : 'float';
        return '<span class="sf-dump-type sf-dump-type-' . $type . '">' . $typeLabel . '</span>' .
                '<span class="sf-dump-number">' . htmlspecialchars($item['value']) . '</span>';
    }

    function renderString(array $item): string
    {
        $originalValue = $item['value'];
        $length = strlen($originalValue);

        // Chaînes de plus de 250 caractères sont collapsables
        if ($length > 250) {
            $preview = htmlspecialchars(substr($originalValue, 0, 250));
            $fullValue = htmlspecialchars($originalValue);

            $html = '<div class="sf-dump-line">';
            $html .= '<span class="sf-dump-toggle collapsed"></span>';
            $html .= '<span class="sf-dump-type sf-dump-type-string">string</span>';
            $html .= '<span class="sf-dump-meta">(' . $length . ')</span> ';
            $html .= '<span class="sf-dump-string">"' . $preview . '..."</span>';
            $html .= '<div class="sf-dump-children collapsed">';
            $html .= '<div class="sf-dump-line">';
            $html .= '<span class="sf-dump-string">"' . $fullValue . '"</span>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';

            return $html;
        }

        $value = htmlspecialchars($originalValue);
        return '<span class="sf-dump-type sf-dump-type-string">string</span>' .
                '<span class="sf-dump-meta">(' . $length . ')</span> ' .
                '<span class="sf-dump-string">"' . $value . '"</span>';
    }

    function renderArray(array $item, int $depth): string
    {
        $total = $item['total'] ?? 0;
        $items = $item['items'] ?? [];

        if ($total === 0) {
            return '<span class="sf-dump-type sf-dump-type-array">array</span>' .
                    '<span class="sf-dump-meta">(empty)</span>';
        }

        $html = '<div class="sf-dump-line">';
        $html .= '<span class="sf-dump-toggle collapsed"></span>';
        $html .= '<span class="sf-dump-type sf-dump-type-array">array</span>';
        $html .= '<span class="sf-dump-meta">(' . $total . ')</span>';
        $html .= '<div class="sf-dump-children collapsed">';

        foreach ($items as $arrayItem) {
            $key = $arrayItem['key'] ?? [];
            $value = $arrayItem['value'] ?? [];

            $html .= '<div class="sf-dump-line">';
            $html .= '<span class="sf-dump-key">';
            $html .= renderDumpItem($key, $depth + 1);
            $html .= '</span>';
            $html .= '<span class="sf-dump-arrow">→</span>';
            $html .= renderDumpItem($value, $depth + 1);
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    function renderObject(array $item, int $depth): string
    {
        $class = $item['class'] ?? 'object';
        $shortName = $item['shortName'] ?? $class;
        $properties = $item['properties'] ?? [];

        $html = '<div class="sf-dump-line">';

        if (!empty($properties)) {
            $html .= '<span class="sf-dump-toggle collapsed"></span>';
        }

        $html .= '<span class="sf-dump-type sf-dump-type-object">object</span>';
        $html .= '<span class="sf-dump-class">' . htmlspecialchars($shortName) . '</span>';
        $html .= '<span class="sf-dump-meta">(' . count($properties) . ')</span>';

        if (!empty($properties)) {
            $html .= '<div class="sf-dump-children collapsed">';

            foreach ($properties as $propName => $prop) {
                $visibility = $prop['visibility'] ?? 'public';
                $visSymbol = match($visibility) {
                    'public' => '+',
                    'protected' => '#',
                    'private' => '-',
                    default => '?',
                };

                $html .= '<div class="sf-dump-line">';
                $html .= '<span class="sf-dump-visibility">' . $visSymbol . '</span>';
                $html .= '<span class="sf-dump-property">' . htmlspecialchars($propName) . '</span>';

                if (isset($prop['value'])) {
                    $html .= '<span class="sf-dump-arrow">:</span>';
                    $html .= renderDumpItem($prop['value'], $depth + 1);
                } elseif (!($prop['isInitialized'] ?? true)) {
                    $html .= '<span class="sf-dump-meta"> (uninitialized)</span>';
                }

                $html .= '</div>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    function renderEnum(array $item): string
    {
        $name = $item['name'] ?? '';
        $value = $item['value'] ?? '';

        $html = '<span class="sf-dump-type sf-dump-type-enum">enum</span>';
        $html .= '<span class="sf-dump-class">' . htmlspecialchars($name) . '</span>';

        if ($value !== '') {
            $html .= '<span class="sf-dump-arrow">:</span>';
            $html .= '<span class="sf-dump-value">' . htmlspecialchars($value) . '</span>';
        }

        return $html;
    }

    function renderCallable(array $item, int $depth): string
    {
        $type = $item['type'] ?? 'callable';

        $html = '<div class="sf-dump-line">';

        if (isset($item['parameters']) && !empty($item['parameters'])) {
            $html .= '<span class="sf-dump-toggle collapsed"></span>';
        }

        $html .= '<span class="sf-dump-type sf-dump-type-' . $type . '">' . $type . '</span>';

        if (isset($item['class'])) {
            $html .= '<span class="sf-dump-class">' . htmlspecialchars($item['class']) . '</span>';
            if (isset($item['method'])) {
                $html .= '<span>::' . htmlspecialchars($item['method']) . '</span>';
            }
        } elseif (isset($item['name'])) {
            $html .= '<span class="sf-dump-class">' . htmlspecialchars($item['name']) . '</span>';
        }

        $html .= '<span>()</span>';

        if (isset($item['returnType'])) {
            $html .= '<span class="sf-dump-arrow">:</span>';
            $html .= '<span class="sf-dump-meta">' . htmlspecialchars($item['returnType']) . '</span>';
        }

        if (isset($item['parameters']) && !empty($item['parameters'])) {
            $html .= '<div class="sf-dump-children collapsed">';

            foreach ($item['parameters'] as $param) {
                $html .= '<div class="sf-dump-line">';
                $html .= '<span class="sf-dump-property">$' . htmlspecialchars($param['name']) . '</span>';
                $html .= '<span class="sf-dump-arrow">:</span>';
                $html .= '<span class="sf-dump-meta">' . htmlspecialchars($param['type']) . '</span>';

                if (isset($param['default'])) {
                    $html .= ' <span class="sf-dump-meta">=</span> ';
                    $html .= renderDumpItem($param['default'], $depth + 1);
                }

                $html .= '</div>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    function renderUnknown(array $item): string
    {
        return '<span class="sf-dump-type">unknown</span>';
    }
    ?>
<script>
(function() {
    if (window.sfDumpInitialized) return;
    window.sfDumpInitialized = true;

    // Injecter le CSS dans le head
    if (!document.getElementById('sf-dump-styles')) {
        const style = document.createElement('style');
        style.id = 'sf-dump-styles';
        style.textContent = `
            .sf-dump {
                font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, Courier, monospace;
                font-size: 12px;
                line-height: 1.5;
                background: #fff;
                border: 1px solid #e3e3e3;
                border-radius: 4px;
                margin: 15px;
                padding: 0;
                color: #222;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .sf-dump-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: #fff;
                padding: 12px 16px;
                border-radius: 4px 4px 0 0;
                font-size: 11px;
                font-weight: 600;
                letter-spacing: 0.5px;
            }
            .sf-dump-header strong {
                color: #fff;
                font-weight: 700;
            }
            .sf-dump-body {
                padding: 16px;
            }
            .sf-dump-item {
                margin-bottom: 16px;
            }
            .sf-dump-item:last-child {
                margin-bottom: 0;
            }
            .sf-dump-type {
                display: inline-block;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 10px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-right: 6px;
            }
            .sf-dump-type-null {
                background: #cbd5e0;
                color: #2d3748;
            }
            .sf-dump-type-boolean {
                background: #c3dafe;
                color: #2c5282;
            }
            .sf-dump-type-integer,
            .sf-dump-type-double {
                background: #bee3f8;
                color: #2c5282;
            }
            .sf-dump-type-string {
                background: #c6f6d5;
                color: #22543d;
            }
            .sf-dump-type-array {
                background: #fed7d7;
                color: #742a2a;
            }
            .sf-dump-type-object {
                background: #feebc8;
                color: #7c2d12;
            }
            .sf-dump-type-enum {
                background: #e9d8fd;
                color: #44337a;
            }
            .sf-dump-type-closure,
            .sf-dump-type-function,
            .sf-dump-type-method,
            .sf-dump-type-invokable {
                background: #fef3c7;
                color: #78350f;
            }
            .sf-dump-value {
                display: inline-block;
                color: #2d3748;
            }
            .sf-dump-string {
                color: #0086b3;
            }
            .sf-dump-number {
                color: #0086b3;
                font-weight: 600;
            }
            .sf-dump-boolean {
                color: #0086b3;
                font-weight: 600;
            }
            .sf-dump-null {
                color: #999;
                font-style: italic;
            }
            .sf-dump-meta {
                color: #999;
                font-size: 11px;
            }
            .sf-dump-key {
                color: #a71d5d;
                font-weight: 600;
            }
            .sf-dump-class {
                color: #a71d5d;
                font-weight: 600;
            }
            .sf-dump-property {
                color: #795da3;
            }
            .sf-dump-visibility {
                color: #795da3;
                font-weight: 600;
                margin-right: 4px;
            }
            .sf-dump-toggle {
                cursor: pointer;
                user-select: none;
                display: inline-block;
                width: 12px;
                height: 12px;
                line-height: 12px;
                text-align: center;
                margin-right: 4px;
            }
            .sf-dump-toggle:before {
                content: "▼";
                font-size: 8px;
                color: #999;
            }
            .sf-dump-toggle.collapsed:before {
                content: "▶";
            }
            .sf-dump-children {
                margin-left: 24px;
                margin-top: 8px;
                border-left: 2px solid #e3e3e3;
                padding-left: 12px;
            }
            .sf-dump-children.collapsed {
                display: none;
            }
            .sf-dump-line {
                margin: 4px 0;
            }
            .sf-dump-arrow {
                color: #999;
                margin: 0 6px;
            }
        `;
        document.head.appendChild(style);
    }

    document.addEventListener("click", function(e) {
        if (e.target.classList.contains("sf-dump-toggle")) {
            e.preventDefault();
            const parent = e.target.closest('.sf-dump-line') || e.target.parentElement;
            const children = parent.querySelector(".sf-dump-children");
            if (children) {
                children.classList.toggle("collapsed");
                e.target.classList.toggle("collapsed");
            }
        }
    });
})();
</script>
<?php } ?>

<div class="sf-dump">
    <div class="sf-dump-header">
        <strong>File:</strong> <?= htmlspecialchars($render['header']['file']) ?>
        <strong>Line:</strong> <?= htmlspecialchars($render['header']['line']) ?>
    </div>
    <div class="sf-dump-body">
        <?php foreach ($render['items'] as $index => $item): ?>
            <div class="sf-dump-item">
                <?= renderDumpItem($item, 0) ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

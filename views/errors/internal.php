<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= trans('framework.errors.title', context: ['type' => htmlspecialchars($error->type ?? 'unknown')]) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            line-height: 1.6;
        }

        .error-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 900px;
            width: 100%;
            overflow: hidden;
            animation: slideIn 0.4s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .error-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .error-icon {
            font-size: 72px;
            margin-bottom: 20px;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .error-header h1 {
            font-size: 32px;
            margin-bottom: 12px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .error-header .http-code {
            font-size: 20px;
            opacity: 0.95;
            font-weight: 500;
        }

        .error-body {
            padding: 40px 30px;
        }

        .error-message {
            background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
            border-left: 5px solid #f5576c;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(245, 87, 108, 0.1);
        }

        .error-message h2 {
            color: #c53030;
            font-size: 20px;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .error-message p {
            color: #742a2a;
            line-height: 1.8;
            font-family: 'Monaco', 'Menlo', 'Courier New', monospace;
            word-break: break-word;
            font-size: 15px;
        }

        .production-message {
            text-align: center;
            color: #4a5568;
            line-height: 2;
            padding: 20px 0;
        }

        .production-message h2 {
            color: #2d3748;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .production-message p {
            font-size: 16px;
            margin-bottom: 10px;
        }

        .production-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .debug-section {
            margin-top: 10px;
        }

        .toggle-details {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            box-shadow: 0 4px 14px rgba(102, 126, 234, 0.4);
        }

        .toggle-details:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .toggle-details:active {
            transform: translateY(0);
        }

        .error-details {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease-out;
        }

        .error-details.show {
            max-height: 2000px;
            transition: max-height 0.6s ease-in;
        }

        .detail-section {
            margin-top: 25px;
            background: #f7fafc;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #e2e8f0;
        }

        .detail-section h3 {
            color: #2d3748;
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .detail-section h3::before {
            content: '▸';
            font-size: 16px;
            color: #667eea;
        }

        .detail-content {
            background: #ffffff;
            padding: 18px;
            border-radius: 6px;
            font-family: 'Monaco', 'Menlo', 'Courier New', monospace;
            font-size: 13px;
            color: #2d3748;
            overflow-x: auto;
            border: 1px solid #e2e8f0;
            line-height: 1.8;
        }

        .detail-content strong {
            color: #4a5568;
            font-weight: 600;
        }

        .trace-item {
            padding: 15px;
            border-bottom: 2px solid #edf2f7;
            margin-bottom: 12px;
            background: #fafafa;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .trace-item:hover {
            background: #f7fafc;
            transform: translateX(4px);
        }

        .trace-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .trace-number {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
            margin-right: 8px;
        }

        .trace-file {
            color: #667eea;
            font-weight: 600;
        }

        .trace-line {
            color: #f5576c;
            font-weight: 700;
        }

        .trace-function {
            color: #4a5568;
            margin-top: 8px;
            padding-left: 24px;
            font-style: italic;
        }

        .error-footer {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 25px 30px;
            text-align: center;
            color: #718096;
            font-size: 14px;
            border-top: 1px solid #e2e8f0;
        }

        .error-footer strong {
            color: #4a5568;
        }

        @media (max-width: 768px) {
            .error-container {
                border-radius: 8px;
            }

            .error-header {
                padding: 30px 20px;
            }

            .error-header h1 {
                font-size: 24px;
            }

            .error-body {
                padding: 25px 20px;
            }

            .detail-content {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-header">
            <div class="error-icon">⚠️</div>
            <h1><?= trans('framework.errors.main_title') ?></h1>
            <div class="http-code">
                <?= trans('framework.errors.http_code', context: [
                    'code' => $error->httpCode->value ?? '500',
                    'label' => $error->httpCode->label() ?? ''
                ]) ?>
            </div>
        </div>

        <div class="error-body">
            <?php if ($isDebug ?? false): ?>
                <!-- Version détaillée pour le développement -->
                <div class="error-message">
                    <h2><?= htmlspecialchars($error->type ?? '') ?></h2>
                    <p><?= htmlspecialchars($error->message ?? '') ?></p>
                </div>

                <div class="debug-section">
                    <button class="toggle-details" onclick="toggleDetails()">
                        <?= trans('framework.errors.show_details') ?>
                    </button>

                    <div class="error-details" id="errorDetails">
                        <div class="detail-section">
                            <h3><?= trans('framework.errors.details.location') ?></h3>
                            <div class="detail-content">
                                <strong><?= trans('framework.errors.details.file') ?></strong> <?= htmlspecialchars($error->file ?? '') ?><br>
                                <strong><?= trans('framework.errors.details.line') ?></strong> <span class="trace-line"><?= $error->line ?? 0 ?></span><br>
                                <strong><?= trans('framework.errors.details.code_number') ?></strong> <?= $error->code ?? 0 ?>
                            </div>
                        </div>

                        <?php if (!empty($error->trace)): ?>
                            <div class="detail-section">
                                <h3><?= trans('framework.errors.details.trace') ?></h3>
                                <div class="detail-content">
                                    <?php foreach ($error->trace as $index => $trace): ?>
                                        <div class="trace-item">
                                            <div>
                                                <span class="trace-number">#<?= $index ?></span>
                                                <span class="trace-file"><?= htmlspecialchars($trace['file'] ?? 'unknown') ?></span>
                                                <span class="trace-line">(<?= $trace['line'] ?? 0 ?>)</span>
                                            </div>
                                            <div class="trace-function">
                                                <?php if (isset($trace['class'])): ?>
                                                    <?= htmlspecialchars($trace['class']) ?><?= htmlspecialchars($trace['type'] ?? '::') ?>
                                                <?php endif; ?>
                                                <?= htmlspecialchars($trace['function'] ?? 'unknown') ?>()
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Version simplifiée pour la production -->
                <div class="production-message">
                    <div class="production-icon">😔</div>
                    <h2><?= trans('framework.errors.production.title') ?></h2>
                    <p><?= trans('framework.errors.production.lines.0') ?></p>
                    <p><?= trans('framework.errors.production.lines.1') ?></p>
                    <p style="margin-top: 20px; color: #718096;">
                        <?= trans('framework.errors.production.lines.2') ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <div class="error-footer">
            <?php if ($isDebug ?? false): ?>
                <?= trans('framework.errors.development') ?>
            <?php else: ?>
                <?= trans('framework.errors.production.lines.3') ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleDetails() {
            const details = document.getElementById('errorDetails');
            const button = document.querySelector('.toggle-details');

            if (details.classList.contains('show')) {
                details.classList.remove('show');
                button.textContent = '<?= trans('framework.errors.show_details') ?>';
            } else {
                details.classList.add('show');
                button.textContent = '<?= trans('framework.errors.hide_details') ?>';
            }
        }
    </script>
</body>
</html>

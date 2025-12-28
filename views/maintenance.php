<?php
/**
 * @param int $seconds
 * @return string
 */
function formatRetryTime(int $seconds): string
{
    if ($seconds < 60) {
        return trans('framework.maintenance.time.seconds', $seconds, ['time' => $seconds]);
    }
    if ($seconds < 3600) {
        $minutes = round($seconds / 60);
        return trans('framework.maintenance.time.minutes', $minutes, ['time' => $minutes]);
    }
    $hours = round($seconds / 3600, 1);
    return trans('framework.maintenance.time.hours', $hours, ['time' => $hours]);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= trans('framework.maintenance.title') ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #FA8BFF 0%, #2BD2FF 50%, #2BFF88 100%);
            background-size: 200% 200%;
            animation: gradientShift 15s ease infinite;
            padding: 20px;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .maintenance-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .maintenance-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .maintenance-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: rotate 3s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .maintenance-header h1 {
            font-size: 32px;
            font-weight: 700;
            margin: 0;
        }

        .maintenance-body {
            padding: 40px 30px;
        }

        .maintenance-message {
            text-align: center;
            margin-bottom: 40px;
        }

        .maintenance-message p {
            font-size: 18px;
            color: #4a5568;
            line-height: 1.6;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-box {
            background: linear-gradient(135deg, #f6f8fb 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }

        .info-box-label {
            font-size: 12px;
            color: #718096;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .info-box-value {
            font-size: 24px;
            color: #2d3748;
            font-weight: 700;
        }

        .progress-container {
            margin-top: 30px;
        }

        .progress-label {
            font-size: 14px;
            color: #718096;
            text-align: center;
            margin-bottom: 10px;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            animation: progress <?= $retry ?? 3600 ?>s linear forwards;
        }

        @keyframes progress {
            from { width: 0; }
            to { width: 100%; }
        }

        .footer-note {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #a0aec0;
            font-size: 14px;
        }

        @media (max-width: 640px) {
            .maintenance-header h1 {
                font-size: 24px;
            }

            .maintenance-icon {
                font-size: 60px;
            }

            .maintenance-message p {
                font-size: 16px;
            }

            .info-box-value {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-header">
            <div class="maintenance-icon">🔧</div>
            <h1><?= trans('framework.maintenance.title') ?></h1>
        </div>

        <div class="maintenance-body">
            <div class="maintenance-message">
                <p>
                    <?= trans('framework.maintenance.message') ?>
                </p>
            </div>

            <div class="info-grid">
                <div class="info-box">
                    <div class="info-box-label"><?= trans('framework.maintenance.boxes.estimate') ?></div>
                    <div class="info-box-value"><?= formatRetryTime($retry ?? 3600) ?></div>
                </div>

                <div class="info-box">
                    <div class="info-box-label"><?= trans('framework.maintenance.boxes.update') ?></div>
                    <div class="info-box-value" id="current-time">--:--</div>
                </div>

                <div class="info-box">
                    <div class="info-box-label"><?= trans('framework.maintenance.boxes.back') ?></div>
                    <div class="info-box-value" id="return-time">--:--</div>
                </div>
            </div>

            <div class="progress-container">
                <div class="progress-label"><?= trans('framework.maintenance.progress') ?></div>
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
            </div>

            <div class="footer-note">
                <?= trans('framework.maintenance.footer') ?>
            </div>
        </div>
    </div>

    <script>
        const retryAfter = <?= ($retry ?? 3600) * 1000 ?>;

        // Format time as HH:MM
        function formatTime(date) {
            const hours = date.getHours().toString().padStart(2, '0');
            const minutes = date.getMinutes().toString().padStart(2, '0');
            return `${hours}:${minutes}`;
        }

        // Set current time and return time
        const now = new Date();
        const returnDate = new Date(now.getTime() + retryAfter);

        document.getElementById('current-time').textContent = formatTime(now);
        document.getElementById('return-time').textContent = formatTime(returnDate);

        // Auto-reload after retry period
        setTimeout(() => {
            window.location.reload();
        }, retryAfter);
    </script>
</body>
</html>

<?php
http_response_code(500);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - System Error</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f4f6f9;
            font-family: Arial, sans-serif;
        }

        .error-wrapper {
            min-height: 100vh;
        }

        .error-code {
            font-size: 120px;
            font-weight: 700;
            color: #dc3545;
            line-height: 1;
        }

        .error-box {
            background: #ffffff;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center error-wrapper">
    <div class="col-lg-6">
        <div class="error-box text-center">

            <div class="error-code">
                500
            </div>

            <h2 class="mb-3">
                Internal System Error
            </h2>

            <p class="text-muted mb-4">
                The application encountered an unexpected error while processing your request.
            </p>

            <div class="d-flex justify-content-center gap-2 flex-wrap">

                <a href="<?= function_exists('url') ? url('dashboard') : '/film_studio/public/index.php?page=dashboard'; ?>"
                   class="btn btn-primary">
                    Return to Dashboard
                </a>

                <a href="<?= function_exists('url') ? url('auth') : '/film_studio/public/index.php?page=auth'; ?>"
                   class="btn btn-outline-secondary">
                    Login Page
                </a>

            </div>

        </div>
    </div>
</div>

</body>
</html>
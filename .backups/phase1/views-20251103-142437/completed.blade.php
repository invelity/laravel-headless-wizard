<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wizard Completed</title>
</head>
<body>
    <div class="wizard-container">
        <div class="wizard-completed">
            <h1>Wizard Completed Successfully!</h1>

            @if($message)
                <p>{{ $message }}</p>
            @endif

            <div class="wizard-summary">
                <h2>Summary</h2>
                <pre>{{ json_encode($data, JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit: {{ $step->getTitle() }} - Wizard</title>
</head>
<body>
    <div class="wizard-container">
        <div class="wizard-progress">
            <div class="progress-bar" style="width: {{ $progress->percentComplete }}%"></div>
            <span class="progress-text">{{ $progress->percentComplete }}% Complete</span>
        </div>

        <h1>Edit: {{ $step->getTitle() }}</h1>

        @if($errors->any())
            <div class="alert alert-error">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('wizard.update', ['wizard' => $wizard, 'wizardId' => $wizardId, 'step' => $step->getId()]) }}">
            @csrf
            @method('PUT')

            {{ $step->render($data) }}

            <div class="wizard-actions">
                <button type="submit" class="btn btn-primary">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</body>
</html>

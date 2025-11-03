<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $step->getTitle() }} - Wizard</title>
</head>
<body>
    <div class="wizard-container">
        <div class="wizard-progress">
            <div class="progress-bar" style="width: {{ $progress->percentComplete }}%"></div>
            <span class="progress-text">{{ $progress->percentComplete }}% Complete</span>
        </div>

        <nav class="wizard-breadcrumbs">
            @foreach($navigation->getItems() as $item)
                <div class="breadcrumb-item 
                    {{ $item->isCurrent ? 'current' : '' }} 
                    {{ $item->isCompleted ? 'completed' : '' }}
                    {{ $item->isAccessible ? 'accessible' : 'disabled' }}">
                    @if($item->isAccessible && !$item->isCurrent)
                        <a href="{{ $item->url }}">{{ $item->title }}</a>
                    @else
                        <span>{{ $item->title }}</span>
                    @endif
                </div>
            @endforeach
        </nav>

        <h1>{{ $step->getTitle() }}</h1>

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

        <form method="POST" action="{{ route('wizard.store', ['wizard' => $wizard, 'step' => $step->getId()]) }}">
            @csrf

            {{ $step->render($data) }}

            <div class="wizard-actions">
                @if($canGoBack && $previousStep)
                    <a href="{{ route('wizard.show', ['wizard' => $wizard, 'step' => $previousStep->getId()]) }}" class="btn btn-secondary">
                        &larr; Back
                    </a>
                @endif

                @if($step->canSkip() && $step->isOptional())
                    <a href="{{ route('wizard.skip', ['wizard' => $wizard, 'step' => $step->getId()]) }}" class="btn btn-secondary">
                        Skip (Optional)
                    </a>
                @endif

                <button type="submit" class="btn btn-primary">
                    {{ $progress->isComplete ? 'Complete' : 'Next &rarr;' }}
                </button>
            </div>
        </form>
    </div>
</body>
</html>

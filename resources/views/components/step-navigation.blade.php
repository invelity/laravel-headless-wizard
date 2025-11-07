<div class="wizard-navigation">
    @if($canGoBack && $previousStep)
        <a href="{{ route('wizard.show', $previousStep) }}" class="btn btn-secondary">
            {{ $backText }}
        </a>
    @endif

    @if($isLastStep)
        <button type="submit" class="btn btn-primary">
            {{ $completeText }}
        </button>
    @elseif($canGoForward)
        <button type="submit" class="btn btn-primary">
            {{ $nextText }}
        </button>
    @endif
</div>

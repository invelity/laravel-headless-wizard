<div class="wizard-progress-bar">
    <div class="progress-container">
        <div class="progress-bar" role="progressbar" style="width: {{ $percentage }}%" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
            {{ $percentage }}%
        </div>
    </div>
    
    <div class="steps-list">
        @foreach($steps as $step)
            <div class="step-item {{ $step['id'] === $currentStep ? 'active' : '' }}">
                <span class="step-number">{{ $step['order'] }}</span>
                <span class="step-title">{{ $step['title'] }}</span>
            </div>
        @endforeach
    </div>
</div>

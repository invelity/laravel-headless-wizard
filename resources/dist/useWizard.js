import { ref, computed, reactive } from 'vue';

/**
 * Vue 3 Composable for Wizard State Management
 * 
 * @param {string} wizardId - The wizard identifier
 * @param {object} options - Configuration options
 * @returns {object} Wizard state and methods
 */
export function useWizard(wizardId, options = {}) {
    const config = {
        apiBaseUrl: options.apiBaseUrl || '/api/wizard',
        onComplete: options.onComplete || null,
        onError: options.onError || null,
        ...options
    };

    const state = reactive({
        currentStepIndex: 0,
        steps: [],
        formData: {},
        errors: {},
        loading: false,
        completed: false,
        wizardData: null
    });

    const currentStep = computed(() => {
        return state.steps[state.currentStepIndex] || null;
    });

    const canGoBack = computed(() => {
        return state.currentStepIndex > 0;
    });

    const canGoForward = computed(() => {
        return state.currentStepIndex < state.steps.length - 1;
    });

    const isLastStep = computed(() => {
        return state.currentStepIndex === state.steps.length - 1;
    });

    async function initialize() {
        state.loading = true;
        state.errors = {};

        try {
            const response = await fetch(`${config.apiBaseUrl}/${wizardId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            state.steps = data.steps || [];
            state.currentStepIndex = data.currentStepIndex || 0;
            state.formData = data.formData || {};
        } catch (error) {
            console.error('Failed to initialize wizard:', error);
            if (config.onError) {
                config.onError(error);
            }
            throw error;
        } finally {
            state.loading = false;
        }
    }

    async function submitStep(stepData = {}) {
        if (!currentStep.value) {
            throw new Error('No current step available');
        }

        state.loading = true;
        state.errors = {};

        const payload = {
            ...state.formData,
            ...stepData
        };

        try {
            const response = await fetch(`${config.apiBaseUrl}/${wizardId}/${currentStep.value.id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (!response.ok) {
                if (response.status === 422 && data.errors) {
                    state.errors = data.errors;
                    return { success: false, errors: data.errors };
                }
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }

            state.formData = { ...state.formData, ...stepData };
            state.errors = {};

            if (data.completed) {
                state.completed = true;
                state.wizardData = data.data;
                if (config.onComplete) {
                    config.onComplete(data.data);
                }
                return { success: true, completed: true, data: data.data };
            }

            if (data.next_step) {
                const nextStepIndex = state.steps.findIndex(s => s.id === data.next_step);
                if (nextStepIndex !== -1) {
                    state.currentStepIndex = nextStepIndex;
                }
            } else {
                state.currentStepIndex++;
            }

            return { success: true, completed: false };
        } catch (error) {
            console.error('Failed to submit step:', error);
            if (config.onError) {
                config.onError(error);
            }
            return { success: false, error: error.message };
        } finally {
            state.loading = false;
        }
    }

    function goToStep(stepId) {
        const stepIndex = state.steps.findIndex(s => s.id === stepId);
        
        if (stepIndex === -1) {
            console.warn(`Step with id "${stepId}" not found`);
            return false;
        }

        if (stepIndex > state.currentStepIndex) {
            console.warn('Cannot skip forward to future steps');
            return false;
        }

        state.currentStepIndex = stepIndex;
        state.errors = {};
        return true;
    }

    function goBack() {
        if (canGoBack.value) {
            state.currentStepIndex--;
            state.errors = {};
            return true;
        }
        return false;
    }

    function goForward() {
        if (canGoForward.value) {
            state.currentStepIndex++;
            state.errors = {};
            return true;
        }
        return false;
    }

    function setFieldValue(field, value) {
        state.formData[field] = value;
        if (state.errors[field]) {
            delete state.errors[field];
        }
    }

    function getFieldError(field) {
        return state.errors[field]?.[0] || null;
    }

    function clearErrors() {
        state.errors = {};
    }

    function reset() {
        state.currentStepIndex = 0;
        state.formData = {};
        state.errors = {};
        state.completed = false;
        state.wizardData = null;
    }

    return {
        state,
        currentStep,
        canGoBack,
        canGoForward,
        isLastStep,
        initialize,
        submitStep,
        goToStep,
        goBack,
        goForward,
        setFieldValue,
        getFieldError,
        clearErrors,
        reset
    };
}

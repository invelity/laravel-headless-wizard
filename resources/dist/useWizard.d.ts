import { Ref, ComputedRef } from 'vue';

export interface WizardStep {
    id: string;
    title: string;
    order: number;
    isOptional?: boolean;
    canSkip?: boolean;
}

export interface WizardState {
    currentStepIndex: number;
    steps: WizardStep[];
    formData: Record<string, any>;
    errors: Record<string, string[]>;
    loading: boolean;
    completed: boolean;
    wizardData: any | null;
}

export interface WizardOptions {
    apiBaseUrl?: string;
    onComplete?: (data: any) => void;
    onError?: (error: Error) => void;
}

export interface SubmitStepResult {
    success: boolean;
    completed?: boolean;
    data?: any;
    errors?: Record<string, string[]>;
    error?: string;
}

export interface UseWizardReturn {
    state: WizardState;
    currentStep: ComputedRef<WizardStep | null>;
    canGoBack: ComputedRef<boolean>;
    canGoForward: ComputedRef<boolean>;
    isLastStep: ComputedRef<boolean>;
    initialize: () => Promise<void>;
    submitStep: (stepData?: Record<string, any>) => Promise<SubmitStepResult>;
    goToStep: (stepId: string) => boolean;
    goBack: () => boolean;
    goForward: () => boolean;
    setFieldValue: (field: string, value: any) => void;
    getFieldError: (field: string) => string | null;
    clearErrors: () => void;
    reset: () => void;
}

export function useWizard(wizardId: string, options?: WizardOptions): UseWizardReturn;

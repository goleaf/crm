import { useForm } from 'laravel-precognition-vue';
import { watchDebounced } from '@vueuse/core';

/**
 * Create a precognitive form with common patterns
 * 
 * @param {string} method - HTTP method (post, put, patch, delete)
 * @param {string} url - API endpoint URL
 * @param {object} initialData - Initial form data
 * @param {object} options - Configuration options
 * @returns {object} Form instance with helpers
 */
export function usePrecognitiveForm(method, url, initialData = {}, options = {}) {
    const {
        debounce = 500,
        validateOnBlur = true,
        validateOnChange = true,
        onSuccess = null,
        onError = null,
    } = options;

    const form = useForm(method, url, initialData);

    /**
     * Setup debounced validation for a field
     * 
     * @param {string} field - Field name to watch
     * @param {number} delay - Debounce delay in ms
     */
    const setupDebouncedValidation = (field, delay = debounce) => {
        watchDebounced(
            () => form[field],
            (newValue) => {
                if (newValue !== null && newValue !== '') {
                    form.validate(field);
                }
            },
            { debounce: delay }
        );
    };

    /**
     * Validate field on blur
     * 
     * @param {string} field - Field name
     */
    const validateOnBlurHandler = (field) => {
        if (validateOnBlur) {
            form.validate(field);
        }
    };

    /**
     * Validate field on change
     * 
     * @param {string} field - Field name
     */
    const validateOnChangeHandler = (field) => {
        if (validateOnChange) {
            form.validate(field);
        }
    };

    /**
     * Submit form with callbacks
     */
    const submitForm = () => {
        form.submit({
            preserveScroll: true,
            onSuccess: (response) => {
                if (onSuccess) {
                    onSuccess(response);
                }
            },
            onError: (errors) => {
                if (onError) {
                    onError(errors);
                }
            },
        });
    };

    /**
     * Check if form has any errors
     */
    const hasErrors = () => form.hasErrors;

    /**
     * Check if form is currently processing
     */
    const isProcessing = () => form.processing;

    /**
     * Check if a specific field is valid
     */
    const isFieldValid = (field) => form.valid(field);

    /**
     * Check if a specific field is invalid
     */
    const isFieldInvalid = (field) => form.invalid(field);

    /**
     * Get error message for a field
     */
    const getFieldError = (field) => form.errors[field];

    /**
     * Reset form to initial state
     */
    const resetForm = () => {
        form.reset();
    };

    return {
        form,
        setupDebouncedValidation,
        validateOnBlur: validateOnBlurHandler,
        validateOnChange: validateOnChangeHandler,
        submit: submitForm,
        hasErrors,
        isProcessing,
        isFieldValid,
        isFieldInvalid,
        getFieldError,
        reset: resetForm,
    };
}

/**
 * Common validation patterns for different field types
 */
export const validationPatterns = {
    /**
     * Email field validation pattern
     */
    email: {
        debounce: 500,
        validateOn: 'blur',
        showSuccessIndicator: true,
    },

    /**
     * Text field validation pattern
     */
    text: {
        debounce: 300,
        validateOn: 'blur',
        showSuccessIndicator: false,
    },

    /**
     * Select field validation pattern
     */
    select: {
        debounce: 0,
        validateOn: 'change',
        showSuccessIndicator: false,
    },

    /**
     * Phone field validation pattern
     */
    phone: {
        debounce: 500,
        validateOn: 'blur',
        showSuccessIndicator: false,
    },

    /**
     * Unique field validation pattern (username, email, etc.)
     */
    unique: {
        debounce: 500,
        validateOn: 'input',
        showSuccessIndicator: true,
    },
};

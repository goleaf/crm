<script setup>
import { usePrecognitiveForm, validationPatterns } from '@/composables/usePrecognition';
import { onMounted } from 'vue';

const props = defineProps({
    contact: {
        type: Object,
        default: null,
    },
    companies: {
        type: Array,
        required: true,
    },
    personas: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['success', 'cancel']);

const isEditing = !!props.contact;
const method = isEditing ? 'put' : 'post';
const url = isEditing ? `/api/contacts/${props.contact.id}` : '/api/contacts';

const {
    form,
    setupDebouncedValidation,
    validateOnBlur,
    validateOnChange,
    submit,
    hasErrors,
    isProcessing,
    isFieldValid,
    isFieldInvalid,
    getFieldError,
} = usePrecognitiveForm(method, url, {
    name: props.contact?.name || '',
    email: props.contact?.email || '',
    phone: props.contact?.phone || '',
    mobile: props.contact?.mobile || '',
    company_id: props.contact?.company_id || null,
    title: props.contact?.title || '',
    department: props.contact?.department || '',
    persona_id: props.contact?.persona_id || null,
    address: props.contact?.address || '',
}, {
    onSuccess: (response) => {
        emit('success', response.data);
    },
    onError: (errors) => {
        console.error('Form validation errors:', errors);
    },
});

// Setup debounced validation for email (unique check)
onMounted(() => {
    setupDebouncedValidation('email', validationPatterns.email.debounce);
    setupDebouncedValidation('name', validationPatterns.text.debounce);
});

const handleSubmit = () => {
    submit();
};

const handleCancel = () => {
    emit('cancel');
};
</script>

<template>
    <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Name Field -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('app.labels.name') }} <span class="text-red-500">*</span>
            </label>
            <input
                id="name"
                v-model="form.name"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                :class="{
                    'border-red-500 focus:border-red-500 focus:ring-red-500': isFieldInvalid('name'),
                    'border-green-500 focus:border-green-500 focus:ring-green-500': isFieldValid('name'),
                }"
                @blur="validateOnBlur('name')"
            />
            <p v-if="isFieldInvalid('name')" class="mt-1 text-sm text-red-600 dark:text-red-400">
                {{ getFieldError('name') }}
            </p>
        </div>

        <!-- Email Field -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('app.labels.email') }} <span class="text-red-500">*</span>
            </label>
            <input
                id="email"
                v-model="form.email"
                type="email"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                :class="{
                    'border-red-500 focus:border-red-500 focus:ring-red-500': isFieldInvalid('email'),
                    'border-green-500 focus:border-green-500 focus:ring-green-500': isFieldValid('email'),
                }"
                @blur="validateOnBlur('email')"
            />
            <p v-if="isFieldInvalid('email')" class="mt-1 text-sm text-red-600 dark:text-red-400">
                {{ getFieldError('email') }}
            </p>
            <p v-else-if="isFieldValid('email')" class="mt-1 text-sm text-green-600 dark:text-green-400">
                ✓ {{ __('app.messages.email_available') }}
            </p>
        </div>

        <!-- Phone and Mobile Fields -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('app.labels.phone') }}
                </label>
                <input
                    id="phone"
                    v-model="form.phone"
                    type="tel"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                    :class="{
                        'border-red-500 focus:border-red-500 focus:ring-red-500': isFieldInvalid('phone'),
                    }"
                    @blur="validateOnBlur('phone')"
                />
                <p v-if="isFieldInvalid('phone')" class="mt-1 text-sm text-red-600 dark:text-red-400">
                    {{ getFieldError('phone') }}
                </p>
            </div>

            <div>
                <label for="mobile" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('app.labels.mobile') }}
                </label>
                <input
                    id="mobile"
                    v-model="form.mobile"
                    type="tel"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                    :class="{
                        'border-red-500 focus:border-red-500 focus:ring-red-500': isFieldInvalid('mobile'),
                    }"
                    @blur="validateOnBlur('mobile')"
                />
                <p v-if="isFieldInvalid('mobile')" class="mt-1 text-sm text-red-600 dark:text-red-400">
                    {{ getFieldError('mobile') }}
                </p>
            </div>
        </div>

        <!-- Company Select -->
        <div>
            <label for="company_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('app.labels.company') }} <span class="text-red-500">*</span>
            </label>
            <select
                id="company_id"
                v-model="form.company_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                :class="{
                    'border-red-500 focus:border-red-500 focus:ring-red-500': isFieldInvalid('company_id'),
                }"
                @change="validateOnChange('company_id')"
            >
                <option :value="null">{{ __('app.placeholders.select_company') }}</option>
                <option v-for="company in companies" :key="company.id" :value="company.id">
                    {{ company.name }}
                </option>
            </select>
            <p v-if="isFieldInvalid('company_id')" class="mt-1 text-sm text-red-600 dark:text-red-400">
                {{ getFieldError('company_id') }}
            </p>
        </div>

        <!-- Title and Department -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('app.labels.title') }}
                </label>
                <input
                    id="title"
                    v-model="form.title"
                    type="text"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                    @blur="validateOnBlur('title')"
                />
            </div>

            <div>
                <label for="department" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('app.labels.department') }}
                </label>
                <input
                    id="department"
                    v-model="form.department"
                    type="text"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                    @blur="validateOnBlur('department')"
                />
            </div>
        </div>

        <!-- Persona Select -->
        <div v-if="personas.length > 0">
            <label for="persona_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('app.labels.persona') }}
            </label>
            <select
                id="persona_id"
                v-model="form.persona_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                @change="validateOnChange('persona_id')"
            >
                <option :value="null">{{ __('app.placeholders.select_persona') }}</option>
                <option v-for="persona in personas" :key="persona.id" :value="persona.id">
                    {{ persona.name }}
                </option>
            </select>
        </div>

        <!-- Address Field -->
        <div>
            <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('app.labels.address') }}
            </label>
            <textarea
                id="address"
                v-model="form.address"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                @blur="validateOnBlur('address')"
            ></textarea>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3">
            <button
                type="button"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
                @click="handleCancel"
            >
                {{ __('app.actions.cancel') }}
            </button>
            <button
                type="submit"
                :disabled="isProcessing() || hasErrors()"
                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span v-if="isProcessing()">{{ __('app.actions.saving') }}...</span>
                <span v-else>{{ isEditing ? __('app.actions.update') : __('app.actions.create') }}</span>
            </button>
        </div>
    </form>
</template>

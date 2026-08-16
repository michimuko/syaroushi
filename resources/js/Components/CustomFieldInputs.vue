<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import MyNumberWarning from '@/Components/MyNumberWarning.vue';
import TextInput from '@/Components/TextInput.vue';
import { containsMyNumberLikeString } from '@/Composables/useMyNumberDetection';

defineProps({
    definitions: {
        type: Array,
        default: () => [],
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const values = defineModel({ type: Object, required: true });
</script>

<template>
    <div v-if="definitions.length > 0">
        <h3
            class="mt-6 border-t border-gray-100 pt-6 text-sm font-semibold text-gray-700"
        >
            カスタムフィールド
        </h3>
        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div v-for="definition in definitions" :key="definition.id">
                <InputLabel :for="`custom-field-${definition.id}`">
                    {{ definition.label }}
                </InputLabel>

                <TextInput
                    v-if="definition.field_type === 'text'"
                    :id="`custom-field-${definition.id}`"
                    class="mt-1 block w-full disabled:bg-gray-50 disabled:text-gray-500"
                    v-model="values[definition.id]"
                    :disabled="disabled"
                />
                <input
                    v-else-if="definition.field_type === 'number'"
                    :id="`custom-field-${definition.id}`"
                    type="number"
                    v-model="values[definition.id]"
                    :disabled="disabled"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-500"
                />
                <input
                    v-else-if="definition.field_type === 'date'"
                    :id="`custom-field-${definition.id}`"
                    type="date"
                    v-model="values[definition.id]"
                    :disabled="disabled"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-500"
                />
                <select
                    v-else-if="definition.field_type === 'select'"
                    :id="`custom-field-${definition.id}`"
                    v-model="values[definition.id]"
                    :disabled="disabled"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-500"
                >
                    <option value="">未選択</option>
                    <option
                        v-for="option in definition.options"
                        :key="option"
                        :value="option"
                    >
                        {{ option }}
                    </option>
                </select>
                <label
                    v-else-if="definition.field_type === 'checkbox'"
                    class="mt-1 flex items-center gap-2"
                >
                    <input
                        :id="`custom-field-${definition.id}`"
                        type="checkbox"
                        v-model="values[definition.id]"
                        :disabled="disabled"
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    />
                    <span class="text-sm text-gray-700">はい</span>
                </label>

                <InputError
                    class="mt-1"
                    :message="errors[`custom_fields.${definition.id}`]"
                />
                <MyNumberWarning
                    v-if="definition.field_type === 'text'"
                    class="mt-1"
                    :show="containsMyNumberLikeString(values[definition.id])"
                />
            </div>
        </div>
    </div>
</template>

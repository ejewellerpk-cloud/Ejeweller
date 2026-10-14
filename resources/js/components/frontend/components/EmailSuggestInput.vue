<template>
    <div class="relative w-full email-suggest-root">
        <input
            :id="inputId"
            :type="inputType"
            :value="modelValue"
            :class="inputClass"
            :placeholder="placeholder"
            :autocomplete="autocomplete"
            @input="onInput"
            @focus="open = true"
            @blur="onBlur"
            @keydown.down.prevent="move(1)"
            @keydown.up.prevent="move(-1)"
            @keydown.enter.prevent="pickHighlighted"
            @keydown.esc="open = false"
        />
        <ul
            v-if="open && suggestions.length"
            class="email-suggest-list absolute left-0 right-0 top-full mt-1 z-30 max-h-52 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-xl py-1"
            role="listbox"
        >
            <li
                v-for="(item, index) in suggestions"
                :key="item"
                role="option"
                :aria-selected="index === highlightIndex"
                class="px-3 py-2 text-sm cursor-pointer transition-colors"
                :class="index === highlightIndex ? 'bg-primary/10 text-primary' : 'hover:bg-gray-50 text-heading'"
                @mousedown.prevent="select(item)"
            >
                {{ item }}
            </li>
        </ul>
    </div>
</template>

<script>
import { getEmailDomainSuggestions } from '../../../utils/emailDomainSuggest';

export default {
    name: 'EmailSuggestInput',
    props: {
        modelValue: { type: String, default: '' },
        inputId: { type: String, default: 'email' },
        inputClass: { type: String, default: '' },
        inputType: { type: String, default: 'email' },
        placeholder: { type: String, default: '' },
        autocomplete: { type: String, default: 'email' },
    },
    emits: ['update:modelValue'],
    data() {
        return {
            open: false,
            highlightIndex: 0,
        };
    },
    computed: {
        suggestions() {
            return getEmailDomainSuggestions(this.modelValue);
        },
    },
    watch: {
        suggestions() {
            this.highlightIndex = 0;
        },
    },
    methods: {
        onInput(event) {
            this.open = true;
            this.$emit('update:modelValue', event.target.value);
        },
        onBlur() {
            window.setTimeout(() => {
                this.open = false;
            }, 120);
        },
        select(value) {
            this.$emit('update:modelValue', value);
            this.open = false;
        },
        move(delta) {
            if (!this.suggestions.length) {
                return;
            }
            this.open = true;
            const len = this.suggestions.length;
            this.highlightIndex = (this.highlightIndex + delta + len) % len;
        },
        pickHighlighted() {
            if (!this.open || !this.suggestions.length) {
                return;
            }
            this.select(this.suggestions[this.highlightIndex]);
        },
    },
};
</script>

<template>
    <div v-if="loading" class="mb-6 text-sm text-gray-500">
        <i class="fa-solid fa-spinner fa-spin mr-2"></i>{{ $t('label.loading') || 'Loading options…' }}
    </div>

    <div v-else-if="groups.length" class="product-variation-picker mb-6 space-y-5">
        <div v-for="(group, groupIndex) in groups" :key="group.attributeId" class="variation-option-group">
            <div class="flex flex-wrap items-baseline justify-between gap-2 mb-2">
                <span class="text-sm font-bold uppercase tracking-wide text-heading">
                    {{ group.name }}
                    <span class="text-red-500 font-semibold normal-case" v-if="!selected[group.attributeId]">*</span>
                </span>
                <span
                    v-if="selected[group.attributeId]"
                    class="text-xs font-semibold text-primary capitalize"
                >
                    {{ optionLabel(group, selected[group.attributeId]) }}
                </span>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    v-for="opt in group.options"
                    :key="opt.optionId"
                    type="button"
                    :disabled="!isAvailable(group, opt, groupIndex)"
                    @click="selectOption(group, opt, groupIndex)"
                    :class="buttonClass(group, opt, groupIndex)"
                    class="min-h-[40px] px-4 py-2 rounded-xl text-sm font-semibold capitalize border-2 transition-all duration-200"
                >
                    {{ opt.optionName }}
                    <span
                        v-if="!isAvailable(group, opt, groupIndex)"
                        class="block text-[10px] font-normal normal-case opacity-80"
                    >
                        {{ $t('label.unavailable') || 'Unavailable' }}
                    </span>
                </button>
            </div>
        </div>

        <p v-if="selectionSummary" class="text-sm text-gray-600 bg-[#F7F7FC] rounded-xl px-4 py-3">
            <span class="font-semibold text-heading">{{ $t('label.selected') || 'Selected' }}:</span>
            {{ selectionSummary }}
            <span v-if="matchedLeaf && matchedLeaf.variant.stock <= 0" class="text-red-600 font-semibold ml-1">
                — {{ $t('label.stock_out') }}
            </span>
        </p>

        <p v-else-if="groups.length && !matchedLeaf" class="text-sm text-amber-700">
            {{ $t('message.select_all_options') || 'Please choose all options above.' }}
        </p>
    </div>
</template>

<script>
import axios from 'axios';
import {
    autoSelectSingleOptions,
    buildOptionGroups,
    findMatchingLeaf,
    formatSelectionLabel,
    isOptionAvailable,
} from '../../../utils/productVariationPicker';

export default {
    name: 'ProductVariationPicker',
    props: {
        productSlug: { type: String, required: true },
        method: { type: Function, required: true },
    },
    emits: ['ready', 'change'],
    data() {
        return {
            loading: true,
            groups: [],
            leaves: [],
            selected: {},
        };
    },
    computed: {
        matchedLeaf() {
            return findMatchingLeaf(this.leaves, this.selected, this.groups.length);
        },
        selectionSummary() {
            if (!this.matchedLeaf) {
                return '';
            }
            return formatSelectionLabel(this.selected, this.groups);
        },
    },
    watch: {
        productSlug: {
            immediate: true,
            handler() {
                this.loadVariations();
            },
        },
    },
    methods: {
        async loadVariations() {
            if (!this.productSlug) {
                return;
            }
            this.loading = true;
            this.selected = {};
            this.groups = [];
            this.leaves = [];
            this.method(null);

            try {
                const res = await axios.get(`frontend/product/all-variation/${this.productSlug}`);
                const tree = res.data?.data || [];
                const { groups, leaves } = buildOptionGroups(tree);
                this.groups = groups;
                this.leaves = leaves;
                this.selected = autoSelectSingleOptions(groups);
                this.emitSelection();
                this.$emit('ready', { groups, leaves });
            } catch (e) {
                this.groups = [];
                this.leaves = [];
            } finally {
                this.loading = false;
            }
        },
        optionLabel(group, optionId) {
            const opt = group.options.find((o) => String(o.optionId) === String(optionId));
            return opt ? opt.optionName : '';
        },
        isAvailable(group, opt, groupIndex) {
            return isOptionAvailable(
                this.leaves,
                this.selected,
                group.attributeId,
                opt.optionId,
                groupIndex,
                this.groups
            );
        },
        selectOption(group, opt, groupIndex) {
            if (!this.isAvailable(group, opt, groupIndex)) {
                return;
            }
            const next = { ...this.selected, [group.attributeId]: opt.optionId };
            this.groups.slice(groupIndex + 1).forEach((g) => {
                delete next[g.attributeId];
            });
            this.selected = next;
            this.emitSelection();
        },
        buttonClass(group, opt, groupIndex) {
            const isSelected = String(this.selected[group.attributeId]) === String(opt.optionId);
            const available = this.isAvailable(group, opt, groupIndex);
            if (!available) {
                return 'border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed line-through opacity-60';
            }
            if (isSelected) {
                return 'border-primary bg-primary text-white shadow-[0_4px_14px_rgba(255,92,0,0.35)]';
            }
            return 'border-gray-200 bg-white text-secondary hover:border-primary/50 hover:bg-orange-50';
        },
        emitSelection() {
            const leaf = this.matchedLeaf;
            const variant = leaf?.variant ?? null;
            this.method(variant);
            this.$emit('change', {
                variant,
                selected: { ...this.selected },
                complete: !!variant,
            });
        },
    },
};
</script>

<style scoped>
.product-variation-picker button:not(:disabled):active {
    transform: scale(0.97);
}
</style>

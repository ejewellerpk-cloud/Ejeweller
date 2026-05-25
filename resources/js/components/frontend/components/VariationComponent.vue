<template>
    <div v-if="loading" class="mb-6 flex items-center gap-2 text-sm text-gray-500">
        <span class="inline-block w-4 h-4 border-2 border-primary border-t-transparent rounded-full animate-spin"></span>
        <span>{{ $t('label.loading') || 'Loading options...' }}</span>
    </div>

    <div v-else-if="variationTree.length > 0" class="product-variation-picker space-y-4 mb-6">
        <div
            v-for="level in visibleLevels"
            :key="'attr-' + level.levelIndex"
            class="variation-picker-row"
        >
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2 mb-2">
                <span class="capitalize text-sm sm:text-base font-semibold text-heading min-w-[4rem]">
                    {{ level.attributeName }}:
                </span>
                <span
                    v-if="level.levelIndex > 0 && selectedByLevel[level.levelIndex - 1] == null"
                    class="text-xs text-gray-500 italic"
                >
                    {{ $t('message.select_previous_option', { name: previousAttributeName(level.levelIndex) }) || ('Select ' + previousAttributeName(level.levelIndex) + ' first') }}
                </span>
            </div>

            <div class="flex flex-wrap gap-2 sm:gap-2.5" role="listbox" :aria-label="level.attributeName">
                <button
                    v-for="opt in level.options"
                    :key="opt.id"
                    type="button"
                    role="option"
                    :aria-selected="opt.selected"
                    :aria-disabled="!opt.available"
                    :disabled="!opt.available"
                    @click="selectOption(level.levelIndex, opt)"
                    :class="optionButtonClass(opt, level)"
                    :title="opt.available ? opt.product_attribute_option_name : ($t('message.option_unavailable') || 'Unavailable')"
                >
                    <span
                        v-if="level.isColor"
                        class="variation-color-swatch"
                        :style="{ backgroundColor: colorSwatchValue(opt.product_attribute_option_name) }"
                    ></span>
                    <span class="variation-option-label">{{ opt.product_attribute_option_name }}</span>
                    <span v-if="!opt.available" class="variation-unavailable-mark">×</span>
                </button>
            </div>
        </div>

        <p v-if="selectionSummary" class="text-sm text-gray-600">
            <span class="font-semibold text-heading">{{ $t('label.selected') || 'Selected' }}:</span>
            {{ selectionSummary }}
        </p>
        <p v-else-if="visibleLevels.length" class="text-sm text-gray-500">
            {{ $t('message.select_all_options') || 'Please select all options above.' }}
        </p>
    </div>

    <!-- Legacy API cascade fallback -->
    <template v-else-if="variations.length > 0">
        <dl class="flex flex-wrap items-center gap-x-6 gap-y-3 mb-6">
            <dt v-for="variationLabel in variations.slice(0, 1)" :key="variationLabel" class="capitalize text-lg font-semibold">
                {{ variationLabel.product_attribute_name }}:
            </dt>
            <dd class="flex flex-wrap items-center gap-3">
                <button
                    @click="selectLegacyVariation(variation)"
                    :class="legacySelectedId === variation.id ? 'text-white bg-primary ring-2 ring-primary ring-offset-1' : 'hover:ring-1 hover:ring-gray-300'"
                    type="button"
                    v-for="(variation, index) in variations"
                    :key="index"
                    class="px-3 min-h-9 h-auto py-1 leading-snug text-center rounded-full text-sm font-medium capitalize flex-shrink-0 cursor-pointer text-secondary bg-[#F7F7FC] disabled:opacity-40 disabled:cursor-not-allowed"
                    :disabled="!variationHasStock(variation)"
                >
                    {{ variation.product_attribute_option_name }}
                </button>
            </dd>
        </dl>
        <VariationComponent
            :method="method"
            :key="legacyChildKey"
            v-if="legacyChildren.length > 0"
            :variations="legacyChildren"
            :use-legacy-only="true"
        />
    </template>
</template>

<script>
import {
    normalizeVariationTree,
    buildVisibleLevels,
    getLeafFromPath,
    findPathToVariationId,
    getAttributeName,
    getOptionsForLevel,
} from '../../../utils/variationPicker';

const COLOR_MAP = {
    red: '#dc2626',
    blue: '#2563eb',
    green: '#16a34a',
    black: '#171717',
    white: '#f5f5f5',
    gold: '#ca8a04',
    silver: '#94a3b8',
    pink: '#ec4899',
    purple: '#9333ea',
    orange: '#ea580c',
    yellow: '#eab308',
    brown: '#78350f',
    beige: '#d6d3d1',
    grey: '#6b7280',
    gray: '#6b7280',
    navy: '#1e3a5f',
    maroon: '#7f1d1d',
    rose: '#fb7185',
    cream: '#fef3c7',
};

export default {
    name: 'VariationComponent',
    props: {
        variations: { type: [Array, Object], default: () => [] },
        method: { type: Function, required: true },
        productSlug: { type: String, default: '' },
        variationTreeData: { type: Array, default: () => [] },
        initialVariantId: { type: [Number, String], default: null },
        useLegacyOnly: { type: Boolean, default: false },
    },
    data() {
        return {
            variationTree: [],
            selectedByLevel: [],
            loading: false,
            legacySelectedId: null,
            legacyChildren: [],
            legacyChildKey: 0,
        };
    },
    computed: {
        visibleLevels() {
            return buildVisibleLevels(this.variationTree, this.selectedByLevel);
        },
        selectionSummary() {
            const parts = [];
            for (let i = 0; i < this.selectedByLevel.length; i++) {
                const id = this.selectedByLevel[i];
                if (id == null) {
                    continue;
                }
                const opts = getOptionsForLevel(this.variationTree, this.selectedByLevel, i);
                const match = opts.find((o) => Number(o.id) === Number(id));
                if (match) {
                    parts.push(match.product_attribute_option_name);
                }
            }
            const leaf = getLeafFromPath(this.variationTree, this.selectedByLevel);
            if (leaf && leaf.sku && parts.length) {
                return parts.join(' / ');
            }
            return parts.length ? parts.join(' / ') : '';
        },
    },
    watch: {
        variationTreeData: {
            immediate: true,
            deep: true,
            handler(tree) {
                if (this.useLegacyOnly) {
                    return;
                }
                if (Array.isArray(tree) && tree.length > 0) {
                    this.applyTree(tree);
                }
            },
        },
        productSlug: {
            immediate: true,
            handler(slug) {
                if (!this.useLegacyOnly && slug && !this.variationTree.length) {
                    this.loadVariationTree(slug);
                }
            },
        },
        initialVariantId(variantId) {
            if (variantId && this.variationTree.length) {
                this.applyVariantFromId(variantId);
            }
        },
        variations: {
            immediate: true,
            handler(list) {
                if (this.useLegacyOnly || this.productSlug) {
                    return;
                }
                if (Array.isArray(list) && list.length > 0 && !this.variationTree.length) {
                    this.legacyChildren = [];
                }
            },
        },
    },
    methods: {
        applyTree(tree) {
            this.variationTree = normalizeVariationTree(tree);
            this.loading = false;
            this.selectedByLevel = [];
            this.emitSelection(null);
            if (this.initialVariantId) {
                this.applyVariantFromId(this.initialVariantId);
            }
        },
        loadVariationTree(slug) {
            this.loading = true;
            this.variationTree = [];
            this.selectedByLevel = [];
            this.emitSelection(null);

            this.$store.dispatch('frontendProductVariation/allVariation', slug).then((res) => {
                this.applyTree(res.data.data);
            }).catch(() => {
                this.loading = false;
            });
        },
        applyVariantFromId(variantId) {
            const path = findPathToVariationId(this.variationTree, variantId);
            if (!path) {
                return;
            }
            this.selectedByLevel = [...path];
            this.emitCurrentLeaf();
        },
        previousAttributeName(levelIndex) {
            const prev = getOptionsForLevel(this.variationTree, this.selectedByLevel, levelIndex - 1);
            return getAttributeName(prev) || this.$t('label.option') || 'option';
        },
        selectOption(levelIndex, opt) {
            if (!opt.available) {
                return;
            }
            const next = [...this.selectedByLevel];
            next[levelIndex] = opt.id;
            next.length = levelIndex + 1;
            this.selectedByLevel = next;
            this.emitCurrentLeaf();
        },
        emitCurrentLeaf() {
            const leaf = getLeafFromPath(this.variationTree, this.selectedByLevel);
            this.emitSelection(leaf && leaf.sku ? leaf : null);
        },
        emitSelection(variation) {
            if (typeof this.method === 'function') {
                this.method(variation);
            }
        },
        optionButtonClass(opt, level) {
            const base = [
                'variation-option-btn',
                'inline-flex items-center gap-2',
                'px-3 sm:px-4 min-h-10 py-1.5',
                'rounded-lg text-sm font-semibold capitalize',
                'border-2 transition-all duration-200',
                'focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-1',
            ];
            if (!opt.available) {
                base.push('variation-option-btn--unavailable', 'opacity-45 cursor-not-allowed line-through');
            } else if (opt.selected) {
                base.push('variation-option-btn--selected', 'border-primary bg-primary text-white shadow-sm');
            } else {
                base.push('variation-option-btn--idle', 'border-gray-200 bg-white text-secondary hover:border-primary/60');
            }
            if (level.isColor && opt.available) {
                base.push('pl-2');
            }
            return base;
        },
        colorSwatchValue(name) {
            const key = String(name || '').trim().toLowerCase();
            return COLOR_MAP[key] || '#e5e7eb';
        },
        variationHasStock(variation) {
            return (parseInt(variation?.stock, 10) || 0) > 0 || !variation?.sku;
        },
        selectLegacyVariation(variation) {
            this.legacySelectedId = variation.id;
            if (!variation.sku) {
                this.emitSelection(null);
            } else {
                this.emitSelection(variation);
            }
            this.$store.dispatch('frontendProductVariation/childrenVariation', variation.id).then((res) => {
                this.legacyChildren = res.data.data || [];
                this.legacyChildKey++;
            }).catch(() => {});
        },
    },
};
</script>

<style scoped>
.product-variation-picker .variation-color-swatch {
    width: 1.125rem;
    height: 1.125rem;
    border-radius: 9999px;
    border: 1px solid rgba(0, 0, 0, 0.12);
    flex-shrink: 0;
}

.variation-unavailable-mark {
    font-size: 0.65rem;
    opacity: 0.7;
    margin-left: 0.15rem;
}

.variation-option-btn--selected .variation-color-swatch {
    border-color: rgba(255, 255, 255, 0.85);
    box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.08);
}
</style>

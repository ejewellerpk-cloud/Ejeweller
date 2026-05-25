<template>
    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 transition-transform hover:-translate-y-0.5"
        :class="{ 'ring-1 ring-emerald-500/40': pulse }">
        <p class="text-xs uppercase tracking-wider text-slate-500 mb-1">{{ label }}</p>
        <p class="text-2xl font-bold" :class="accent === 'emerald' ? 'text-emerald-400' : 'text-white'">
            {{ value }}
        </p>
    </div>
</template>

<script>
export default {
    name: 'KpiCard',
    props: {
        label: { type: String, required: true },
        value: { type: [String, Number], default: 0 },
        accent: { type: String, default: '' },
        pulse: { type: Boolean, default: false },
    },
};
</script>

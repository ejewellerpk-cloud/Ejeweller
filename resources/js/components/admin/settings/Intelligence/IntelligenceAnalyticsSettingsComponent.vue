<template>
    <LoadingComponent :props="loading" />
    <div class="db-card">
        <div class="db-card-header">
            <h3 class="db-card-title">{{ $t('menu.intelligence_analytics') || 'Customer Intelligence Keys' }}</h3>
        </div>
        <div class="db-card-body">
            <p class="text-sm text-gray-500 mb-5">
                {{ $t('message.intelligence_analytics_help') || 'Configure tracking keys for your store. Public key is used by tracker.js on the storefront.' }}
            </p>

            <form @submit.prevent="save">
                <div class="row">
                    <div class="form-col-12 sm:form-col-6">
                        <label class="db-field-title">{{ $t('label.status') }}</label>
                        <div class="db-field-radio-group">
                            <div class="db-field-radio">
                                <div class="custom-radio">
                                    <input :value="enums.activityEnum.ENABLE" v-model="form.analytics_enabled"
                                        id="intel_enable" type="radio" class="custom-radio-field" />
                                    <span class="custom-radio-span"></span>
                                </div>
                                <label for="intel_enable" class="db-field-label">{{ $t('label.enable') }}</label>
                            </div>
                            <div class="db-field-radio">
                                <div class="custom-radio">
                                    <input :value="enums.activityEnum.DISABLE" v-model="form.analytics_enabled"
                                        id="intel_disable" type="radio" class="custom-radio-field" />
                                    <span class="custom-radio-span"></span>
                                </div>
                                <label for="intel_disable" class="db-field-label">{{ $t('label.disable') }}</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-col-12 sm:form-col-6">
                        <label class="db-field-title" for="intel_name">{{ $t('label.name') }}</label>
                        <input v-model="form.name" type="text" id="intel_name" class="db-field-control" />
                    </div>

                    <div class="form-col-12 sm:form-col-6">
                        <label class="db-field-title" for="intel_domain">{{ $t('label.domain') || 'Domain' }}</label>
                        <input v-model="form.domain" type="text" id="intel_domain" class="db-field-control"
                            placeholder="ejeweller.pk" />
                    </div>

                    <div class="form-col-12 sm:form-col-6">
                        <label class="db-field-title" for="intel_origins">{{ $t('label.allowed_origins') || 'Allowed origins (CORS)' }}</label>
                        <input v-model="form.allowed_origins" type="text" id="intel_origins" class="db-field-control"
                            placeholder="https://ejeweller.pk, https://www.ejeweller.pk or *" />
                    </div>

                    <div class="form-col-12">
                        <label class="db-field-title" for="intel_public_key">{{ $t('label.public_key') || 'Public Key (tracker)' }}</label>
                        <div class="flex gap-2">
                            <input v-model="form.public_key" type="text" id="intel_public_key"
                                class="db-field-control font-mono text-sm" placeholder="pk_..." />
                            <button type="button" @click="copyKey(form.public_key)"
                                class="db-btn bg-gray-100 text-gray-700 shrink-0 px-4">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">{{ $t('message.public_key_readonly_hint') || 'Use Regenerate to create a new key, or paste an existing pk_ key.' }}</p>
                    </div>

                    <div v-if="revealedSecret" class="form-col-12 p-4 rounded-xl bg-amber-50 border border-amber-200">
                        <p class="text-sm font-semibold text-amber-800 mb-2">{{ $t('label.secret_key') || 'Secret Key' }} ({{ $t('label.one_time') || 'one time only' }})</p>
                        <div class="flex gap-2">
                            <input :value="revealedSecret" type="text" class="db-field-control font-mono text-sm" readonly />
                            <button type="button" @click="copyKey(revealedSecret)"
                                class="db-btn bg-amber-500 text-white shrink-0 px-4">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-col-12 text-sm text-gray-500 space-y-1">
                        <p><strong>Tracker:</strong> <code class="text-xs">{{ form.tracker_url }}</code></p>
                        <p><strong>Collect API:</strong> <code class="text-xs">{{ form.collect_url }}</code></p>
                    </div>

                    <div class="form-col-12 flex flex-wrap gap-3">
                        <button type="submit" class="text-white db-btn bg-primary">
                            <i class="lab lab-fill-save"></i>
                            <span>{{ $t('button.save') }}</span>
                        </button>
                        <button type="button" @click="regenerate" class="db-btn bg-gray-700 text-white">
                            <i class="fa-solid fa-key"></i>
                            <span>{{ $t('button.regenerate_keys') || 'Regenerate Keys' }}</span>
                        </button>
                        <router-link :to="{ name: 'admin.intelligence.dashboard' }"
                            class="db-btn bg-slate-800 text-white inline-flex items-center gap-2">
                            <i class="fa-solid fa-chart-line"></i>
                            <span>{{ $t('menu.intelligence_dashboard') || 'Open Dashboard' }}</span>
                        </router-link>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import activityEnum from '../../../../enums/modules/activityEnum';
import LoadingComponent from '../../components/LoadingComponent';
import alertService from '../../../../services/alertService';

export default {
    name: 'IntelligenceAnalyticsSettingsComponent',
    components: { LoadingComponent },
    data() {
        return {
            loading: { isActive: false },
            revealedSecret: null,
            form: {
                name: '',
                domain: '',
                public_key: '',
                analytics_enabled: activityEnum.ENABLE,
                allowed_origins: '*',
                tracker_url: '',
                collect_url: '',
            },
            enums: { activityEnum },
        };
    },
    mounted() {
        this.load();
    },
    methods: {
        load() {
            this.loading.isActive = true;
            axios.get('admin/setting/intelligence-analytics').then((res) => {
                this.applySettings(res.data.data);
                this.loading.isActive = false;
            }).catch((err) => {
                this.loading.isActive = false;
                alertService.error(this.connectionErrorMessage(err, 'Failed to load settings'));
            });
        },
        save() {
            this.loading.isActive = true;
            const payload = {
                name: this.form.name,
                domain: this.form.domain,
                analytics_enabled: this.form.analytics_enabled,
                allowed_origins: this.form.allowed_origins,
            };
            const pk = (this.form.public_key || '').trim();
            if (pk.startsWith('pk_') && pk.length >= 20 && !pk.includes('...')) {
                payload.public_key = pk;
            }
            axios.put('admin/setting/intelligence-analytics', payload).then((res) => {
                this.applySettings(res.data.data);
                this.loading.isActive = false;
                alertService.success(res.data.message || 'Settings saved');
            }).catch((err) => {
                this.loading.isActive = false;
                alertService.error(this.connectionErrorMessage(err, 'Save failed'));
            });
        },
        connectionErrorMessage(err, fallback) {
            if (err.response?.data?.message) {
                return err.response.data.message;
            }
            if (!err.response && (err.code === 'ERR_NETWORK' || err.message === 'Network Error')) {
                return 'Backend connection lost. Restart: php artisan serve (and npm run dev if using Vite).';
            }
            return err.message || fallback;
        },
        applySettings(d) {
            if (!d) return;
            this.form = {
                name: d.name || '',
                domain: d.domain || '',
                public_key: d.public_key || '',
                analytics_enabled: Number(d.analytics_enabled ?? this.enums.activityEnum.ENABLE),
                allowed_origins: d.allowed_origins || '*',
                tracker_url: d.tracker_url || '',
                collect_url: d.collect_url || '',
            };
        },
        regenerate() {
            if (!confirm('Generate new keys? Old public key will stop working until you save and deploy.')) {
                return;
            }
            this.loading.isActive = true;
            axios.post('admin/setting/intelligence-analytics/regenerate-keys').then((res) => {
                const d = res.data.data || {};
                this.form.public_key = d.public_key || '';
                this.revealedSecret = d.secret_key || null;
                this.loading.isActive = false;
                alertService.success(res.data.message || 'Keys regenerated');
            }).catch((err) => {
                this.loading.isActive = false;
                if (err.response?.data?.message) {
                    alertService.error(err.response.data.message);
                    return;
                }
                alertService.error(err.message || 'Regenerate failed');
            });
        },
        copyKey(text) {
            if (!text) return;
            navigator.clipboard.writeText(text).then(() => {
                alertService.success('Copied to clipboard');
            }).catch(() => alertService.error('Copy failed'));
        },
    },
};
</script>

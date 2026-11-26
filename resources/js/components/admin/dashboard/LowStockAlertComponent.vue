<template>
    <div class="col-12 mb-6" v-if="items.length > 0">
        <div class="db-card">
            <div class="db-card-header border-b border-gray-100">
                <h3 class="db-card-title capitalize">Low stock alerts</h3>
                <router-link
                    v-if="permissionChecker('products')"
                    :to="{ name: 'admin.products.list' }"
                    class="text-sm font-medium text-primary hover:underline"
                >
                    View products
                </router-link>
            </div>
            <div class="db-table-responsive">
                <table class="db-table">
                    <thead class="db-table-head">
                        <tr class="db-table-head-tr">
                            <th class="db-table-head-th">Product</th>
                            <th class="db-table-head-th">SKU</th>
                            <th class="db-table-head-th">Stock</th>
                            <th class="db-table-head-th">Warning at</th>
                        </tr>
                    </thead>
                    <tbody class="db-table-body">
                        <tr class="db-table-body-tr" v-for="item in items" :key="item.product_id">
                            <td class="db-table-body-td">
                                <router-link
                                    v-if="permissionChecker('products')"
                                    :to="{ name: 'admin.product.show', params: { id: item.product_id } }"
                                    class="font-medium text-heading hover:text-primary"
                                >
                                    {{ item.name }}
                                </router-link>
                                <span v-else>{{ item.name }}</span>
                            </td>
                            <td class="db-table-body-td">{{ item.sku || '—' }}</td>
                            <td class="db-table-body-td">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold text-[#B42318] bg-[#FEE4E2]">
                                    {{ item.stock }}
                                </span>
                            </td>
                            <td class="db-table-body-td">{{ item.threshold }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
import appService from "../../../services/appService";

export default {
    name: "LowStockAlertComponent",
    data() {
        return {
            items: [],
        };
    },
    mounted() {
        this.$store.dispatch("dashboard/lowStock").then((res) => {
            this.items = res.data?.data || [];
        }).catch(() => {
            this.items = [];
        });
    },
    methods: {
        permissionChecker(permission) {
            return appService.permissionChecker(permission);
        },
    },
};
</script>

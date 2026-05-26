<template>
    <article class="courier-slip" v-if="slip">
        <header class="courier-slip__header">
            <div class="courier-slip__brand">{{ slip.brandName }}</div>
            <div class="courier-slip__barcode-block">
                <div class="courier-slip__barcode" v-html="slip.orderRefBarcodeSvg"></div>
                <div class="courier-slip__barcode-label">{{ slip.orderRef }}</div>
            </div>
            <div class="courier-slip__barcode-block courier-slip__barcode-block--wide">
                <div class="courier-slip__barcode" v-html="slip.trackingBarcodeSvg"></div>
                <div class="courier-slip__barcode-label">{{ slip.trackingNo }}</div>
            </div>
            <div class="courier-slip__dest-code">{{ slip.destinationCode }}</div>
        </header>

        <div class="courier-slip__body">
            <div class="courier-slip__col courier-slip__col--left">
                <section class="courier-slip__section">
                    <h4 class="courier-slip__section-title">{{ $t('label.consignee_information') }}</h4>
                    <table class="courier-slip__kv">
                        <tbody>
                            <tr>
                                <th>{{ $t('label.name') }}</th>
                                <td>{{ slip.consigneeName }}</td>
                            </tr>
                            <tr>
                                <th>{{ $t('label.contact') }}</th>
                                <td>{{ slip.consigneePhone }}</td>
                            </tr>
                            <tr>
                                <th>{{ $t('label.delivery_address') }}</th>
                                <td>{{ slip.deliveryAddress }}</td>
                            </tr>
                        </tbody>
                    </table>
                </section>
                <section class="courier-slip__section">
                    <h4 class="courier-slip__section-title">{{ $t('label.shipper_information') }}</h4>
                    <table class="courier-slip__kv">
                        <tbody>
                            <tr>
                                <th>{{ $t('label.name') }}</th>
                                <td>{{ slip.shipperName }}</td>
                            </tr>
                            <tr>
                                <th>{{ $t('label.contact') }}</th>
                                <td>{{ slip.shipperPhone }}</td>
                            </tr>
                            <tr>
                                <th>{{ $t('label.pickup_address') }}</th>
                                <td>{{ slip.pickupAddress }}</td>
                            </tr>
                            <tr>
                                <th>{{ $t('label.return_address') }}</th>
                                <td>{{ slip.returnAddress }}</td>
                            </tr>
                        </tbody>
                    </table>
                </section>
            </div>

            <div class="courier-slip__col">
                <section class="courier-slip__section courier-slip__section--full">
                    <h4 class="courier-slip__section-title">{{ $t('label.shipment_information') }}</h4>
                    <table class="courier-slip__kv courier-slip__kv--shipment">
                        <tbody>
                            <tr>
                                <th>{{ $t('label.pieces') }}</th>
                                <td>{{ slip.pieces }}</td>
                            </tr>
                            <tr>
                                <th>{{ $t('label.order_ref') }}</th>
                                <td>{{ slip.orderRef }}</td>
                            </tr>
                            <tr>
                                <th>{{ $t('label.tracking_no') }}</th>
                                <td>{{ slip.trackingNo }}</td>
                            </tr>
                            <tr>
                                <th>{{ $t('label.origin') }}</th>
                                <td>{{ slip.origin }}</td>
                            </tr>
                            <tr>
                                <th>{{ $t('label.destination') }}</th>
                                <td>{{ slip.destination }}</td>
                            </tr>
                            <tr>
                                <th>{{ $t('label.return_city') }}</th>
                                <td>{{ slip.returnCity }}</td>
                            </tr>
                            <tr>
                                <th>{{ $t('label.remarks') }}</th>
                                <td class="courier-slip__remarks">{{ slip.remarks }}</td>
                            </tr>
                        </tbody>
                    </table>
                </section>
            </div>

            <div class="courier-slip__col courier-slip__col--order">
                <section class="courier-slip__section courier-slip__section--full">
                    <h4 class="courier-slip__section-title">{{ $t('label.order_information') }}</h4>
                    <div class="courier-slip__qr-wrap">
                        <img class="courier-slip__qr" :src="slip.qrCodeUrl" alt="QR" crossorigin="anonymous" />
                    </div>
                    <table class="courier-slip__kv">
                        <tbody>
                            <tr>
                                <th>{{ $t('label.amount') }}</th>
                                <td>{{ slip.amount }}/-</td>
                            </tr>
                            <tr>
                                <th>{{ $t('label.date') }}</th>
                                <td>{{ slip.date }}</td>
                            </tr>
                            <tr>
                                <th>{{ $t('label.order_type') }}</th>
                                <td>{{ slip.orderTypeLabel }}</td>
                            </tr>
                        </tbody>
                    </table>
                </section>
            </div>
        </div>

        <footer class="courier-slip__footer">
            <strong>{{ $t('label.order_details') }}:</strong>
            <span>{{ slip.orderDetails }}</span>
        </footer>
    </article>
</template>

<script>
export default {
    name: "OnlineOrderShippingSlipComponent",
    props: {
        slip: {
            type: Object,
            default: null,
        },
    },
};
</script>

<style>
/* Mirror of shippingSlipPrintCss for off-screen render */
.courier-slip {
    box-sizing: border-box;
    width: 100%;
    height: 100%;
    border: 1px solid #000;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 10px;
    line-height: 1.32;
    color: #000;
    background: #fff;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.courier-slip__header {
    display: grid;
    grid-template-columns: 1fr 1.1fr 1.4fr auto;
    align-items: center;
    gap: 3px;
    padding: 3px 5px;
    border-bottom: 1px solid #000;
    flex: 0 0 auto;
}
.courier-slip__brand { font-size: 19px; font-weight: 700; line-height: 1.1; }
.courier-slip__barcode-block { text-align: center; }
.courier-slip__barcode { display: flex; justify-content: center; min-height: 30px; }
.courier-slip__barcode svg { max-width: 100%; height: 30px; }
.courier-slip__barcode-label { font-size: 9px; font-weight: 700; margin-top: 1px; }
.courier-slip__dest-code { font-size: 17px; font-weight: 700; padding: 0 3px; text-align: center; }
.courier-slip__body {
    display: grid;
    grid-template-columns: 1.35fr 0.95fr 0.85fr;
    flex: 1 1 auto;
    min-height: 0;
}
.courier-slip__col {
    border-right: 1px solid #000;
    display: flex;
    flex-direction: column;
    min-height: 0;
}
.courier-slip__col:last-child { border-right: none; }
.courier-slip__col--left {
    display: grid;
    grid-template-rows: minmax(0, 1.05fr) minmax(0, 0.95fr);
}
.courier-slip__section {
    border-bottom: 1px solid #000;
    display: flex;
    flex-direction: column;
    min-height: 0;
    overflow: hidden;
}
.courier-slip__section--full {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    border-bottom: none;
    min-height: 0;
}
.courier-slip__section-title {
    margin: 0;
    padding: 3px 5px;
    background: #d3d3d3;
    font-size: 10px;
    font-weight: 700;
    border-bottom: 1px solid #000;
    flex: 0 0 auto;
}
.courier-slip__kv { width: 100%; border-collapse: collapse; table-layout: fixed; flex: 1 1 auto; }
.courier-slip__kv th, .courier-slip__kv td {
    border-bottom: 1px solid #000;
    padding: 3px 5px;
    vertical-align: top;
    text-align: left;
    line-height: 1.28;
}
.courier-slip__kv th { font-weight: 400; width: 36%; white-space: nowrap; font-size: 9.5px; }
.courier-slip__kv td { font-weight: 700; font-size: 10px; word-break: break-word; }
.courier-slip__kv--shipment th { width: 40%; }
.courier-slip__kv tr:last-child th, .courier-slip__kv tr:last-child td { border-bottom: none; }
.courier-slip__remarks { text-transform: uppercase; font-size: 9.5px; }
.courier-slip__qr-wrap {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2px 4px;
    border-bottom: 1px solid #000;
    flex: 0 0 auto;
}
.courier-slip__qr { width: 64px; height: 64px; object-fit: contain; }
.courier-slip__footer {
    border-top: 1px solid #000;
    padding: 4px 6px;
    font-size: 9.5px;
    line-height: 1.35;
    flex: 0 0 auto;
}
.courier-slip__footer strong { margin-right: 4px; }
</style>

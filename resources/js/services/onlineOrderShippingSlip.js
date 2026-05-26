import addressTypeEnum from "../enums/modules/addressTypeEnum";
import orderTypeEnum from "../enums/modules/orderTypeEnum";

function formatPhone(address) {
    if (!address) {
        return "";
    }
    const code = address.country_code || "";
    const phone = address.phone || "";
    return `${code}${phone}`.trim();
}

function formatAddress(address) {
    if (!address) {
        return "";
    }
    return [address.address, address.city, address.state, address.country, address.zip_code]
        .filter(Boolean)
        .join(", ");
}

function destinationCode(city) {
    if (!city) {
        return "";
    }
    const cleaned = city.replace(/[^a-zA-Z]/g, "");
    return cleaned.slice(0, 3).toUpperCase();
}

function trackingNumber(order) {
    const base = String(order.id || order.order_serial_no || "").replace(/\D/g, "");
    return base.padStart(14, "0").slice(-14);
}

function orderDetailsLine(products) {
    if (!products || !products.length) {
        return "";
    }
    return products
        .map((p) => `[ ${p.quantity} x ${p.product_name}${p.variation_names ? ` (${p.variation_names})` : ""} - ]`)
        .join(" ");
}

function piecesCount(products) {
    if (!products || !products.length) {
        return 1;
    }
    return products.reduce((sum, p) => sum + (Number(p.quantity) || 0), 0) || 1;
}

/** Simple visual barcode as inline SVG (print-friendly, no external deps). */
export function barcodeSvg(text, height = 34) {
    const value = String(text || "0");
    let x = 0;
    let rects = "";
    const unit = 2;

    for (let i = 0; i < value.length; i++) {
        const code = value.charCodeAt(i);
        const bits = [(code >> 2) & 1, (code >> 1) & 1, code & 1];
        bits.forEach((bit) => {
            if (bit) {
                const w = unit + (code % 2);
                rects += `<rect x="${x}" y="0" width="${w}" height="${height}" fill="#000"/>`;
                x += w;
            }
            x += unit;
        });
        x += unit;
    }

    const width = Math.max(x, 80);
    return `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">${rects}</svg>`;
}

export function qrCodeUrl(text) {
    const data = encodeURIComponent(String(text || ""));
    return `https://api.qrserver.com/v1/create-qr-code/?size=88x88&margin=0&data=${data}`;
}

export function buildShippingSlip(order, company = {}) {
    const addresses = order.order_address || [];
    const shipping = addresses.find((a) => a.address_type === addressTypeEnum.SHIPPING) || addresses[0] || null;
    const products = order.order_products || order.products || [];
    const tracking = trackingNumber(order);
    const originCity = company.company_city || "";
    const shipperAddress = company.company_address || "";
    const consigneeName = shipping?.full_name || order.user?.name || "";
    const consigneePhone = shipping ? formatPhone(shipping) : (order.user?.phone || "");
    const destinationCity = (shipping?.city || "").toUpperCase();

    return {
        brandName: company.company_name || "Courier",
        destinationCode: destinationCode(shipping?.city),
        orderRef: `#${order.order_serial_no}`,
        orderRefRaw: String(order.order_serial_no),
        trackingNo: tracking,
        consigneeName,
        consigneePhone,
        deliveryAddress: formatAddress(shipping),
        shipperName: company.company_name || "",
        shipperPhone: `${company.company_calling_code || ""}${company.company_phone || ""}`.trim(),
        pickupAddress: shipperAddress,
        returnAddress: shipperAddress,
        pieces: piecesCount(products),
        origin: originCity || "—",
        destination: destinationCity || "—",
        returnCity: originCity || "—",
        remarks: (order.note || "CALL BEFORE DELIVERY").toUpperCase(),
        amount: order.total_amount_price || order.total_currency_price || "",
        date: order.order_date || order.order_datetime || "",
        orderTypeLabel: order.order_type === orderTypeEnum.PICK_UP ? "Pick Up" : "Normal",
        orderDetails: orderDetailsLine(products),
        orderRefBarcodeSvg: barcodeSvg(String(order.order_serial_no)),
        trackingBarcodeSvg: barcodeSvg(tracking),
        qrCodeUrl: qrCodeUrl(`ORDER:${order.order_serial_no}|${tracking}`),
    };
}

export function chunkSlips(slips, perPage = 3) {
    const pages = [];
    for (let i = 0; i < slips.length; i += perPage) {
        const chunk = slips.slice(i, i + perPage);
        while (chunk.length < perPage) {
            chunk.push(null);
        }
        pages.push(chunk);
    }
    return pages;
}

export function preloadImages(urls) {
    const unique = [...new Set(urls.filter(Boolean))];
    return Promise.all(
        unique.map(
            (url) =>
                new Promise((resolve) => {
                    const img = new Image();
                    img.onload = () => resolve();
                    img.onerror = () => resolve();
                    img.src = url;
                })
        )
    );
}

/** Full CSS injected into the print popup (vue3-print-nb iframe missed scoped/off-screen styles). */
export const shippingSlipPrintCss = `
@page { size: A4 portrait; margin: 4mm; }
* { box-sizing: border-box; }
body { margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; color: #000; background: #fff; }
.shipping-slip-page {
  width: 100%;
  height: 287mm;
  page-break-after: always;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 2mm;
}
.shipping-slip-page:last-child { page-break-after: auto; }
.shipping-slip-slot { flex: 0 0 32%; min-height: 32%; max-height: 32%; overflow: hidden; }
.courier-slip {
  width: 100%;
  height: 100%;
  border: 1px solid #000;
  font-size: 9px;
  line-height: 1.25;
  background: #fff;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.courier-slip__header {
  display: grid;
  grid-template-columns: 1fr 1.1fr 1.4fr auto;
  align-items: center;
  gap: 4px;
  padding: 4px 6px;
  border-bottom: 1px solid #000;
}
.courier-slip__brand { font-size: 18px; font-weight: 700; }
.courier-slip__barcode-block { text-align: center; }
.courier-slip__barcode { display: flex; justify-content: center; min-height: 34px; }
.courier-slip__barcode svg { max-width: 100%; height: 34px; }
.courier-slip__barcode-label { font-size: 8px; font-weight: 700; margin-top: 1px; }
.courier-slip__dest-code { font-size: 16px; font-weight: 700; padding: 0 4px; text-align: center; }
.courier-slip__body {
  display: grid;
  grid-template-columns: 1.35fr 0.95fr 0.85fr;
  flex: 1;
  min-height: 0;
}
.courier-slip__col { border-right: 1px solid #000; display: flex; flex-direction: column; }
.courier-slip__col:last-child { border-right: none; }
.courier-slip__section { border-bottom: 1px solid #000; flex: 1; }
.courier-slip__section--full { flex: 1; display: flex; flex-direction: column; border-bottom: none; }
.courier-slip__section-title {
  margin: 0;
  padding: 2px 4px;
  background: #d3d3d3;
  font-size: 8px;
  font-weight: 700;
  border-bottom: 1px solid #000;
}
.courier-slip__kv { width: 100%; border-collapse: collapse; }
.courier-slip__kv th, .courier-slip__kv td {
  border-bottom: 1px solid #000;
  padding: 2px 4px;
  vertical-align: top;
  text-align: left;
}
.courier-slip__kv th { font-weight: 400; width: 38%; white-space: nowrap; }
.courier-slip__kv td { font-weight: 700; }
.courier-slip__kv--shipment th { width: 42%; }
.courier-slip__remarks { text-transform: uppercase; font-size: 8px; }
.courier-slip__qr-wrap {
  display: flex;
  justify-content: center;
  padding: 4px;
  border-bottom: 1px solid #000;
}
.courier-slip__qr { width: 72px; height: 72px; object-fit: contain; }
.courier-slip__footer { border-top: 1px solid #000; padding: 3px 5px; font-size: 8px; }
.courier-slip__footer strong { margin-right: 4px; }
`;

export function openPrintWindow(html, title = "Print") {
    const printWindow = window.open("", "_blank");
    if (!printWindow) {
        return null;
    }

    printWindow.document.open();
    printWindow.document.write(
        `<!DOCTYPE html><html><head><meta charset="utf-8"><title>${title}</title>` +
            `<style>${shippingSlipPrintCss}</style></head><body>${html}</body></html>`
    );
    printWindow.document.close();

    const triggerPrint = () => {
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    };

    const images = printWindow.document.images;
    if (!images.length) {
        setTimeout(triggerPrint, 200);
        return printWindow;
    }

    let loaded = 0;
    const onImageDone = () => {
        loaded += 1;
        if (loaded >= images.length) {
            triggerPrint();
        }
    };

    Array.from(images).forEach((img) => {
        if (img.complete) {
            onImageDone();
        } else {
            img.onload = onImageDone;
            img.onerror = onImageDone;
        }
    });

    setTimeout(triggerPrint, 2500);
    return printWindow;
}

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

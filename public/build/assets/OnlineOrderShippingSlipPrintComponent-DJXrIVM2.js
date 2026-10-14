import{_ as w,o as p,c as _,a as e,t as n,b as x,B as $,l as f,r as S,w as C,G as P,F as b,e as y,n as O,f as z}from"./appZone-CqK0bLkK.js";import{a as N}from"./addressTypeEnum-B8tmtoFX.js";const R={name:"OnlineOrderShippingSlipComponent",props:{slip:{type:Object,default:null}}},A={key:0,class:"courier-slip"},B={class:"courier-slip__header"},T={class:"courier-slip__brand"},q={class:"courier-slip__barcode-block"},L=["innerHTML"],E={class:"courier-slip__barcode-label"},D={class:"courier-slip__barcode-block courier-slip__barcode-block--wide"},U=["innerHTML"],I={class:"courier-slip__barcode-label"},j={class:"courier-slip__dest-code"},H={class:"courier-slip__body"},M={class:"courier-slip__col courier-slip__col--left"},F={class:"courier-slip__section"},V={class:"courier-slip__section-title"},G={class:"courier-slip__kv"},W={class:"courier-slip__section"},Y={class:"courier-slip__section-title"},K={class:"courier-slip__kv"},Q={class:"courier-slip__col"},Z={class:"courier-slip__section courier-slip__section--full"},J={class:"courier-slip__section-title"},X={class:"courier-slip__kv courier-slip__kv--shipment"},ee={class:"courier-slip__remarks"},ie={class:"courier-slip__col courier-slip__col--order"},te={class:"courier-slip__section courier-slip__section--full"},ne={class:"courier-slip__section-title"},le={class:"courier-slip__qr-wrap"},re=["src"],oe={class:"courier-slip__kv"},se={class:"courier-slip__footer"};function ae(i,r,t,l,o,c){return t.slip?(p(),_("article",A,[e("header",B,[e("div",T,n(t.slip.brandName),1),e("div",q,[e("div",{class:"courier-slip__barcode",innerHTML:t.slip.orderRefBarcodeSvg},null,8,L),e("div",E,n(t.slip.orderRef),1)]),e("div",D,[e("div",{class:"courier-slip__barcode",innerHTML:t.slip.trackingBarcodeSvg},null,8,U),e("div",I,n(t.slip.trackingNo),1)]),e("div",j,n(t.slip.destinationCode),1)]),e("div",H,[e("div",M,[e("section",F,[e("h4",V,n(i.$t("label.consignee_information")),1),e("table",G,[e("tbody",null,[e("tr",null,[e("th",null,n(i.$t("label.name")),1),e("td",null,n(t.slip.consigneeName),1)]),e("tr",null,[e("th",null,n(i.$t("label.contact")),1),e("td",null,n(t.slip.consigneePhone),1)]),e("tr",null,[e("th",null,n(i.$t("label.delivery_address")),1),e("td",null,n(t.slip.deliveryAddress),1)])])])]),e("section",W,[e("h4",Y,n(i.$t("label.shipper_information")),1),e("table",K,[e("tbody",null,[e("tr",null,[e("th",null,n(i.$t("label.name")),1),e("td",null,n(t.slip.shipperName),1)]),e("tr",null,[e("th",null,n(i.$t("label.contact")),1),e("td",null,n(t.slip.shipperPhone),1)]),e("tr",null,[e("th",null,n(i.$t("label.pickup_address")),1),e("td",null,n(t.slip.pickupAddress),1)]),e("tr",null,[e("th",null,n(i.$t("label.return_address")),1),e("td",null,n(t.slip.returnAddress),1)])])])])]),e("div",Q,[e("section",Z,[e("h4",J,n(i.$t("label.shipment_information")),1),e("table",X,[e("tbody",null,[e("tr",null,[e("th",null,n(i.$t("label.pieces")),1),e("td",null,n(t.slip.pieces),1)]),e("tr",null,[e("th",null,n(i.$t("label.order_ref")),1),e("td",null,n(t.slip.orderRef),1)]),e("tr",null,[e("th",null,n(i.$t("label.tracking_no")),1),e("td",null,n(t.slip.trackingNo),1)]),e("tr",null,[e("th",null,n(i.$t("label.origin")),1),e("td",null,n(t.slip.origin),1)]),e("tr",null,[e("th",null,n(i.$t("label.destination")),1),e("td",null,n(t.slip.destination),1)]),e("tr",null,[e("th",null,n(i.$t("label.return_city")),1),e("td",null,n(t.slip.returnCity),1)]),e("tr",null,[e("th",null,n(i.$t("label.remarks")),1),e("td",ee,n(t.slip.remarks),1)])])])])]),e("div",ie,[e("section",te,[e("h4",ne,n(i.$t("label.order_information")),1),e("div",le,[e("img",{class:"courier-slip__qr",src:t.slip.qrCodeUrl,alt:"QR",crossorigin:"anonymous"},null,8,re)]),e("table",oe,[e("tbody",null,[e("tr",null,[e("th",null,n(i.$t("label.amount")),1),e("td",null,n(t.slip.amount)+"/-",1)]),e("tr",null,[e("th",null,n(i.$t("label.date")),1),e("td",null,n(t.slip.date),1)]),e("tr",null,[e("th",null,n(i.$t("label.order_type")),1),e("td",null,n(t.slip.orderTypeLabel),1)])])])])])]),e("footer",se,[e("strong",null,n(i.$t("label.order_details"))+":",1),e("span",null,n(t.slip.orderDetails),1)])])):x("",!0)}const ce=w(R,[["render",ae]]);function de(i){if(!i)return"";const r=i.country_code||"",t=i.phone||"";return`${r}${t}`.trim()}function pe(i){return i?[i.address,i.city,i.state,i.country,i.zip_code].filter(Boolean).join(", "):""}function ue(i){return i?i.replace(/[^a-zA-Z]/g,"").slice(0,3).toUpperCase():""}function _e(i){return String(i.id||i.order_serial_no||"").replace(/\D/g,"").padStart(14,"0").slice(-14)}function he(i){return!i||!i.length?"":i.map(r=>`[ ${r.quantity} x ${r.product_name}${r.variation_names?` (${r.variation_names})`:""} - ]`).join(" ")}function me(i){return!i||!i.length?1:i.reduce((r,t)=>r+(Number(t.quantity)||0),0)||1}function v(i,r=34){const t=String(i||"0");let l=0,o="";const c=2;for(let s=0;s<t.length;s++){const d=t.charCodeAt(s);[d>>2&1,d>>1&1,d&1].forEach(h=>{if(h){const m=c+d%2;o+=`<rect x="${l}" y="0" width="${m}" height="${r}" fill="#000"/>`,l+=m}l+=c}),l+=c}const a=Math.max(l,80);return`<svg xmlns="http://www.w3.org/2000/svg" width="${a}" height="${r}" viewBox="0 0 ${a} ${r}">${o}</svg>`}function fe(i){return`https://api.qrserver.com/v1/create-qr-code/?size=88x88&margin=0&data=${encodeURIComponent(String(i||""))}`}function ge(i,r={}){var m,g;const t=i.order_address||[],l=t.find(k=>k.address_type===N.SHIPPING)||t[0]||null,o=i.order_products||i.products||[],c=_e(i),a=r.company_city||"",s=r.company_address||"",d=(l==null?void 0:l.full_name)||((m=i.user)==null?void 0:m.name)||"",u=l?de(l):((g=i.user)==null?void 0:g.phone)||"",h=((l==null?void 0:l.city)||"").toUpperCase();return{brandName:r.company_name||"Courier",destinationCode:ue(l==null?void 0:l.city),orderRef:`#${i.order_serial_no}`,orderRefRaw:String(i.order_serial_no),trackingNo:c,consigneeName:d,consigneePhone:u,deliveryAddress:pe(l),shipperName:r.company_name||"",shipperPhone:`${r.company_calling_code||""}${r.company_phone||""}`.trim(),pickupAddress:s,returnAddress:s,pieces:me(o),origin:a||"—",destination:h||"—",returnCity:a||"—",remarks:(i.note||"CALL BEFORE DELIVERY").toUpperCase(),amount:i.total_amount_price||i.total_currency_price||"",date:i.order_date||i.order_datetime||"",orderTypeLabel:i.order_type===$.PICK_UP?"Pick Up":"Normal",orderDetails:he(o),orderRefBarcodeSvg:v(String(i.order_serial_no)),trackingBarcodeSvg:v(c),qrCodeUrl:fe(`ORDER:${i.order_serial_no}|${c}`)}}function be(i,r=3){const t=[];for(let l=0;l<i.length;l+=r){const o=i.slice(l,l+r);for(;o.length<r;)o.push(null);t.push(o)}return t}function ye(i){const r=[...new Set(i.filter(Boolean))];return Promise.all(r.map(t=>new Promise(l=>{const o=new Image;o.onload=()=>l(),o.onerror=()=>l(),o.src=t})))}const ve=`
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
  gap: 1.5mm;
}
.shipping-slip-page:last-child { page-break-after: auto; }
.shipping-slip-slot {
  flex: 0 0 32%;
  min-height: 32%;
  max-height: 32%;
  overflow: hidden;
  display: flex;
}
.shipping-slip-slot--empty { visibility: hidden; pointer-events: none; }
.courier-slip {
  width: 100%;
  height: 100%;
  border: 1px solid #000;
  font-size: 10px;
  line-height: 1.32;
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
.courier-slip__barcode-label { font-size: 9px; font-weight: 700; margin-top: 1px; line-height: 1.2; }
.courier-slip__dest-code { font-size: 17px; font-weight: 700; padding: 0 3px; text-align: center; line-height: 1; }
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
  line-height: 1.2;
}
.courier-slip__kv {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
  flex: 1 1 auto;
}
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
.courier-slip__remarks { text-transform: uppercase; font-size: 9.5px; line-height: 1.25; }
.courier-slip__qr-wrap {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 2px 4px;
  border-bottom: 1px solid #000;
  flex: 0 0 auto;
}
.courier-slip__qr { width: 64px; height: 64px; object-fit: contain; display: block; }
.courier-slip__footer {
  border-top: 1px solid #000;
  padding: 4px 6px;
  font-size: 9.5px;
  line-height: 1.35;
  flex: 0 0 auto;
}
.courier-slip__footer strong { margin-right: 4px; }
`;function we(i,r="Print"){const t=window.open("","_blank");if(!t)return null;t.document.open(),t.document.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><title>${r}</title><style>${ve}</style></head><body>${i}</body></html>`),t.document.close();const l=()=>{t.focus(),t.print(),t.close()},o=t.document.images;if(!o.length)return setTimeout(l,200),t;let c=0;const a=()=>{c+=1,c>=o.length&&l()};return Array.from(o).forEach(s=>{s.complete?a():(s.onload=a,s.onerror=a)}),setTimeout(l,2500),t}const xe={name:"OnlineOrderShippingSlipPrintComponent",components:{OnlineOrderShippingSlipComponent:ce},data(){return{slipPages:[]}},mounted(){this.$store.dispatch("company/lists").catch(()=>{})},computed:{company(){return this.$store.getters["company/lists"]||{}}},methods:{async printOrders(i){var a;const r=[...new Set((i||[]).filter(Boolean))];if(!r.length)return!1;const t=[];for(const s of r){const d=await this.$store.dispatch("onlineOrder/show",s);t.push(ge(d.data.data,this.company))}if(!t.length)return f.error(this.$t("message.no_data_found")),!1;await ye(t.map(s=>s.qrCodeUrl)),this.slipPages=be(t,3),await this.$nextTick(),await new Promise(s=>setTimeout(s,350));const l=this.$refs.printRoot,o=(a=l==null?void 0:l.innerHTML)==null?void 0:a.trim();return o?we(o,this.$t("menu.online_orders"))?!0:(f.error("Please allow pop-ups to print shipping slips."),!1):(f.error(this.$t("message.something_wrong")),!1)},clear(){this.slipPages=[]}}},ke={ref:"printRoot",class:"shipping-slip-print-root"};function $e(i,r,t,l,o,c){const a=S("OnlineOrderShippingSlipComponent");return C((p(),_("div",ke,[(p(!0),_(b,null,y(o.slipPages,(s,d)=>(p(),_("div",{key:"page-"+d,class:"shipping-slip-page"},[(p(!0),_(b,null,y(s,(u,h)=>(p(),_("div",{key:"slot-"+d+"-"+h,class:O(["shipping-slip-slot",{"shipping-slip-slot--empty":!u}])},[u?(p(),z(a,{key:0,slip:u},null,8,["slip"])):x("",!0)],2))),128))]))),128))],512)),[[P,o.slipPages.length>0]])}const Pe=w(xe,[["render",$e],["__scopeId","data-v-46f66428"]]);export{Pe as O};

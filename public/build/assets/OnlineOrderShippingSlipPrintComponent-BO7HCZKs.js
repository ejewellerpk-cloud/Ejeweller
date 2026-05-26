import{o as $,a as f}from"./app-DybyxEO6.js";import{_ as w}from"./vendor-quill-DfKHtYZQ.js";import{c as p,d as u,m as e,t as n,y as k,l as S,E as C,N as P,F as b,v as y,z as O}from"./vendor-vue-core-C3HMa0WP.js";import{a as N}from"./addressTypeEnum-B8tmtoFX.js";const R={name:"OnlineOrderShippingSlipComponent",props:{slip:{type:Object,default:null}}},A={key:0,class:"courier-slip"},T={class:"courier-slip__header"},q={class:"courier-slip__brand"},z={class:"courier-slip__barcode-block"},B=["innerHTML"],E={class:"courier-slip__barcode-label"},L={class:"courier-slip__barcode-block courier-slip__barcode-block--wide"},D=["innerHTML"],U={class:"courier-slip__barcode-label"},I={class:"courier-slip__dest-code"},j={class:"courier-slip__body"},H={class:"courier-slip__col"},M={class:"courier-slip__section"},F={class:"courier-slip__section-title"},V={class:"courier-slip__kv"},W={class:"courier-slip__section"},Y={class:"courier-slip__section-title"},G={class:"courier-slip__kv"},K={class:"courier-slip__col"},Q={class:"courier-slip__section courier-slip__section--full"},Z={class:"courier-slip__section-title"},J={class:"courier-slip__kv courier-slip__kv--shipment"},X={class:"courier-slip__remarks"},ee={class:"courier-slip__col courier-slip__col--order"},te={class:"courier-slip__section courier-slip__section--full"},ie={class:"courier-slip__section-title"},ne={class:"courier-slip__qr-wrap"},re=["src"],oe={class:"courier-slip__kv"},le={class:"courier-slip__footer"};function se(t,o,i,r,l,c){return i.slip?(p(),u("article",A,[e("header",T,[e("div",q,n(i.slip.brandName),1),e("div",z,[e("div",{class:"courier-slip__barcode",innerHTML:i.slip.orderRefBarcodeSvg},null,8,B),e("div",E,n(i.slip.orderRef),1)]),e("div",L,[e("div",{class:"courier-slip__barcode",innerHTML:i.slip.trackingBarcodeSvg},null,8,D),e("div",U,n(i.slip.trackingNo),1)]),e("div",I,n(i.slip.destinationCode),1)]),e("div",j,[e("div",H,[e("section",M,[e("h4",F,n(t.$t("label.consignee_information")),1),e("table",V,[e("tbody",null,[e("tr",null,[e("th",null,n(t.$t("label.name")),1),e("td",null,n(i.slip.consigneeName),1)]),e("tr",null,[e("th",null,n(t.$t("label.contact")),1),e("td",null,n(i.slip.consigneePhone),1)]),e("tr",null,[e("th",null,n(t.$t("label.delivery_address")),1),e("td",null,n(i.slip.deliveryAddress),1)])])])]),e("section",W,[e("h4",Y,n(t.$t("label.shipper_information")),1),e("table",G,[e("tbody",null,[e("tr",null,[e("th",null,n(t.$t("label.name")),1),e("td",null,n(i.slip.shipperName),1)]),e("tr",null,[e("th",null,n(t.$t("label.contact")),1),e("td",null,n(i.slip.shipperPhone),1)]),e("tr",null,[e("th",null,n(t.$t("label.pickup_address")),1),e("td",null,n(i.slip.pickupAddress),1)]),e("tr",null,[e("th",null,n(t.$t("label.return_address")),1),e("td",null,n(i.slip.returnAddress),1)])])])])]),e("div",K,[e("section",Q,[e("h4",Z,n(t.$t("label.shipment_information")),1),e("table",J,[e("tbody",null,[e("tr",null,[e("th",null,n(t.$t("label.pieces")),1),e("td",null,n(i.slip.pieces),1)]),e("tr",null,[e("th",null,n(t.$t("label.order_ref")),1),e("td",null,n(i.slip.orderRef),1)]),e("tr",null,[e("th",null,n(t.$t("label.tracking_no")),1),e("td",null,n(i.slip.trackingNo),1)]),e("tr",null,[e("th",null,n(t.$t("label.origin")),1),e("td",null,n(i.slip.origin),1)]),e("tr",null,[e("th",null,n(t.$t("label.destination")),1),e("td",null,n(i.slip.destination),1)]),e("tr",null,[e("th",null,n(t.$t("label.return_city")),1),e("td",null,n(i.slip.returnCity),1)]),e("tr",null,[e("th",null,n(t.$t("label.remarks")),1),e("td",X,n(i.slip.remarks),1)])])])])]),e("div",ee,[e("section",te,[e("h4",ie,n(t.$t("label.order_information")),1),e("div",ne,[e("img",{class:"courier-slip__qr",src:i.slip.qrCodeUrl,alt:"QR",crossorigin:"anonymous"},null,8,re)]),e("table",oe,[e("tbody",null,[e("tr",null,[e("th",null,n(t.$t("label.amount")),1),e("td",null,n(i.slip.amount)+"/-",1)]),e("tr",null,[e("th",null,n(t.$t("label.date")),1),e("td",null,n(i.slip.date),1)]),e("tr",null,[e("th",null,n(t.$t("label.order_type")),1),e("td",null,n(i.slip.orderTypeLabel),1)])])])])])]),e("footer",le,[e("strong",null,n(t.$t("label.order_details"))+":",1),e("span",null,n(i.slip.orderDetails),1)])])):k("",!0)}const ae=w(R,[["render",se]]);function ce(t){if(!t)return"";const o=t.country_code||"",i=t.phone||"";return`${o}${i}`.trim()}function de(t){return t?[t.address,t.city,t.state,t.country,t.zip_code].filter(Boolean).join(", "):""}function pe(t){return t?t.replace(/[^a-zA-Z]/g,"").slice(0,3).toUpperCase():""}function ue(t){return String(t.id||t.order_serial_no||"").replace(/\D/g,"").padStart(14,"0").slice(-14)}function _e(t){return!t||!t.length?"":t.map(o=>`[ ${o.quantity} x ${o.product_name}${o.variation_names?` (${o.variation_names})`:""} - ]`).join(" ")}function he(t){return!t||!t.length?1:t.reduce((o,i)=>o+(Number(i.quantity)||0),0)||1}function v(t,o=34){const i=String(t||"0");let r=0,l="";const c=2;for(let s=0;s<i.length;s++){const d=i.charCodeAt(s);[d>>2&1,d>>1&1,d&1].forEach(h=>{if(h){const m=c+d%2;l+=`<rect x="${r}" y="0" width="${m}" height="${o}" fill="#000"/>`,r+=m}r+=c}),r+=c}const a=Math.max(r,80);return`<svg xmlns="http://www.w3.org/2000/svg" width="${a}" height="${o}" viewBox="0 0 ${a} ${o}">${l}</svg>`}function me(t){return`https://api.qrserver.com/v1/create-qr-code/?size=88x88&margin=0&data=${encodeURIComponent(String(t||""))}`}function fe(t,o={}){var m,g;const i=t.order_address||[],r=i.find(x=>x.address_type===N.SHIPPING)||i[0]||null,l=t.order_products||t.products||[],c=ue(t),a=o.company_city||"",s=o.company_address||"",d=(r==null?void 0:r.full_name)||((m=t.user)==null?void 0:m.name)||"",_=r?ce(r):((g=t.user)==null?void 0:g.phone)||"",h=((r==null?void 0:r.city)||"").toUpperCase();return{brandName:o.company_name||"Courier",destinationCode:pe(r==null?void 0:r.city),orderRef:`#${t.order_serial_no}`,orderRefRaw:String(t.order_serial_no),trackingNo:c,consigneeName:d,consigneePhone:_,deliveryAddress:de(r),shipperName:o.company_name||"",shipperPhone:`${o.company_calling_code||""}${o.company_phone||""}`.trim(),pickupAddress:s,returnAddress:s,pieces:he(l),origin:a||"—",destination:h||"—",returnCity:a||"—",remarks:(t.note||"CALL BEFORE DELIVERY").toUpperCase(),amount:t.total_amount_price||t.total_currency_price||"",date:t.order_date||t.order_datetime||"",orderTypeLabel:t.order_type===$.PICK_UP?"Pick Up":"Normal",orderDetails:_e(l),orderRefBarcodeSvg:v(String(t.order_serial_no)),trackingBarcodeSvg:v(c),qrCodeUrl:me(`ORDER:${t.order_serial_no}|${c}`)}}function ge(t,o=3){const i=[];for(let r=0;r<t.length;r+=o){const l=t.slice(r,r+o);for(;l.length<o;)l.push(null);i.push(l)}return i}function be(t){const o=[...new Set(t.filter(Boolean))];return Promise.all(o.map(i=>new Promise(r=>{const l=new Image;l.onload=()=>r(),l.onerror=()=>r(),l.src=i})))}const ye=`
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
`;function ve(t,o="Print"){const i=window.open("","_blank");if(!i)return null;i.document.open(),i.document.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><title>${o}</title><style>${ye}</style></head><body>${t}</body></html>`),i.document.close();const r=()=>{i.focus(),i.print(),i.close()},l=i.document.images;if(!l.length)return setTimeout(r,200),i;let c=0;const a=()=>{c+=1,c>=l.length&&r()};return Array.from(l).forEach(s=>{s.complete?a():(s.onload=a,s.onerror=a)}),setTimeout(r,2500),i}const we={name:"OnlineOrderShippingSlipPrintComponent",components:{OnlineOrderShippingSlipComponent:ae},data(){return{slipPages:[]}},mounted(){this.$store.dispatch("company/lists").catch(()=>{})},computed:{company(){return this.$store.getters["company/lists"]||{}}},methods:{async printOrders(t){var a;const o=[...new Set((t||[]).filter(Boolean))];if(!o.length)return!1;const i=[];for(const s of o){const d=await this.$store.dispatch("onlineOrder/show",s);i.push(fe(d.data.data,this.company))}if(!i.length)return f.error(this.$t("message.no_data_found")),!1;await be(i.map(s=>s.qrCodeUrl)),this.slipPages=ge(i,3),await this.$nextTick(),await new Promise(s=>setTimeout(s,350));const r=this.$refs.printRoot,l=(a=r==null?void 0:r.innerHTML)==null?void 0:a.trim();return l?ve(l,this.$t("menu.online_orders"))?!0:(f.error("Please allow pop-ups to print shipping slips."),!1):(f.error(this.$t("message.something_wrong")),!1)},clear(){this.slipPages=[]}}},ke={ref:"printRoot",class:"shipping-slip-print-root"};function xe(t,o,i,r,l,c){const a=S("OnlineOrderShippingSlipComponent");return C((p(),u("div",ke,[(p(!0),u(b,null,y(l.slipPages,(s,d)=>(p(),u("div",{key:"page-"+d,class:"shipping-slip-page"},[(p(!0),u(b,null,y(s,(_,h)=>(p(),u("div",{key:"slot-"+d+"-"+h,class:"shipping-slip-slot"},[_?(p(),O(a,{key:0,slip:_},null,8,["slip"])):k("",!0)]))),128))]))),128))],512)),[[P,l.slipPages.length>0]])}const Oe=w(we,[["render",xe],["__scopeId","data-v-7cfd4e3f"]]);export{Oe as O};

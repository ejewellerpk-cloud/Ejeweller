import{_ as v,o as T,d as _,a0 as w,a as $}from"./appZone-BlG3yHe-.js";import{v as u,c as p,f as t,t as o,e as h,k as E,M as x,N,F as y,j as C,n as O,b as L,y as R,i as b,g as q,Y as z,a6 as M}from"./vendor-vuex-CplFPXFY.js";import{a as B}from"./addressTypeEnum-B8tmtoFX.js";import{a as P}from"./vendor-axios-BuNY4Ty6.js";import{L as D}from"./LoadingComponent-BXYj7yb9.js";const I={name:"OnlineOrderShippingSlipComponent",props:{slip:{type:Object,default:null}}},U={key:0,class:"courier-slip"},j={class:"courier-slip__header"},H={class:"courier-slip__brand"},V={class:"courier-slip__barcode-block"},W=["innerHTML"],F={class:"courier-slip__barcode-label"},Y={class:"courier-slip__barcode-block courier-slip__barcode-block--wide"},G=["innerHTML"],K={class:"courier-slip__barcode-label"},Q={class:"courier-slip__dest-code"},Z={class:"courier-slip__body"},J={class:"courier-slip__col courier-slip__col--left"},X={class:"courier-slip__section"},ee={class:"courier-slip__section-title"},te={class:"courier-slip__kv"},se={class:"courier-slip__section"},ie={class:"courier-slip__section-title"},oe={class:"courier-slip__kv"},ne={class:"courier-slip__col"},le={class:"courier-slip__section courier-slip__section--full"},re={class:"courier-slip__section-title"},ae={class:"courier-slip__kv courier-slip__kv--shipment"},ce={class:"courier-slip__remarks"},de={class:"courier-slip__col courier-slip__col--order"},ue={class:"courier-slip__section courier-slip__section--full"},pe={class:"courier-slip__section-title"},he={class:"courier-slip__qr-wrap"},_e=["src"],me={class:"courier-slip__kv"},fe={class:"courier-slip__footer"};function ge(e,i,s,n,l,r){return s.slip?(u(),p("article",U,[t("header",j,[t("div",H,o(s.slip.brandName),1),t("div",V,[t("div",{class:"courier-slip__barcode",innerHTML:s.slip.orderRefBarcodeSvg},null,8,W),t("div",F,o(s.slip.orderRef),1)]),t("div",Y,[t("div",{class:"courier-slip__barcode",innerHTML:s.slip.trackingBarcodeSvg},null,8,G),t("div",K,o(s.slip.trackingNo),1)]),t("div",Q,o(s.slip.destinationCode),1)]),t("div",Z,[t("div",J,[t("section",X,[t("h4",ee,o(e.$t("label.consignee_information")),1),t("table",te,[t("tbody",null,[t("tr",null,[t("th",null,o(e.$t("label.name")),1),t("td",null,o(s.slip.consigneeName),1)]),t("tr",null,[t("th",null,o(e.$t("label.contact")),1),t("td",null,o(s.slip.consigneePhone),1)]),t("tr",null,[t("th",null,o(e.$t("label.delivery_address")),1),t("td",null,o(s.slip.deliveryAddress),1)])])])]),t("section",se,[t("h4",ie,o(e.$t("label.shipper_information")),1),t("table",oe,[t("tbody",null,[t("tr",null,[t("th",null,o(e.$t("label.name")),1),t("td",null,o(s.slip.shipperName),1)]),t("tr",null,[t("th",null,o(e.$t("label.contact")),1),t("td",null,o(s.slip.shipperPhone),1)]),t("tr",null,[t("th",null,o(e.$t("label.pickup_address")),1),t("td",null,o(s.slip.pickupAddress),1)]),t("tr",null,[t("th",null,o(e.$t("label.return_address")),1),t("td",null,o(s.slip.returnAddress),1)])])])])]),t("div",ne,[t("section",le,[t("h4",re,o(e.$t("label.shipment_information")),1),t("table",ae,[t("tbody",null,[t("tr",null,[t("th",null,o(e.$t("label.pieces")),1),t("td",null,o(s.slip.pieces),1)]),t("tr",null,[t("th",null,o(e.$t("label.order_ref")),1),t("td",null,o(s.slip.orderRef),1)]),t("tr",null,[t("th",null,o(e.$t("label.tracking_no")),1),t("td",null,o(s.slip.trackingNo),1)]),t("tr",null,[t("th",null,o(e.$t("label.origin")),1),t("td",null,o(s.slip.origin),1)]),t("tr",null,[t("th",null,o(e.$t("label.destination")),1),t("td",null,o(s.slip.destination),1)]),t("tr",null,[t("th",null,o(e.$t("label.return_city")),1),t("td",null,o(s.slip.returnCity),1)]),t("tr",null,[t("th",null,o(e.$t("label.remarks")),1),t("td",ce,o(s.slip.remarks),1)])])])])]),t("div",de,[t("section",ue,[t("h4",pe,o(e.$t("label.order_information")),1),t("div",he,[t("img",{class:"courier-slip__qr",src:s.slip.qrCodeUrl,alt:"QR",crossorigin:"anonymous"},null,8,_e)]),t("table",me,[t("tbody",null,[t("tr",null,[t("th",null,o(e.$t("label.amount")),1),t("td",null,o(s.slip.amount)+"/-",1)]),t("tr",null,[t("th",null,o(e.$t("label.date")),1),t("td",null,o(s.slip.date),1)]),t("tr",null,[t("th",null,o(e.$t("label.order_type")),1),t("td",null,o(s.slip.orderTypeLabel),1)])])])])])]),t("footer",fe,[t("strong",null,o(e.$t("label.order_details"))+":",1),t("span",null,o(s.slip.orderDetails),1)])])):h("",!0)}const be=v(I,[["render",ge]]);function xe(e){if(!e)return"";const i=e.country_code||"",s=e.phone||"";return`${i}${s}`.trim()}function ye(e){return e?[e.address,e.city,e.state,e.country,e.zip_code].filter(Boolean).join(", "):""}function ve(e){return e?e.replace(/[^a-zA-Z]/g,"").slice(0,3).toUpperCase():""}function ke(e){return String(e.id||e.order_serial_no||"").replace(/\D/g,"").padStart(14,"0").slice(-14)}function we(e){return!e||!e.length?"":e.map(i=>`[ ${i.quantity} x ${i.product_name}${i.variation_names?` (${i.variation_names})`:""} - ]`).join(" ")}function $e(e){return!e||!e.length?1:e.reduce((i,s)=>i+(Number(s.quantity)||0),0)||1}function S(e,i=34){const s=String(e||"0");let n=0,l="";const r=2;for(let a=0;a<s.length;a++){const d=s.charCodeAt(a);[d>>2&1,d>>1&1,d&1].forEach(f=>{if(f){const g=r+d%2;l+=`<rect x="${n}" y="0" width="${g}" height="${i}" fill="#000"/>`,n+=g}n+=r}),n+=r}const c=Math.max(n,80);return`<svg xmlns="http://www.w3.org/2000/svg" width="${c}" height="${i}" viewBox="0 0 ${c} ${i}">${l}</svg>`}function Ce(e){return`https://api.qrserver.com/v1/create-qr-code/?size=88x88&margin=0&data=${encodeURIComponent(String(e||""))}`}function Pe(e,i={}){var g,k;const s=e.order_address||[],n=s.find(A=>A.address_type===B.SHIPPING)||s[0]||null,l=e.order_products||e.products||[],r=ke(e),c=i.company_city||"",a=i.company_address||"",d=(n==null?void 0:n.full_name)||((g=e.user)==null?void 0:g.name)||"",m=n?xe(n):((k=e.user)==null?void 0:k.phone)||"",f=((n==null?void 0:n.city)||"").toUpperCase();return{brandName:i.company_name||"Courier",destinationCode:ve(n==null?void 0:n.city),orderRef:`#${e.order_serial_no}`,orderRefRaw:String(e.order_serial_no),trackingNo:r,consigneeName:d,consigneePhone:m,deliveryAddress:ye(n),shipperName:i.company_name||"",shipperPhone:`${i.company_calling_code||""}${i.company_phone||""}`.trim(),pickupAddress:a,returnAddress:a,pieces:$e(l),origin:c||"—",destination:f||"—",returnCity:c||"—",remarks:(e.note||"CALL BEFORE DELIVERY").toUpperCase(),amount:e.total_amount_price||e.total_currency_price||"",date:e.order_date||e.order_datetime||"",orderTypeLabel:e.order_type===T.PICK_UP?"Pick Up":"Normal",orderDetails:we(l),orderRefBarcodeSvg:S(String(e.order_serial_no)),trackingBarcodeSvg:S(r),qrCodeUrl:Ce(`ORDER:${e.order_serial_no}|${r}`)}}function Se(e,i=3){const s=[];for(let n=0;n<e.length;n+=i){const l=e.slice(n,n+i);for(;l.length<i;)l.push(null);s.push(l)}return s}function Ee(e){const i=[...new Set(e.filter(Boolean))];return Promise.all(i.map(s=>new Promise(n=>{const l=new Image;l.onload=()=>n(),l.onerror=()=>n(),l.src=s})))}const Oe=`
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
`;function Ae(e,i="Print"){const s=window.open("","_blank");if(!s)return null;s.document.open(),s.document.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><title>${i}</title><style>${Oe}</style></head><body>${e}</body></html>`),s.document.close();const n=()=>{s.focus(),s.print(),s.close()},l=s.document.images;if(!l.length)return setTimeout(n,200),s;let r=0;const c=()=>{r+=1,r>=l.length&&n()};return Array.from(l).forEach(a=>{a.complete?c():(a.onload=c,a.onerror=c)}),setTimeout(n,2500),s}const Te={name:"OnlineOrderShippingSlipPrintComponent",components:{OnlineOrderShippingSlipComponent:be},data(){return{slipPages:[]}},mounted(){this.$store.dispatch("company/lists").catch(()=>{})},computed:{company(){return this.$store.getters["company/lists"]||{}}},methods:{async printOrders(e){var c;const i=[...new Set((e||[]).filter(Boolean))];if(!i.length)return!1;const s=[];for(const a of i){const d=await this.$store.dispatch("onlineOrder/show",a);s.push(Pe(d.data.data,this.company))}if(!s.length)return _.error(this.$t("message.no_data_found")),!1;await Ee(s.map(a=>a.qrCodeUrl)),this.slipPages=Se(s,3),await this.$nextTick(),await new Promise(a=>setTimeout(a,350));const n=this.$refs.printRoot,l=(c=n==null?void 0:n.innerHTML)==null?void 0:c.trim();return l?Ae(l,this.$t("menu.online_orders"))?!0:(_.error("Please allow pop-ups to print shipping slips."),!1):(_.error(this.$t("message.something_wrong")),!1)},clear(){this.slipPages=[]}}},Ne={ref:"printRoot",class:"shipping-slip-print-root"};function Le(e,i,s,n,l,r){const c=E("OnlineOrderShippingSlipComponent");return x((u(),p("div",Ne,[(u(!0),p(y,null,C(l.slipPages,(a,d)=>(u(),p("div",{key:"page-"+d,class:"shipping-slip-page"},[(u(!0),p(y,null,C(a,(m,f)=>(u(),p("div",{key:"slot-"+d+"-"+f,class:O(["shipping-slip-slot",{"shipping-slip-slot--empty":!m}])},[m?(u(),L(c,{key:0,slip:m},null,8,["slip"])):h("",!0)],2))),128))]))),128))],512)),[[N,l.slipPages.length>0]])}const ut=v(Te,[["render",Le],["__scopeId","data-v-46f66428"]]),Re={name:"OnlineOrderCancelModal",components:{LoadingComponent:D},emits:["done"],data(){return{loading:{isActive:!1},order:null,form:{reason:"",cancelPostEx:!0},error:""}},computed:{hasPostEx(){var e;return!!((e=this.order)!=null&&e.postex_tracking_number)},isOnTheWay(){var e;return Number((e=this.order)==null?void 0:e.status)===w.ON_THE_WAY},postexLooksInTransit(){var i;const e=String(((i=this.order)==null?void 0:i.postex_status)||"").toLowerCase();return e?e.includes("out for")||e.includes("transit")||e.includes("deliver")||e.includes("picked")||e.includes("warehouse")||e.includes("en-route")||e.includes("en route"):!1},showDeliveryWarning(){return this.isOnTheWay||this.hasPostEx||this.postexLooksInTransit}},methods:{open(e){this.order=e||null,this.form.reason="",this.form.cancelPostEx=!!this.hasPostEx,this.error="",$.modalShow("#orderCancelModal")},close(){$.modalHide("#orderCancelModal"),this.error="",this.form.reason="",this.order=null},async cancelPostExApi(e){await P.put(`admin/online-order/${e}/postex/cancel`)},async submitCancelPostExOnly(){var e,i,s;if(!(!((e=this.order)!=null&&e.id)||!this.hasPostEx))try{this.loading.isActive=!0,await this.cancelPostExApi(this.order.id),_.success(this.$t("button.cancel_postex")||"PostEx cancelled"),this.$emit("done",{orderId:this.order.id,canceledOrder:!1,canceledPostEx:!0}),this.close()}catch(n){_.error(((s=(i=n==null?void 0:n.response)==null?void 0:i.data)==null?void 0:s.message)||(n==null?void 0:n.message)||this.$t("message.something_wrong"))}finally{this.loading.isActive=!1}},async submitCancelOrder(){var i,s,n,l,r;if(!((i=this.order)!=null&&i.id))return;const e=String(this.form.reason||"").trim();if(!e){this.error=this.$t("message.reason_required")||"Reason is required.";return}this.error="";try{this.loading.isActive=!0,await P.post(`admin/online-order/change-status/${this.order.id}`,{status:w.CANCELED,reason:e});let c=!1;if(this.form.cancelPostEx&&this.hasPostEx)try{await this.cancelPostExApi(this.order.id),c=!0}catch(a){_.error((this.$t("message.postex_cancel_failed_order_canceled")||"Order canceled, but PostEx cancel failed: ")+(((n=(s=a==null?void 0:a.response)==null?void 0:s.data)==null?void 0:n.message)||(a==null?void 0:a.message)||""))}_.successFlip(1,this.$t("label.status")),this.$emit("done",{orderId:this.order.id,canceledOrder:!0,canceledPostEx:c}),this.close()}catch(c){this.error=((r=(l=c==null?void 0:c.response)==null?void 0:l.data)==null?void 0:r.message)||(c==null?void 0:c.message)||this.$t("message.something_wrong")}finally{this.loading.isActive=!1}}}},qe={id:"orderCancelModal",class:"modal"},ze={class:"modal-dialog"},Me={class:"modal-header"},Be={class:"modal-title"},De={class:"modal-body"},Ie={key:0,class:"mb-4 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900"},Ue={class:"font-semibold mb-1"},je={class:"leading-relaxed"},He={key:0,class:"mt-2 text-xs font-medium"},Ve={key:0},We={class:"form-row"},Fe={class:"form-col-12"},Ye={for:"cancel_reason",class:"db-field-title required"},Ge=["placeholder"],Ke={key:0,class:"db-field-alert"},Qe={key:0,class:"form-col-12"},Ze={class:"flex items-start gap-2 cursor-pointer select-none"},Je={class:"text-sm leading-5"},Xe={class:"font-semibold text-heading"},et={class:"block text-xs text-slate-500 mt-0.5"},tt={class:"form-col-12"},st={class:"modal-btns flex flex-wrap gap-2 justify-end"},it=["disabled"],ot=["disabled"];function nt(e,i,s,n,l,r){var a;const c=E("LoadingComponent");return u(),p(y,null,[R(c,{props:l.loading},null,8,["props"]),t("div",qe,[t("div",ze,[t("div",Me,[t("h3",Be,o(e.$t("label.canceled")||"Cancel Order"),1),t("button",{type:"button",class:"modal-close fa-solid fa-xmark text-xl text-slate-400 hover:text-red-500",onClick:i[0]||(i[0]=b((...d)=>r.close&&r.close(...d),["prevent"]))})]),t("div",De,[r.showDeliveryWarning?(u(),p("div",Ie,[t("p",Ue,o(e.$t("message.cancel_delivery_warning_title")||"This order may already be out for delivery"),1),t("p",je,o(e.$t("message.cancel_delivery_warning_body")||"Cancelling here only updates the shop order. The PostEx parcel can still move with the courier unless you also cancel PostEx."),1),(a=l.order)!=null&&a.postex_tracking_number?(u(),p("p",He,[q(o(e.$t("label.tracking_no")||"Tracking")+": "+o(l.order.postex_tracking_number)+" ",1),l.order.postex_status?(u(),p("span",Ve," · "+o(l.order.postex_status),1)):h("",!0)])):h("",!0)])):h("",!0),t("form",{onSubmit:i[5]||(i[5]=b((...d)=>r.submitCancelOrder&&r.submitCancelOrder(...d),["prevent"])),class:"w-full block"},[t("div",We,[t("div",Fe,[t("label",Ye,o(e.$t("label.reason")),1),x(t("input",{id:"cancel_reason","onUpdate:modelValue":i[1]||(i[1]=d=>l.form.reason=d),type:"text",class:O(["db-field-control",l.error?"invalid":""]),placeholder:e.$t("label.reason")||"Reason"},null,10,Ge),[[z,l.form.reason]]),l.error?(u(),p("small",Ke,o(l.error),1)):h("",!0)]),r.hasPostEx?(u(),p("div",Qe,[t("label",Ze,[x(t("input",{type:"checkbox","onUpdate:modelValue":i[2]||(i[2]=d=>l.form.cancelPostEx=d),class:"mt-1 cs-custom-checkbox"},null,512),[[M,l.form.cancelPostEx]]),t("span",Je,[t("span",Xe,o(e.$t("button.cancel_postex")||"Cancel PostEx"),1),t("span",et,o(e.$t("message.cancel_postex_with_order_hint")||"Also call PostEx cancel for this tracking number when saving."),1)])])])):h("",!0),t("div",tt,[t("div",st,[t("button",{type:"button",class:"modal-btn-outline modal-close",onClick:i[3]||(i[3]=b((...d)=>r.close&&r.close(...d),["prevent"]))},[i[6]||(i[6]=t("i",{class:"lab lab-fill-close-circle"},null,-1)),t("span",null,o(e.$t("button.close")),1)]),r.hasPostEx?(u(),p("button",{key:0,type:"button",class:"db-btn py-2 text-white bg-rose-600",disabled:l.loading.isActive,onClick:i[4]||(i[4]=b((...d)=>r.submitCancelPostExOnly&&r.submitCancelPostExOnly(...d),["prevent"]))},[i[7]||(i[7]=t("i",{class:"fa-solid fa-truck"},null,-1)),t("span",null,o(e.$t("button.cancel_postex")||"Cancel PostEx"),1)],8,it)):h("",!0),t("button",{type:"submit",class:"db-btn py-2 text-white bg-[#FB4E4E]",disabled:l.loading.isActive},[i[8]||(i[8]=t("i",{class:"lab lab-fill-close-circle"},null,-1)),t("span",null,o(e.$t("button.cancel_order")||e.$t("label.canceled")||"Cancel Order"),1)],8,ot)])])])],32)])])])],64)}const pt=v(Re,[["render",nt]]);export{pt as O,ut as a};

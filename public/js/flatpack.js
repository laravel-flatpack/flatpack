/*! For license information please see flatpack.js.LICENSE.txt */
(()=>{"use strict";var t,i={225:()=>{var t,i=function(){return i=Object.assign||function(t){for(var i,e=1,n=arguments.length;e<n;e++)for(var o in i=arguments[e])Object.prototype.hasOwnProperty.call(i,o)&&(t[o]=i[o]);return t},i.apply(this,arguments)},e=function(){function t(t){this.options=t,this.listeners={}}return t.prototype.on=function(t,i){var e=this.listeners[t]||[];this.listeners[t]=e.concat([i])},t.prototype.triggerEvent=function(t,i){var e=this;(this.listeners[t]||[]).forEach((function(t){return t({target:e,event:i})}))},t}();!function(t){t[t.Add=0]="Add",t[t.Remove=1]="Remove"}(t||(t={}));var n,o=function(){function i(){this.notifications=[]}return i.prototype.push=function(i){this.notifications.push(i),this.updateFn(i,t.Add,this.notifications)},i.prototype.splice=function(i,e){var n=this.notifications.splice(i,e)[0];return this.updateFn(n,t.Remove,this.notifications),n},i.prototype.indexOf=function(t){return this.notifications.indexOf(t)},i.prototype.onUpdate=function(t){this.updateFn=t},i}();!function(t){t.Dismiss="dismiss",t.Click="click"}(n||(n={}));var s={types:[{type:"success",className:"notyf__toast--success",backgroundColor:"#3dc763",icon:{className:"notyf__icon--success",tagName:"i"}},{type:"error",className:"notyf__toast--error",backgroundColor:"#ed3d3d",icon:{className:"notyf__icon--error",tagName:"i"}}],duration:2e3,ripple:!0,position:{x:"right",y:"bottom"},dismissible:!1},r=function(){function e(){this.notifications=[],this.events={},this.X_POSITION_FLEX_MAP={left:"flex-start",center:"center",right:"flex-end"},this.Y_POSITION_FLEX_MAP={top:"flex-start",center:"center",bottom:"flex-end"};var t=document.createDocumentFragment(),i=this._createHTMLElement({tagName:"div",className:"notyf"});t.appendChild(i),document.body.appendChild(t),this.container=i,this.animationEndEventName=this._getAnimationEndEventName(),this._createA11yContainer()}return e.prototype.on=function(t,e){var n;this.events=i(i({},this.events),((n={})[t]=e,n))},e.prototype.update=function(i,e){e===t.Add?this.addNotification(i):e===t.Remove&&this.removeNotification(i)},e.prototype.removeNotification=function(t){var i,e,n=this,o=this._popRenderedNotification(t);o&&((i=o.node).classList.add("notyf__toast--disappear"),i.addEventListener(this.animationEndEventName,e=function(t){t.target===i&&(i.removeEventListener(n.animationEndEventName,e),n.container.removeChild(i))}))},e.prototype.addNotification=function(t){var i=this._renderNotification(t);this.notifications.push({notification:t,node:i}),this._announce(t.options.message||"Notification")},e.prototype._renderNotification=function(t){var i,e=this._buildNotificationCard(t),n=t.options.className;return n&&(i=e.classList).add.apply(i,n.split(" ")),this.container.appendChild(e),e},e.prototype._popRenderedNotification=function(t){for(var i=-1,e=0;e<this.notifications.length&&i<0;e++)this.notifications[e].notification===t&&(i=e);if(-1!==i)return this.notifications.splice(i,1)[0]},e.prototype.getXPosition=function(t){var i;return(null===(i=null==t?void 0:t.position)||void 0===i?void 0:i.x)||"right"},e.prototype.getYPosition=function(t){var i;return(null===(i=null==t?void 0:t.position)||void 0===i?void 0:i.y)||"bottom"},e.prototype.adjustContainerAlignment=function(t){var i=this.X_POSITION_FLEX_MAP[this.getXPosition(t)],e=this.Y_POSITION_FLEX_MAP[this.getYPosition(t)],n=this.container.style;n.setProperty("justify-content",e),n.setProperty("align-items",i)},e.prototype._buildNotificationCard=function(t){var i=this,e=t.options,o=e.icon;this.adjustContainerAlignment(e);var s=this._createHTMLElement({tagName:"div",className:"notyf__toast"}),r=this._createHTMLElement({tagName:"div",className:"notyf__ripple"}),a=this._createHTMLElement({tagName:"div",className:"notyf__wrapper"}),c=this._createHTMLElement({tagName:"div",className:"notyf__message"});c.innerHTML=e.message||"";var p=e.background||e.backgroundColor;if(o){var f=this._createHTMLElement({tagName:"div",className:"notyf__icon"});if(("string"==typeof o||o instanceof String)&&(f.innerHTML=new String(o).valueOf()),"object"==typeof o){var l=o.tagName,u=void 0===l?"i":l,d=o.className,v=o.text,h=o.color,m=void 0===h?p:h,y=this._createHTMLElement({tagName:u,className:d,text:v});m&&(y.style.color=m),f.appendChild(y)}a.appendChild(f)}if(a.appendChild(c),s.appendChild(a),p&&(e.ripple?(r.style.background=p,s.appendChild(r)):s.style.background=p),e.dismissible){var g=this._createHTMLElement({tagName:"div",className:"notyf__dismiss"}),_=this._createHTMLElement({tagName:"button",className:"notyf__dismiss-btn"});g.appendChild(_),a.appendChild(g),s.classList.add("notyf__toast--dismissible"),_.addEventListener("click",(function(e){var o,s;null===(s=(o=i.events)[n.Dismiss])||void 0===s||s.call(o,{target:t,event:e}),e.stopPropagation()}))}s.addEventListener("click",(function(e){var o,s;return null===(s=(o=i.events)[n.Click])||void 0===s?void 0:s.call(o,{target:t,event:e})}));var N="top"===this.getYPosition(e)?"upper":"lower";return s.classList.add("notyf__toast--"+N),s},e.prototype._createHTMLElement=function(t){var i=t.tagName,e=t.className,n=t.text,o=document.createElement(i);return e&&(o.className=e),o.textContent=n||null,o},e.prototype._createA11yContainer=function(){var t=this._createHTMLElement({tagName:"div",className:"notyf-announcer"});t.setAttribute("aria-atomic","true"),t.setAttribute("aria-live","polite"),t.style.border="0",t.style.clip="rect(0 0 0 0)",t.style.height="1px",t.style.margin="-1px",t.style.overflow="hidden",t.style.padding="0",t.style.position="absolute",t.style.width="1px",t.style.outline="0",document.body.appendChild(t),this.a11yContainer=t},e.prototype._announce=function(t){var i=this;this.a11yContainer.textContent="",setTimeout((function(){i.a11yContainer.textContent=t}),100)},e.prototype._getAnimationEndEventName=function(){var t,i=document.createElement("_fake"),e={MozTransition:"animationend",OTransition:"oAnimationEnd",WebkitTransition:"webkitAnimationEnd",transition:"animationend"};for(t in e)if(void 0!==i.style[t])return e[t];return"animationend"},e}(),a=function(){function t(t){var e=this;this.dismiss=this._removeNotification,this.notifications=new o,this.view=new r;var a=this.registerTypes(t);this.options=i(i({},s),t),this.options.types=a,this.notifications.onUpdate((function(t,i){return e.view.update(t,i)})),this.view.on(n.Dismiss,(function(t){var i=t.target,o=t.event;e._removeNotification(i),i.triggerEvent(n.Dismiss,o)})),this.view.on(n.Click,(function(t){var i=t.target,e=t.event;return i.triggerEvent(n.Click,e)}))}return t.prototype.error=function(t){var i=this.normalizeOptions("error",t);return this.open(i)},t.prototype.success=function(t){var i=this.normalizeOptions("success",t);return this.open(i)},t.prototype.open=function(t){var n=this.options.types.find((function(i){return i.type===t.type}))||{},o=i(i({},n),t);this.assignProps(["ripple","position","dismissible"],o);var s=new e(o);return this._pushNotification(s),s},t.prototype.dismissAll=function(){for(;this.notifications.splice(0,1););},t.prototype.assignProps=function(t,i){var e=this;t.forEach((function(t){i[t]=null==i[t]?e.options[t]:i[t]}))},t.prototype._pushNotification=function(t){var i=this;this.notifications.push(t);var e=void 0!==t.options.duration?t.options.duration:this.options.duration;e&&setTimeout((function(){return i._removeNotification(t)}),e)},t.prototype._removeNotification=function(t){var i=this.notifications.indexOf(t);-1!==i&&this.notifications.splice(i,1)},t.prototype.normalizeOptions=function(t,e){var n={type:t};return"string"==typeof e?n.message=e:"object"==typeof e&&(n=i(i({},n),e)),n},t.prototype.registerTypes=function(t){var e=(t&&t.types||[]).slice();return s.types.map((function(t){var n=-1;e.forEach((function(i,e){i.type===t.type&&(n=e)}));var o=-1!==n?e.splice(n,1)[0]:{};return i(i({},t),o)})).concat(e)},t}(),c={duration:3e3,position:{x:"center",y:"bottom"},types:[{type:"warning",background:"#fbbf24"},{type:"success",background:"#22c55e",duration:2e3},{type:"error",background:"#f43f5e"},{type:"info",background:"#60a5fa"}]};const p=function(t){var i=t.message,e=t.type,n=void 0===e?"info":e;new a(c).open({type:n,message:i})};window.livewire.on("redirect",(function(t){var i=t.message,e=t.url;p(i),setTimeout((function(){window.location.href=e}),1e3)})),window.livewire.on("notify",(function(t){p(t)}))},475:()=>{}},e={};function n(t){var o=e[t];if(void 0!==o)return o.exports;var s=e[t]={exports:{}};return i[t](s,s.exports,n),s.exports}n.m=i,t=[],n.O=(i,e,o,s)=>{if(!e){var r=1/0;for(f=0;f<t.length;f++){for(var[e,o,s]=t[f],a=!0,c=0;c<e.length;c++)(!1&s||r>=s)&&Object.keys(n.O).every((t=>n.O[t](e[c])))?e.splice(c--,1):(a=!1,s<r&&(r=s));if(a){t.splice(f--,1);var p=o();void 0!==p&&(i=p)}}return i}s=s||0;for(var f=t.length;f>0&&t[f-1][2]>s;f--)t[f]=t[f-1];t[f]=[e,o,s]},n.o=(t,i)=>Object.prototype.hasOwnProperty.call(t,i),(()=>{var t={49:0,112:0};n.O.j=i=>0===t[i];var i=(i,e)=>{var o,s,[r,a,c]=e,p=0;if(r.some((i=>0!==t[i]))){for(o in a)n.o(a,o)&&(n.m[o]=a[o]);if(c)var f=c(n)}for(i&&i(e);p<r.length;p++)s=r[p],n.o(t,s)&&t[s]&&t[s][0](),t[s]=0;return n.O(f)},e=self.webpackChunklaravel_flatpack=self.webpackChunklaravel_flatpack||[];e.forEach(i.bind(null,0)),e.push=i.bind(null,e.push.bind(e))})(),n.O(void 0,[112],(()=>n(225)));var o=n.O(void 0,[112],(()=>n(475)));o=n.O(o)})();
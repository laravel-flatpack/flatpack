(()=>{function t(i){return t="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},t(i)}function i(t,i){var e=Object.keys(t);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(t);i&&(n=n.filter((function(i){return Object.getOwnPropertyDescriptor(t,i).enumerable}))),e.push.apply(e,n)}return e}function e(t){for(var e=1;e<arguments.length;e++){var r=null!=arguments[e]?arguments[e]:{};e%2?i(Object(r),!0).forEach((function(i){n(t,i,r[i])})):Object.getOwnPropertyDescriptors?Object.defineProperties(t,Object.getOwnPropertyDescriptors(r)):i(Object(r)).forEach((function(i){Object.defineProperty(t,i,Object.getOwnPropertyDescriptor(r,i))}))}return t}function n(i,e,n){return(e=function(i){var e=function(i,e){if("object"!==t(i)||null===i)return i;var n=i[Symbol.toPrimitive];if(void 0!==n){var r=n.call(i,e||"default");if("object"!==t(r))return r;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(i)}(i,"string");return"symbol"===t(e)?e:String(e)}(e))in i?Object.defineProperty(i,e,{value:n,enumerable:!0,configurable:!0,writable:!0}):i[e]=n,i}window.Flatpack=e(e({},window.Flatpack),{},{editable:{editableInstance:function(t,i){return{data:null,isEditing:!1,saveInput:function(i){this.$wire.set(t,i)},toggleEditing:function(){var t=this;this.isEditing=!this.isEditing,this.isEditing&&this.$nextTick((function(){t.$refs.input.focus()})),this.saveInput(this.data)},disableEditing:function(){this.isEditing=!1,this.saveInput(this.data)},undo:function(){this.isEditing=!1,this.data=this.$wire.get(t)},initEditable:function(){this.data=this.$wire.get(t)}}}}})})();
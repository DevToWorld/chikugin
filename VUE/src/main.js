import Vue from "vue";
import App from "./App.vue";
import router from "./router";
import store from "./store";
import restrictedDirective from "./directives/restricted";
import "../styleguide.css"
import "../globals.css"
import "./assets/styles/restrictions.css"
import { getApiUrl } from './config/api'

Vue.config.productionTip = false;

// グローバルディレクティブを登録
Vue.directive('restricted', restrictedDirective);

// Warm-up API (reduce cold start penalty in production)
try {
  const healthUrl = getApiUrl('/api/health')
  const controller = (typeof AbortController !== 'undefined') ? new AbortController() : null
  if (controller && typeof controller.signal !== 'undefined') {
    // use a short timeout
    setTimeout(() => controller.abort(), 1500)
    fetch(healthUrl, { method: 'GET', signal: controller.signal }).catch(() => {})
  } else {
    fetch(healthUrl).catch(() => {})
  }
} catch (e) { /* noop */ }

new Vue({
  render: h => h(App),
  router,
  store
}).$mount("#app");

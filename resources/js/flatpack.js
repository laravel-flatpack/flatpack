// Flatpack version: 1.0.0
import { alert, confirm, notify, navbar } from "./components";
import { getSetting, setSetting, isActive } from "./helpers";

// Flatpack redirects
window.livewire.on("updateUrl", (url) => {
  history.pushState(null, null, url);
});

window.livewire.on("redirect", ({ url }) => {
  setTimeout(() => {
    window.location.href = url;
  }, 800);
});

// Flatpack notifications
window.livewire.on("notify", (event) => {
  notify(event);
});

// Flatpack global object
window.Flatpack = {
  alert,
  confirm,
  navbar: navbar(),
  settings: {
    get: getSetting,
    set: setSetting,
    is: isActive,
  },
};

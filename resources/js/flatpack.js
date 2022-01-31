// Flatpack version: 1.0.0
import { notify, taginput } from "./components";

// Flatpack redirects
window.livewire.on("redirect", ({ url }) => {
  setTimeout(() => {
    window.location.href = url;
  }, 1000);
});

// Flatpack notifications
window.livewire.on("notify", (event) => {
  notify(event);
});

// Flatpack global object
window.Flatpack = { taginput };

// Flatpack version: 1.0.0
import { notify, taginput, navbar } from "./components";
import { getSetting, setSetting, isActive } from "./helpers";

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
window.Flatpack = {
  toggleDarkMode: () => {
    const { document } = window;
    document.documentElement.classList.toggle("dark");
  },
  taginput,
  navbar: navbar(),
  settings: {
    get: getSetting,
    set: setSetting,
    is: isActive,
  },
};

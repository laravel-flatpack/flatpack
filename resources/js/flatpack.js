// Flatpack version: 1.0.0
import { notify } from "./components";

window.livewire.on("redirect", ({ message, url }) => {
  notify(message);
  setTimeout(() => {
    window.location.href = url;
  }, 1000);
});

window.livewire.on("notify", (event) => {
  notify(event);
});

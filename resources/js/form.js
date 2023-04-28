// Flatpack version: 1.0.0
import { taginput } from "./components";

// Add components to Flatpack global object
window.Flatpack = {
  ...window.Flatpack,
  taginput,
  form: {
    inputChange: (event, key) => {
      Livewire.emit('flatpack-form-field:updated', event?.target?.value, key);
    },
    data: {}
  }
};

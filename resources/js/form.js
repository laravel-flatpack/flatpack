import { taginput } from "./components";

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

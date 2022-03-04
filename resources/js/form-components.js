// Flatpack version: 1.0.0
import editor from "./components/editor";
import taginput from "./components/taginput";

// Add components to Flatpack global object
window.Flatpack = {
  ...window.Flatpack,
  editor,
  taginput,
};

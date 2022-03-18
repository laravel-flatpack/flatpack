import FLATPACK_EVENT from "./events";
import eventListeners from "./event-listeners";

const initEventListeners = () => {
  Object.values(FLATPACK_EVENT).forEach((event) => {
    window.livewire.on(event, eventListeners[event]);
  });

  window.addEventListener("beforeunload", function (event) {
    event.preventDefault();
    const form = document.querySelector("#form");
    if (form && form.classList.contains("has-unsaved-changes")) {
      event.returnValue =
        "You have unsaved changes. Are you sure you want to leave?";
    } else {
      delete event["returnValue"];
    }
  });
};

export default initEventListeners;

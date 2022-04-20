import FLATPACK_EVENT from "./events";
import eventListeners from "./event-listeners";

const initEventListeners = () => {
  Object.values(FLATPACK_EVENT).forEach((event) => {
    window.livewire.on(event, eventListeners[event]);
  });
};

export default initEventListeners;

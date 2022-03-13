import FLATPACK_EVENT from "./events";
import eventListeners from "./event-listeners";
import { onErrorAction } from "./actions";

const initEventListeners = () => {
  Object.values(FLATPACK_EVENT).forEach((event) => {
    window.livewire.on(event, eventListeners[event]);
  });

  // window.livewire.onError((error) => onErrorAction(error));
};

export default initEventListeners;

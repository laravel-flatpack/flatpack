import FLATPACK_EVENT from "./events";
import eventListeners from "./event-listeners";
import { confirm } from "../components";

const initEventListeners = () => {
  document.addEventListener('livewire:load', () => {
    Object.values(FLATPACK_EVENT).forEach((event) => {
      window.livewire.on(event, eventListeners[event]);
    });

    Livewire.onPageExpired((response, message) => {
      confirm(
        'Your session has expired. Would you like to refresh the page?',
        {
          action: 'Refresh',
          title: 'Session expired',
          allowOutsideClick: false
        }
      ).then(({ isConfirmed }) => isConfirmed && window.location.reload());

      return false;
    });
  });
};

export default initEventListeners;

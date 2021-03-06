import { alert, confirm, notify, navbar } from "../components";
import { getSetting, setSetting, isActive, performAction } from "../helpers";

/*
|--------------------------------------------------------------------------
| Flatpack global object
|--------------------------------------------------------------------------
|
| This is the global object that is exposed to the browser. It contains
| all of the client-side helpers and components.
|
*/
const createFlatpack = () => {
  window.Flatpack = {
    alert,
    confirm,
    notify,
    action: performAction,
    navbar: navbar(),
    settings: {
      get: getSetting,
      set: setSetting,
      is: isActive,
    },
  };
};

export default createFlatpack;

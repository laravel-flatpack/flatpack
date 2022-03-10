import FLATPACK_EVENT from "./events";
import { notifyAction, redirectAction, updateUrlAction } from "./actions";

const eventListeners = {
  [FLATPACK_EVENT.NOTIFY]: notifyAction,
  [FLATPACK_EVENT.REDIRECT]: redirectAction,
  [FLATPACK_EVENT.UPDATE_URL]: updateUrlAction,
};

export default eventListeners;

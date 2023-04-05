import {
  isMobile,
  isLargeScreen,
  isMediumScreen,
  isSmallScreen,
} from "./device";
import { getSetting, setSetting, isActive } from "./settings";
import performAction from "./actions";
import shortcutAction from "./shortcuts";

export {
  performAction,
  shortcutAction,
  getSetting,
  setSetting,
  isActive,
  isMobile,
  isLargeScreen,
  isMediumScreen,
  isSmallScreen,
};

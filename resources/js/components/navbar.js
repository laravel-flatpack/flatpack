import {
  isActive,
  isMobile,
  isMediumScreen,
  isSmallScreen,
  setSetting,
} from "../helpers";

const navbar = () => {
  const navbarSetting = isActive("navbar");
  const desktop = !isMobile() && !isSmallScreen() && !isMediumScreen();

  return {
    minimized: desktop ? navbarSetting : "true",
    set: (value) => setSetting("navbar", value),
  };
};

export default navbar;

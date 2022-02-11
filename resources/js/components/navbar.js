import {
  isActive,
  isMobile,
  isLargeScreen,
  isMediumScreen,
  isSmallScreen,
  setSetting,
} from "../helpers";

const navbar = () => {
  const navbarSetting = isActive("navbar");
  const desktop =
    !isMobile() && !isSmallScreen() && !isMediumScreen() && isLargeScreen();

  return {
    minimized: desktop ? navbarSetting : "true",
    set: (value) => setSetting("navbar", value),
  };
};

export default navbar;

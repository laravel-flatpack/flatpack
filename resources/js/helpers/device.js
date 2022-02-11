export const isMobile = () =>
  /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
    navigator.userAgent
  );

export const isLargeScreen = () =>
  window.matchMedia("only screen and (min-width: 1280px)").matches;

export const isMediumScreen = () =>
  window.matchMedia("only screen and (max-width: 1192px)").matches;

export const isSmallScreen = () =>
  window.matchMedia("only screen and (max-width: 728px)").matches;

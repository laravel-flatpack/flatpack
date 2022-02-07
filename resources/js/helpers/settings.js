const PREFIX = "flatpack_settings";

export const getSetting = (setting) => {
  return localStorage.getItem(`${PREFIX}_${setting}`);
};

export const isActive = (setting) => {
  return getSetting(setting) === "true";
};

export const setSetting = (setting, value) => {
  console.log(`${PREFIX}_${setting}`, value);
  return localStorage.setItem(`${PREFIX}_${setting}`, value);
};

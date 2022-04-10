const PREFIX = "flatpack_settings";

export const getSetting = (setting) => {
  return localStorage.getItem(`${PREFIX}_${setting}`);
};

export const isActive = (setting) => {
  return [true, "true"].includes(getSetting(setting));
};

export const setSetting = (setting, value) => {
  return localStorage.setItem(`${PREFIX}_${setting}`, value);
};

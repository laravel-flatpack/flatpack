const updateUrlAction = (url) => {
  history.pushState(null, null, url);
};

export default updateUrlAction;

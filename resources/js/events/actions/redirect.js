const redirectAction = (url, timeout = 800) => {
  setTimeout(() => {
    window.location.href = url;
  }, timeout);
};

export default redirectAction;

const redirectAction = (url) => {
  setTimeout(() => {
    window.location.href = url;
  }, 800);
};

export default redirectAction;

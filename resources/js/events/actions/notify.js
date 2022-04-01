const notifyAction = ({ type, message }) => {
  window.Flatpack.notify(type, message);
};

export default notifyAction;

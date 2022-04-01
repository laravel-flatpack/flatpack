const notify = (type, message, description = null) => {
  if (!["success", "error", "info", "warning", "question"].includes(type)) {
    return;
  }

  if (!message) {
    return;
  }

  return window.$wireui.notify({
    title: message,
    description,
    icon: type,
    timeout: 3000,
  });
};

export default notify;

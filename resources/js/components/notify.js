const formatMessage = (message) => message?.[0].toUpperCase() + message.slice(1);

const notify = (type, message, description, options) => {
  if (!["success", "error", "info", "warning", "question"].includes(type) || !message) {
    return;
  }

  return window.$wireui.notify({
    title: formatMessage(message),
    description,
    icon: type,
    timeout: 2000,
  });
};

export default notify;

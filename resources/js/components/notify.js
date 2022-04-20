const formatMessage = (message) => message[0].toUpperCase() + message.slice(1);

const notify = (type, message, description = null) => {
  if (
    !["success", "error", "info", "warning", "question"].includes(type) ||
    !message
  ) {
    return;
  }

  return window.$wireui.notify({
    title: formatMessage(message),
    description,
    icon: type,
    timeout: 3000,
  });
};

export default notify;

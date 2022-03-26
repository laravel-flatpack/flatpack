import Toastify from "toastify-js";

const config = {
  duration: 3000,
  close: false,
  gravity: "top",
  position: "center",
  stopOnFocus: true,
  style: {
    background: "#1f2937",
    color: "#fff",
  },
  offset: {
    y: "2rem",
  },
};

const styles = {
  info: {
    background: "#3b82f6",
  },
  success: {
    background: "#22c55e",
  },
  error: {
    background: "#ef4444",
  },
};

const notify = ({ message, type = "info" }) => {
  if (!message) return;

  const style = styles[type] || config.style;

  return Toastify({
    ...config,
    style,
    text: message,
  }).showToast();
};

export default notify;

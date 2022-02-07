import Toastify from "toastify-js";
import "toastify-js/src/toastify.css";

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

const notifications = {
  info: (message) =>
    Toastify({
      ...config,
      style: {
        background: "#3b82f6",
      },
      text: message,
    }).showToast(),

  success: (message) =>
    Toastify({
      ...config,
      style: {
        background: "#22c55e",
      },
      text: message,
    }).showToast(),
  error: (message) =>
    Toastify({
      ...config,
      style: {
        background: "#ef4444",
      },
      text: message,
    }).showToast(),
};

const notify = ({ message, type = "info" }) => notifications[type](message);

export default notify;

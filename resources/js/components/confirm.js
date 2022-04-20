import Swal from "sweetalert2/dist/sweetalert2.js";

const styles = {
  danger: "#ef5350",
  warning: "#ffc107",
  info: "#3085d6",
  success: "#66bb6a",
  default: "#37474f",
};

const confirm = (text, options = {}) => {
  const { title, action, cancel, style } = options;

  return Swal.fire({
    title: title ?? "",
    text,
    reverseButtons: true,
    showCancelButton: true,
    showCloseButton: true,
    confirmButtonColor: styles[style] ?? styles.default,
    confirmButtonText: action ?? "Ok",
    cancelButtonText: cancel ?? "Cancel",
  });
};

export default confirm;

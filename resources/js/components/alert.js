import Swal from "sweetalert2/dist/sweetalert2.js";

const alert = (text, options = {}) => {
  const { title, action } = options;
  return Swal.fire({
    title: title ?? "",
    text,
    showCloseButton: true,
    showCancelButton: false,
    confirmButtonText: action ?? "Ok",
  });
};

export default alert;

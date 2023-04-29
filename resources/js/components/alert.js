import Swal from "sweetalert2/dist/sweetalert2.js";

const alert = (text, options = {}) => {
  const { title, action } = options;

  const configuration = {
    title: title || '',
    text,
    showCloseButton: true,
    showCancelButton: false,
    confirmButtonText: action || 'Ok',
  };

  return Swal.fire(configuration);
};

export default alert;

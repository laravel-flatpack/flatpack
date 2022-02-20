import Swal from "sweetalert2/dist/sweetalert2.js";

const getColor = (style) => {
  switch (style) {
    case "danger":
      return "#ef5350";
    case "warning":
      return "#ffc107";
    case "info":
      return "#3085d6";
    case "success":
      return "#66bb6a";
    default:
      return "#37474f";
  }
};

const confirm = (text, options = {}) => {
  const { title, action, cancel, style } = options;

  return Swal.fire({
    title: title ?? "",
    text,
    reverseButtons: true,
    showCancelButton: true,
    showCloseButton: true,
    confirmButtonColor: getColor(style),
    confirmButtonText: action ?? "Ok",
    cancelButtonText: cancel ?? "Cancel",
  });
};

export default confirm;

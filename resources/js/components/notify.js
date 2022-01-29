import { Notyf } from "notyf";

const configuration = {
  duration: 2000,
  position: {
    x: "center",
    y: "bottom",
  },
  types: [
    {
      type: "warning",
      background: "orange",
      icon: {
        className: "material-icons",
        tagName: "i",
        text: "warning",
      },
    },
    {
      type: "success",
      background: "green",
    },
    {
      type: "error",
      background: "indianred",
      dismissible: true,
    },
    {
      type: "info",
      background: "blue",
      dismissible: true,
    },
  ],
};

const notify = ({ message, type = "info" }) => {
  const notyf = new Notyf(configuration);
  notyf.open({
    type,
    message,
  });
};
export default notify;

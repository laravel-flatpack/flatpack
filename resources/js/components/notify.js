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
      background: "#fbbf24",
      dismissible: true,
    },
    {
      type: "success",
      background: "#22c55e",
    },
    {
      type: "error",
      background: "#f43f5e",
      dismissible: true,
    },
    {
      type: "info",
      background: "#60a5fa",
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

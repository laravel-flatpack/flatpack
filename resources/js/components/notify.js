import { Notyf } from "notyf";

const configuration = {
  duration: 4000,
  position: {
    x: "center",
    y: "bottom",
  },
  types: [
    {
      type: "warning",
      background: "#fbbf24",
    },
    {
      type: "success",
      background: "#22c55e",
      duration: 2000,
    },
    {
      type: "error",
      background: "#f43f5e",
    },
    {
      type: "info",
      background: "#60a5fa",
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

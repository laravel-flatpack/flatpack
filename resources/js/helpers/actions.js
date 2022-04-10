import { confirm } from "../components";

const performAction = (callback, action, options) => {
  return options?.confirm === true
    ? confirm(options?.confirmationMessage, {
        title: options?.label,
        action: options?.label,
        style: options?.style,
      }).then(({ isConfirmed }) => isConfirmed && callback(action, options))
    : callback(action, options);
};

export default performAction;

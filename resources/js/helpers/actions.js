import { confirm } from "../components";

const performAction = (callback, action, options) => {
  return options?.confirm
    ? confirm(options?.confirmationMessage || 'Are you sure you want to proceed?', {
      title: options?.label,
      action: options?.label,
      style: options?.style,
    }).then(({ isConfirmed }) => isConfirmed && callback(action, options))
    : callback(action, options);
};

export default performAction;

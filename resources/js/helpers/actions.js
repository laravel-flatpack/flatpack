import { alert, confirm } from "../components";

const performAction = (callback, action, options) => {
  const { confirmationMessage, label, style } = options;
  return !options?.confirm ? callback(action, options) :
    confirm(confirmationMessage || 'Are you sure you want to proceed?', {
      title: label,
      action: label,
      style: style,
    }).then(({ isConfirmed }) => isConfirmed && callback(action, options));
}


export default performAction;

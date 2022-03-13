const messages = {
  419: `Session expired. Please refresh the page.`,
  400: `Bad request. Please refresh the page.`,
  401: `Unauthorized. Please refresh the page.`,
  403: `Forbidden. Please refresh the page.`,
  500: `Internal server error. Please try again later.`,
  503: `Service unavailable. Please try again later.`,
  0: `Something went wrong. Please try again later.`,
};

const onErrorAction = (error) => {
  const message = messages[error] || messages[0];
  window.Flatpack.notify({ message, type: "error" });

  return false;
};

export default onErrorAction;

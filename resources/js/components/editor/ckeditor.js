import ClassicEditor from "./classic";
import UploadAdapter from "./plugins/upload-adapter";

const editorInstance = (dataProperty, editorId, options) => {
  const { upload, token } = options;

  const debounce = (func, timeout = 400) => {
    let timer;
    return (...args) => {
      clearTimeout(timer);
      timer = setTimeout(() => {
        func.apply(this, args);
      }, timeout);
    };
  };

  return {
    data: null,
    instance: null,
    saveInput(data) {
      this.$wire.set(dataProperty, data);
    },
    initEditor() {
      this.data = this.$wire.get(dataProperty);

      const UploadAdapterPlugin = (editor) => {
        editor.plugins.get("FileRepository").createUploadAdapter = (loader) => {
          return new UploadAdapter(loader, upload, token);
        };
      };

      this.instance = ClassicEditor.create(document.getElementById(editorId), {
        extraPlugins: [UploadAdapterPlugin],
      })
        .then((editor) =>
          editor.model.document.on(
            "change:data",
            debounce(() => this.saveInput(editor.getData()))
          )
        )
        .catch((error) => {
          console.error(error);
        });
    },
  };
};

window.Flatpack = {
  ...window.Flatpack,
  ckEditor: {
    editorInstance,
  },
};

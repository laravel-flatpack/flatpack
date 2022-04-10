const editableInstance = (dataProperty, inputId) => {
  return {
    data: null,
    isEditing: false,

    saveInput(data) {
      this.$wire.set(dataProperty, data);
    },
    toggleEditing() {
      this.isEditing = !this.isEditing;

      if (this.isEditing) {
        this.$nextTick(() => {
          this.$refs.input.focus();
        });
      }

      this.saveInput(this.data);
    },

    disableEditing() {
      this.isEditing = false;
      this.saveInput(this.data);
    },

    undo() {
      this.isEditing = false;
      this.data = this.$wire.get(dataProperty);
    },

    initEditable() {
      this.data = this.$wire.get(dataProperty);
    },
  };
};

window.Flatpack = {
  ...window.Flatpack,
  editable: {
    editableInstance,
  },
};

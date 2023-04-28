const editableInstance = () => {
  return {
    isEditing: false,

    toggleEditing() {
      this.isEditing = !this.isEditing;

      if (this.isEditing) {
        this.$nextTick(() => this.$refs.input.focus());
      }
    },

    disableEditing() {
      this.isEditing = false;
    },
  };
};

window.Flatpack = {
  ...window.Flatpack,
  editable: {
    editableInstance,
  },
};

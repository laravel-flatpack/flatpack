import Tagify from "@yaireo/tagify";

function tagTemplate(tagData) {
  return `
    <tag
      title="${tagData.name}"
      contenteditable='true'
      spellcheck='true'
      tabIndex="-1"
      class="tagify__tag ${tagData.class ? tagData.class : ""}">
      <x title='' class='tagify__tag__removeBtn' role='button' aria-label='remove tag'></x>
      <div>
        <span class='tagify__tag-text'>${tagData.name}</span>
      </div>
    </tag>
  `;
}

function suggestionItemTemplate(tagData) {
  return `
      <div ${this.getAttributes(tagData)}
          class='tagify__dropdown__item ${tagData.class ? tagData.class : ""}'
          tabindex="0"
          role="option">
          <span>${tagData.name}</span>
      </div>
  `;
}

const tagInput = ({ key, input, values, canCreate, source }) => {

  const tagify = new Tagify(input, {
    tagTextProp: 'name',
    enforceWhitelist: canCreate == false,
    whitelist: values,
    skipInvalid: false,
    editTags: false,
    dropdown: {
      closeOnSelect: true,
      maxItems: 10,
      searchKeys: ["name", "value"],
    },
    originalInputValueFormat: items => items.map(item => item.value),
    templates: {
      tag: tagTemplate,
      dropdownItem: suggestionItemTemplate,
    },
  });

  let controller;

  /**
   * Listen to any keystrokes which modify tagify's input
   */
  tagify.on('input', ({ detail }) => {
    const value = `${detail.value}`

    if (value.length < 2) {
      return false;
    }

    // https://developer.mozilla.org/en-US/docs/Web/API/AbortController/abort
    controller && controller.abort();
    controller = new AbortController();

    tagify.whitelist = values;
    tagify.loading(true).dropdown.hide();

    fetch(source + '&search=' + value, { signal: controller.signal })
      .then(response => response.json())
      .then(function (response) {
        const newValues = [...response].map(
          ({ value, display }) => ({ value, name: display })
        );
        tagify.whitelist = [
          ...values,
          ...newValues
        ]
        tagify.loading(false).dropdown.show(value)
      })
  });

  /**
   * Listen to new tag event
   */
  tagify.on("add", (e) => {
    const { data, tagify, index } = e?.detail;
    const { whitelist } = tagify;
    const isNew = whitelist.map(item => `${item.value}`).indexOf(data.value) === -1;

    if (isNew && canCreate) {
      Livewire.emit(`flatpack-taginput:create`, key, data.value);
      Livewire.on(`flatpack-form:related-entity-created:${key}`, (value) => {
        const newTag = { value, name: data.name };
        whitelist.push(newTag);
        tagify.replaceTag(tagify.getTagElms()[index], newTag);
      });
    }

    return false;
  });

  /**
   * Listen to "change" events on the "original" input element.
   */
  tagify.DOM.originalInput.addEventListener("change", (e) => {
    const { value } = e.target;
    Livewire.emit("flatpack-taginput:change", key, value);
  });

  return tagify;
};

export default tagInput;

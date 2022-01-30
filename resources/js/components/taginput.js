import Tagify from "@yaireo/tagify";

const tagTemplate = (tagData) => `
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

const suggestionItemTemplate = (tagData) => `
  <div
    class='tagify__dropdown__item ${tagData.class ? tagData.class : ""}'
    tabindex="0"
    role="option">
      <strong>${tagData.name}</strong>
  </div>
  `;

const taginput = (key, input, values, addNewEntries = false) => {
  var tagify = new Tagify(input, {
    tagTextProp: "name",
    enforceWhitelist: !addNewEntries,
    whitelist: values,
    skipInvalid: false,
    editTags: false,
    dropdown: {
      closeOnSelect: true,
      maxItems: 3,
      classname: "input-tags-list",
      searchKeys: ["name", "value"],
    },
    originalInputValueFormat: (valuesArr) =>
      valuesArr.map((item) => item.value),
    templates: {
      tag: tagTemplate,
      dropdownItem: suggestionItemTemplate,
    },
  });

  // Add new tag
  tagify.on("add", (e) => {
    setTimeout(() => {
      const { data, tagify, index } = e?.detail;
      const { whitelist } = tagify;
      const isNew =
        whitelist.map((item) => `${item.value}`).indexOf(data.value) === -1;

      if (isNew) {
        // Create new model
        Livewire.emit(`flatpack-taginput:new-tag`, key, data.value);
        // Add to whitelist and sync value
        Livewire.on(`flatpack-taginput:new-tag-created:${key}`, (value) => {
          const newTag = { value: value, name: `${data.name}` };
          // Update whitelist
          whitelist.push(newTag);
          // Update tag value
          tagify.replaceTag(tagify.getTagElms()[index], newTag);
        });
      }
    }, 0);
  });

  // listen to "change" events on the "original" input/textarea element
  tagify.DOM.originalInput.addEventListener("change", (e) => {
    const { name, value } = e.target;
    const key = name.replace("fields.", "");
    Livewire.emit("flatpack-taginput:change", key, value);
  });

  return tagify;
};

export default taginput;

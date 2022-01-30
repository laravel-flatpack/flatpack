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

const taginput = (input, values) => {
  var tagify = new Tagify(input, {
    tagTextProp: "name",
    enforceWhitelist: true,
    skipInvalid: false,
    dropdown: {
      closeOnSelect: false,
      enabled: 0,
      classname: "input-tags-list",
      searchKeys: ["name", "value"],
    },
    originalInputValueFormat: (valuesArr) =>
      valuesArr.map((item) => item.value),
    templates: {
      tag: tagTemplate,
      dropdownItem: suggestionItemTemplate,
    },
    whitelist: values,
  });

  // listen to dropdown events
  // tagify.on("dropdown:show dropdown:updated", onDropdownShow);
  function onDropdownShow(e) {
    var dropdownContentElm = e.detail.tagify.DOM.dropdown.content;
  }

  if (tagify?.DOM?.originalInput) {
    // listen to "change" events on the "original" input/textarea element
    tagify.DOM.originalInput.addEventListener("change", onTagsChange);
    function onTagsChange(e) {
      const { name, value } = e.target;
      const key = name.replace("fields.", "");

      Livewire.emit("flatpack-taginput-change", key, value);
    }
  }

  return tagify;
};

export default taginput;

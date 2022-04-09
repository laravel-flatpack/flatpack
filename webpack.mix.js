const mix = require("laravel-mix");
const tailwindcss = require("tailwindcss");

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Compile Flatpack assets using Laravel Mix.
 |
 */

mix
  .js("resources/js/alpine.js", "public/js")
  .js("resources/js/flatpack.js", "public/js")
  .js("resources/js/form.js", "public/js")
  .js("resources/js/list.js", "public/js")
  .js("resources/js/components/editor/editorjs.js", "public/js")
  .js("resources/js/components/editor/ckeditor.js", "public/js")
  .sass("resources/sass/flatpack.scss", "public/css")
  .options({
    postCss: [tailwindcss("./tailwind.config.js")],
  });

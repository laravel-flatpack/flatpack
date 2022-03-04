const mix = require("laravel-mix");

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Compile Flatpack assets using Laravel Mix.
 |
 */

mix
  .js("resources/js/flatpack.js", "public/js")
  .js("resources/js/form-components.js", "public/js")
  .js("resources/js/list-components.js", "public/js")
  .postCss("resources/css/flatpack.css", "public/css", [
    require("tailwindcss"),
  ]);

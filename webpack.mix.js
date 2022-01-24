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
  .postCss("resources/css/flatpack.css", "public/css", [
    require("tailwindcss"),
  ]);

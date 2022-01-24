const defaultTheme = require("tailwindcss/defaultTheme");

module.exports = {
  darkMode: "class",
  mode: "jit",
  content: [
    "./vendor/rappasoft/laravel-livewire-tables/resources/views/tailwind/**/*.blade.php",
    "./resources/css/**/*.css",
    "./resources/js/**/*.js",
    "./resources/**/*.blade.php",
  ],
  purge: [
    "./vendor/rappasoft/laravel-livewire-tables/resources/views/tailwind/**/*.blade.php",
    "./resources/css/**/*.css",
    "./resources/js/**/*.js",
    "./resources/**/*.blade.php",
  ],

  theme: {
    extend: {
      fontFamily: {
        sans: ["Inter var", ...defaultTheme.fontFamily.sans],
      },
    },
  },

  variants: {
    extend: {
      backgroundColor: ["disabled"],
      textColor: ["disabled"],
    },
  },

  plugins: [
    require("@tailwindcss/forms"),
    require("@tailwindcss/typography"),
    require("@tailwindcss/aspect-ratio"),
    require("@tailwindcss/line-clamp"),
  ],
};

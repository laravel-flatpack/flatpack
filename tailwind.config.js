const colors = require("tailwindcss/colors");
const defaultTheme = require("tailwindcss/defaultTheme");

module.exports = {
  mode: "jit",
  content: [
    "./vendor/wireui/wireui/resources/**/*.blade.php",
    "./vendor/wireui/wireui/ts/**/*.ts",
    "./vendor/wireui/wireui/src/View/**/*.php",
    "./vendor/rappasoft/laravel-livewire-tables/resources/views/tailwind/**/*.blade.php",
    "./resources/css/**/*.css",
    "./resources/js/**/*.js",
    "./resources/**/*.blade.php",
  ],
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        slate: colors.slate,
        primary: colors.dark,
        secondary: colors.slate,
        positive: colors.emerald,
        negative: colors.red,
        warning: colors.amber,
        info: colors.blue,
      },
      fontSize: {
        "3xs": "0.5rem",
        "2xs": "0.65rem",
      },
      fontFamily: {
        sans: ["Inter var", ...defaultTheme.fontFamily.sans],
      },
      spacing: {
        2.2: "0.55rem",
        3.5: "0.875rem",
        4.5: "1.13rem",
        5.5: "1.38rem",
        6.5: "1.63rem",
        9.5: "2.38rem",
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
    require("./vendor/wireui/wireui/tailwindcss/plugins/hideScrollbar"),
    require("@tailwindcss/typography"),
    require("@tailwindcss/aspect-ratio"),
    require("@tailwindcss/line-clamp"),
  ],
};

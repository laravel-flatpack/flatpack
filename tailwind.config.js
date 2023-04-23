const colors = require("tailwindcss/colors");
const defaultTheme = require("tailwindcss/defaultTheme");

module.exports = {
  mode: "jit",
  content: [
    "./vendor/wireui/wireui/resources/**/*.blade.php",
    "./vendor/wireui/wireui/ts/**/*.ts",
    "./vendor/wireui/wireui/src/View/**/*.php",
    "./vendor/rappasoft/laravel-livewire-tables/resources/views/**/*.blade.php",
    "./resources/css/**/*.css",
    "./resources/js/**/*.js",
    "./resources/**/*.blade.php",
  ],
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: "#374151",
          50: "#8E9CB2",
          100: "#8191AA",
          200: "#697C99",
          300: "#586882",
          400: "#485569",
          500: "#374151",
          600: "#2B323F",
          700: "#1E242D",
          800: "#12151A",
          900: "#060708",
        },
        secondary: {
          DEFAULT: "#6E7E96",
          50: "#D8DDE3",
          100: "#CDD2DB",
          200: "#B5BDCA",
          300: "#9DA8B9",
          400: "#8593A8",
          500: "#6E7E96",
          600: "#556377",
          700: "#3E4856",
          800: "#262D35",
          900: "#0F1115",
        },
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
    require("./resources/js/plugins/hideScrollbar.js"),
    require("@tailwindcss/typography"),
    require("@tailwindcss/aspect-ratio"),
    require("@tailwindcss/line-clamp"),
  ],
};

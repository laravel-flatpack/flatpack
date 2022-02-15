const { description } = require("../../package");

module.exports = {
  base: "/",

  /**
   * Ref：https://v1.vuepress.vuejs.org/config/#title
   */
  title: "Flatpack",

  /**
   * Ref：https://v1.vuepress.vuejs.org/config/#description
   */
  description: description,

  /**
   * Extra tags to be injected to the page HTML `<head>`
   *
   * ref：https://v1.vuepress.vuejs.org/config/#head
   */
  head: [
    ["meta", { name: "theme-color", content: "#1F2937" }],
    ["meta", { name: "apple-mobile-web-app-capable", content: "yes" }],
    [
      "meta",
      { name: "apple-mobile-web-app-status-bar-style", content: "black" },
    ],
  ],

  /**
   * Theme configuration, here is the default theme configuration for VuePress.
   *
   * ref：https://v1.vuepress.vuejs.org/theme/default-theme-config.html
   */
  themeConfig: {
    repo: "",
    editLinks: false,
    docsDir: "",
    editLinkText: "",
    lastUpdated: false,
    logo: "/flatpack-logo.png",
    nav: [
      {
        text: "Documentation",
        link: "/documentation/",
      },
      {
        text: "Reference",
        link: "/reference/",
      },
      {
        text: "Github",
        link: "https://github.com/faustoq/laravel-flatpack",
      },
    ],
    sidebar: {
      "/documentation/": [
        {
          title: "Documentation",
          collapsable: false,
          children: ["", "using-vue"],
        },
      ],
      "/reference/": [
        {
          title: "Reference",
          path: "/reference/",
          collapsable: false,
          children: ["form-fields", "table-columns", "layout"],
        },
      ],
    },
  },

  /**
   * Apply plugins，ref：https://v1.vuepress.vuejs.org/zh/plugin/
   */
  plugins: ["vuepress-plugin-smooth-scroll"],

  markdown: {
    plugins: {
      "@centerforopenscience/markdown-it-video": {},
    },
  },
};

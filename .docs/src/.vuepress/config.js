module.exports = {
  lang: "en-US",
  title: "Flatpack",
  description: "Administration panel for Laravel, ready to assemble.",
  head: [
    ["meta", { name: "theme-color", content: "#1F2937" }],
    ["meta", { name: "apple-mobile-web-app-capable", content: "yes" }],
    [
      "meta",
      { name: "apple-mobile-web-app-status-bar-style", content: "black" },
    ],
  ],

  theme: "@vuepress/theme-default",

  themeConfig: {
    darkMode: false,
    repo: "",
    editLinks: false,
    docsDir: "",
    editLinkText: "",
    lastUpdated: false,
    logo: "/flatpack-logo.png",
    navbar: [
      {
        text: "Guide",
        link: "/guide/",
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
      "/guide/": [
        {
          title: "Guide",
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

  plugins: ["@vuepress/plugin-search"],

  markdown: {
    code: {
      lineNumbers: false,
    },
    html: true,
  },
};

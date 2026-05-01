/** @type {import("stylelint").Config} */
export default {
  extends: ["stylelint-config-standard-scss"],
  ignoreFiles: [
    "**/node_modules/**",
    "public/**",
    "writable/**",
    "vendor/**",
    "system/**",
  ],
  rules: {
    // Allow BEM-style blocks and elements (e.g. .locale-switcher__btn)
    "selector-class-pattern": null,
  },
};

import fs from "node:fs";
import path from "node:path";

import { sentryVitePlugin } from "@sentry/vite-plugin";
import sass from "sass";
import { defineConfig, type Plugin } from "vite";

function viteSentryPlugins(): Plugin[] {
  const authToken = process.env.SENTRY_AUTH_TOKEN;
  const org = process.env.SENTRY_ORG;
  const project = process.env.SENTRY_PROJECT;
  const release =
    process.env.SENTRY_RELEASE ??
    process.env.VITE_SENTRY_RELEASE ??
    "";

  if (!authToken || !org || !project || !release) {
    return [];
  }

  return [
    sentryVitePlugin({
      authToken,
      org,
      project,
      url: process.env.SENTRY_URL,
      release: { name: release },
      sourcemaps: {
        assets: "./public/assets/dist/**",
      },
    }),
  ];
}

/** When "1", re-emits portal/tutorial CSS with external `.css.map` so Chrome maps rules to `.scss` (Lando uses `dist`). */
const cssSourceMapToScss = process.env.VITE_FULL_CSS_MAP === "1";

/**
 * Vite's `dist` CSS pipeline concatenates styles without preserving Sass source maps.
 * Recompile SCSS entries with dart-sass after Rollup so DevTools show `resources/scss/...` paths.
 */
function sassDistSourcemapPlugin(): Plugin {
  let root = "";
  return {
    name: "sass-dist-sourcemap",
    configResolved(config) {
      root = config.root;
    },
    async closeBundle() {
      if (!cssSourceMapToScss) return;

      const outDir = path.join(root, "public/assets/dist/css");
      const entries = [
        { inFile: path.join(root, "resources/scss/portal.scss"), css: path.join(outDir, "portal.css") },
        {
          inFile: path.join(root, "resources/scss/tutorial.scss"),
          css: path.join(outDir, "tutorialStyle.css"),
        },
      ];

      fs.mkdirSync(outDir, { recursive: true });

      for (const { inFile, css: cssPath } of entries) {
        const result = sass.compile(inFile, {
          style: "expanded",
          sourceMap: true,
          sourceMapIncludeSources: true,
        });
        const mapBasename = path.basename(cssPath) + ".map";
        const cssOut =
          result.css.trimEnd() + `\n/*# sourceMappingURL=${mapBasename} */\n`;
        fs.writeFileSync(cssPath, cssOut, "utf8");
        fs.writeFileSync(
          path.join(outDir, mapBasename),
          JSON.stringify(result.sourceMap),
          "utf8",
        );
      }
    },
  };
}

export default defineConfig({
  publicDir: false,
  plugins: [...viteSentryPlugins(), ...(cssSourceMapToScss ? [sassDistSourcemapPlugin()] : [])],
  css: {
    devSourcemap: true,
  },
  build: {
    outDir: "public/assets/dist",
    emptyOutDir: true,
    /** Hidden maps for Sentry upload (`@sentry/vite-plugin` when env set); omit `//# sourceMappingURL` so stacks reference deployed chunk URLs. */
    sourcemap: "hidden",
    // sourcemap: true,
    rollupOptions: {
      input: {
        tutorial: "resources/ts/tutorial.ts",
        tutorialStyle: "resources/scss/tutorial.scss",
        portal: "resources/ts/portal.ts",
      },
      output: {
        entryFileNames: "js/[name].js",
        chunkFileNames: "js/[name].js",
        assetFileNames: "css/[name][extname]",
      },
    },
  },
});

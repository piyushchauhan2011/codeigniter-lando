import { defineConfig } from "vite";

export default defineConfig({
  publicDir: false,
  build: {
    outDir: "public/assets/dist",
    emptyOutDir: true,
    sourcemap: true,
    rollupOptions: {
      input: {
        tutorial: "resources/ts/tutorial.ts",
        tutorialStyle: "resources/scss/tutorial.scss",
        portal: "resources/ts/portal.ts",
      },
      output: {
        entryFileNames: "js/[name].js",
        chunkFileNames: "js/[name].js",
        assetFileNames: "css/[name][extname]"
      }
    }
  }
});

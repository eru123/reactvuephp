import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import vue from "@vitejs/plugin-vue";
import { resolve } from "path";
import phpDevServer from "./php-dev-server.js";

export default defineConfig({
  plugins: [
    react(),
    vue(),
    phpDevServer({
      port: 8000,
      host: "127.0.0.1",
      baseDir: "./",
    }),
  ],
  resolve: {
    alias: {
      "@r": resolve(__dirname, "./react-app/src"),
      "@v": resolve(__dirname, "./vue-app/src"),
    },
  },
  build: {
    manifest: true,
    outDir: "dist",
    emptyOutDir: true,
    assetsDir: "__",
    rollupOptions: {
      input: {
        vue: resolve(__dirname, "vue/main.js"),
        react: resolve(__dirname, "react/main.tsx"),
      },
    },
  },
});

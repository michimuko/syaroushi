import { defineConfig } from "vite";
// @ts-expect-error type error without @types/node package
import process from "node:process";
const host = process.env.TAURI_DEV_HOST;

// https://vite.dev/config/
export default defineConfig(() => ({
  // desktop-appはTailwindを使わない素のCSSのため、自身のpostcss.config.jsを持たない。
  // 未指定のままだとVite/postcss-load-configがリポジトリルートのpostcss.config.js
  // （Laravel側のTailwind用、tailwindcss依存）を親ディレクトリ探索で拾ってしまい、
  // desktop-app/node_modulesにtailwindcssが無いためビルドが失敗する（Windows環境で顕在化）。
  css: { postcss: {} },

  // Vite options tailored for Tauri development and only applied in `tauri dev` or `tauri build`
  //
  // 1. prevent Vite from obscuring rust errors
  clearScreen: false,
  // 2. tauri expects a fixed port, fail if that port is not available
  server: {
    port: 1420,
    strictPort: true,
    host: host || false,
    hmr: host
      ? {
          protocol: "ws",
          host,
          port: 1421,
        }
      : undefined,
    watch: {
      // 3. tell Vite to ignore watching `src-tauri`
      ignored: ["**/src-tauri/**"],
    },
  },
}));

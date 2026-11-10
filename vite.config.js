import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['src/main.tsx', 'resources/css/app.css'],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@/components': path.resolve(__dirname, './src/config/components'),
            '@': path.resolve(__dirname, './src'),
        },
    },
    css: {
        // 核心修复：把 CSS 处理引擎强制切回 esbuild，完美绕过 lightningcss 的语法不兼容 bug
        transformer: 'esbuild',
        // 开启压缩，绝不偷懒
        minify: true,
    },
    build: {
        // 确保生产环境开启压缩
        minify: 'esbuild',
    }
});

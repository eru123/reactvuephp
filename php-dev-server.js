import { spawn } from "child_process";
import { existsSync } from "fs";

/**
 * Custom Vite plugin to run a PHP development server.
 * @param {object} options - Plugin configuration options.
 * @param {number} [options.port=8000] - The port for the PHP server.
 * @param {string} [options.baseDir='public'] - The document root for the PHP server.
 * @returns {import('vite').Plugin}
 */
export function phpDevServer(options = {}) {
    let phpProcess;
    let config;

    // Set default values
    const { port = 8000, host = "127.0.0.1", baseDir = "" } = options;

    const startServer = () => {
        if (phpProcess) return;

        console.log(
            `\nStarting PHP Dev Server on port ${port}, serving from ${baseDir ? baseDir : "current directory"}...`,
        );

        // Command: php -S <host>:<port> <baseDir>/index.php
        const indexFile = `${baseDir}${baseDir.length > 0 && !baseDir.endsWith("/") ? "/" : ""}index.php`;

        if (!existsSync(indexFile)) {
            console.error(
                `Error: ${indexFile} not found. Please ensure the baseDir is correct.`,
            );
            return;
        }

        console.log(`Running command: php -S ${host}:${port} ${indexFile}`);

        phpProcess = spawn("php", ["-S", `${host}:${port}`, indexFile], {
            stdio: "inherit",
        });

        phpProcess.on("error", (err) => {
            console.error("PHP server failed to start:", err);
        });

        // Ensure PHP process is killed when the main process exits
        process.on("exit", () => {
            if (phpProcess) {
                phpProcess.kill();
            }
        });

        process.on("SIGINT", () => {
            if (phpProcess) {
                phpProcess.kill();
            }
            process.exit();
        });

        process.on("SIGTERM", () => {
            if (phpProcess) {
                phpProcess.kill();
            }
            process.exit();
        });
    };

    return {
        name: "php-dev-server",
        enforce: "pre",

        configResolved(resolvedConfig) {
            config = resolvedConfig;
        },

        // Hook called when the dev server is starting
        configureServer(server) {
            if (config.command === "serve") {
                startServer();
            }
        },

        // Hook called when the build starts
        buildStart() {
            if (config.command === "build" && config.build.watch) {
                startServer();
            }
        },

        // Hook called when the bundle is finished
        closeBundle() {
            if (phpProcess && !config.build.watch) {
                console.log("Stopping PHP Development Server...");
                phpProcess.kill();
                phpProcess = null;
            }
        },
    };
}

export default phpDevServer;

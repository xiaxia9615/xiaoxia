import { defineConfig } from "orval";

export default defineConfig({
  api: {
    input: {
      target: "./storage/app/private/scribe/openapi.yaml",
    },
    output: {
      target: "./src/lib/api/generated.ts",
      client: "fetch",
      mode: "single",
      mock: false,
      override: {
        mutator: {
          path: "./src/lib/api/orval-mutator.ts",
          name: "customFetch",
        },
      },
    },
    hooks: {
      afterAllFilesWrite: "prettier --write",
    },
  },
});

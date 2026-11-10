interface ImportMetaEnv {
  /** 后端 API 基地址，如 https://xiaoxia.ren/api */
  readonly VITE_API_BASE?: string;
  /** 可观测数据上报地址；未配置时不产生上报请求 */
  readonly VITE_TELEMETRY_ENDPOINT?: string;
  readonly DEV: boolean;
  readonly PROD: boolean;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
  /**
   * Vite 的批量静态引入。源码导出页用它把全部源文件以文本形式读入，
   * 便于把代码带出平台（详见 src/features/source-export）。
   */
  glob: (
    pattern: string | string[],
    options?: {
      query?: string;
      import?: string;
      eager?: boolean;
    },
  ) => Record<string, string>;
}

/** Vite 的 `?raw` 后缀导入：模块内容为文件原始文本 */
declare module "*?raw" {
  const content: string;
  export default content;
}

declare module "*.css";

interface Window {
  __MUSE__: {
    appId: string;
    appVersionId: string;
    domain: string;
    env: "dev" | "prod";
    appwrite?: {
      endpoint: string;
      projectId: string;
      databaseId: string;
      storageBucketId?: string;
    };
  };
  __TERN__: {
    user?: {
      clientUser: {
        avatarUrl: string;
        displayName: string;
        userId: string;
        userName: string;
        nickName: string;
      };
      outUserNo: string;
    };
  };
  __MUSE_PREVIEW_ERROR_CAPTURE__?: {
    reportError: (
      error: unknown,
      options?: {
        kind?: "manual" | "react-error-boundary";
        severity?: "error" | "fatal";
      },
    ) => void;
  };
}
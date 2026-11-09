/**
 * 开放参与功能出口。
 * 路由只依赖本 barrel，不直接引用内部实现（策略、hook 文件路径），
 * 因此新增参与类型或调整内部结构都不会波及路由。
 */
export { DEFAULT_PARTICIPATION_TYPE } from "./strategies/registry";
export { contributeStreamQuery } from "./use-contribute-stream";
export type { ContributeView } from "./types";

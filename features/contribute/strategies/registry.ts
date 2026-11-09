import { joinPlanStrategy } from "./join-plan";
import type { ParticipationStrategy } from "./types";

/**
 * 参与类型登记表。
 * 新增参与类型：写一个策略文件，在此登记一行即可，路由与页面无需改动。
 */
const STRATEGIES: readonly ParticipationStrategy[] = [joinPlanStrategy];

/** 默认参与类型（URL 未指定或指定了未知类型时使用）。 */
export const DEFAULT_PARTICIPATION_TYPE = joinPlanStrategy.id;

export function listParticipationStrategies(): readonly ParticipationStrategy[] {
  return STRATEGIES;
}

/** 是否已登记的参与类型。 */
export function isParticipationType(value: string): boolean {
  return STRATEGIES.some((s) => s.id === value);
}

/** 取策略；未知类型回退默认策略，保证旧链接仍可用。 */
export function getParticipationStrategy(id: string | undefined): ParticipationStrategy {
  return STRATEGIES.find((s) => s.id === id) ?? joinPlanStrategy;
}

export type { ParticipationStrategy };

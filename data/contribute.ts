/**
 * 开放参与 · 静态展示数据
 *
 * 参与记录已由 QMuse 云服务持久化（见 lib/api/contribute.ts），
 * 本文件仅承载仍以静态文案展示的阶段目标与开放计划。
 */

export interface Milestone {
  id: number;
  label: string;
  /** 目标值 */
  target: number;
  /** 当前值 */
  current: number;
  unit: string;
}

export interface OpenPlan {
  id: string;
  title: string;
  desc: string;
  state: string;
}

/** 阶段性目标 */
export const MILESTONES: Milestone[] = [
  { id: 1, label: "共同参与人次", target: 20000, current: 12846, unit: "人" },
  { id: 2, label: "累计助力次数", target: 500000, current: 356720, unit: "次" },
  { id: 3, label: "开放计划认领", target: 100, current: 82, unit: "项" },
];

/** 开放参与计划清单 */
export const OPEN_PLANS = [
  { id: "plan-1", title: "新器物共创", desc: "大家投票选下一季主推器物", state: "进行中" },
  { id: "plan-2", title: "导购语料共建", desc: "共同完善 AI 导购的理解词库", state: "认领中" },
  { id: "plan-3", title: "生活志约稿", desc: "欢迎投稿你与好物的故事", state: "开放中" },
  { id: "plan-4", title: "公益回馈计划", desc: "每季度利润按比例回馈社区", state: "进行中" },
];
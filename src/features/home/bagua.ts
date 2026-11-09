/**
 * 先天八卦阵盘数据。
 *
 * 每卦三条爻，自下而上（初爻、二爻、三爻）；true 为阳爻，false 为阴爻。
 * 方位取先天八卦：乾上坤下、离左坎右，震巽艮兑布四维。
 */

export type Yao = boolean;

export interface Gua {
  /** 卦名（单字） */
  name: string;
  /** 象（天、地、水、火、雷、风、山、泽） */
  symbol: string;
  /** 自然属性 */
  nature: string;
  /** 三爻，自下而上 */
  yao: [Yao, Yao, Yao];
  /** 阵盘角度（0 度为正上方） */
  angle: number;
  /** 对应后端域语义，用作阵法与业务的映射 */
  domain: string;
}

export const EIGHT_TRIGRAMS: Gua[] = [
  { name: "乾", symbol: "天", nature: "三连", yao: [true, true, true], angle: 0, domain: "auth" },
  { name: "兑", symbol: "泽", nature: "上缺", yao: [true, true, false], angle: 45, domain: "order" },
  { name: "离", symbol: "火", nature: "中虚", yao: [true, false, true], angle: 90, domain: "pay" },
  { name: "震", symbol: "雷", nature: "仰盂", yao: [true, false, false], angle: 135, domain: "queue" },
  { name: "巽", symbol: "风", nature: "下断", yao: [false, true, true], angle: 180, domain: "cart" },
  { name: "坎", symbol: "水", nature: "中满", yao: [false, true, false], angle: 225, domain: "goods" },
  { name: "艮", symbol: "山", nature: "覆碗", yao: [false, false, true], angle: 270, domain: "stock" },
  { name: "坤", symbol: "地", nature: "六断", yao: [false, false, false], angle: 315, domain: "user" },
];

/** 极坐标转阵盘坐标（0 度朝上，顺时针）。 */
export function polar(angle: number, radius: number): { x: number; y: number } {
  const rad = ((angle - 90) * Math.PI) / 180;
  return {
    x: 250 + radius * Math.cos(rad),
    y: 250 + radius * Math.sin(rad),
  };
}

import { PRODUCTS, type Product } from "@/data/products";

export interface AssistantReply {
  text: string;
  suggestions: Product[];
}

const SUGGEST_COUNT = 3;

function pickByCategory(category: string): Product[] {
  return PRODUCTS.filter((p) => p.category === category).slice(0, SUGGEST_COUNT);
}

/**
 * 本地导购引擎。
 *
 * 说明：真实后端（xiaoxia.ren）的 AI 接口契约在沙箱外无法访问，
 * 因此这里先用关键词规则给出可用回复，并保留替换为真实 LLM 接口的对接点。
 * 真实契约确认后，可将本函数替换为对 POST /chat 等端点的调用。
 */
export function askAssistant(raw: string): AssistantReply {
  const input = raw.trim();

  if (!input) {
    return {
      text: "你想找点什么？告诉我预算、使用场景或喜欢的风格，我来帮你挑选。",
      suggestions: PRODUCTS.slice(0, SUGGEST_COUNT),
    };
  }

  const text = input.toLowerCase();

  const greetings = ["你好", "hi", "hello", "嗨", "在吗"];
  if (greetings.some((g) => text.includes(g))) {
    return {
      text: "你好呀，我是小夏导购！想给家里添点什么，或者有特定的预算和场合，都可以告诉我。",
      suggestions: PRODUCTS.slice(0, SUGGEST_COUNT),
    };
  }

  if (text.includes("杯") || text.includes("咖啡") || text.includes("茶") || text.includes("喝")) {
    return {
      text: "想要一套有质感的饮茶器物吗？这只手工陶土釉面咖啡杯，握感温润、保温性好，很多客人回购。需要的话我也可以为你搭配托盘。",
      suggestions: pickByCategory("drink"),
    };
  }

  if (text.includes("沙发") || text.includes("抱枕") || text.includes("毯") || text.includes("床")) {
    return {
      text: "想让沙发或床榻更舒服，亚麻抱枕和羊毛盖毯是很棒的选择，材质亲肤透气，色调也柔和百搭。",
      suggestions: pickByCategory("home"),
    };
  }

  if (
    text.includes("香") ||
    text.includes("蜡烛") ||
    text.includes("灯") ||
    text.includes("氛围") ||
    text.includes("礼物") ||
    text.includes("礼")
  ) {
    return {
      text: "想营造氛围或挑份礼物，香氛蜡烛和黄铜台灯都是不错的选项，温暖又有格调，很适合送人。",
      suggestions: pickByCategory("decor"),
    };
  }

  if (text.includes("收纳") || text.includes("托盘") || text.includes("整理") || text.includes("桌面")) {
    return {
      text: "想整理桌面的话，黑胡桃木托盘和藤编收纳篮都很实用，一木一器的温润质感，让收纳也有好心情。",
      suggestions: pickByCategory("decor"),
    };
  }

  if (text.includes("贵") || text.includes("预算") || text.includes("便宜") || text.includes("多少钱")) {
    return {
      text: "我们的好物价位从几十元到几百元不等。如果你告诉我大概预算，我可以帮你精选出最合适的几件。",
      suggestions: PRODUCTS.slice(0, SUGGEST_COUNT),
    };
  }

  return {
    text: "我听到你的需求了。为了更准确地帮你挑选，可以再多告诉我一些：比如用途场景、预算范围，或者喜欢的材质与风格。",
    suggestions: PRODUCTS.slice(0, SUGGEST_COUNT),
  };
}
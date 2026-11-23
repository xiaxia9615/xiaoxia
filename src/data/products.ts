export type CategoryId = "all" | "drink" | "home" | "decor";

export interface Category {
  id: CategoryId;
  label: string;
}

export interface Product {
  id: string;
  name: string;
  category: Exclude<CategoryId, "all">;
  price: number;
  originalPrice: number;
  rating: number;
  reviews: number;
  description: string;
  detail: string;
  tags: string[];
  image: string;
  images?: string[];
  featured?: boolean;
}

export const CATEGORIES: Category[] = [
  { id: "all", label: "全部" },
  { id: "drink", label: "茶饮器物" },
  { id: "home", label: "家居织物" },
  { id: "decor", label: "氛围陈设" },
];

const IMG = {
  hero: "/images/products/placeholder.svg",
  mug: "/images/products/placeholder.svg",
  pillow: "/images/products/placeholder.svg",
  candle: "/images/products/placeholder.svg",
  lamp: "/images/products/placeholder.svg",
  tray: "/images/products/placeholder.svg",
  basket: "/images/products/placeholder.svg",
  throw: "/images/products/placeholder.svg",
};

export const HERO_IMAGE = IMG.hero;

export const PRODUCTS: Product[] = [
  {
    id: "mug-terracotta",
    name: "陶土釉面咖啡杯",
    category: "drink",
    price: 12900,
    originalPrice: 15900,
    rating: 4.9,
    reviews: 328,
    description: "手工拉坯，哑光陶土釉，握感温润",
    detail:
      "每一只都由陶艺师手工拉坯而成，哑光陶土釉在光线下呈现细腻的层次。杯壁厚实保温，握感温润，适合每日的咖啡与茶时光。",
    tags: ["手工", "陶艺", "保温"],
    image: IMG.mug,
    featured: true,
  },
  {
    id: "linen-throw-pillow",
    name: "亚麻纹理抱枕",
    category: "home",
    price: 8900,
    originalPrice: 10900,
    rating: 4.8,
    reviews: 412,
    description: "天然亚麻，亲肤透气，柔和不刺肤",
    detail:
      "选用天然亚麻面料，经纬交织出细腻的肌理。内芯饱满回弹，抱感柔软而不塌陷，为沙发与床榻增添一份松弛的质感。",
    tags: ["亚麻", "亲肤", "沙发"],
    image: IMG.pillow,
    featured: true,
  },
  {
    id: "amber-candle-cedar",
    name: "雪松琥珀香氛蜡烛",
    category: "decor",
    price: 9900,
    originalPrice: 12900,
    rating: 4.9,
    reviews: 256,
    description: "天然大豆蜡，雪松与琥珀的沉静气息",
    detail:
      "以天然大豆蜡与植物精油调和，点燃后弥漫雪松与琥珀的沉静气息。琥珀色玻璃罐身，配木盖防尘，燃烧时长约 40 小时。",
    tags: ["香氛", "大豆蜡", "40小时"],
    image: IMG.candle,
    featured: true,
  },
  {
    id: "brass-table-lamp",
    name: "黄铜亚麻台灯",
    category: "decor",
    price: 35900,
    originalPrice: 42900,
    rating: 4.7,
    reviews: 118,
    description: "复古黄铜灯身，亚麻灯罩，暖光入夜",
    detail:
      "复古黄铜灯身经过拉丝处理，搭配亚麻灯罩，点亮时投下柔和暖光。三段触控调光，为书房与床头营造静谧的夜间氛围。",
    tags: ["复古", "触控调光", "暖光"],
    image: IMG.lamp,
    featured: true,
  },
  {
    id: "walnut-tray",
    name: "黑胡桃木收纳托盘",
    category: "decor",
    price: 7900,
    originalPrice: 9900,
    rating: 4.8,
    reviews: 203,
    description: "整木黑胡桃，温润木纹，一木一器",
    detail:
      "取自整块黑胡桃木，边缘手工打磨至圆润。纹理温润自然，可作桌面收纳、茶点托盘，随着使用愈显光泽。",
    tags: ["黑胡桃", "整木", "桌面"],
    image: IMG.tray,
  },
  {
    id: "rattan-basket",
    name: "藤编收纳篮",
    category: "home",
    price: 11900,
    originalPrice: 14900,
    rating: 4.7,
    reviews: 164,
    description: "手工藤编，天然材质，收纳有温度",
    detail:
      "以天然藤条手工编织，质地坚韧而富有弹性。可收纳织物、杂志与杂物，为家居带来一抹自然的呼吸感。",
    tags: ["藤编", "天然", "收纳"],
    image: IMG.basket,
  },
  {
    id: "cream-wool-throw",
    name: "奶油羊毛盖毯",
    category: "home",
    price: 19900,
    originalPrice: 24900,
    rating: 4.9,
    reviews: 287,
    description: "亲肤羊毛针织，轻盈保暖，四季可用",
    detail:
      "选用细软羊毛针织而成，触感轻盈蓬松，保暖而不闷热。奶油色调柔和，铺在沙发或床尾即是温柔的点缀。",
    tags: ["羊毛", "针织", "四季"],
    image: IMG.throw,
  },
];

export function getProduct(id: string): Product | undefined {
  return PRODUCTS.find((p) => p.id === id);
}

export function formatPrice(value: number): string {
  return `¥${(value / 1000).toFixed(2)}`;
}
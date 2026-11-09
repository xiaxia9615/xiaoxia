import { degrade, num, request } from "./request";

/** 文章 DTO（显式契约声明）。 */
export interface ArticleDto {
  id?: number;
  title?: string;
  cover?: string;
  summary?: string;
  content?: string;
  author?: string;
  category_id?: number;
  add_time?: string;
  created_at?: string;
}

export interface Article {
  id: number;
  title: string;
  cover?: string;
  summary?: string;
  content?: string;
  author?: string;
  category_id?: number;
  add_time?: string;
  created_at?: string;
}

export interface ArticleCategoryDto {
  id?: number;
  name?: string;
}

export interface ArticleCategory {
  id: number;
  name: string;
}

export function toArticle(dto: ArticleDto): Article {
  return {
    id: num(dto.id),
    title: dto.title ?? "",
    cover: dto.cover,
    summary: dto.summary,
    content: dto.content,
    author: dto.author,
    category_id: dto.category_id,
    add_time: dto.add_time,
    created_at: dto.created_at,
  };
}

export function toArticleCategory(dto: ArticleCategoryDto): ArticleCategory {
  return { id: num(dto.id), name: dto.name ?? "" };
}

/** 文章列表。内容区为可选展示数据，失败时降级为空列表。 */
export async function fetchArticles(
  query: { page?: number; limit?: number; category_id?: number } = {},
): Promise<{ list: Article[]; total: number }> {
  return degrade("文章列表", { list: [], total: 0 }, async () => {
    const data = await request<{ list?: ArticleDto[]; data?: ArticleDto[]; total?: number }>(
      "/article/index",
      {
        params: {
          page: query.page ?? 1,
          page_size: query.limit ?? 10,
          ...(query.category_id ? { category_id: query.category_id } : {}),
        },
      },
    );
    const rawList = data.list ?? data.data ?? [];
    const list = rawList.map(toArticle);
    return { list, total: typeof data.total === "number" ? data.total : list.length };
  });
}

export async function fetchArticleCategories(): Promise<ArticleCategory[]> {
  return degrade("文章分类", [], async () => {
    const data = await request<{ category?: ArticleCategoryDto[]; data?: ArticleCategoryDto[] }>(
      "/article/category",
    );
    return (data.category ?? data.data ?? []).map(toArticleCategory);
  });
}

export async function fetchArticleDetail(id: number): Promise<Article | null> {
  return degrade("文章详情", null, async () => {
    const data = await request<ArticleDto>("/article/detail", { params: { id } });
    if (!data || Object.keys(data).length === 0) return null;
    return toArticle(data);
  });
}

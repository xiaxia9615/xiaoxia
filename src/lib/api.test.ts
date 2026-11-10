import { describe, it, expect, vi, beforeEach } from 'vitest';
import { fetchProducts, fetchProduct } from './api/goods';

// 模拟 fetch
global.fetch = vi.fn();

describe('Goods API Tests', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('fetchProducts 正常返回列表', async () => {
    (global.fetch as any).mockResolvedValueOnce({
      ok: true,
      json: async () => ({ code: 0, msg: 'ok', data: { list: [{ id: '1', name: '商品A' }] } }),
    });

    const list = await fetchProducts();
    expect(list.length).toBe(1);
    expect(list[0].name).toBe('商品A');
  });

  it('fetchProduct 正常返回单个详情', async () => {
    (global.fetch as any).mockResolvedValueOnce({
      ok: true,
      json: async () => ({ code: 0, msg: 'ok', data: { id: '7', name: '单品', price: 100 } }),
    });

    const p = await fetchProduct("7");
    expect(p?.id).toBe("7");
    expect(p?.name).toBe("单品");
  });
});

import { useCallback, useEffect, useMemo, useState } from "react";

export interface CartLine {
  productId: string;
  qty: number;
}

const STORAGE_KEY = "xiaoxia.cart";

export function useCart() {
  const [lines, setLines] = useState<CartLine[]>(() => {
    if (typeof window === "undefined") return [];
    try {
      const raw = window.localStorage.getItem(STORAGE_KEY);
      if (!raw) return [];
      const parsed = JSON.parse(raw) as CartLine[];
      return Array.isArray(parsed) ? parsed : [];
    } catch {
      return [];
    }
  });

  useEffect(() => {
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(lines));
    } catch {
      // ignore storage quota / availability errors
    }
  }, [lines]);

  const totalQty = useMemo(() => lines.reduce((sum, l) => sum + l.qty, 0), [lines]);

  const add = useCallback((productId: string, qty = 1): Promise<boolean> => {
    setLines((prev) => {
      const existing = prev.find((l) => l.productId === productId);
      if (existing) {
        return prev.map((l) =>
          l.productId === productId ? { ...l, qty: l.qty + qty } : l,
        );
      }
      return [...prev, { productId, qty }];
    });
    return Promise.resolve(true);
  }, []);

  const setQty = useCallback((productId: string, qty: number) => {
    setLines((prev) =>
      qty <= 0
        ? prev.filter((l) => l.productId !== productId)
        : prev.map((l) => (l.productId === productId ? { ...l, qty } : l)),
    );
  }, []);

  const remove = useCallback((productId: string) => {
    setLines((prev) => prev.filter((l) => l.productId !== productId));
  }, []);

  const clear = useCallback(() => setLines([]), []);

  return { lines, totalQty, add, setQty, remove, clear };
}

export type CartApi = ReturnType<typeof useCart>;
import { useEffect, useRef, useState } from "react";
import { askAssistant } from "@/lib/assistant";
import type { Product } from "@/data/products";

export interface AssistantMessage {
  id: string;
  role: "user" | "assistant";
  text: string;
  suggestions?: Product[];
}

/** 开场引导语（欢迎卡片上的快捷提问） */
export const ASSISTANT_SUGGESTIONS = [
  "想给家里添置点温暖的器物",
  "预算 200 以内，适合送朋友",
  "沙发想放个舒服的抱枕",
  "想要有氛围感的香薰",
];

let messageId = 0;
const nextId = () => `msg-${messageId++}`;

/**
 * AI 导购对话状态机：消息列表、输入、思考中状态、发送与自动滚动。
 *
 * 说明：真实后端 AI 模块尚未上线，当前使用本地导购（lib/assistant）。
 * 接入流式接口后，只需替换本 hook 内的请求实现，页面无需改动。
 */
export function useAssistantChat() {
  const [messages, setMessages] = useState<AssistantMessage[]>([]);
  const [input, setInput] = useState("");
  const [thinking, setThinking] = useState(false);
  const scrollRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const el = scrollRef.current;
    if (el) el.scrollTop = el.scrollHeight;
  }, [messages, thinking]);

  const send = (text: string) => {
    const trimmed = text.trim();
    if (!trimmed || thinking) return;

    setMessages((prev) => [...prev, { id: nextId(), role: "user", text: trimmed }]);
    setInput("");
    setThinking(true);

    window.setTimeout(() => {
      const local = askAssistant(trimmed);
      setMessages((prev) => [
        ...prev,
        {
          id: nextId(),
          role: "assistant",
          text: local.text,
          suggestions: local.suggestions,
        },
      ]);
      setThinking(false);
    }, 600);
  };

  return {
    messages,
    input,
    setInput,
    thinking,
    scrollRef,
    send,
    isEmpty: messages.length === 0 && !thinking,
    canSend: input.trim().length > 0 && !thinking,
  };
}

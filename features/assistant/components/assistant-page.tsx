import { Link } from "@tanstack/react-router";
import { ArrowRight, MessageCircleHeart, Send, Sparkles } from "lucide-react";
import { SiteLayout } from "@/components/site/layout";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { formatPrice } from "@/data/products";
import { cn } from "@/lib/utils";
import { AppImage } from "@/components/system/app-image";
import { ASSISTANT_SUGGESTIONS, useAssistantChat, type AssistantMessage } from "../use-assistant-chat";

function WelcomeCard({ onPick }: { onPick: (text: string) => void }) {
  return (
    <div className="animate-fade-up mx-auto max-w-xl text-center">
      <span className="mx-auto grid size-14 place-items-center rounded-lg bg-clay text-clay-foreground shadow-glow">
        <Sparkles className="size-7" />
      </span>
      <h1 className="mt-4 font-display text-2xl font-bold text-ink sm:text-3xl">
        你好，我是小夏导购
      </h1>
      <p className="mt-2 text-sm leading-6 text-muted-foreground">
        告诉我你的喜好、预算和使用场景，我来为你挑选最合适的生活好物。
      </p>
      <div className="mt-6 flex flex-wrap justify-center gap-2">
        {ASSISTANT_SUGGESTIONS.map((s) => (
          <button
            key={s}
            onClick={() => onPick(s)}
            className="rounded border border-border bg-card px-4 py-2 text-sm text-muted-foreground transition-colors hover:border-clay/40 hover:text-clay"
          >
            {s}
          </button>
        ))}
      </div>
    </div>
  );
}

function ChatMessage({ message }: { message: AssistantMessage }) {
  const isUser = message.role === "user";
  return (
    <div className={cn("flex", isUser ? "justify-end" : "justify-start")}>
      <div
        className={cn(
          "max-w-[85%] rounded-xl px-4 py-3 sm:max-w-[75%]",
          isUser
            ? "rounded-br-md bg-clay text-clay-foreground"
            : "rounded-bl-md border border-border/70 bg-card text-foreground",
        )}
      >
        <p className="whitespace-pre-wrap text-sm leading-6">{message.text}</p>
        {message.suggestions && message.suggestions.length > 0 && (
          <div className="mt-3 grid gap-2">
            {message.suggestions.map((p) => (
              <Link
                key={p.id}
                to="/products/$productId"
                params={{ productId: p.id }}
                className="flex items-center gap-3 rounded-lg border border-border/70 bg-background/60 p-2 transition-colors hover:border-clay/40"
              >
                <AppImage
                  src={p.image}
                  alt={p.name}
                  className="size-12 shrink-0 rounded-xl object-cover"
                />
                <div className="min-w-0 flex-1">
                  <p className="truncate text-sm font-medium text-ink">{p.name}</p>
                  <p className="truncate text-xs text-muted-foreground">
                    {p.description}
                  </p>
                </div>
                <div className="text-right">
                  <p className="font-display text-sm font-bold text-clay">
                    {formatPrice(p.price)}
                  </p>
                  <span className="mt-0.5 inline-flex items-center gap-1 text-xs text-clay">
                    去看看 <ArrowRight className="size-3" />
                  </span>
                </div>
              </Link>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

function TypingIndicator() {
  return (
    <div className="flex justify-start">
      <div className="rounded-xl rounded-bl-md border border-border/70 bg-card px-4 py-3">
        <div className="flex items-center gap-1">
          <span className="size-2 animate-pulse-dot rounded bg-clay" />
          <span className="size-2 animate-pulse-dot rounded bg-clay [animation-delay:0.2s]" />
          <span className="size-2 animate-pulse-dot rounded bg-clay [animation-delay:0.4s]" />
        </div>
      </div>
    </div>
  );
}

/** AI 导购对话页：对话状态机在 useAssistantChat，组件只负责渲染。 */
export function AssistantPage() {
  const { messages, input, setInput, thinking, scrollRef, send, isEmpty, canSend } =
    useAssistantChat();

  return (
    <SiteLayout>
      <section className="mx-auto flex max-w-3xl flex-col px-4 py-10 sm:px-6">
        <div className="mb-6 flex items-center gap-2">
          <span className="inline-flex items-center gap-1.5 rounded bg-clay/10 px-3 py-1 text-xs font-medium text-clay">
            <MessageCircleHeart className="size-3.5" />
            AI 导购助手
          </span>
        </div>

        <div
          ref={scrollRef}
          className="flex min-h-[60vh] flex-col gap-4 overflow-y-auto pb-4"
        >
          {isEmpty ? (
            <WelcomeCard onPick={send} />
          ) : (
            <>
              {messages.map((m) => (
                <ChatMessage key={m.id} message={m} />
              ))}
              {thinking && <TypingIndicator />}
            </>
          )}
        </div>

        <form
          className="mt-2 flex items-end gap-2 rounded-xl border border-border bg-card p-2 shadow-card"
          onSubmit={(e) => {
            e.preventDefault();
            send(input);
          }}
        >
          <Textarea
            value={input}
            onChange={(e) => setInput(e.target.value)}
            placeholder="说说你的需求，例如：预算 300 送朋友一盏灯"
            rows={1}
            className="max-h-32 min-h-11 resize-none border-0 bg-transparent px-3 py-2.5 focus-visible:ring-0"
            onKeyDown={(e) => {
              if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                send(input);
              }
            }}
          />
          <Button
            type="submit"
            size="icon"
            className="size-11 shrink-0 rounded-lg"
            disabled={!canSend}
            aria-label="发送"
          >
            <Send className="size-4" />
          </Button>
        </form>
        <p className="mt-3 text-center text-xs text-muted-foreground">
          当前为本地演示导购，AI 对话服务接入后将提供更精准的推荐
        </p>
      </section>
    </SiteLayout>
  );
}
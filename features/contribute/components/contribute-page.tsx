import { useState } from "react";
import { HandHeart, Radio, Sparkles } from "lucide-react";
import { SiteLayout } from "@/components/site/layout";
import { ParticipationFlow } from "@/components/site/participation-flow";
import { ScrollReveal, useCountUp } from "@/components/site/motion";
import { Button } from "@/components/ui/button";
import { MILESTONES, OPEN_PLANS } from "@/data/contribute";
import { useContributeForm } from "../use-contribute-form";
import { useContributeStream } from "../use-contribute-stream";
import { getParticipationStrategy } from "../strategies/registry";
import { readJoinedFlag } from "../storage";
import type { ContributeView } from "../types";
import { ProgressMeter, StatBlock } from "./contribute-stats";
import { JoinDialog } from "./join-dialog";
import { ParticipantStream } from "./participant-stream";
import { PlanGrid } from "./plan-grid";

/**
 * 参与页容器：负责区块编排与视图切换，
 * 数据与表单状态全部来自 feature hook。
 */
export function ContributePage({
  view,
  onViewChange,
  typeId,
}: {
  view: ContributeView;
  onViewChange: (view: ContributeView) => void;
  /** 参与类型 id：由路由参数透传，新增类型无需改动本文件与路由 */
  typeId: string;
}) {
  const [dialogOpen, setDialogOpen] = useState(false);
  const [joined, setJoined] = useState(readJoinedFlag);
  const { items, total, isLoading } = useContributeStream();
  const strategy = getParticipationStrategy(typeId);
  const form = useContributeForm({
    typeId,
    onSuccess: () => {
      setJoined(true);
      setDialogOpen(false);
    },
  });
  const animatedTotal = useCountUp(total);

  const scrollToStream = () => {
    document.getElementById("stream")?.scrollIntoView({ behavior: "smooth", block: "start" });
  };

  return (
    <SiteLayout>
      <section className="terminal-grid relative overflow-hidden border-b border-border">
        <div className="pointer-events-none absolute -right-20 -top-24 size-96 rounded-full bg-clay/10 blur-3xl" />
        <div className="pointer-events-none absolute -bottom-28 -left-16 size-80 rounded-full bg-ok/5 blur-3xl" />

        <div className="relative mx-auto max-w-6xl px-4 py-16 sm:px-6 md:py-24">
          <ScrollReveal>
            <span className="inline-flex items-center gap-2 rounded border border-ok/30 bg-ok/10 px-3 py-1 font-mono text-xs font-medium text-ok">
              <Radio className="size-3.5 animate-pulse-dot" />
              OPEN CONTRIBUTE · 开放进行中
            </span>
          </ScrollReveal>
          <ScrollReveal delay={0.08}>
            <h1 className="mt-6 max-w-2xl font-display text-4xl font-black leading-tight tracking-tight text-ink sm:text-5xl md:text-6xl">
              好物，不止被挑选
              <br />
              更<span className="text-clay">一起长成</span>
            </h1>
          </ScrollReveal>
          <ScrollReveal delay={0.16}>
            <p className="mt-5 max-w-lg text-base leading-7 text-muted-foreground">
              把选品、内容与回馈开放给大家。每一次参与，都是让这里更像「我们的小夏」的一小步。
            </p>
          </ScrollReveal>
          <ScrollReveal delay={0.24}>
            <div className="mt-8 flex flex-wrap gap-3">
              <Button
                size="lg"
                className="rounded px-7 font-mono transition-transform active:scale-95"
                onClick={() => setDialogOpen(true)}
              >
                <HandHeart className="size-4" />
                {joined ? "我已加入" : strategy.label}
              </Button>
              <Button
                size="lg"
                variant="outline"
                className="rounded px-7 font-mono transition-transform active:scale-95"
                onClick={scrollToStream}
              >
                <Sparkles className="size-4 text-clay" />
                看看大家
              </Button>
            </div>
          </ScrollReveal>

          <div className="mt-12 grid gap-4 sm:grid-cols-3">
            <ScrollReveal direction="up" delay={0.1}>
              <StatBlock
                label="共同参与"
                value={animatedTotal.toLocaleString()}
                sub="条参与记录已持久化"
              />
            </ScrollReveal>
            <ScrollReveal direction="up" delay={0.18}>
              <StatBlock label="累计助力" value="实时累计" sub="参与越多，目标越快点亮" />
            </ScrollReveal>
            <ScrollReveal direction="up" delay={0.26}>
              <StatBlock
                label="开放目标"
                value={`${OPEN_PLANS.length} 项`}
                sub="在途计划进行中"
              />
            </ScrollReveal>
          </div>
        </div>
      </section>

      <section id="stream" className="mx-auto max-w-6xl px-4 py-14 sm:px-6 md:py-20">
        <ScrollReveal>
          <div className="flex flex-wrap items-end justify-between gap-4">
            <div>
              <p className="font-mono text-xs uppercase tracking-widest text-clay">LIVE FEED</p>
              <h2 className="mt-2 font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">
                此刻正在发生
              </h2>
            </div>
            <div className="flex rounded border border-border bg-surface p-1">
              {(
                [
                  { id: "participants", label: "参与流" },
                  { id: "plans", label: "开放计划" },
                ] as const
              ).map((tab) => (
                <button
                  key={tab.id}
                  onClick={() => onViewChange(tab.id)}
                  className={`rounded px-4 py-1.5 font-mono text-sm transition-colors ${
                    view === tab.id
                      ? "bg-clay text-clay-foreground"
                      : "text-muted-foreground hover:text-ink"
                  }`}
                >
                  {tab.label}
                </button>
              ))}
            </div>
          </div>
        </ScrollReveal>

        <div className="mt-8">
          {view === "participants" ? (
            <ParticipantStream items={items} isLoading={isLoading} />
          ) : (
            <PlanGrid onJoin={() => setDialogOpen(true)} />
          )}
        </div>
      </section>

      <section className="mx-auto max-w-6xl px-4 py-14 sm:px-6 md:py-20">
        <ScrollReveal direction="left">
          <p className="font-mono text-xs uppercase tracking-widest text-clay">DATA FLOW</p>
          <h2 className="mt-2 font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">
            你的参与，如何抵达参与墙
          </h2>
          <p className="mt-3 max-w-lg text-sm leading-6 text-muted-foreground">
            每一步都在 QMuse 云服务中持久化，多人共享、随时可读。
          </p>
        </ScrollReveal>
        <ScrollReveal direction="right" delay={0.12} className="mt-8">
          <ParticipationFlow />
        </ScrollReveal>
      </section>

      <section className="border-t border-border bg-surface/40">
        <div className="mx-auto max-w-6xl px-4 py-14 sm:px-6 md:py-20">
          <ScrollReveal>
            <p className="font-mono text-xs uppercase tracking-widest text-clay">MILESTONES</p>
            <h2 className="mt-2 font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">
              一起把目标点亮
            </h2>
          </ScrollReveal>
          <div className="mt-8 grid gap-4 lg:grid-cols-3">
            {MILESTONES.map((m, i) => (
              <ScrollReveal key={m.id} direction="scale" delay={i * 0.1}>
                <ProgressMeter {...m} />
              </ScrollReveal>
            ))}
          </div>
        </div>
      </section>

      <JoinDialog
        open={dialogOpen}
        onOpenChange={setDialogOpen}
        form={form}
        strategy={strategy}
      />
    </SiteLayout>
  );
}

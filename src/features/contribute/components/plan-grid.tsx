import { ArrowRight, Lightbulb, Megaphone, Target, Users } from "lucide-react";
import { OPEN_PLANS } from "@/data/contribute";
import { Button } from "@/components/ui/button";

const PLAN_ICONS = [Users, Lightbulb, Megaphone, Target];

/** 开放计划网格 */
export function PlanGrid({ onJoin }: { onJoin: () => void }) {
  return (
    <div className="mt-8 grid gap-4 sm:grid-cols-2">
      {OPEN_PLANS.map((plan, i) => {
        const Icon = PLAN_ICONS[i % PLAN_ICONS.length];
        return (
          <div
            key={plan.id}
            className="flex flex-col rounded border border-border bg-card/60 p-5 shadow-soft"
          >
            <div className="flex items-start justify-between">
              <span className="grid size-10 place-items-center rounded bg-surface text-clay">
                <Icon className="size-5" />
              </span>
              <span className="rounded border border-ok/30 bg-ok/10 px-2 py-0.5 font-mono text-[10px] text-ok">
                {plan.state}
              </span>
            </div>
            <h3 className="mt-4 font-display text-lg font-bold text-ink">{plan.title}</h3>
            <p className="mt-1 flex-1 text-sm leading-6 text-muted-foreground">{plan.desc}</p>
            <Button
              variant="outline"
              size="sm"
              className="mt-5 self-start rounded font-mono"
              onClick={onJoin}
            >
              参与计划
              <ArrowRight className="size-4" />
            </Button>
          </div>
        );
      })}
    </div>
  );
}

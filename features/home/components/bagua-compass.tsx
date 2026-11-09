import { useState } from "react";
import { EIGHT_TRIGRAMS, polar, type Gua } from "../bagua";

/** 单条爻：阳爻为整线，阴爻为断线。 */
function YaoLine({ yao, x, y, width }: { yao: boolean; x: number; y: number; width: number }) {
  const half = width / 2;
  if (yao) {
    return <rect x={x - half} y={y} width={width} height={4} rx={1} fill="currentColor" />;
  }
  const gap = width * 0.22;
  return (
    <>
      <rect x={x - half} y={y} width={(width - gap) / 2} height={4} rx={1} fill="currentColor" />
      <rect x={x + gap / 2} y={y} width={(width - gap) / 2} height={4} rx={1} fill="currentColor" />
    </>
  );
}

function GuaMark({ gua }: { gua: Gua }) {
  const p = polar(gua.angle, 186);
  return (
    <g className="zhen-gua" style={{ color: "var(--zhen-gold)" }}>
      {/* 沿径向放置三条爻，从外向内为初、二、三爻 */}
      {gua.yao.map((y, i) => (
        <YaoLine key={i} yao={y} x={p.x} y={p.y + 16 - i * 9} width={34} />
      ))}
      <text
        x={polar(gua.angle, 152).x}
        y={polar(gua.angle, 152).y + 8}
        textAnchor="middle"
        fontSize={22}
        fill="currentColor"
        style={{ fontFamily: "var(--zhen-seraph)" }}
      >
        {gua.name}
      </text>
      <text
        x={polar(gua.angle, 216).x}
        y={polar(gua.angle, 216).y + 5}
        textAnchor="middle"
        fontSize={10}
        fill="var(--zhen-ink-dim)"
        style={{ fontFamily: "var(--zhen-mono)" }}
      >
        {gua.symbol}
      </text>
    </g>
  );
}

/** 阵盘：外环刻度 + 中环八卦（反向慢转）+ 内圈太极。 */
export function BaguaCompass() {
  const [active, setActive] = useState<string>("离");

  const ticks = Array.from({ length: 64 }, (_, i) => {
    const angle = (i * 360) / 64;
    const inner = polar(angle, i % 8 === 0 ? 226 : 233);
    const outer = polar(angle, 240);
    return { angle, inner, outer, major: i % 8 === 0 };
  });

  return (
    <div className="relative mx-auto w-full max-w-[560px]">
      <svg viewBox="0 0 500 500" className="w-full" role="img" aria-label="先天八卦阵盘：太极居中，八卦环列">
        {/* 外环与刻度：慢速顺转 */}
        <g className="zhen-rotate-outer">
          <circle cx={250} cy={250} r={240} fill="none" stroke="var(--zhen-line)" strokeWidth={1} />
          <circle cx={250} cy={250} r={224} fill="none" stroke="var(--zhen-line)" strokeWidth={1} />
          {ticks.map((t) => (
            <line
              key={t.angle}
              x1={t.inner.x}
              y1={t.inner.y}
              x2={t.outer.x}
              y2={t.outer.y}
              stroke="var(--zhen-line)"
              strokeWidth={t.major ? 1.6 : 0.8}
            />
          ))}
          {/* 四正方位的朱砂定位点 */}
          {[0, 90, 180, 270].map((a) => {
            const p = polar(a, 240);
            return <circle key={a} cx={p.x} cy={p.y} r={3} fill="var(--zhen-cinnabar)" />;
          })}
        </g>

        {/* 中环八卦：慢速逆转，与外环形成相对运动 */}
        <g className="zhen-rotate-mid">
          <circle cx={250} cy={250} r={130} fill="none" stroke="var(--zhen-line)" strokeWidth={1} />
          <circle cx={250} cy={250} r={168} fill="none" stroke="var(--zhen-line)" strokeWidth={0.6} />
          {EIGHT_TRIGRAMS.map((g) => (
            <GuaMark key={g.name} gua={g} />
          ))}
        </g>

        {/* 内圈阴阳分界线 */}
        <circle cx={250} cy={250} r={104} fill="none" stroke="var(--zhen-line)" strokeWidth={0.8} />

        {/* 太极图 */}
        <g className="zhen-taiji-breathe">
          <circle cx={250} cy={250} r={86} fill="oklch(0.94 0.01 250)" />
          <path d="M250,164 a86,86 0 0,1 0,172 a43,43 0 0,1 0,-86 a43,43 0 0,0 0,-86" fill="var(--zhen-bg-deep)" />
          <circle cx={250} cy={207} r={13} fill="oklch(0.94 0.01 250)" />
          <circle cx={250} cy={293} r={13} fill="var(--zhen-bg-deep)" />
          <circle cx={250} cy={250} r={86} fill="none" stroke="var(--zhen-gold)" strokeWidth={0.8} />
        </g>
      </svg>

      {/* 阵眼标注：当前域，朱砂印 */}
      <div className="pointer-events-none absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-center">
        <div className="zhen-seal mx-auto size-12 text-2xl font-bold">
          {active}
        </div>
      </div>

      <div className="mt-4 flex flex-wrap justify-center gap-1.5 font-mono text-[10px] text-[var(--zhen-ink-dim)]">
        {EIGHT_TRIGRAMS.map((g) => (
          <button
            key={g.name}
            type="button"
            onClick={() => setActive(g.name)}
            className={`border px-2 py-1 transition-colors ${
              active === g.name
                ? "border-[var(--zhen-cinnabar)] text-[var(--zhen-cinnabar-bright)]"
                : "border-[var(--zhen-line)] hover:text-[var(--zhen-ink)]"
            }`}
          >
            {g.name}·{g.domain}
          </button>
        ))}
      </div>
    </div>
  );
}

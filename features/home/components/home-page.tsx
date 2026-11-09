import "../styles.css";
import { SiteLayout } from "@/components/site/layout";
import { ZhenHero } from "./zhen-hero";
import { DomainMatrix, FeaturedGoods, FourDomains, GuideBanner, LifeJournal } from "./zhen-sections";

/** 首页：新中式·数字阵法。仅组装区块，数据与交互由各区块自治。 */
export function HomePage() {
  return (
    <SiteLayout>
      <ZhenHero />
      <FourDomains />
      <FeaturedGoods />
      <DomainMatrix />
      <GuideBanner />
      <LifeJournal />
    </SiteLayout>
  );
}

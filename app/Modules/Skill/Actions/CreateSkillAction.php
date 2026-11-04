<?php

declare(strict_types=1);

namespace App\Modules\Skill\Actions;

use App\Models\User;
use App\Modules\Product\Contracts\SkillProductContract;
use App\Modules\Skill\Enums\SkillStatus;
use App\Modules\Skill\Models\SkillDetail;
use App\Modules\User\Contracts\UserContract;
use RuntimeException;

final class CreateSkillAction
{
    public function __construct(
        private readonly SkillProductContract $products,
        private readonly UserContract $users,
    ) {}

    /**
     * @return array{id: int, status: string}
     */
    public function execute(User $user, array $data): array
    {
        // 自营模式：暂时关闭开发者入驻校验，登录即可创建（考取资质后恢复）
        // if (! $this->users->isDeveloper((int) $user->id)) {
        //     throw new RuntimeException('仅认证开发者可创建技能');
        // }

        $this->contentCheck($data['name'].' '.$data['description']);
        $productId = $this->products->createSkillProduct(
            (int) $user->id,
            $data['name'],
            $data['description'],
            (int) $data['price'],
        );
        SkillDetail::create([
            'product_id' => $productId,
            'status' => SkillStatus::Draft->value,
            'endpoint' => $data['endpoint'],
            'method' => $data['method'],
            'api_key' => $data['api_key'] ?? null,
            'request_template' => $data['request_template'] ?? null,
            'provider_share' => 80,
            'platform_share' => 10,
            'influencer_share' => 5,
            'donation_share' => 5,
        ]);

        return ['id' => $productId, 'status' => SkillStatus::Draft->value];
    }

    private function contentCheck(string $text): void
    {
        $blocked = ['赌博', '色情', '枪支', '毒品', '诈骗', '代考', '洗钱', '博彩', '翻墙', '外挂'];
        foreach ($blocked as $word) {
            if (mb_strpos($text, $word) !== false) {
                throw new RuntimeException('内容含违规词，无法上架');
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Skill\Actions;

use App\Models\User;
use App\Modules\Product\Contracts\SkillProductContract;
use App\Modules\Skill\Models\SkillDetail;
use RuntimeException;

final class CreateSkillAction
{
    public function __construct(
        private readonly SkillProductContract $products,
    ) {}

    /**
     * @return array{id: int, status: string}
     */
    public function execute(User $user, array $data): array
    {
        $this->contentCheck($data['name'].' '.$data['description']);
        $productId = $this->products->createSkillProduct(
            (int) $user->id,
            $data['name'],
            $data['description'],
            (int) $data['price'],
        );
        SkillDetail::create([
            'product_id' => $productId,
            'endpoint' => $data['endpoint'],
            'method' => $data['method'],
            'api_key' => $data['api_key'] ?? null,
            'request_template' => $data['request_template'] ?? null,
            'provider_share' => 80,
            'platform_share' => 10,
            'influencer_share' => 5,
            'donation_share' => 5,
        ]);

        return ['id' => $productId, 'status' => 'pending'];
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

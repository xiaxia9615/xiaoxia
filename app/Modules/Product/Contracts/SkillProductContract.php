<?php

declare(strict_types=1);

namespace App\Modules\Product\Contracts;

interface SkillProductContract
{
    /**
     * 技能商品分页列表（paginator toArray 结构）
     *
     * @return array<string, mixed>
     */
    public function listSkills(): array;

    /**
     * 查询技能商品（含 provider 信息），不存在返回 null
     *
     * @return array<string, mixed>|null
     */
    public function findSkill(int $id): ?array;

    /**
     * 创建技能商品（status=0 待审核），返回商品 ID
     */
    public function createSkillProduct(int $userId, string $name, string $description, int $price): int;

    /**
     * 审核技能商品，商品不存在返回 null
     *
     * @return array{id: int, status: int}|null
     */
    public function auditSkill(int $id, bool $approve, ?string $note): ?array;
}

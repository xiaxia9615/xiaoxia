<?php
declare(strict_types=1);
namespace App\Modules\Skill\Actions;
use App\Modules\Product\Contracts\SkillProductContract;
use RuntimeException;

final class AuditSkillAction
{
    public function __construct(
        private readonly SkillProductContract $products,
    ) {}

    /**
     * @return array{id: int, status: int}
     */
    public function execute(int $id, bool $approve, ?string $note): array
    {
        $result = $this->products->auditSkill($id, $approve, $note);
        if ($result === null) {
            throw new RuntimeException("Skill not found: {$id}");
        }
        return $result;
    }
}

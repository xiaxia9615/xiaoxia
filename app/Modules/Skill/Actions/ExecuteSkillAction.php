<?php
declare(strict_types=1);
namespace App\Modules\Skill\Actions;
use App\Modules\AiProvider\Actions\SelectBestProviderAction;
use App\Modules\AiProvider\Contracts\AiClientInterface;
use App\Modules\Ledger\Events\AiUsageRecorded;
use Illuminate\Support\Facades\Event;
final class ExecuteSkillAction
{
    public function __construct(
        private readonly SelectBestProviderAction $selectBestProvider,
        private readonly AiClientInterface $aiClient,
    ) {}
    /**
     * 智能路由网关：选择最低成本 Provider，发起 AI 调用并记录用量计费
     *
     * @return array<string, mixed>
     */
    public function execute(int $userId, string $prompt): array
    {
        $provider = $this->selectBestProvider->execute();
        $response = $this->aiClient->chat($prompt);
        Event::dispatch(new AiUsageRecorded(
            userId: $userId,
            providerId: (int) $provider['id'],
            cost: $response->cost,
            inputTokens: $response->inputTokens,
            outputTokens: $response->outputTokens,
        ));
        return [
            'provider' => [
                'id'           => (int) $provider['id'],
                'name'         => (string) $provider['name'],
                'display_name' => (string) $provider['display_name'],
            ],
            'content'      => $response->content,
            'input_tokens'  => $response->inputTokens,
            'output_tokens' => $response->outputTokens,
            'cost'         => $response->cost,
        ];
    }
}

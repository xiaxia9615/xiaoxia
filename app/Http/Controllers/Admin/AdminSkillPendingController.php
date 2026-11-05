<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Skill\Actions\AuditSkillAction;
use App\Modules\Skill\Services\AdminSkillService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class AdminSkillPendingController extends Controller
{
    public function __construct(
        private readonly AdminSkillService $skillService,
        private readonly AuditSkillAction $auditSkill,
    ) {}

    public function index(): View
    {
        $skills = $this->skillService->paginatePendingSkills();

        $rows = $skills->getCollection()->map(fn ($s): array => [
            $s->id,
            $s->product_id,
            e((string) ($s->product->name ?? '-')),
            e((string) $s->endpoint),
            e((string) $s->method),
            '<span class="px-2 py-0.5 text-xs rounded-full bg-amber-50 text-amber-700">待审核</span>',
            '<form method="POST" action="'.route('admin.skills.pending.audit', $s->product_id).'" class="inline-flex gap-2">'
                .csrf_field()
                .'<button type="submit" name="action" value="approve" class="px-2 py-1 text-xs rounded bg-emerald-100 text-emerald-700 hover:bg-emerald-200">通过</button>'
                .'<button type="submit" name="action" value="reject" class="px-2 py-1 text-xs rounded bg-red-100 text-red-700 hover:bg-red-200">驳回</button>'
                .'</form>',
        ])->all();

        return view('admin.skills.pending', [
            'skills' => $skills,
            'rows' => $rows,
        ]);
    }

    public function audit(Request $request, int $id): RedirectResponse
    {
        $action = $request->input('action');
        $approve = $action === 'approve';

        try {
            $this->auditSkill->execute($id, $approve, $approve ? null : '管理员驳回');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['audit' => $e->getMessage()]);
        }

        Log::info('admin.audit_skill', [
            'operator' => $request->user()->id,
            'skill_id' => $id,
            'approve' => $approve,
            'ip' => $request->ip(),
        ]);

        return back()->with('status', $approve ? '技能已通过审核' : '技能已驳回');
    }
}

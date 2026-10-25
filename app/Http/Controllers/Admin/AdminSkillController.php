<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Skill\Services\AdminSkillService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class AdminSkillController extends Controller
{
    public function __construct(
        private readonly AdminSkillService $service,
    ) {}

    public function index(): View
    {
        $skills = $this->service->paginateSkills();

        $rows = $skills->getCollection()->map(fn ($s): array => [
            $s->id,
            e((string) ($s->product_name ?? '-')),
            e((string) $s->endpoint),
            e((string) $s->method),
            e((string) $s->pricing_type),
            '<code>' . e($s->masked_api_key) . '</code>',
            $s->calls_count,
            '<a href="' . route('admin.skills.show', $s->id) . '" class="text-indigo-600 hover:text-indigo-800">详情</a>',
        ])->all();

        return view('admin.skills.index', [
            'stats'  => $this->service->stats(),
            'skills' => $skills,
            'rows'   => $rows,
        ]);
    }

    public function calls(Request $request): View
    {
        $calls = $this->service->paginateCalls(30, $request->query('status'));

        $rows = $calls->getCollection()->map(fn ($c): array => [
            e((string) $c->created_at),
            e((string) $c->request_id),
            $c->user_id,
            $c->product_id,
            $c->status_code === 200
                ? '<span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span><span class="text-sm text-slate-600">' . $c->status_code . '</span></span>'
                : '<span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-500"></span><span class="text-sm text-slate-600">' . $c->status_code . '</span></span>',
            $c->duration_ms . 'ms',
            number_format($c->cost / 100, 4),
            '-',
        ])->all();

        return view('admin.skills.calls', [
            'calls'   => $calls,
            'rows'    => $rows,
            'filters' => ['status' => $request->query('status')],
        ]);
    }

    public function show(int $id): View
    {
        $skill = $this->service->getSkill($id);

        if (!$skill) {
            abort(404);
        }

        return view('admin.skills.show', ['skill' => $skill]);
    }
}

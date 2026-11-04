<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\User\Services\DeveloperService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class AdminDeveloperController extends Controller
{
    public function __construct(
        private readonly DeveloperService $developerService,
    ) {}

    public function index(): View
    {
        $developers = $this->developerService->paginatePendingDevelopers();

        $rows = $developers->getCollection()->map(fn ($u): array => [
            $u->id,
            e((string) $u->name),
            e((string) $u->email),
            '<span class="px-2 py-0.5 text-xs rounded-full bg-amber-50 text-amber-700">待审核</span>',
            e((string) ($u->created_at ?? '-')),
            '<form method="POST" action="' . route('admin.developers.audit', $u->id) . '" class="inline-flex gap-2">'
                . csrf_field()
                . '<button type="submit" name="action" value="approve" class="px-2 py-1 text-xs rounded bg-emerald-100 text-emerald-700 hover:bg-emerald-200">通过</button>'
                . '<button type="submit" name="action" value="reject" class="px-2 py-1 text-xs rounded bg-red-100 text-red-700 hover:bg-red-200">驳回</button>'
                . '</form>',
        ])->all();

        return view('admin.developers.index', [
            'developers' => $developers,
            'rows'       => $rows,
        ]);
    }

    public function audit(Request $request, int $id): RedirectResponse
    {
        $action = $request->input('action');
        $approve = $action === 'approve';

        try {
            $this->developerService->audit($id, $approve);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['audit' => $e->getMessage()]);
        }

        Log::info('admin.audit_developer', [
            'operator' => $request->user()->id,
            'target'   => $id,
            'approve'  => $approve,
            'ip'       => $request->ip(),
        ]);

        return back()->with('status', $approve ? '开发者已通过审核' : '开发者申请已驳回');
    }
}

@extends('layouts.admin')

@section('title', '财务中心 - 提现管理')

@section('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">提现管理</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.finance.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">财务中心</a>
                <span class="text-sm text-slate-500">共 {{ $withdrawals->total() }} 条</span>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-700">
            ⚠️ 操作提示：请先点击『核对通过』完成信息核对，线下完成转账后再点击『确认已转账』。『确认已转账』会实际扣除用户冻结款，请务必确认转账已完成后再操作，避免误操作。
        </div>

        <div class="bg-white rounded-lg border border-slate-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-slate-500">ID</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-500">用户</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-500">邮箱</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-500">金额（元）</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-500">状态</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-500">审核时间</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-500">申请时间</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-500">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($withdrawals as $w)
                        <tr>
                            <td class="px-4 py-3 text-slate-500">{{ $w->id }}</td>
                            <td class="px-4 py-3 text-slate-900">{{ $w->user->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $w->user->email ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-900">¥ {{ number_format($w->amount / 1000, 2) }}</td>
                            <td class="px-4 py-3">
                                @if ($w->status === \App\Models\Withdrawal::STATUS_PENDING)
                                    <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">待核对</span>
                                @elseif($w->status === \App\Models\Withdrawal::STATUS_APPROVED)
                                    <span class="inline-flex rounded-full bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700">已核对/待转账</span>
                                @elseif($w->status === \App\Models\Withdrawal::STATUS_PAID)
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">已转账</span>
                                @else
                                    <span class="inline-flex rounded-full bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700">已驳回</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ $w->audited_at ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $w->created_at ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @if ($w->status === \App\Models\Withdrawal::STATUS_PENDING)
                                    <form method="POST" action="{{ route('admin.finance.withdrawals.approve', $w->id) }}" onsubmit="return confirm('确认核对通过提现单 #{{ $w->id }}（¥ {{ number_format($w->amount / 1000, 2) }}）？此操作不会扣款。');">
                                        @csrf
                                        <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-800">核对通过</button>
                                    </form>
                                @elseif($w->status === \App\Models\Withdrawal::STATUS_APPROVED)
                                    <form method="POST" action="{{ route('admin.finance.withdrawals.mark-paid', $w->id) }}" onsubmit="return confirm('⚠️ 确认已线下完成转账？点击后将实际扣除用户冻结款 ¥ {{ number_format($w->amount / 1000, 2) }}，请谨慎操作！');">
                                        @csrf
                                        <button type="submit" class="text-sm text-rose-600 hover:text-rose-800">确认已转账</button>
                                    </form>
                                @else
                                    <span class="text-sm text-slate-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-400">暂无提现记录</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pagination :paginator="$withdrawals" />
    </div>
@endsection

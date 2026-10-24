@extends('layouts.admin')

@section('title', 'Ledger')

@section('content')
    <div class="space-y-6">
        {{-- 统计卡片 --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-stat-card title="账户数" :value="$stats['accounts']" />
            <x-stat-card title="流水数" :value="$stats['entries']" />
            <x-stat-card title="总余额（毫）" :value="$stats['total_balance']" />
        </div>

        {{-- 账户列表 --}}
        <div>
            <h3 class="text-sm font-semibold text-slate-900 mb-3">账户列表</h3>
            <x-table
                :headers="['ID', 'Owner 类型', 'Owner ID', '账户类型', '币种', '余额', '流水数', '操作']"
                :rows="$rows"
                empty="暂无账户" />
            {{ $accounts->links() }}
        </div>
    </div>
@endsection

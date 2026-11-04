@extends('layouts.admin')

@section('title', '充值流水')

@section('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">充值流水</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.wallets.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">钱包列表</a>
                <span class="text-sm text-slate-500">共 {{ $transactions->total() }} 条</span>
            </div>
        </div>

        <x-table :headers="['ID', '交易号', '用户', '邮箱', '金额（分）', '类型', '时间']" :rows="$rows" empty="暂无充值记录" />
        <x-pagination :paginator="$transactions" />
    </div>
@endsection

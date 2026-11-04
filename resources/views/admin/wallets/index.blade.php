@extends('layouts.admin')

@section('title', '钱包管理')

@section('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">钱包管理</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.wallets.recharges') }}" class="text-sm text-indigo-600 hover:text-indigo-800">充值流水</a>
                <span class="text-sm text-slate-500">共 {{ $wallets->total() }} 个钱包</span>
            </div>
        </div>

        <x-table :headers="['ID', '用户ID', '姓名', '邮箱', '余额（分）', '冻结（分）', '更新时间']" :rows="$rows" empty="暂无钱包数据" />
        <x-pagination :paginator="$wallets" />
    </div>
@endsection

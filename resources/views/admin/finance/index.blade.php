@extends('layouts.admin')

@section('title', '财务中心')

@section('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">财务中心</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.finance.recharges') }}" class="text-sm text-indigo-600 hover:text-indigo-800">充值流水</a>
                <a href="{{ route('admin.finance.withdrawals') }}" class="text-sm text-indigo-600 hover:text-indigo-800">提现管理</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            {{-- 平台总收入（累计充值，单位：元） --}}
            <div class="bg-white rounded-lg border border-slate-200 p-6">
                <p class="text-sm font-medium text-slate-500">平台总收入</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">¥ {{ number_format($totalRecharge / 1000, 2) }}</p>
                <p class="mt-1 text-xs text-slate-400">累计充值金额</p>
            </div>

            {{-- 今日新增充值（单位：元） --}}
            <div class="bg-white rounded-lg border border-slate-200 p-6">
                <p class="text-sm font-medium text-slate-500">今日新增充值</p>
                <p class="mt-2 text-3xl font-bold text-emerald-600">¥ {{ number_format($todayRecharge / 1000, 2) }}</p>
                <p class="mt-1 text-xs text-slate-400">今日充值金额</p>
            </div>

            {{-- 平台净资产（LedgerContract::balance，单位：元） --}}
            <div class="bg-white rounded-lg border border-slate-200 p-6">
                <p class="text-sm font-medium text-slate-500">平台净资产</p>
                <p class="mt-2 text-3xl font-bold text-indigo-600">¥ {{ number_format($netAsset / 1000, 2) }}</p>
                <p class="mt-1 text-xs text-slate-400">账本口径净资产</p>
            </div>

            {{-- 待处理提现数量 --}}
            <div class="bg-white rounded-lg border border-slate-200 p-6">
                <p class="text-sm font-medium text-slate-500">待处理提现</p>
                <p class="mt-2 text-3xl font-bold {{ $pendingWithdrawals > 0 ? 'text-amber-600' : 'text-slate-900' }}">{{ number_format($pendingWithdrawals) }}</p>
                <p class="mt-1 text-xs text-slate-400">笔待审核提现申请</p>
            </div>
        </div>
    </div>
@endsection

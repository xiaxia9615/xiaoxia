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

            {{-- 平台净资产（单位：元） --}}
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

        {{-- 今日数据简报 --}}
        <div class="bg-white rounded-lg border border-slate-200 p-6">
            <h3 class="text-base font-semibold text-slate-900 mb-4">今日数据简报</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-lg bg-emerald-50 border border-emerald-100 p-4">
                    <p class="text-sm text-emerald-600">今日新增充值</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-700">¥ {{ number_format($todayRecharge / 1000, 2) }}</p>
                </div>
                <div class="rounded-lg bg-amber-50 border border-amber-100 p-4">
                    <p class="text-sm text-amber-600">今日新增提现</p>
                    <p class="mt-1 text-2xl font-bold text-amber-700">¥ {{ number_format($todayWithdrawal / 1000, 2) }}</p>
                </div>
                <div class="rounded-lg bg-sky-50 border border-sky-100 p-4">
                    <p class="text-sm text-sky-600">今日订单分账总额</p>
                    <p class="mt-1 text-2xl font-bold text-sky-700">¥ {{ number_format($todayOrderSettlement / 1000, 2) }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection

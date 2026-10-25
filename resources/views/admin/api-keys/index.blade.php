@extends('layouts.admin')
@section('title', 'API Keys')
@section('content')
    <div class="space-y-6">
        @if(session('success'))
            <div class="bg-emerald-50 text-emerald-800 px-4 py-3 rounded">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 text-red-800 px-4 py-3 rounded">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-xs text-slate-500">总数</p>
                <p class="text-2xl font-semibold text-slate-900">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-xs text-slate-500">活跃</p>
                <p class="text-2xl font-semibold text-slate-900">{{ $stats['active'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-xs text-slate-500">已吊销</p>
                <p class="text-2xl font-semibold text-slate-900">{{ $stats['revoked'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-xs text-slate-500">已过期</p>
                <p class="text-2xl font-semibold text-slate-900">{{ $stats['expired'] }}</p>
            </div>
        </div>

        <x-table
            :headers="['ID', '用户', '名称', 'Key 前缀', '状态', '最后使用', '过期时间', '操作']"
            :rows="$rows"
            empty="暂无 API Key" />

        {{ $keys->links() }}
    </div>
@endsection

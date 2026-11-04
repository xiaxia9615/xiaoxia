@extends('layouts.admin')

@section('title', '开发者审核')

@section('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">开发者审核</h2>
            <span class="text-sm text-slate-500">共 {{ $developers->total() }} 条待审核</span>
        </div>

        @if (session('status'))
            <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-md text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="p-3 bg-red-50 border border-red-200 rounded-md">
                @foreach ($errors->all() as $error)
                    <p class="text-sm text-red-600">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <x-table :headers="['ID', '姓名', '邮箱', '状态', '申请时间', '操作']" :rows="$rows" empty="暂无待审核的开发者申请" />
        <x-pagination :paginator="$developers" />
    </div>
@endsection

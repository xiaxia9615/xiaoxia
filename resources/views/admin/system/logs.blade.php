@extends('layouts.admin')
@section('title', '应用日志')
@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">应用日志（最近 100 行）</h3>
            @if(Route::has('admin.dashboard'))
                <a href="{{ route('admin.dashboard') }}" class="text-sm text-indigo-600 hover:text-indigo-800">返回仪表盘</a>
            @endif
        </div>
        <div class="bg-slate-900 text-slate-100 p-4 rounded font-mono text-xs overflow-x-auto">
            @forelse($lines as $line)
                <div class="whitespace-pre-wrap">{{ $line }}</div>
            @empty
                <div class="text-slate-400">无日志</div>
            @endforelse
        </div>
    </div>
@endsection

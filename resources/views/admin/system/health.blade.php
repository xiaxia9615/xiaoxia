@extends('layouts.admin')
@section('title', '系统健康')
@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">系统健康</h3>
            @if(Route::has('admin.dashboard'))
                <a href="{{ route('admin.dashboard') }}" class="text-sm text-indigo-600 hover:text-indigo-800">返回仪表盘</a>
            @endif
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="text-sm text-slate-500 mb-2">MySQL</div>
                <x-status-badge :status="$health['db'] ? 'ok' : 'down'" />
            </div>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="text-sm text-slate-500 mb-2">Redis</div>
                <x-status-badge :status="$health['cache'] ? 'ok' : 'down'" />
            </div>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="text-sm text-slate-500 mb-2">Nginx</div>
                <x-status-badge :status="$health['nginx'] ? 'ok' : 'down'" />
            </div>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="text-sm text-slate-500 mb-2">PHP-FPM</div>
                <x-status-badge :status="$health['php-fpm'] ? 'ok' : 'down'" />
            </div>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="text-sm text-slate-500 mb-2">Queue Worker</div>
                <x-status-badge :status="$health['worker'] ? 'ok' : 'down'" />
            </div>
        </div>
    </div>
@endsection

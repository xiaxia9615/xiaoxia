@extends('layouts.admin')

@section('title', '仪表盘')

@section('content')
    <div class="space-y-6">
        {{-- 顶部统计卡片 --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card title="用户数" :value="$counts['users']" />
            <x-stat-card title="今日调用" :value="$counts['calls_today']" :subtitle="'累计 ' . $counts['calls_total']" />
            <x-stat-card title="活跃技能" :value="$counts['skills']" :subtitle="'产品 ' . $counts['products']" />
            <x-stat-card title="API Keys" :value="$counts['api_keys']" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- 系统健康 --}}
            <div class="bg-white shadow-sm rounded-lg p-5">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">系统健康</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600">数据库</span>
                        <x-status-badge :status="$health['db'] ? 'ok' : 'down'" />
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600">缓存</span>
                        <x-status-badge :status="$health['cache'] ? 'ok' : 'down'" />
                    </div>
                </div>
            </div>

            {{-- 服务进程 --}}
            <div class="bg-white shadow-sm rounded-lg p-5">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">服务进程</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600">Nginx</span>
                        <x-status-badge :status="$services['nginx'] ? 'ok' : 'down'" />
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600">PHP-FPM</span>
                        <x-status-badge :status="$services['php-fpm'] ? 'ok' : 'down'" />
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600">Queue Worker</span>
                        <x-status-badge :status="$services['worker'] ? 'ok' : 'down'" />
                    </div>
                </div>
            </div>
        </div>

        {{-- 最近活动 --}}
        <div>
            <h3 class="text-sm font-semibold text-slate-900 mb-3">最近活动</h3>
            <x-table
                :headers="['时间', '用户', '产品', '状态码', '耗时']"
                :rows="$activityRows"
                empty="暂无 API 调用记录" />
        </div>
    </div>
@endsection

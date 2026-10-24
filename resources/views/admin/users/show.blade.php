@extends('layouts.admin')

@section('title', '用户详情')

@section('content')
    <div class="space-y-4 max-w-3xl">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">用户详情</h2>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-indigo-600 hover:underline">返回列表</a>
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

        {{-- 基本信息 --}}
        <div class="bg-white shadow-sm rounded-lg p-5">
            <h3 class="text-sm font-semibold text-slate-900 mb-4">基本信息</h3>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-slate-500">ID</dt><dd class="mt-1 text-slate-900">{{ $user->id }}</dd></div>
                <div><dt class="text-slate-500">姓名</dt><dd class="mt-1 text-slate-900">{{ $user->name }}</dd></div>
                <div><dt class="text-slate-500">邮箱</dt><dd class="mt-1 text-slate-900">{{ $user->email }}</dd></div>
                <div><dt class="text-slate-500">状态</dt><dd class="mt-1">@if ($user->is_active)<x-status-badge status="ok" label="启用" />@else<x-status-badge status="down" label="禁用" />@endif</dd></div>
                <div><dt class="text-slate-500">注册时间</dt><dd class="mt-1 text-slate-900">{{ $user->created_at?->format('Y-m-d H:i') }}</dd></div>
                <div><dt class="text-slate-500">当前角色</dt><dd class="mt-1 text-slate-900">{{ $user->roles->pluck('name')->implode(', ') ?: '无' }}</dd></div>
            </dl>
        </div>

        {{-- 角色切换 --}}
        <div class="bg-white shadow-sm rounded-lg p-5">
            <h3 class="text-sm font-semibold text-slate-900 mb-4">角色切换</h3>
            <form method="POST" action="{{ route('admin.users.updateRole', $user->id) }}" class="flex items-center gap-3">
                @csrf
                @method('PATCH')
                <select name="role" class="rounded-md border-slate-300 shadow-sm text-sm">
                    <option value="admin" @selected($user->hasRole('admin'))>admin</option>
                    <option value="user" @selected($user->hasRole('user') && ! $user->hasRole('admin'))>user</option>
                </select>
                <button type="submit" class="px-3 py-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-md">更新角色</button>
            </form>
        </div>

        {{-- 启用/禁用 --}}
        <div class="bg-white shadow-sm rounded-lg p-5">
            <h3 class="text-sm font-semibold text-slate-900 mb-4">账号状态</h3>
            <form method="POST" action="{{ route('admin.users.toggle', $user->id) }}">
                @csrf
                <button type="submit" class="px-3 py-1.5 text-sm rounded-md {{ $user->is_active ? 'bg-red-500 hover:bg-red-600 text-white' : 'bg-emerald-500 hover:bg-emerald-600 text-white' }}">
                    {{ $user->is_active ? '禁用账号' : '启用账号' }}
                </button>
            </form>
        </div>

        {{-- 关联数据 --}}
        <div class="bg-white shadow-sm rounded-lg p-5">
            <h3 class="text-sm font-semibold text-slate-900 mb-4">关联数据</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="border border-slate-200 rounded-md p-4">
                    <p class="text-sm text-slate-500">API Keys</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">{{ $stats['api_keys_count'] }}</p>
                </div>
                <div class="border border-slate-200 rounded-md p-4">
                    <p class="text-sm text-slate-500">API 调用次数</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">{{ $stats['api_calls_count'] }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection

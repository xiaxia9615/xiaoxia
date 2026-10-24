@extends('layouts.admin')

@section('title', '用户管理')

@section('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">用户管理</h2>
            <span class="text-sm text-slate-500">共 {{ $users->total() }} 个用户</span>
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

        @php
            $rows = $users->map(function ($u) {
                $roles = $u->roles->pluck('name')->map(function ($r) {
                    return '<span class="px-2 py-0.5 text-xs rounded-full bg-indigo-50 text-indigo-700">' . e($r) . '</span>';
                })->implode(' ');

                $status = $u->is_active
                    ? '<span class="px-2 py-0.5 text-xs rounded-full bg-emerald-50 text-emerald-700">启用</span>'
                    : '<span class="px-2 py-0.5 text-xs rounded-full bg-red-50 text-red-700">禁用</span>';

                $link = '<a href="' . route('admin.users.show', $u->id) . '" class="text-indigo-600 hover:underline">查看</a>';

                return [$u->id, e($u->name), e($u->email), $roles, $status, $link];
            })->all();
        @endphp

        <x-table :headers="['ID', '姓名', '邮箱', '角色', '状态', '操作']" :rows="$rows" />

        <x-pagination :paginator="$users" />
    </div>
@endsection

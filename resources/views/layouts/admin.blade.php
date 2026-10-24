<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - @yield('title', '仪表盘')</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-50 min-h-screen">
    <div class="flex min-h-screen">
        <aside class="w-56 bg-white border-r border-slate-200 flex-shrink-0">
            <div class="h-16 flex items-center px-6 border-b border-slate-200">
                <span class="text-lg font-bold text-indigo-600">管理后台</span>
            </div>
            <nav class="p-3 space-y-1">
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
                    仪表盘
                </a>
                <a href="{{ route('admin.users.index') }}"
                   class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.users.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
                    用户管理
                </a>
                <a href="#"
                   class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.ledger.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
                    Ledger
                </a>
                <a href="#"
                   class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.ai-providers.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
                    AI 供应商
                </a>
                <a href="#"
                   class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.skills.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
                    技能
                </a>
                <a href="#"
                   class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.api-keys.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
                    API Keys
                </a>
                <a href="#"
                   class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.system.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
                    系统
                </a>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col">
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-end px-6">
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-slate-600">{{ auth()->user()?->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="text-sm text-slate-500 hover:text-red-600">登出</button>
                    </form>
                </div>
            </header>

            <main class="flex-1 p-6">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>

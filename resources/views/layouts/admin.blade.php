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
                @php
                    $nav = [
                        ['route' => 'admin.dashboard',       'label' => '仪表盘'],
                        ['route' => 'admin.users.index',     'label' => '用户'],
                        ['route' => 'admin.ledger.index',    'label' => 'Ledger'],
                        ['route' => 'admin.ai-providers.index', 'label' => 'AI 供应商'],
                        ['route' => 'admin.skills.index',    'label' => '技能'],
                        ['route' => 'admin.api-keys.index',  'label' => 'API Keys'],
                        ['route' => 'admin.system.health',   'label' => '系统'],
                    ];
                @endphp
                @foreach($nav as $item)
                    @if(Route::has($item['route']))
                        <a href="{{ route($item['route']) }}"
                           class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs($item['route'].'*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span class="flex items-center px-3 py-2 rounded-md text-sm font-medium text-slate-400 cursor-not-allowed"
                              title="待实现">{{ $item['label'] }}</span>
                    @endif
                @endforeach
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

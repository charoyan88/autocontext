<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Auto-Context Docs</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full antialiased font-sans text-slate-900">
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100">
        <header class="sticky top-0 z-20 border-b border-slate-200/70 bg-white/70 backdrop-blur">
            <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-8">
                <a href="{{ url('/') }}" class="flex items-center gap-2">
                    <svg class="h-7 w-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span class="whitespace-nowrap font-bold text-lg tracking-tight text-slate-900">Auto-Context</span>
                </a>
                <div class="flex items-center gap-x-6">
                    <a href="{{ route('docs') }}"
                        class="text-sm font-semibold leading-6 text-indigo-600">Docs</a>
                    @auth
                        <a href="{{ url('/dashboard') }}"
                            class="text-sm font-semibold leading-6 text-slate-900 hover:text-indigo-600 transition">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}"
                            class="text-sm font-semibold leading-6 text-slate-900 hover:text-indigo-600 transition">Log in</a>
                        <a href="{{ route('register') }}"
                            class="rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">Get
                            started</a>
                    @endauth
                </div>
            </nav>
        </header>

        <main class="mx-auto max-w-7xl px-6 py-16 lg:px-8">
            <div class="grid grid-cols-1 gap-10 lg:grid-cols-12">
                <aside class="lg:col-span-3">
                    <div class="rounded-2xl border border-slate-200 bg-white/80 p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">On this page</p>
                        <nav class="mt-4 space-y-2 text-sm">
                            <a class="block text-slate-700 hover:text-indigo-600" href="#quick-start">Quick start</a>
                            <a class="block text-slate-700 hover:text-indigo-600" href="#auth">Authentication</a>
                            <a class="block text-slate-700 hover:text-indigo-600" href="#deployments">Deployments</a>
                            <a class="block text-slate-700 hover:text-indigo-600" href="#logs">Log ingestion</a>
                            <a class="block text-slate-700 hover:text-indigo-600" href="#levels">Log levels</a>
                            <a class="block text-slate-700 hover:text-indigo-600" href="#downstream">Downstream</a>
                        </nav>
                    </div>
                    <div class="mt-6 rounded-2xl bg-indigo-600 p-5 text-white shadow-sm">
                        <p class="text-sm font-semibold">Need a demo key?</p>
                        <p class="mt-2 text-xs text-emerald-50">Create a project and generate an API key in the dashboard.</p>
                    </div>
                </aside>

                <div class="lg:col-span-9">
                    <div class="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
                        <p class="text-sm font-semibold text-indigo-600">Auto-Context Docs</p>
                        <h1 class="mt-2 text-4xl font-bold tracking-tight text-slate-900">Implementation guide</h1>
                        <p class="mt-4 text-lg text-slate-600">
                            Enrich logs with deploy metadata, filter noise, and forward high-signal events to your downstream tools.
                        </p>
                        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="rounded-xl bg-slate-50 p-4">
                                <p class="text-xs text-slate-500">Step 01</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">Create API key</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <p class="text-xs text-slate-500">Step 02</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">Report deployments</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <p class="text-xs text-slate-500">Step 03</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">Ship logs</p>
                            </div>
                        </div>
                    </div>

                    <section id="quick-start" class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-2xl font-semibold text-slate-900">Quick start</h2>
                        <ol class="mt-4 space-y-3 text-slate-600">
                            <li>1) Create a project and generate an API key in the dashboard.</li>
                            <li>2) Report deployments via the API (commit SHA or build tag in <code class="text-sm">version</code>).</li>
                            <li>3) Send logs in batches to <code class="text-sm">/api/logs</code>.</li>
                        </ol>
                    </section>

                    <section id="auth" class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-2xl font-semibold text-slate-900">Authentication</h2>
                        <p class="mt-3 text-slate-600">All API requests require an <code class="text-sm">X-Api-Key</code> header.</p>
                    </section>

                    <section id="deployments" class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-2xl font-semibold text-slate-900">Create a deployment</h2>
                        <p class="mt-3 text-slate-600">
                            Use this endpoint from CI/CD to register a deployment. The <code class="text-sm">version</code>
                            field is where you place your commit SHA, tag, or build number.
                        </p>
                        <div class="mt-4 rounded-xl bg-slate-950 p-4 text-sm text-slate-100 shadow-inner overflow-x-auto">
                            <pre class="font-mono leading-6 text-slate-100"><code>curl -X POST http://localhost:8080/api/deployments \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: YOUR_API_KEY" \
  -d '{
    "version": "9f2c1",
    "environment": "production",
    "region": "us-east-1",
    "started_at": "2026-01-25T12:00:00Z"
  }'</code></pre>
                        </div>
                    </section>

                    <section id="logs" class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-2xl font-semibold text-slate-900">Send logs</h2>
                        <p class="mt-3 text-slate-600">
                            Send a batch using the <code class="text-sm">events</code> array. Required fields:
                            <code class="text-sm">timestamp</code>, <code class="text-sm">level</code>, <code class="text-sm">message</code>.
                        </p>
                        <div class="mt-4 rounded-xl bg-slate-950 p-4 text-sm text-slate-100 shadow-inner overflow-x-auto">
                            <pre class="font-mono leading-6 text-slate-100"><code>curl -X POST http://localhost:8080/api/logs \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: YOUR_API_KEY" \
  -d '{
    "events": [
      {
        "timestamp": "2026-01-25T12:05:00Z",
        "level": "ERROR",
        "message": "timeout in /api/orders",
        "context": { "order_id": 123 },
        "service": "checkout",
        "region": "us-east-1"
      }
    ]
  }'</code></pre>
                        </div>
                    </section>

                    <section id="levels" class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-2xl font-semibold text-slate-900">Supported log levels</h2>
                        <p class="mt-3 text-slate-600">
                            DEBUG, TRACE, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY
                        </p>
                    </section>

                    <section id="downstream" class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-2xl font-semibold text-slate-900">Downstream forwarding</h2>
                        <p class="mt-3 text-slate-600">
                            Configure downstream endpoints per project in the dashboard. Auto-Context forwards only
                            enriched, high-signal events.
                        </p>
                        <p class="mt-3 text-slate-600">
                            Downstream endpoints are a trusted-admin feature. HTTP, Sentry, and file targets should be
                            configured only for destinations you control.
                        </p>
                    </section>
                </div>
            </div>
        </main>
    </div>
</body>

</html>

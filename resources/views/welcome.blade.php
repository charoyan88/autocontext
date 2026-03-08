<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Auto-Context | Smart Log Observability</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full antialiased font-sans text-gray-900">
    <div class="bg-white">
        <!-- Header -->
        <header class="absolute inset-x-0 top-0 z-50">
            <nav class="flex items-center justify-between p-6 lg:px-8" aria-label="Global">
                <div class="flex lg:flex-1">
                    <a href="#" class="-m-1.5 p-1.5 flex items-center gap-2">
                        <span class="sr-only">Auto-Context</span>
                        <x-application-logo class="h-8 w-8" />
                        <span class="whitespace-nowrap font-bold text-xl tracking-tight text-gray-900">Auto-Context</span>
                    </a>
                </div>
                <div class="flex flex-1 justify-end gap-x-6">
                    @auth
                        <a href="{{ route('docs') }}"
                            class="text-sm font-semibold leading-6 text-gray-900 hover:text-indigo-600 transition">Docs</a>
                        <a href="#how"
                            class="text-sm font-semibold leading-6 text-gray-900 hover:text-indigo-600 transition">How it works</a>
                        <a href="{{ url('/dashboard') }}"
                            class="text-sm font-semibold leading-6 text-gray-900 hover:text-indigo-600 transition">Go to
                            Dashboard <span aria-hidden="true">&rarr;</span></a>
                    @else
                        <a href="{{ route('docs') }}"
                            class="text-sm font-semibold leading-6 text-gray-900 hover:text-indigo-600 transition">Docs</a>
                        <a href="#how"
                            class="text-sm font-semibold leading-6 text-gray-900 hover:text-indigo-600 transition">How it works</a>
                        <a href="{{ route('login') }}"
                            class="text-sm font-semibold leading-6 text-gray-900 hover:text-indigo-600 transition">Log
                            in</a>
                        <a href="{{ route('register') }}"
                            class="rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition">Get
                            started</a>
                    @endauth
                </div>
            </nav>
        </header>

        <!-- Hero Section -->
        <div class="relative isolate pt-14">
            <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80"
                aria-hidden="true">
                <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-30 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]"
                    style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)">
                </div>
            </div>

            <div class="py-24 sm:py-32 lg:pb-40">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-6xl">
                            Context-Aware Observability Between Deployments and Incidents
                        </h1>
                        <p class="mt-6 text-lg leading-8 text-gray-600">
                            Stop guessing which deployment caused that error.
                            Auto-Context sits between your app and observability tools,
                            enriching logs with deploy metadata, filtering noise,
                            and aggregating errors so teams can ship faster with confidence.
                        </p>
                        <p class="mt-4 text-sm text-gray-500">
                            Forward curated events to file, Sentry-style, or generic HTTP downstreams.
                        </p>
                        <div class="mt-10 flex items-center justify-center gap-x-6">
                            @auth
                                <a href="{{ url('/dashboard') }}"
                                    class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition">Go
                                    to Dashboard</a>
                            @else
                                <a href="{{ route('register') }}"
                                    class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition">Get
                                    started for free</a>
                                <a href="#features" class="text-sm font-semibold leading-6 text-gray-900">Learn more <span
                                        aria-hidden="true">→</span></a>
                            @endauth
                        </div>
                        <p class="mt-4 text-xs text-gray-500">Local-first MVP for evaluation and self-hosted experimentation.</p>
                        <div class="mt-8 flex flex-wrap items-center justify-center gap-3 text-sm text-gray-600">
                            <span class="rounded-full bg-gray-100 px-3 py-1">DevOps</span>
                            <span class="rounded-full bg-gray-100 px-3 py-1">SRE</span>
                            <span class="rounded-full bg-gray-100 px-3 py-1">Backend Leads</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="absolute inset-x-0 top-[calc(100%-13rem)] -z-10 transform-gpu overflow-hidden blur-3xl sm:top-[calc(100%-30rem)]"
                aria-hidden="true">
                <div class="relative left-[calc(50%+3rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-30 sm:left-[calc(50%+36rem)] sm:w-[72.1875rem]"
                    style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)">
                </div>
            </div>
        </div>

        <!-- How It Works -->
        <section id="how" class="bg-gray-50 py-20 sm:py-24">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-base font-semibold leading-7 text-indigo-600">How it works</h2>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                            From raw logs to deploy-level clarity
                        </p>
                </div>
                <div class="mx-auto mt-12 grid max-w-5xl grid-cols-1 gap-6 sm:grid-cols-3">
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <p class="text-sm font-semibold text-indigo-600">01</p>
                        <h3 class="mt-2 text-lg font-semibold text-gray-900">Ingest + enrich</h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Attach commit SHA, deployment ID, region, and service metadata automatically.
                        </p>
                    </div>
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <p class="text-sm font-semibold text-indigo-600">02</p>
                        <h3 class="mt-2 text-lg font-semibold text-gray-900">Filter + dedupe</h3>
                        <p class="mt-2 text-sm text-gray-600">Remove health checks and repeated errors so real incidents stand out.</p>
                    </div>
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <p class="text-sm font-semibold text-indigo-600">03</p>
                        <h3 class="mt-2 text-lg font-semibold text-gray-900">Forward downstream</h3>
                        <p class="mt-2 text-sm text-gray-600">Send only high-signal events to file, Sentry-style, or your HTTP endpoint.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Proof Points -->
        <section class="bg-white py-20 sm:py-24">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div class="rounded-2xl border border-gray-200 p-6">
                        <p class="text-3xl font-bold text-gray-900">Up to 50%</p>
                        <p class="mt-2 text-sm text-gray-600">less log noise after smart filtering</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 p-6">
                        <p class="text-3xl font-bold text-gray-900">2x</p>
                        <p class="mt-2 text-sm text-gray-600">faster incident triage with deploy context</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 p-6">
                        <p class="text-3xl font-bold text-gray-900">Lower noise</p>
                        <p class="mt-2 text-sm text-gray-600">by forwarding fewer high-signal events downstream</p>
                    </div>
                </div>
                <p class="mt-6 text-xs text-gray-500 text-center">Illustrative flow for the current MVP feature set.</p>
            </div>
        </section>

        <!-- Mini Demo -->
        <section class="bg-gray-50 py-20 sm:py-24">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-base font-semibold leading-7 text-indigo-600">Mini demo</h2>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Before vs after</p>
                </div>
                <div class="mx-auto mt-12 grid max-w-5xl grid-cols-1 gap-6 sm:grid-cols-2">
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Before</h3>
                        <p class="mt-2 text-sm text-gray-600">Noisy logs with no deploy context or ownership.</p>
                        <div class="mt-4 space-y-2 text-sm">
                            <div class="rounded-lg bg-gray-100 px-3 py-2">ERROR: timeout in /api/orders</div>
                            <div class="rounded-lg bg-gray-100 px-3 py-2">ERROR: timeout in /api/orders</div>
                            <div class="rounded-lg bg-gray-100 px-3 py-2">WARN: /healthcheck 503</div>
                            <div class="rounded-lg bg-gray-100 px-3 py-2">ERROR: timeout in /api/orders</div>
                        </div>
                    </div>
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">After</h3>
                        <p class="mt-2 text-sm text-gray-600">Grouped errors clearly tied to a single deployment.</p>
                        <div class="mt-4 space-y-2 text-sm">
                            <div class="rounded-lg bg-indigo-50 px-3 py-2 text-indigo-900">
                                ERROR: timeout in /api/orders
                                <span class="block text-xs text-indigo-700">Deploy v1.14.2 · commit 9f2c1</span>
                            </div>
                            <div class="rounded-lg bg-gray-100 px-3 py-2">Noise filtered: health checks (128)</div>
                            <div class="rounded-lg bg-gray-100 px-3 py-2">Duplicates removed: 73</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <div id="features" class="bg-white py-24 sm:py-32">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mx-auto max-w-2xl lg:text-center">
                    <h2 class="text-base font-semibold leading-7 text-indigo-600">Deploy with Confidence</h2>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Everything you need to
                        debug faster</p>
                    <p class="mt-6 text-lg leading-8 text-gray-600">Traditional logging tools are noisy and
                        disconnected. We fix that by linking every log line to the exact code version and deployment
                        that produced it.</p>
                </div>
                <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-4xl">
                    <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-10 lg:max-w-none lg:grid-cols-2 lg:gap-y-16">
                        <div class="relative pl-16">
                            <dt class="text-base font-semibold leading-7 text-gray-900">
                                <div
                                    class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                                    </svg>
                                </div>
                                Deployment Linking
                            </dt>
                            <dd class="mt-2 text-base leading-7 text-gray-600">Every error is tagged with the commit
                                SHA, deployment ID, and version. Know exactly what changed.</dd>
                        </div>
                        <div class="relative pl-16">
                            <dt class="text-base font-semibold leading-7 text-gray-900">
                                <div
                                    class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                    </svg>
                                </div>
                                Smart Filtering
                            </dt>
                            <dd class="mt-2 text-base leading-7 text-gray-600">Automatically strip health check noise
                                and duplicates so on-call focuses on real incidents.</dd>
                        </div>
                        <div class="relative pl-16">
                            <dt class="text-base font-semibold leading-7 text-gray-900">
                                <div
                                    class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                Downstream Forwarding
                            </dt>
                            <dd class="mt-2 text-base leading-7 text-gray-600">Process logs here, then forward the
                                important ones to file, Sentry-style, or your custom HTTP endpoints.</dd>
                        </div>
                        <div class="relative pl-16">
                            <dt class="text-base font-semibold leading-7 text-gray-900">
                                <div
                                    class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                                    </svg>
                                </div>
                                Fast Integration
                            </dt>
                            <dd class="mt-2 text-base leading-7 text-gray-600">One HTTP endpoint for all services. Start forwarding in minutes.</dd>
                        </div>
                        <div class="relative pl-16">
                            <dt class="text-base font-semibold leading-7 text-gray-900">
                                <div
                                    class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                    </svg>
                                </div>
                                Project Access Controls
                            </dt>
                            <dd class="mt-2 text-base leading-7 text-gray-600">Per-project API keys and admin-only
                                management routes for the current MVP workflow.</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Integrations -->
        <section class="bg-gray-50 py-20 sm:py-24">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-base font-semibold leading-7 text-indigo-600">Integrations</h2>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Fits your toolchain</p>
                    <p class="mt-6 text-lg leading-8 text-gray-600">Forward high-signal events to your existing stack without re-wiring pipelines.</p>
                    <p class="mt-2 text-sm text-gray-500">Auto-Context forwards events after enrichment and filtering — your tools stay unchanged.</p>
                </div>
                <div class="mx-auto mt-12 grid max-w-4xl grid-cols-2 gap-4 text-center text-sm text-gray-600 sm:grid-cols-4">
                    <div class="rounded-xl bg-white py-4 ring-1 ring-gray-200">Sentry</div>
                    <div class="rounded-xl bg-white py-4 ring-1 ring-gray-200">HTTP Webhook</div>
                    <div class="rounded-xl bg-white py-4 ring-1 ring-gray-200">File</div>
                    <div class="rounded-xl bg-white py-4 ring-1 ring-gray-200">Dashboard</div>
                </div>
            </div>
        </section>

        <!-- Use Cases -->
        <section class="bg-white py-20 sm:py-24">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-base font-semibold leading-7 text-indigo-600">Use cases</h2>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Made for teams shipping fast</p>
                </div>
                <div class="mx-auto mt-12 grid max-w-5xl grid-cols-1 gap-6 sm:grid-cols-3">
                    <div class="rounded-2xl border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900">Post-deploy regressions</h3>
                        <p class="mt-2 text-sm text-gray-600">Pinpoint which release broke production in minutes.</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900">On-call focus</h3>
                        <p class="mt-2 text-sm text-gray-600">Reduce alert fatigue by dropping duplicates and noise.</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900">Release accountability</h3>
                        <p class="mt-2 text-sm text-gray-600">Maintain a clean timeline of deploys and incidents.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final CTA -->
        <section class="bg-gray-900 py-20">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                        See exactly which deployment caused the spike
                    </h2>
                    <p class="mt-4 text-lg text-gray-300">
                        Create a project, send a few events, and get deploy-level grouping in minutes.
                    </p>
                    <div class="mt-8 flex items-center justify-center gap-x-6">
                        @auth
                            <a href="{{ url('/dashboard') }}"
                                class="rounded-md bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-100 transition">Open dashboard</a>
                        @else
                            <a href="{{ route('register') }}"
                                class="rounded-md bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-100 transition">Get started</a>
                            <a href="#features" class="text-sm font-semibold text-white">See features <span aria-hidden="true">→</span></a>
                        @endauth
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-gray-900 mx-auto mt-32 px-6 lg:px-8 py-12">
            <div class="mx-auto max-w-7xl md:flex md:items-center md:justify-between">
                <div class="flex justify-center space-x-6 md:order-2">
                    <a href="https://github.com/charoyan88/autocontext" class="text-gray-400 hover:text-gray-300 text-xs">
                        GitHub repository
                    </a>
                </div>
                <div class="mt-8 md:order-1 md:mt-0">
                    <p class="text-center text-xs leading-5 text-gray-500">&copy; 2026 Auto-Context. All rights
                        reserved.</p>
                </div>
            </div>
        </footer>
    </div>
</body>

</html>

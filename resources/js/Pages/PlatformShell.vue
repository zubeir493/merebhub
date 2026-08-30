<script setup>
import { Head, usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <Head title="Platform shell" />

    <main class="mx-auto flex min-h-screen max-w-5xl flex-col justify-center gap-6 px-6 py-16">
        <p class="text-sm font-bold uppercase tracking-[0.2em] text-teal-700">MerebHub platform</p>
        <h1 class="text-4xl font-extrabold tracking-tight text-zinc-950">Vue and Inertia foundation ready.</h1>
        <p class="max-w-2xl text-lg leading-8 text-zinc-600">
            This shell is the entry point for the storefront and account experiences. The server shares a safe user summary and a request correlation ID.
        </p>
        <dl class="grid gap-4 text-sm sm:grid-cols-2">
            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4">
                <dt class="font-bold text-zinc-500">Signed-in user</dt>
                <dd class="mt-1 font-semibold text-zinc-900">{{ page.props.auth?.user?.name ?? 'Guest' }}</dd>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4">
                <dt class="font-bold text-zinc-500">Correlation ID</dt>
                <dd class="mt-1 break-all font-mono text-xs text-zinc-900">{{ page.props.correlationId }}</dd>
            </div>
        </dl>
    </main>
</template>

@extends('layouts.storefront')

@section('content')
    <section data-public-page-header class="public-page-header">
        <div class="mx-auto max-w-[1400px] px-5 py-14 sm:py-16 lg:px-8 lg:py-20">
            <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-teal-700">For makers</p>
            <div class="mt-5 grid gap-8 lg:grid-cols-12 lg:items-end">
                <div class="lg:col-span-7">
                    <h1 class="public-page-title max-w-[11ch]">Build once. Sell with confidence.</h1>
                </div>
                <div class="lg:col-span-5 lg:pb-1">
                    <p class="public-lede">A practical path from first application to tested listing, licensing, and support on MerebHub.</p>
                    <div class="mt-7 flex flex-wrap gap-3">
                        <a href="#apply" class="btn-dark group">Apply to list your software <x-heroicon-o-arrow-down class="size-4 transition-transform group-hover:translate-y-0.5" /></a>
                        <a href="{{ route('developers.index') }}" class="editorial-link">Back to makers <x-heroicon-o-arrow-left class="size-4" /></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="guide" class="border-b border-zinc-200 bg-white">
        <div class="mx-auto grid max-w-[1400px] gap-12 px-5 py-12 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-20 lg:px-8 lg:py-16">
            <aside class="self-start lg:sticky lg:top-28" aria-label="Documentation navigation">
                <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-zinc-500">On this page</p>
                <nav class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm font-bold lg:grid lg:gap-1" aria-label="Guide sections">
                    <a href="#process" class="py-1 text-teal-800 hover:text-teal-600">The process</a>
                    <a href="#brief" class="py-1 text-zinc-600 hover:text-zinc-950">What to send us</a>
                    <a href="#connection" class="py-1 text-zinc-600 hover:text-zinc-950">Connect your app</a>
                    <a href="#activation" class="py-1 text-zinc-600 hover:text-zinc-950">Activation choices</a>
                    <a href="#offline" class="py-1 text-zinc-600 hover:text-zinc-950">Offline activation</a>
                    <a href="#submission" class="py-1 text-zinc-600 hover:text-zinc-950">Submit for review</a>
                    <a href="#apply" class="py-1 text-zinc-600 hover:text-zinc-950">Apply to sell</a>
                </nav>
                <div class="mt-8 hidden border-t border-zinc-200 pt-5 text-xs leading-5 text-zinc-500 lg:block">
                    <p class="font-bold text-zinc-700">Read time</p>
                    <p class="mt-1">About 8 minutes</p>
                </div>
            </aside>

            <article class="min-w-0 max-w-3xl">
                <section id="process" class="scroll-mt-28">
                    <p class="text-sm font-extrabold text-teal-700">01 / Start here</p>
                    <h2 class="mt-3 public-section-title">A clear hand-off from idea to listing.</h2>
                    <p class="mt-5 text-base leading-8 text-zinc-600">MerebHub handles the marketplace setup, checkout, and license delivery. You own the product experience. This guide explains what we need from you, what we send back, and the small integration your app should complete before final review.</p>

                    <ol class="mt-10 border-t border-zinc-200">
                        <li class="grid gap-4 border-b border-zinc-200 py-7 sm:grid-cols-[2.5rem_minmax(0,1fr)]">
                            <span class="text-sm font-extrabold tabular-nums text-teal-700">01</span>
                            <div>
                                <h3 class="text-lg font-extrabold text-zinc-950">Apply with the product context</h3>
                                <p class="mt-2 text-sm leading-7 text-zinc-600">Tell us what the app does, who it is for, which platforms it supports, and what stage it is in. A working demo or product link helps, but an honest in-development application is welcome.</p>
                            </div>
                        </li>
                        <li class="grid gap-4 border-b border-zinc-200 py-7 sm:grid-cols-[2.5rem_minmax(0,1fr)]">
                            <span class="text-sm font-extrabold tabular-nums text-teal-700">02</span>
                            <div>
                                <h3 class="text-lg font-extrabold text-zinc-950">Answer the commercial questions</h3>
                                <p class="mt-2 text-sm leading-7 text-zinc-600">After the first review, we will ask about price, billing period, license type, device limits, editions or variants, supported operating systems, and any features that should be included in each edition.</p>
                            </div>
                        </li>
                        <li class="grid gap-4 border-b border-zinc-200 py-7 sm:grid-cols-[2.5rem_minmax(0,1fr)]">
                            <span class="text-sm font-extrabold tabular-nums text-teal-700">03</span>
                            <div>
                                <h3 class="text-lg font-extrabold text-zinc-950">Receive your integration details</h3>
                                <p class="mt-2 text-sm leading-7 text-zinc-600">We add the matching product and policy records to our licensing database, then send you the identifiers, API base URL, policy behavior, test credentials, and any platform-specific notes your implementation needs.</p>
                            </div>
                        </li>
                        <li class="grid gap-4 border-b border-zinc-200 py-7 sm:grid-cols-[2.5rem_minmax(0,1fr)]">
                            <span class="text-sm font-extrabold tabular-nums text-teal-700">04</span>
                            <div>
                                <h3 class="text-lg font-extrabold text-zinc-950">Integrate the identifiers in your app</h3>
                                <p class="mt-2 text-sm leading-7 text-zinc-600">Use the product and policy identifiers we provide when your app validates a license or requests an activation. The interface is yours: a settings screen, first-run prompt, account page, or another flow that fits the product.</p>
                            </div>
                        </li>
                        <li class="grid gap-4 border-b border-zinc-200 py-7 sm:grid-cols-[2.5rem_minmax(0,1fr)]">
                            <span class="text-sm font-extrabold tabular-nums text-teal-700">05</span>
                            <div>
                                <h3 class="text-lg font-extrabold text-zinc-950">Upload the finished build</h3>
                                <p class="mt-2 text-sm leading-7 text-zinc-600">Send us the release-ready installers, checksums, release notes, and a short test path. We test installation, launch, licensing, updates, and the purchase-to-download journey before approving or declining the listing.</p>
                            </div>
                        </li>
                    </ol>
                </section>

                <section id="brief" class="scroll-mt-28 border-t border-zinc-200 pt-14">
                    <p class="text-sm font-extrabold text-teal-700">02 / Before we configure your product</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-[-0.035em] text-zinc-950 sm:text-4xl">Bring the decisions, not just the binary.</h2>
                    <p class="mt-5 text-base leading-8 text-zinc-600">The more precise the commercial brief, the faster we can prepare the right records and the fewer changes your app will need later.</p>

                    <dl class="mt-8 divide-y divide-zinc-200 border-y border-zinc-200">
                        <div class="grid gap-2 py-5 sm:grid-cols-[11rem_minmax(0,1fr)]">
                            <dt class="text-sm font-extrabold text-zinc-950">Offer structure</dt>
                            <dd class="text-sm leading-7 text-zinc-600">Product name, editions or variants, platform builds, and which features belong to each one.</dd>
                        </div>
                        <div class="grid gap-2 py-5 sm:grid-cols-[11rem_minmax(0,1fr)]">
                            <dt class="text-sm font-extrabold text-zinc-950">Commercial model</dt>
                            <dd class="text-sm leading-7 text-zinc-600">Price in Br, one-time or recurring billing, trial or grace period, and whether a customer can renew or upgrade.</dd>
                        </div>
                        <div class="grid gap-2 py-5 sm:grid-cols-[11rem_minmax(0,1fr)]">
                            <dt class="text-sm font-extrabold text-zinc-950">Activation model</dt>
                            <dd class="text-sm leading-7 text-zinc-600">Simple key validation, a device limit, named machines, or an offline workflow. If you are unsure, describe the customer scenario and we will recommend a policy.</dd>
                        </div>
                        <div class="grid gap-2 py-5 sm:grid-cols-[11rem_minmax(0,1fr)]">
                            <dt class="text-sm font-extrabold text-zinc-950">Support promise</dt>
                            <dd class="text-sm leading-7 text-zinc-600">Supported operating systems, minimum versions, expected response time, and how customers should report a licensing problem.</dd>
                        </div>
                    </dl>

                    <div class="mt-8 border-l-2 border-teal-300 pl-5">
                        <p class="text-sm font-bold leading-7 text-zinc-700">Important: identifiers are environment-specific.</p>
                        <p class="mt-1 text-sm leading-7 text-zinc-600">Use the exact values from your MerebHub integration email. Do not copy identifiers from a demo, another product, or a local test environment into a production build.</p>
                    </div>
                </section>

                <section id="connection" class="scroll-mt-28 border-t border-zinc-200 pt-14">
                    <p class="text-sm font-extrabold text-teal-700">03 / Connect your app</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-[-0.035em] text-zinc-950 sm:text-4xl">Keep the online check small and deliberate.</h2>
                    <p class="mt-5 text-base leading-8 text-zinc-600">The public validation endpoint is designed to be called from your software. It does not require a private administrator token. MerebHub will give you the account identifier, API base URL, product identifier, and policy identifier for your product.</p>

                    <div class="mt-8 overflow-hidden rounded-md border border-zinc-200 bg-zinc-950">
                        <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                            <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-zinc-400">Validate a key</span>
                            <span class="text-xs font-bold text-teal-300">POST</span>
                        </div>
                        <pre class="overflow-x-auto p-5 text-xs leading-6 text-zinc-200"><code>https://&lt;api-host&gt;/v1/accounts/&lt;account-id&gt;/licenses/actions/validate-key

{
  "meta": {
    "key": "&lt;customer-license-key&gt;",
    "scope": {
      "product": "&lt;product-id&gt;",
      "policy": "&lt;policy-id&gt;",
      "fingerprint": "&lt;optional-device-fingerprint&gt;"
    }
  }
}</code></pre>
                    </div>

                    <div class="mt-8 space-y-5 text-sm leading-7 text-zinc-600">
                        <p><strong class="text-zinc-900">Treat the response as a decision.</strong> Continue only when the response says the license is valid. Keep the machine-readable result code for your support logs, and show a calm, useful message when a key is expired, suspended, out of seats, or tied to a different product.</p>
                        <p><strong class="text-zinc-900">Scope the check.</strong> Always include the product identifier. Include the policy identifier when we provide one, and include a fingerprint when the policy is tied to a device. This prevents a valid key for another product from being accepted by your app.</p>
                        <p><strong class="text-zinc-900">Plan for network failure.</strong> A timeout is not the same as an invalid license. Offer retry, keep the last successful state only for the grace period we agree on, and never ship a private admin, environment, or product token inside the client.</p>
                    </div>
                </section>

                <section id="activation" class="scroll-mt-28 border-t border-zinc-200 pt-14">
                    <p class="text-sm font-extrabold text-teal-700">04 / Choose an activation experience</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-[-0.035em] text-zinc-950 sm:text-4xl">Make the licensing moment feel native to your product.</h2>
                    <p class="mt-5 text-base leading-8 text-zinc-600">There is no required visual pattern. We care that the flow is understandable, reversible, and tested. These are the implementation paths we support.</p>

                    <div class="mt-8 divide-y divide-zinc-200 border-y border-zinc-200">
                        <div class="py-6">
                            <h3 class="text-lg font-extrabold text-zinc-950">Key-only validation</h3>
                            <p class="mt-2 text-sm leading-7 text-zinc-600">Best for a simple product that only needs to know whether a purchased key is active. Ask for the key, validate it against your product, and store it using the platform’s secure credential storage.</p>
                        </div>
                        <div class="py-6">
                            <h3 class="text-lg font-extrabold text-zinc-950">Device activation</h3>
                            <p class="mt-2 text-sm leading-7 text-zinc-600">Use this when a plan has a machine limit. Generate a stable, privacy-conscious fingerprint, validate it first, then create a machine activation with the license. Do not use a raw MAC address if a hashed or app-generated identifier is more stable for your platform.</p>
                        </div>
                        <div class="py-6">
                            <h3 class="text-lg font-extrabold text-zinc-950">Server-side validation</h3>
                            <p class="mt-2 text-sm leading-7 text-zinc-600">For SaaS products, keep the license check on your server and pass only an entitlement state to the browser or client. Store all private credentials server-side and rotate them if they are ever exposed.</p>
                        </div>
                    </div>

                    <div class="mt-8 overflow-hidden rounded-md border border-zinc-200 bg-zinc-950">
                        <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                            <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-zinc-400">Optional device activation</span>
                            <span class="text-xs font-bold text-teal-300">POST</span>
                        </div>
                        <pre class="overflow-x-auto p-5 text-xs leading-6 text-zinc-200"><code>https://&lt;api-host&gt;/v1/accounts/&lt;account-id&gt;/machines

Authorization: License &lt;customer-license-key&gt;

{
  "data": {
    "type": "machines",
    "attributes": {
      "fingerprint": "&lt;stable-hashed-fingerprint&gt;",
      "name": "&lt;human-readable-device-name&gt;",
      "platform": "&lt;platform&gt;"
    },
    "relationships": {
      "license": {
        "data": { "type": "licenses", "id": "&lt;license-id-from-validation&gt;" }
      }
    }
  }
}</code></pre>
                    </div>
                    <p class="mt-4 text-xs leading-6 text-zinc-500">Your policy may use a different authentication strategy. We will confirm the supported activation request in your integration hand-off.</p>
                </section>

                <section id="offline" class="scroll-mt-28 border-t border-zinc-200 pt-14">
                    <p class="text-sm font-extrabold text-teal-700">05 / Offline activation</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-[-0.035em] text-zinc-950 sm:text-4xl">For customers who cannot keep the app online.</h2>
                    <p class="mt-5 text-base leading-8 text-zinc-600">Offline licensing uses a signed license file instead of a live validation request. The file can carry a tamper-evident snapshot of the license or activated machine, an expiry, and the product relationships your app needs to make a local decision.</p>

                    <ol class="mt-8 border-t border-zinc-200">
                        <li class="grid gap-4 border-b border-zinc-200 py-6 sm:grid-cols-[2.5rem_minmax(0,1fr)]">
                            <span class="text-sm font-extrabold tabular-nums text-teal-700">01</span>
                            <div>
                                <h3 class="text-lg font-extrabold text-zinc-950">Create a request on the disconnected device</h3>
                                <p class="mt-2 text-sm leading-7 text-zinc-600">Your app collects the entered license key, your product and policy identifiers, a stable device fingerprint, and a fresh nonce. Use the request-file helper we provide; do not invent your own file format or include unrelated personal data. Save the result as a <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs font-bold text-zinc-800">.lreq</code> file.</p>
                            </div>
                        </li>
                        <li class="grid gap-4 border-b border-zinc-200 py-6 sm:grid-cols-[2.5rem_minmax(0,1fr)]">
                            <span class="text-sm font-extrabold tabular-nums text-teal-700">02</span>
                            <div>
                                <h3 class="text-lg font-extrabold text-zinc-950">Let the customer transfer the request</h3>
                                <p class="mt-2 text-sm leading-7 text-zinc-600">The customer signs in to MerebHub on an internet-connected device and uploads the request from Purchased Licenses. We check that the request contains a license they own before asking the licensing service to create the response.</p>
                            </div>
                        </li>
                        <li class="grid gap-4 border-b border-zinc-200 py-6 sm:grid-cols-[2.5rem_minmax(0,1fr)]">
                            <span class="text-sm font-extrabold tabular-nums text-teal-700">03</span>
                            <div>
                                <h3 class="text-lg font-extrabold text-zinc-950">Install the returned license file</h3>
                                <p class="mt-2 text-sm leading-7 text-zinc-600">MerebHub returns a signed <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs font-bold text-zinc-800">.lic</code> file. The customer transfers it back to the disconnected device. Your app should import it through a clear “Activate offline” action, not ask the customer to edit or rename its contents.</p>
                            </div>
                        </li>
                        <li class="grid gap-4 border-b border-zinc-200 py-6 sm:grid-cols-[2.5rem_minmax(0,1fr)]">
                            <span class="text-sm font-extrabold tabular-nums text-teal-700">04</span>
                            <div>
                                <h3 class="text-lg font-extrabold text-zinc-950">Verify locally before enabling the product</h3>
                                <p class="mt-2 text-sm leading-7 text-zinc-600">Verify the file’s signature with the public verification material supplied in your integration pack. Then check the product ID, policy ID, device fingerprint, status, entitlements, and expiry. Reject altered, expired, mismatched, or unreadable files with a next-step message.</p>
                            </div>
                        </li>
                        <li class="grid gap-4 border-b border-zinc-200 py-6 sm:grid-cols-[2.5rem_minmax(0,1fr)]">
                            <span class="text-sm font-extrabold tabular-nums text-teal-700">05</span>
                            <div>
                                <h3 class="text-lg font-extrabold text-zinc-950">Respect the file lifetime</h3>
                                <p class="mt-2 text-sm leading-7 text-zinc-600">Offline files can expire so changes such as suspension, renewal, or a device-limit update can eventually reach an installation. Show the customer when a refresh is needed and provide the request-file action again instead of silently locking them out.</p>
                            </div>
                        </li>
                    </ol>

                    <div class="mt-8 border-l-2 border-teal-300 pl-5">
                        <p class="text-sm font-bold leading-7 text-zinc-700">Offline does not mean unsigned.</p>
                        <p class="mt-1 text-sm leading-7 text-zinc-600">Your application must verify the signature locally. Never accept a file because it merely has a <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs font-bold text-zinc-800">.lic</code> extension, and never put a private signing or administrator secret in the app.</p>
                    </div>
                </section>

                <section id="submission" class="scroll-mt-28 border-t border-zinc-200 pt-14">
                    <p class="text-sm font-extrabold text-teal-700">06 / Submit for review</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-[-0.035em] text-zinc-950 sm:text-4xl">Give us a build we can actually test.</h2>
                    <p class="mt-5 text-base leading-8 text-zinc-600">When your integration is complete, send a release candidate rather than a screenshot. We test the same customer journey a buyer will experience.</p>

                    <ul class="mt-8 divide-y divide-zinc-200 border-y border-zinc-200 text-sm leading-7 text-zinc-600">
                        <li class="flex gap-3 py-4"><x-heroicon-o-check class="mt-1 size-4 shrink-0 text-teal-700" /><span>Installers or packages for every platform and variant you listed.</span></li>
                        <li class="flex gap-3 py-4"><x-heroicon-o-check class="mt-1 size-4 shrink-0 text-teal-700" /><span>Version number, release notes, file size, checksum, and minimum system requirements.</span></li>
                        <li class="flex gap-3 py-4"><x-heroicon-o-check class="mt-1 size-4 shrink-0 text-teal-700" /><span>A test key or test account, plus the exact steps for online and offline activation.</span></li>
                        <li class="flex gap-3 py-4"><x-heroicon-o-check class="mt-1 size-4 shrink-0 text-teal-700" /><span>A clean uninstall path and a way to deactivate or move a device when your policy supports it.</span></li>
                        <li class="flex gap-3 py-4"><x-heroicon-o-check class="mt-1 size-4 shrink-0 text-teal-700" /><span>A support contact for licensing failures during review.</span></li>
                    </ul>

                    <p class="mt-7 text-sm leading-7 text-zinc-600">We may approve the submission, request changes, or decline it when the build is not ready for customers. If we request changes, reply with a new build and a short list of what changed so the second review stays focused.</p>
                </section>

                <section id="apply" class="scroll-mt-28 border-t border-zinc-200 pt-14">
                    <div class="border-y border-zinc-200 bg-zinc-50 px-6 py-8 sm:px-8">
                        <p class="text-sm font-extrabold text-teal-700">Ready when you are</p>
                        <h2 class="mt-3 text-3xl font-extrabold tracking-[-0.035em] text-zinc-950 sm:text-4xl">Start with the product, not the paperwork.</h2>
                        <p class="mt-4 max-w-2xl text-sm leading-7 text-zinc-600">Send the essentials now. We will follow up with the commercial and licensing questions before asking for a finished build.</p>
                        <a href="{{ route('developers.index') }}#apply" class="btn-primary mt-6">Open developer application <x-heroicon-o-arrow-up-right class="size-4" /></a>
                    </div>
                </section>
            </article>
        </div>
    </section>
@endsection

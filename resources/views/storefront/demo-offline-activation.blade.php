<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MerebHub Clock Demo · Offline activation</title>
    <style>
        :root { color-scheme: dark; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #0f172a; color: #e2e8f0; }
        main { width: min(760px, calc(100% - 32px)); padding: 34px; background: #172033; border: 1px solid #334155; border-radius: 22px; box-shadow: 0 24px 80px #02061799; }
        .eyebrow { color: #93c5fd; font-weight: 800; letter-spacing: .08em; font-size: 12px; }
        h1 { margin: 8px 0; font-size: clamp(28px, 5vw, 42px); }
        p { color: #94a3b8; line-height: 1.6; }
        label { display: block; margin-top: 22px; margin-bottom: 8px; font-weight: 700; }
        input, textarea { box-sizing: border-box; width: 100%; border: 1px solid #475569; border-radius: 10px; background: #0f172a; color: #e2e8f0; padding: 12px; font: inherit; }
        textarea { min-height: 180px; font-family: ui-monospace, SFMono-Regular, monospace; font-size: 12px; }
        button { margin-top: 18px; border: 0; border-radius: 10px; padding: 12px 18px; background: #2563eb; color: white; font-weight: 800; cursor: pointer; }
        .success { margin-top: 24px; padding: 16px; border: 1px solid #14b8a6; border-radius: 12px; background: #042f2e; }
        .error { margin-top: 18px; padding: 14px; border: 1px solid #f87171; border-radius: 12px; background: #450a0a; color: #fecaca; }
        code { color: #bfdbfe; word-break: break-all; }
    </style>
</head>
<body>
<main>
    <div class="eyebrow">MEREBHUB / CLOCK DEMO</div>
    <h1>Offline activation</h1>
    <p>Upload this page's returned machine file to the disconnected Clock Demo computer. The request is restricted to Demo Product and Demo Policy and to one device fingerprint.</p>

    @if ($error)
        <div class="error">{{ $error }}</div>
    @endif

    @if ($machineFile)
        <div class="success">
            <strong>Machine file ready.</strong>
            <p>Save this as <code>machine.lic</code>, transfer it to the disconnected computer, and activate it there with the original license key.</p>
            <textarea id="machine-file" readonly>{{ $machineFile }}</textarea>
            <button type="button" onclick="downloadMachineFile()">Download machine.lic</button>
        </div>
    @else
        <form method="POST" action="{{ route('offline-activation.generate') }}">
            @csrf
            <label for="request">QR request</label>
            <textarea id="request" name="request" required placeholder="The QR code should open this page with a request already filled.">{{ request('request') }}</textarea>
            @error('request') <div class="error">{{ $message }}</div> @enderror
            <button type="submit">Validate with Keygen and issue machine file</button>
        </form>
    @endif
</main>
<script>
function downloadMachineFile() {
    const content = document.getElementById('machine-file').value;
    const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'machine.lic';
    link.click();
    URL.revokeObjectURL(link.href);
}
</script>
</body>
</html>

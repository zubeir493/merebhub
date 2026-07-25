<x-mail::message>
# Your purchase is ready

Thanks for purchasing **{{ $license->product->name }}** from MerebHub.

Your license key:

<x-mail::panel>
{{ $license->license_key }}
</x-mail::panel>

@if ($downloadUrl)
<x-mail::button :url="$downloadUrl">
Download the latest build
</x-mail::button>
@endif

The download link expires in seven days. Your license remains available in your MerebHub account.

Thanks,<br>
MerebHub
</x-mail::message>

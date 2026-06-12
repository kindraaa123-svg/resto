<x-mail::message>
# Hello!

You are receiving this email because you requested a login access code for your account.

Your security code is:

<x-mail::panel>
<h1 style="text-align: center; font-size: 32px; letter-spacing: 0.5em; margin: 0;">{{ $otp }}</h1>
</x-mail::panel>

This code will expire in 5 minutes. If you did not request this code, no further action is required.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

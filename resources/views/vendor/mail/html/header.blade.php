@props(['url'])
<tr>
<td class="header" style="padding: 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
<td align="left" style="background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 55%, #4338ca 100%); background-color: #5b21b6; padding: 28px 32px 26px; border-radius: 20px 20px 0 0;">
<a href="{{ $url }}" style="text-decoration: none; display: block;">
<span style="font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 800; letter-spacing: -0.02em; color: #ffffff; line-height: 1.2;">
@if (trim($slot) !== '')
{!! $slot !!}
@else
{{ config('app.name') }}
@endif
</span>
<span style="display: block; margin-top: 8px; font-family: ui-sans-serif, system-ui, 'Segoe UI', Roboto, sans-serif; font-size: 13px; line-height: 1.5; color: rgba(255, 255, 255, 0.9);">
{{ __('Gestão clínica no seu consultório') }}
</span>
</a>
</td>
</tr>
</table>
</td>
</tr>

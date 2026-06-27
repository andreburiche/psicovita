@props(['siteContext' => null])

@php
    $siteContext ??= \App\Models\SiteSetting::publicContext();
@endphp

<x-site-social-float :social-links="$siteContext['social_links']" />
<x-site-whatsapp-float :whatsapp="$siteContext['whatsapp']" />
<x-site-back-to-top />

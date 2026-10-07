@php
    $whatsappDisplay = \App\Models\SiteSetting::displayedWhatsappNumber();
@endphp
<x-app-layout
    title="Contact KAYISE IT | Mbombela"
    description="Connect with KAYISE IT in Mbombela. Call +27 87 702 2625, {{ $whatsappDisplay ? 'WhatsApp '.$whatsappDisplay : 'WhatsApp' }}, or email info@kayiseit.co.za."
    keywords="KAYISE IT contact, Mbombela, Nelspruit, IT support, WhatsApp"
>
    @php
        $ref = request('ref');
        $refLabel = $ref ? \Illuminate\Support\Str::of($ref)->replace('-', ' ')->title() : null;
    @endphp

    <x-page-header
        title="Contact Us"
        subtitle="Get in Touch"
        description="Partner with KAYISE IT for practical, future-focused technology solutions and responsive support."
        hero-id="contact-hero-new"
        background-image="images/banner/contact.png"
        height="h-96" />

    <section id="contact-info" class="bg-white">
        <div class="container mx-auto max-w-screen-xl ki-page">
        @if (session('success'))
            <div class="rounded border border-green-300 bg-green-50 px-4 py-3 text-green-800" role="status">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded border border-red-300 bg-red-50 px-4 py-3 text-red-800" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            <div class="ki-stack">
                <div class="ki-card">
                    <p class="ki-kicker ki-kicker-left">Contact Us</p>
                    <h2 class="ki-card-title">Get in touch</h2>
                    <p class="ki-card-body">Send us an email, give us a call, or message us on WhatsApp for advice on IT strategies that help grow your organisation.</p>

                    <ul class="ki-stack-tight">
                        <li>
                            <a href="tel:+27877022625" class="ki-cluster text-sm text-slate-700 hover:text-[#183ea4]">
                                <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center border border-slate-200 text-[#183ea4]" aria-hidden="true">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a11.285 11.285 0 01-6.244-6.244c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L18.3 2.922a1.064 1.064 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                                    </svg>
                                </span>
                                <span>+27 87 702 2625</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('whatsapp') }}" target="_blank" rel="noopener noreferrer" class="ki-cluster text-sm text-slate-700 hover:text-[#183ea4]">
                                <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center border border-slate-200 text-[#25D366]" aria-hidden="true">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M19.05 4.94A9.9 9.9 0 0012 2a9.93 9.93 0 00-8.63 14.84L2 22l5.31-1.39A9.93 9.93 0 1019.05 4.94zm-7.05 15.37a8.28 8.28 0 01-4.22-1.16l-.3-.18-3.15.82.84-3.07-.2-.31A8.28 8.28 0 1112 20.31zm4.54-6.19c-.25-.13-1.47-.72-1.7-.8-.23-.08-.39-.13-.56.12-.16.25-.64.8-.78.97-.14.16-.28.19-.53.06-.25-.13-1.04-.38-1.98-1.2-.74-.66-1.24-1.46-1.38-1.71-.14-.25-.02-.38.11-.5.11-.11.25-.28.38-.42.13-.14.17-.25.25-.41.08-.16.04-.31-.02-.44-.06-.13-.56-1.35-.77-1.84-.2-.48-.4-.42-.56-.43l-.48-.01c-.16 0-.42.06-.64.31-.22.25-.84.82-.84 2 0 1.18.86 2.31.98 2.47.13.16 1.7 2.6 4.12 3.65.58.25 1.03.4 1.38.51.58.18 1.1.15 1.52.09.46-.07 1.47-.6 1.68-1.17.21-.57.21-1.06.15-1.17-.07-.11-.23-.18-.48-.31z"/>
                                    </svg>
                                </span>
                                <span>WhatsApp{{ $whatsappDisplay ? ' '.$whatsappDisplay : '' }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="mailto:info@kayiseit.co.za" class="ki-cluster text-sm text-slate-700 hover:text-[#183ea4]">
                                <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center border border-slate-200 text-[#183ea4]" aria-hidden="true">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                                    </svg>
                                </span>
                                <span>info@kayiseit.co.za</span>
                            </a>
                        </li>
                        <li>
                            <a href="https://maps.app.goo.gl/PANTYMHfVkWBoKSp7" target="_blank" rel="noopener" class="ki-cluster text-sm text-slate-700 hover:text-[#183ea4]">
                                <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center border border-slate-200 text-[#183ea4]" aria-hidden="true">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                                    </svg>
                                </span>
                                <span>Suite 2, 2nd Floor, Nelbro Building, 39B Brown Street, Mbombela</span>
                            </a>
                        </li>
                    </ul>

                    <div class="mt-6">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Social</p>
                        <div class="ki-cluster flex-wrap mt-3 text-sm">
                            <a href="https://www.facebook.com/KAYISEIT?mibextid=ZbWKwL" target="_blank" rel="noopener" class="hover:text-[#183ea4]">Facebook</a>
                            <a href="https://instagram.com/kayiseit?igshid=ZDdkNTZiNTM=" target="_blank" rel="noopener" class="hover:text-[#183ea4]">Instagram</a>
                            <a href="https://www.linkedin.com/company/kayise-it/" target="_blank" rel="noopener" class="hover:text-[#183ea4]">LinkedIn</a>
                            <a href="https://www.youtube.com/channel/UCrAixDqFR92LBqC7OBF3Eqw" target="_blank" rel="noopener" class="hover:text-[#183ea4]">YouTube</a>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden border border-slate-200" style="height: 320px;">
                    <iframe
                        title="KAYISE IT office on Google Maps"
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3601.9765050913793!2d30.97354507517308!3d-25.472459977534736!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1ee84a10007ca0ff%3A0xf2ea30366dcc9afa!2s39%20Brown%20St%2C%20Mbombela%2C%201201!5e0!3m2!1sen!2sza!4v1683621767634!5m2!1sen!2sza"
                        style="width: 100%; height: 100%; border: 0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>

            <article id="contact-form" class="ki-stack">
                @if ($refLabel)
                    <p class="text-sm text-slate-700">
                        Enquiry about: <span class="font-semibold text-slate-900">{{ $refLabel }}</span>
                    </p>
                @endif
                @include('components.contact-form')
            </article>
        </div>
        </div>
    </section>
</x-app-layout>

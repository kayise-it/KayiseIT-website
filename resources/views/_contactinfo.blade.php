<section class="bg-slate-100">
    <div class="container py-5 px-4 md:px-8 max-w-screen-xl mx-auto">
        <div class="mb-6 text-center">
            <p class="text-sm text-slate-700">KAYISE IT delivers practical ICT services, digital solutions, and skills development support for organizations across South Africa.</p>
            <p class="text-sm text-slate-700 mt-2">We welcome partnerships with schools, businesses, and government institutions seeking reliable technology collaboration.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 justify-center gap-4">
            
            <a href="mailto:info@kayiseit.co.za" style="text-decoration: none; color: black" class=" border-none rounded-md bg-slate-100">
                <div class="flex justify-center p-2">
                    <div class="h-32 w-32 rounded-full bg-kg-700 flex items-center justify-center"><img class="w-12" src="{{ asset('images/icons/em.png') }}"></div>
                </div>
                <div class="">
                    <h2 class="mb-2 text-lg font-bold smalltxt flex justify-center">Email Address</h2>
                    <p class="text-sm flex justify-center">info@kayiseit.co.za</p>
                </div>
            </a>

            <div class="border-none rounded-md bg-slate-100">
                <div class="flex justify-center p-2">
                    <div class="h-32 w-32 rounded-full bg-kg-700 flex items-center justify-center"><img class="w-12" src="{{ asset('images/icons/ph.png') }}" alt=""></div>
                </div>
                <div>
                    <h2 class="mb-2 text-lg font-bold smalltxt flex justify-center">Phone &amp; WhatsApp</h2>
                    <a href="tel:+27877022625" class="text-sm flex justify-center text-black hover:underline">+27 87 702 2625</a>
                    @php $whatsappDisplay = \App\Models\SiteSetting::displayedWhatsappNumber(); @endphp
                    <a href="{{ route('whatsapp') }}" target="_blank" rel="noopener noreferrer" class="text-sm flex justify-center text-black hover:underline">WhatsApp{{ $whatsappDisplay ? ' '.$whatsappDisplay : '' }}</a>
                </div>
            </div>

            <a href="https://maps.app.goo.gl/PANTYMHfVkWBoKSp7" style="text-decoration: none; color: black" class="border-none rounded-md bg-slate-100">
                <div class="flex justify-center p-2">
                    <div class="h-32 w-32 rounded-full bg-kg-700 flex items-center justify-center"><img class="w-12" src="{{ asset('images/icons/ad.png') }}"></div>
                </div>
                <div class="">
                    <h2 class="mb-2 text-lg font-bold smalltxt flex justify-center">Address</h2>
                    <p class="text-sm flex justify-center">office 2, 2nd Floor, 39b Brown str,Nelbro Building, Nelspruit, 1201</p>
                </div>
            </a>

        </div>
        <div class="mt-6 text-center">
            <p class="text-sm font-semibold text-slate-700 mb-2">Connect with us on social media</p>
            <div class="flex flex-wrap justify-center gap-3 text-sm">
                <a href="https://www.facebook.com/KAYISEIT?mibextid=ZbWKwL" target="_blank" rel="noopener" class="text-blue-700 hover:underline">Facebook</a>
                <a href="https://instagram.com/kayiseit?igshid=ZDdkNTZiNTM=" target="_blank" rel="noopener" class="text-blue-700 hover:underline">Instagram</a>
                <a href="https://www.linkedin.com/company/kayise-it/" target="_blank" rel="noopener" class="text-blue-700 hover:underline">LinkedIn</a>
                <a href="https://www.youtube.com/channel/UCrAixDqFR92LBqC7OBF3Eqw" target="_blank" rel="noopener" class="text-blue-700 hover:underline">YouTube</a>
            </div>
        </div>
    </div>
</section>
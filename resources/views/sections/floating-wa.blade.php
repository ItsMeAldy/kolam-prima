<a href="{{ \App\Support\WhatsApp::defaultLink() }}" target="_blank" rel="noopener" aria-label="Chat WhatsApp"
   class="group fixed bottom-5 right-5 z-50 flex items-center gap-3 rounded-full bg-whatsapp py-3 pl-3 pr-4 text-white shadow-2xl shadow-whatsapp/30 transition hover:bg-whatsapp-dark sm:bottom-7 sm:right-7">
    <span class="relative grid h-9 w-9 place-items-center">
        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white/40"></span>
        <x-icon.whatsapp class="relative h-7 w-7" />
    </span>
    <span class="hidden text-sm font-semibold sm:block">Chat WhatsApp</span>
</a>

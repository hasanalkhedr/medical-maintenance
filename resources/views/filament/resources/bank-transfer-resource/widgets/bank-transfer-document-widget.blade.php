<x-filament::section>
    <x-slot name="heading">
        Bank Document
    </x-slot>

    @if($documentUrl)
        @if(in_array($documentType, ['pdf']))
            <iframe src="{{ $documentUrl }}" width="100%" height="600px"
                    class="border rounded-lg"></iframe>
        @else
            <img src="{{ $documentUrl }}" alt="Bank Document"
                 class="max-w-full h-auto rounded-lg border shadow-sm">
        @endif

        <div class="mt-4">
            <x-filament::button
                tag="a"
                href="{{ $documentUrl }}"
                target="_blank"
                icon="heroicon-o-arrow-down-tray">
                Download Document
            </x-filament::button>
        </div>
    @else
        <p class="text-gray-500 dark:text-gray-400">No document attached to this check.</p>
    @endif
</x-filament::section>

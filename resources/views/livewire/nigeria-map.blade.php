<div 
    x-data="{ 
        map: null,
        initialized: false
    }"
    x-init="
        if (!initialized) {
            $nextTick(() => {
                map = initializeNigeriaMap('nigeria-map-container', {{ json_encode($states) }});
                initialized = true;
            });
        }
    "
    class="w-full h-full">
    
    <!-- Map Container -->
    <div id="nigeria-map-container" class="w-full rounded-lg shadow-sm border border-gray-200 overflow-hidden" style="height: 500px; background-color: #f3f4f6;"></div>
    
    <!-- Loading State -->
    <div x-show="!initialized" class="absolute inset-0 flex items-center justify-center bg-gray-50 rounded-lg">
        <div class="text-center">
            <svg class="animate-spin h-8 w-8 text-blue-500 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-2 text-sm text-gray-600">Loading Nigeria Map...</p>
        </div>
    </div>
</div>

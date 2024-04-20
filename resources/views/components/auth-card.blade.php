<div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
    <div>
        {{ $logo }}
    </div>

    <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
      
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        {{ $slot }}
        </div>
    </div>
</div>

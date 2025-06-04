<x-payment-gateway::payment-layout title="Payment Failed">
    <div class="text-center">
        <div class="text-6xl mb-6 text-red-600">✗</div>
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Payment Failed</h1>
        <p class="text-lg text-gray-600 mb-8">Unfortunately, your payment could not be processed.</p>
        
        @if($payment)
            <livewire:payment-status :payment-model="$payment" :auto-refresh="false" />
        @endif
        
        <div class="mt-8 space-x-4">
            <a href="{{ url()->previous() }}" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md transition duration-200">
                Try Again
            </a>
            <a href="/" 
               class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-md transition duration-200">
                Go Home
            </a>
        </div>
    </div>
</x-payment-gateway::payment-layout>

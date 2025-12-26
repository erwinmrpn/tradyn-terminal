<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

// Setup Form menggunakan Inertia
const form = useForm({
    name: '',            // Nama Akun (misal: "Akun Scalping")
    exchange: '',        // Nama Platform (Text Bebas)
    strategy_type: 'SPOT', // Tipe Strategi (Dropdown)
    currency: 'USD',     // Mata Uang
    balance: '',         // Saldo Awal
});

const submit = () => {
    // Kirim data ke route 'trading-account.store'
    form.post(route('trading-account.store'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Setup Trading Account" />

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-[#0a0b0d] text-gray-300">
        
        <div class="mb-6 text-center">
            <div class="w-12 h-12 bg-blue-600 rounded mx-auto flex items-center justify-center text-white font-bold text-xl mb-4">
                T
            </div>
            <h2 class="text-2xl font-bold text-white">Setup Your Trading Account</h2>
            <p class="text-gray-500 text-sm mt-2">
                Create your first portfolio to start tracking your journey.
            </p>
        </div>

        <div class="w-full sm:max-w-lg mt-6 px-8 py-8 bg-[#121317] border border-[#1f2128] shadow-xl overflow-hidden sm:rounded-xl">
            
            <form @submit.prevent="submit" class="space-y-6">
                
                <div>
                    <label for="name" class="block mb-2 text-sm font-medium text-white">Account Name</label>
                    <input 
                        id="name"
                        type="text" 
                        v-model="form.name"
                        class="bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 placeholder-gray-500" 
                        placeholder="e.g. My Binance Spot, Scalping Account" 
                        required 
                        autofocus
                    />
                    <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                </div>

                <div>
                    <label for="exchange" class="block mb-2 text-sm font-medium text-white">Exchange / Platform</label>
                    <input 
                        id="exchange"
                        type="text" 
                        v-model="form.exchange"
                        class="bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 placeholder-gray-500" 
                        placeholder="e.g. Binance, Bybit, Metamask, Tokocrypto" 
                        required 
                    />
                    <p class="mt-1 text-xs text-gray-500">Type the name of the exchange or wallet.</p>
                    <div v-if="form.errors.exchange" class="text-red-500 text-xs mt-1">{{ form.errors.exchange }}</div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    
                    <div>
                        <label for="strategy_type" class="block mb-2 text-sm font-medium text-white">Market Type</label>
                        <select 
                            id="strategy_type" 
                            v-model="form.strategy_type"
                            class="bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                        >
                            <option value="SPOT">Spot</option>
                            <option value="FUTURES">Futures</option>
                            <option value="MARGIN">Margin</option>
                            <option value="OPTION">Options</option>
                        </select>
                    </div>

                    <div>
                        <label for="currency" class="block mb-2 text-sm font-medium text-white">Base Currency</label>
                        <select 
                            id="currency" 
                            v-model="form.currency"
                            class="bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                        >
                            <option value="USD">USD ($)</option>
                            <option value="IDR">IDR (Rp)</option>
                            <option value="USDT">USDT</option>
                            <option value="BTC">BTC</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="balance" class="block mb-2 text-sm font-medium text-white">Initial Balance</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <span class="text-gray-400 font-bold text-sm">
                                {{ form.currency === 'IDR' ? 'Rp' : '$' }}
                            </span>
                        </div>
                        <input 
                            id="balance"
                            type="number" 
                            v-model="form.balance"
                            step="0.01"
                            class="bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-8 p-2.5 placeholder-gray-500" 
                            placeholder="0.00" 
                            required 
                        />
                    </div>
                    <div v-if="form.errors.balance" class="text-red-500 text-xs mt-1">{{ form.errors.balance }}</div>
                </div>

                <div class="pt-4">
                    <button 
                        type="submit" 
                        class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-800 font-medium rounded-lg text-sm px-5 py-3 text-center transition-all shadow-lg shadow-blue-500/30"
                        :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing">Setting up...</span>
                        <span v-else>Create Account & Start</span>
                    </button>
                </div>

            </form>
        </div>
        
        <p class="mt-8 text-center text-xs text-gray-600">
            &copy; 2025 Tradyn Terminal. All rights reserved.
        </p>
    </div>
</template>
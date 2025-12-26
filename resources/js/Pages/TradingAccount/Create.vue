<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';

const form = useForm({
    name: '',
    exchange: '',
    strategy_type: 'SPOT',
    currency: 'USD',
    balance: '',
});

const submit = () => {
    form.post(route('trading-account.store'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Add New Account" />

    <div class="min-h-screen bg-[#0a0b0d] text-gray-300 font-sans flex">
        
        <Sidebar />

        <main class="flex-1 ml-[72px] lg:ml-64 flex flex-col min-h-screen">
            <Navbar />

            <div class="pt-8 px-6 lg:px-8">
                <div class="flex items-center gap-4">
                    <Link :href="route('portfolio')" class="p-2 rounded-lg bg-[#121317] border border-[#1f2128] text-gray-400 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    </Link>
                    <div>
                        <h2 class="text-2xl font-bold text-white tracking-tight">Add New Platform/Exchange</h2>
                        <p class="text-sm text-gray-500 mt-1">Connect a new wallet or exchange to your portfolio.</p>
                    </div>
                </div>
            </div>

            <div class="p-6 lg:p-8 max-w-3xl">
                <div class="bg-[#121317] border border-[#1f2128] rounded-xl p-6 shadow-sm">
                    
                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-400">Account Name (Alias)</label>
                            <input 
                                type="text" 
                                v-model="form.name"
                                class="bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" 
                                placeholder="e.g. My Binance Scalping" 
                                required 
                            />
                            <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-400">Exchange / Platform</label>
                            <input 
                                type="text" 
                                v-model="form.exchange"
                                class="bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" 
                                placeholder="e.g. Binance, Bybit, Tokocrypto" 
                                required 
                            />
                            <div v-if="form.errors.exchange" class="text-red-500 text-xs mt-1">{{ form.errors.exchange }}</div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-400">Strategy Type</label>
                                <select v-model="form.strategy_type" class="bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                    <option value="SPOT">Spot</option>
                                    <option value="FUTURES">Futures</option>
                                    <option value="MARGIN">Margin</option>
                                </select>
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-400">Currency</label>
                                <select v-model="form.currency" class="bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                    <option value="USD">USD ($)</option>
                                    <option value="IDR">IDR (Rp)</option>
                                    <option value="USDT">USDT</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-400">Initial Balance</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <span class="text-gray-500 font-bold">{{ form.currency === 'IDR' ? 'Rp' : '$' }}</span>
                                </div>
                                <input 
                                    type="number" 
                                    v-model="form.balance"
                                    step="0.01"
                                    class="bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-8 p-2.5" 
                                    placeholder="0.00" 
                                    required 
                                />
                            </div>
                        </div>

                        <div class="pt-4 flex items-center gap-4">
                            <Link :href="route('portfolio')" class="text-gray-500 hover:text-white text-sm font-medium px-4 py-2">
                                Cancel
                            </Link>
                            <button 
                                type="submit" 
                                class="bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm px-6 py-2.5 transition shadow-lg shadow-blue-500/20"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? 'Saving...' : 'Add Exchange' }}
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </main>
    </div>
</template>
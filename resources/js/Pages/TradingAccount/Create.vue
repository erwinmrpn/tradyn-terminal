<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';
import { ref, onMounted } from 'vue';

// Props dari Controller
const props = defineProps<{
    isInitialSetup: boolean;
}>();

// --- SIDEBAR LOGIC (Hanya dipakai jika bukan initial setup) ---
const isSidebarCollapsed = ref(false);

onMounted(() => {
    const saved = localStorage.getItem("sidebar_collapsed");
    if (saved === "true") isSidebarCollapsed.value = true;
});

const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
    localStorage.setItem("sidebar_collapsed", String(isSidebarCollapsed.value));
}

// --- FORM DATA ---
const form = useForm({
    name: '',
    exchange: '',
    strategy_type: 'SPOT',
    balance: '',
    currency: 'USD',
});

const submit = () => {
    // Tombol akan disable otomatis saat processing
    form.post(route('trading-account.store'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Setup Portfolio" />

    <div v-if="props.isInitialSetup" class="min-h-screen bg-[#0a0b0d] flex items-center justify-center p-4 font-sans text-gray-300">
        <div class="w-full max-w-lg">
            <div class="text-center mb-10">
                <img src="/images/logo.png" class="w-12 h-12 mx-auto mb-4 rounded" alt="Logo" />
                <h1 class="text-2xl font-bold text-white mb-2">Welcome to Tradyn</h1>
                <p class="text-gray-500 text-sm">Create your first portfolio to start journaling.</p>
            </div>
            
            <div class="bg-[#121317] border border-[#1f2128] rounded-xl p-8 shadow-2xl">
                <h2 class="text-lg font-bold text-white mb-6">Setup Main Portfolio</h2>

                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Portfolio Name</label>
                        <input v-model="form.name" type="text" placeholder="e.g. My Binance Spot" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg p-3 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all placeholder-gray-600">
                        <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Exchange / Wallet</label>
                        <input v-model="form.exchange" type="text" placeholder="e.g. Binance, Bybit, Metamask" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg p-3 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all placeholder-gray-600">
                        <div v-if="form.errors.exchange" class="text-red-500 text-xs mt-1">{{ form.errors.exchange }}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Type</label>
                            <select v-model="form.strategy_type" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg p-3 focus:ring-1 focus:ring-blue-500 outline-none">
                                <option value="SPOT">Spot</option>
                                <option value="FUTURES">Futures</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Currency</label>
                            <select v-model="form.currency" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg p-3 focus:ring-1 focus:ring-blue-500 outline-none">
                                <option value="USD">USD ($)</option>
                                <option value="IDR">IDR (Rp)</option>
                                <option value="USDT">USDT</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Initial Balance</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500 font-bold">$</div>
                            <input v-model="form.balance" type="number" step="any" placeholder="0.00" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-lg font-mono rounded-lg pl-8 pr-4 py-3 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all placeholder-gray-600">
                        </div>
                        <div v-if="form.errors.balance" class="text-red-500 text-xs mt-1">{{ form.errors.balance }}</div>
                    </div>

                    <div class="pt-4 flex items-center justify-between">
                        <Link :href="route('logout')" method="post" as="button" class="text-sm text-gray-500 hover:text-white transition-colors">Cancel</Link>
                        <button type="submit" :disabled="form.processing" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-3 px-6 rounded-lg shadow-lg hover:shadow-blue-500/20 transition-all transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ form.processing ? 'Creating...' : 'Create Portfolio' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div v-else class="min-h-screen bg-[#0a0b0d] text-gray-300 font-sans relative">
        <Sidebar :is-collapsed="isSidebarCollapsed" @toggle="toggleSidebar" />

        <div class="transition-all duration-300 ease-in-out min-h-screen flex flex-col" :class="isSidebarCollapsed ? 'ml-[72px]' : 'ml-64'">
            <Navbar />

            <main class="p-6 lg:p-8 space-y-8 flex-1 pb-20 flex items-center justify-center">
                <div class="w-full max-w-2xl">
                    
                    <div class="mb-6">
                        <Link :href="route('portfolio')" class="flex items-center text-sm text-gray-500 hover:text-white transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                            Back to Portfolio
                        </Link>
                    </div>

                    <div class="bg-[#121317] border border-[#1f2128] rounded-xl p-8 shadow-lg">
                        <h2 class="text-xl font-bold text-white mb-1">Add New Portfolio</h2>
                        <p class="text-xs text-gray-500 mb-6">Create a separate journal for another exchange or strategy.</p>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Portfolio Name</label>
                                <input v-model="form.name" type="text" placeholder="e.g. Bybit Futures" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg p-3 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all placeholder-gray-600">
                                <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Exchange / Wallet</label>
                                <input v-model="form.exchange" type="text" placeholder="e.g. Bybit, Binance" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg p-3 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all placeholder-gray-600">
                                <div v-if="form.errors.exchange" class="text-red-500 text-xs mt-1">{{ form.errors.exchange }}</div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Type</label>
                                    <select v-model="form.strategy_type" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg p-3 focus:ring-1 focus:ring-blue-500 outline-none">
                                        <option value="SPOT">Spot</option>
                                        <option value="FUTURES">Futures</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Currency</label>
                                    <select v-model="form.currency" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg p-3 focus:ring-1 focus:ring-blue-500 outline-none">
                                        <option value="USD">USD ($)</option>
                                        <option value="IDR">IDR (Rp)</option>
                                        <option value="USDT">USDT</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Initial Balance</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500 font-bold">$</div>
                                    <input v-model="form.balance" type="number" step="any" placeholder="0.00" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-lg font-mono rounded-lg pl-8 pr-4 py-3 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all placeholder-gray-600">
                                </div>
                                <div v-if="form.errors.balance" class="text-red-500 text-xs mt-1">{{ form.errors.balance }}</div>
                            </div>

                            <div class="pt-4 flex justify-end gap-4">
                                <Link :href="route('portfolio')" class="px-5 py-2.5 rounded-lg border border-[#2d2f36] text-gray-400 text-sm font-bold hover:text-white hover:border-gray-500 transition-all">Cancel</Link>
                                <button type="submit" :disabled="form.processing" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-2.5 px-6 rounded-lg shadow-lg hover:shadow-blue-500/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                    {{ form.processing ? 'Saving...' : 'Add Portfolio' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>

            <Footer :is-sidebar-collapsed="isSidebarCollapsed" />
        </div>
    </div>
</template>
<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';
import { ref, onMounted } from 'vue';

// Props dari Controller
const props = defineProps<{
    account: {
        id: number;
        name: string;
        market_type: string;
        exchange: string;
        strategy_type: string;
        currency: string;
        balance: number;
    };
}>();

// Sidebar State
const isSidebarCollapsed = ref(false);

onMounted(() => {
    const saved = localStorage.getItem("sidebar_collapsed");
    if (saved === "true") isSidebarCollapsed.value = true;
});

const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
    localStorage.setItem("sidebar_collapsed", String(isSidebarCollapsed.value));
}

// Form Data (Data Currency, Strategy, dan Balance tetap dikirim agar validasi backend lolos)
const form = useForm({
    name: props.account.name,
    market_type: props.account.market_type || 'Crypto',
    exchange: props.account.exchange,
    strategy_type: props.account.strategy_type,
    currency: props.account.currency,
    balance: props.account.balance,
});

const submit = () => {
    form.put(route('trading-account.update', props.account.id), {
        onSuccess: () => {
            // Berhasil
        }
    });
};
</script>

<template>
    <Head title="Edit Portfolio" />

    <div class="min-h-screen bg-[#0a0b0d] text-gray-300 font-sans relative">
        
        <Sidebar :is-collapsed="isSidebarCollapsed" @toggle="toggleSidebar" />

        <div class="transition-all duration-300 ease-in-out min-h-screen flex flex-col" 
             :class="isSidebarCollapsed ? 'ml-[72px]' : 'ml-64'">
            
            <Navbar />

            <main class="p-6 lg:p-8 space-y-8 flex-1 pb-20 flex items-center justify-center">
                <div class="w-full max-w-2xl">
                    
                    <div class="mb-6">
                        <Link :href="route('portfolio')" class="flex items-center text-sm text-gray-500 hover:text-white transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Back to Portfolio
                        </Link>
                    </div>

                    <div class="bg-[#121317] border border-[#1f2128] rounded-xl p-8 shadow-lg relative overflow-hidden">
                        <div class="absolute top-0 inset-x-0 h-[2px] bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6]"></div>
                        
                        <h2 class="text-xl font-bold text-white mb-1">Edit Portfolio</h2>
                        <p class="text-xs text-gray-500 mb-6">Update your portfolio details.</p>

                        <form @submit.prevent="submit" class="space-y-6">
                            
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Portfolio Name</label>
                                <input v-model="form.name" type="text" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg p-3 focus:ring-1 focus:ring-[#8c52ff] focus:border-[#8c52ff] outline-none transition-all placeholder-gray-600">
                                <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Market Type</label>
                                <select v-model="form.market_type" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg p-3 focus:ring-1 focus:ring-[#8c52ff] focus:border-[#8c52ff] outline-none cursor-pointer">
                                    <option value="Crypto">Crypto</option>
                                    <option value="Stock">Stock</option>
                                    <option value="Commodity">Commodity</option>
                                </select>
                                <div v-if="form.errors.market_type" class="text-red-500 text-xs mt-1">{{ form.errors.market_type }}</div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Exchange, Wallet, Brokers</label>
                                <input v-model="form.exchange" type="text" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg p-3 focus:ring-1 focus:ring-[#8c52ff] focus:border-[#8c52ff] outline-none transition-all placeholder-gray-600">
                                <div v-if="form.errors.exchange" class="text-red-500 text-xs mt-1">{{ form.errors.exchange }}</div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-2 uppercase">Type (Locked)</label>
                                    <input 
                                        :value="form.strategy_type" 
                                        disabled
                                        class="w-full bg-[#0a0b0d] border border-[#1f2128] text-gray-500 text-sm rounded-lg p-3 cursor-not-allowed opacity-75"
                                    >
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-2 uppercase">Currency (Locked)</label>
                                    <input 
                                        :value="form.currency" 
                                        disabled
                                        class="w-full bg-[#0a0b0d] border border-[#1f2128] text-gray-500 text-sm rounded-lg p-3 cursor-not-allowed opacity-75"
                                    >
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-2 uppercase">Initial Balance (Locked)</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-600 font-bold">
                                        {{ form.currency === 'IDR' ? 'Rp' : '$' }}
                                    </div>
                                    <input 
                                        v-model="form.balance" 
                                        type="number" 
                                        disabled
                                        class="w-full bg-[#0a0b0d] border border-[#1f2128] text-gray-500 text-lg font-mono rounded-lg pl-10 pr-4 py-3 cursor-not-allowed opacity-75"
                                    >
                                </div>
                                <p class="text-[10px] text-gray-600 mt-1 italic">*To adjust balance, please use the Deposit/Withdraw feature (coming soon).</p>
                            </div>

                            <div class="pt-4 flex justify-end gap-4">
                                <Link :href="route('portfolio')" class="px-5 py-2.5 rounded-lg border border-[#2d2f36] text-gray-400 text-sm font-bold hover:text-white hover:border-gray-500 transition-all">
                                    Cancel
                                </Link>
                                <button type="submit" :disabled="form.processing" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-2.5 px-6 rounded-lg shadow-lg hover:shadow-blue-500/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed transform active:scale-95">
                                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
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
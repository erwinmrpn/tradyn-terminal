<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';
import { ref, onMounted } from 'vue';

// Props dari Controller
const props = defineProps<{
    accounts: any[];
    totalBalance: number;
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

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
};
</script>

<template>
    <Head title="Portfolio" />

    <div class="min-h-screen bg-[#0a0b0d] text-gray-300 font-sans relative">
        
        <Sidebar :is-collapsed="isSidebarCollapsed" @toggle="toggleSidebar" />

        <div class="transition-all duration-300 ease-in-out min-h-screen flex flex-col"
            :class="isSidebarCollapsed ? 'ml-[72px]' : 'ml-64'">
            
            <Navbar />

            <main class="p-6 lg:p-8 space-y-8 flex-1 pb-20">
                
                <div class="flex flex-col sm:flex-row justify-between items-end gap-4 border-b border-[#1f2128] pb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-white mb-1">Portfolio</h1>
                        <p class="text-xs text-gray-500">Manage your connected exchanges and wallets.</p>
                    </div>
                    <Link :href="route('trading-account.setup')" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2.5 px-5 rounded-lg shadow-lg flex items-center gap-2 transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        CONNECT ACCOUNT
                    </Link>
                </div>

                <div class="bg-gradient-to-r from-[#121317] to-[#1a1b20] border border-[#1f2128] rounded-xl p-8 text-center shadow-lg relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 to-purple-500"></div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Net Worth</p>
                    <h2 class="text-4xl md:text-5xl font-black text-white tracking-tight">{{ formatCurrency(props.totalBalance) }}</h2>
                </div>

                <div v-if="props.accounts.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    
                    <div v-for="account in props.accounts" :key="account.id" class="bg-[#121317] border border-[#1f2128] rounded-xl p-5 hover:border-gray-600 transition-all group">
                        
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-[#1f2128] flex items-center justify-center text-lg font-bold text-gray-400 group-hover:text-white group-hover:bg-blue-600 transition-all">
                                    {{ account.exchange.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-white">{{ account.name }}</h3>
                                    <span class="text-[10px] uppercase font-bold px-1.5 py-0.5 rounded border"
                                        :class="account.strategy_type === 'SPOT' ? 'text-blue-400 border-blue-500/30 bg-blue-500/10' : 'text-purple-400 border-purple-500/30 bg-purple-500/10'">
                                        {{ account.strategy_type }}
                                    </span>
                                </div>
                            </div>
                            
                            <button class="text-gray-600 hover:text-white"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" /></svg></button>
                        </div>

                        <div class="space-y-1">
                            <p class="text-xs text-gray-500 font-medium">Current Balance</p>
                            <p class="text-xl font-bold text-white font-mono">{{ formatCurrency(account.balance) }}</p>
                        </div>

                        <div class="mt-4 pt-4 border-t border-[#1f2128] flex justify-between items-center text-[10px] text-gray-500">
                            <span>{{ account.exchange }}</span>
                            <span>{{ account.currency }}</span>
                        </div>
                    </div>

                </div>

                <div v-else class="text-center py-20 bg-[#121317] border border-[#1f2128] border-dashed rounded-xl">
                    <svg class="w-12 h-12 mx-auto text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                    <h3 class="text-gray-400 font-bold mb-1">No Accounts Connected</h3>
                    <p class="text-xs text-gray-600 mb-4">Start by adding your first exchange or wallet.</p>
                    <Link :href="route('trading-account.setup')" class="text-blue-500 hover:text-blue-400 text-xs font-bold uppercase">Connect Now &rarr;</Link>
                </div>

            </main>
            
            <Footer :is-sidebar-collapsed="isSidebarCollapsed" />
        </div>
    </div>
</template>
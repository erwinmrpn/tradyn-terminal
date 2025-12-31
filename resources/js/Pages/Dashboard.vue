<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';
import { ref, onMounted } from 'vue';

// --- PROPS DARI CONTROLLER ---
const props = defineProps<{
    totalBalance: number;
    activePositions: number;
    todaysPnL: number;
    recentTrades: any[];
}>();

// --- SIDEBAR STATE ---
const isSidebarCollapsed = ref(false);

onMounted(() => {
    const saved = localStorage.getItem("sidebar_collapsed");
    if (saved === "true") isSidebarCollapsed.value = true;
});

const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
    localStorage.setItem("sidebar_collapsed", String(isSidebarCollapsed.value));
}

// --- FORMAT CURRENCY ---
const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
};

// --- FORMAT TANGGAL ---
const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    const options: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'short', year: '2-digit' };
    return new Date(dateString).toLocaleDateString('en-US', options);
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="min-h-screen bg-[#0a0b0d] text-gray-300 font-sans relative">
        
        <Sidebar :is-collapsed="isSidebarCollapsed" @toggle="toggleSidebar" />

        <div class="transition-all duration-300 ease-in-out min-h-screen flex flex-col"
            :class="isSidebarCollapsed ? 'ml-[72px]' : 'ml-64'">
            
            <Navbar />

            <main class="p-6 lg:p-8 space-y-8 flex-1 pb-20">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <div class="bg-[#121317] border border-[#1f2128] rounded-xl p-6 shadow-lg relative overflow-hidden group hover:border-blue-500/30 transition-colors">
                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                            <svg class="w-16 h-16 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Balance</p>
                        <h3 class="text-2xl font-bold text-white">{{ formatCurrency(props.totalBalance) }}</h3>
                    </div>

                    <div class="bg-[#121317] border border-[#1f2128] rounded-xl p-6 shadow-lg relative overflow-hidden group hover:border-yellow-500/30 transition-colors">
                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                            <svg class="w-16 h-16 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        </div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Active Positions</p>
                        <div class="flex items-center gap-2">
                            <h3 class="text-2xl font-bold text-white">{{ props.activePositions }}</h3>
                            <span v-if="props.activePositions > 0" class="text-[10px] bg-yellow-500/20 text-yellow-500 px-2 py-0.5 rounded font-bold uppercase animate-pulse">Live</span>
                        </div>
                    </div>

                    <div class="bg-[#121317] border border-[#1f2128] rounded-xl p-6 shadow-lg relative overflow-hidden group hover:border-purple-500/30 transition-colors">
                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                            <svg class="w-16 h-16 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>
                        </div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Today's PnL</p>
                        <h3 class="text-2xl font-bold text-gray-400">--</h3>
                        <p class="text-[10px] text-gray-600 mt-1 italic">Waiting for close position data</p>
                    </div>
                </div>

                <div class="bg-[#121317] border border-[#1f2128] rounded-xl overflow-hidden shadow-sm">
                    <div class="p-5 border-b border-[#1f2128] flex justify-between items-center bg-[#1a1b20]/50">
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Recent Activity
                        </h3>
                        <a :href="route('trade.log')" class="text-xs text-blue-500 hover:text-blue-400 font-bold uppercase transition-colors">View All Logs &rarr;</a>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left">
                            <thead class="bg-[#1a1b20] text-gray-500 uppercase text-[10px] tracking-wider font-bold">
                                <tr>
                                    <th class="px-6 py-3">Type</th>
                                    <th class="px-6 py-3">Date</th>
                                    <th class="px-6 py-3">Asset</th>
                                    <th class="px-6 py-3">Side</th>
                                    <th class="px-6 py-3 text-right">Price</th>
                                    <th class="px-6 py-3 text-right">Value/Margin</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#1f2128]">
                                <tr v-for="trade in props.recentTrades" :key="trade.id" class="hover:bg-[#1a1b20]/50 transition-colors group text-sm">
                                    
                                    <td class="px-6 py-4">
                                        <span class="text-[10px] font-bold px-2 py-1 rounded border" 
                                            :class="trade.category === 'SPOT' 
                                                ? 'bg-blue-500/10 text-blue-400 border-blue-500/20' 
                                                : 'bg-purple-500/10 text-purple-400 border-purple-500/20'">
                                            {{ trade.category }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-gray-400 text-xs font-mono">
                                        {{ formatDate(trade.display_date) }}
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="font-bold text-white">{{ trade.symbol }}</div>
                                        <div class="text-[10px] text-gray-500">{{ trade.trading_account ? trade.trading_account.name : 'Unknown' }}</div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="font-bold uppercase text-xs" 
                                            :class="['BUY', 'LONG'].includes(trade.type) ? 'text-green-500' : 'text-red-500'">
                                            {{ trade.type }}
                                            <span v-if="trade.leverage" class="ml-1 text-[10px] text-gray-500 bg-[#1f2128] px-1 rounded">{{ trade.leverage }}x</span>
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-right font-mono text-gray-300">
                                        {{ formatCurrency(trade.entry_price || trade.price) }}
                                    </td>

                                    <td class="px-6 py-4 text-right font-mono font-bold text-xs text-white">
                                        {{ formatCurrency(trade.margin || trade.total) }}
                                    </td>
                                </tr>

                                <tr v-if="props.recentTrades.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500 text-sm">
                                        No recent trades found. Start by adding a trade in the <a :href="route('trade.log')" class="text-blue-500 hover:underline">Trade Log</a>.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
            
            <Footer :is-sidebar-collapsed="isSidebarCollapsed" />
        </div>
    </div>
</template>
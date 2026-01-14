<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';
import { ref, watch, onMounted, computed } from 'vue'; 

import ActivityForm from './Partials/ActivityForm.vue';
import ActivityTable from './Partials/ActivityTable.vue';

// 1. UPDATE PROPS (Terima data lengkap dari backend)
const props = defineProps<{
    accounts: any[];
    transactions: any[];
    availableMarketTypes: string[];
    filters: { 
        range: string;
        strategy_type: string;
        market_type: string;
        account_id: string;
    };
    stats: {
        total_balance: number;
        balance_change_pct: number; // Update nama props
        inflow: number;
        outflow: number;
        comparison_label: string;
    };
}>();

// --- STATE SIDEBAR ---
const isSidebarCollapsed = ref(false);
onMounted(() => {
    const saved = localStorage.getItem("sidebar_collapsed");
    if (saved === "true") isSidebarCollapsed.value = true;
});
const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
    localStorage.setItem("sidebar_collapsed", String(isSidebarCollapsed.value));
}

// --- STATE FILTER ---
const filterForm = ref({
    range: props.filters.range || 'all',
    strategy_type: props.filters.strategy_type || 'all',
    market_type: props.filters.market_type || 'all',
    account_id: props.filters.account_id || 'all',
});

// Computed Dropdown Accounts
const dropdownAccounts = computed(() => {
    return props.accounts.filter(acc => {
        const matchStrategy = filterForm.value.strategy_type === 'all' || acc.strategy_type === filterForm.value.strategy_type;
        const matchMarket = filterForm.value.market_type === 'all' || acc.market_type === filterForm.value.market_type;
        return matchStrategy && matchMarket;
    });
});

// Watcher Filter
watch(filterForm, (newFilters) => {
    router.get(
        route('account.activity'), 
        { ...newFilters }, 
        { preserveState: true, preserveScroll: true }
    );
}, { deep: true });

// Helper Format Currency
const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-US', { 
        style: 'currency', 
        currency: 'USD',
        minimumFractionDigits: 2 
    }).format(value);
};
</script>

<template>
    <Head title="Account Activity Log" />

    <div class="min-h-screen bg-[#0a0b0d] text-gray-300 font-sans relative">
        <Sidebar :is-collapsed="isSidebarCollapsed" @toggle="toggleSidebar" />

        <div class="transition-all duration-300 ease-in-out min-h-screen flex flex-col" 
             :class="isSidebarCollapsed ? 'ml-[72px]' : 'ml-64'">
            
            <Navbar />

            <main class="flex-1 flex flex-col pb-20">
                
                <div class="pt-8 px-6 lg:px-8 mb-6">
                    <h2 class="text-2xl font-bold text-white tracking-tight">Account Activity Log</h2>
                    <p class="text-sm text-gray-500 mt-1">Track inflows, outflows, and transfer history.</p>
                </div>

                <div class="p-6 lg:p-8 pt-0">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
                        
                        <div class="relative bg-[#121317] rounded-xl p-6 border border-[#1f2128] shadow-sm overflow-hidden group">
                            <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6]"></div>
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Balance</p>
                                    <h3 class="text-2xl font-black text-white tracking-tight mb-2">{{ formatCurrency(props.stats.total_balance) }}</h3>
                                    
                                    <div v-if="filterForm.range !== 'all'" class="flex items-center text-xs">
                                        <span class="text-gray-500 mr-2">{{ props.stats.comparison_label }}</span>
                                        <div class="flex items-center font-bold" 
                                            :class="props.stats.balance_change_pct >= 0 ? 'text-green-500' : 'text-red-500'">
                                            <span v-if="props.stats.balance_change_pct > 0">+</span>
                                            <span>{{ props.stats.balance_change_pct }}%</span>
                                        </div>
                                    </div>
                                    <div v-else class="text-xs text-gray-600 italic">
                                        Lifetime Balance
                                    </div>

                                </div>
                                <div class="p-2 bg-[#1f2128] rounded-lg text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                            </div>
                        </div>

                        <div class="relative bg-[#121317] rounded-xl p-6 border border-[#1f2128] shadow-sm overflow-hidden group">
                            <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-[#10b981] to-[#34d399]"></div>
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Inflow (Deposit)</p>
                                    <h3 class="text-2xl font-black text-green-400 tracking-tight">+{{ formatCurrency(props.stats.inflow) }}</h3>
                                </div>
                                <div class="p-2 bg-green-500/10 rounded-lg text-green-500 border border-green-500/20">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                                </div>
                            </div>
                        </div>

                        <div class="relative bg-[#121317] rounded-xl p-6 border border-[#1f2128] shadow-sm overflow-hidden group">
                            <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-[#ef4444] to-[#f87171]"></div>
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Outflow (Withdraw)</p>
                                    <h3 class="text-2xl font-black text-red-400 tracking-tight">-{{ formatCurrency(props.stats.outflow) }}</h3>
                                </div>
                                <div class="p-2 bg-red-500/10 rounded-lg text-red-500 border border-red-500/20">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="flex flex-col md:flex-row gap-4 mb-8 items-center bg-[#121317] border border-[#1f2128] p-3 rounded-xl relative z-20">
                        
                        <div class="w-full md:w-auto">
                            <div class="flex bg-[#0a0b0d] p-1 rounded-lg border border-[#2d2f36]">
                                <button @click="filterForm.strategy_type = 'all'" class="px-4 py-2 text-[10px] font-bold uppercase rounded transition-all" :class="filterForm.strategy_type === 'all' ? 'bg-[#2d2f36] text-white shadow' : 'text-gray-500 hover:text-gray-300'">All</button>
                                <button @click="filterForm.strategy_type = 'SPOT'" class="px-4 py-2 text-[10px] font-bold uppercase rounded transition-all" :class="filterForm.strategy_type === 'SPOT' ? 'bg-[#2d2f36] text-white shadow' : 'text-gray-500 hover:text-gray-300'">Spot</button>
                                <button @click="filterForm.strategy_type = 'FUTURES'" class="px-4 py-2 text-[10px] font-bold uppercase rounded transition-all" :class="filterForm.strategy_type === 'FUTURES' ? 'bg-[#2d2f36] text-white shadow' : 'text-gray-500 hover:text-gray-300'">Futures</button>
                            </div>
                        </div>

                        <div class="h-8 w-[1px] bg-[#2d2f36] hidden md:block"></div>

                        <div class="w-full md:w-48 relative">
                            <select v-model="filterForm.account_id" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-xs font-bold rounded-lg pl-3 pr-8 py-2.5 focus:border-[#8c52ff] outline-none appearance-none cursor-pointer hover:bg-[#1a1b20] transition-colors">
                                <option value="all">All Accounts</option>
                                <option v-for="acc in dropdownAccounts" :key="acc.id" :value="acc.id">{{ acc.name }} ({{ acc.exchange }})</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-500"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg></div>
                        </div>

                        <div class="w-full md:w-40 relative">
                            <select v-model="filterForm.market_type" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-xs font-bold rounded-lg pl-3 pr-8 py-2.5 focus:border-[#8c52ff] outline-none appearance-none cursor-pointer hover:bg-[#1a1b20] transition-colors">
                                <option value="all">All Markets</option>
                                <option v-for="type in props.availableMarketTypes" :key="type" :value="type">{{ type }}</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-500"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg></div>
                        </div>

                        <div class="hidden md:block flex-1"></div>

                        <div class="w-full md:w-40 relative">
                            <select v-model="filterForm.range" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-xs font-bold rounded-lg pl-3 pr-8 py-2.5 focus:border-[#8c52ff] outline-none appearance-none cursor-pointer hover:bg-[#1a1b20] transition-colors">
                                <option value="all">All Time</option>
                                <option value="today">Today</option>
                                <option value="yesterday">Yesterday</option>
                                <option value="week">Last 7 Days</option>
                                <option value="month">Last 30 Days</option>
                                <option value="year">Last 1 Year</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-500"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg></div>
                        </div>

                    </div>

                    <div class="relative bg-[#121317] border border-[#1f2128] rounded-xl overflow-hidden mb-8">
                        <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6]"></div>
                        <ActivityForm :accounts="props.accounts" />
                    </div>
                    
                    <div class="relative bg-[#121317] border border-[#1f2128] rounded-xl overflow-hidden">
                        <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6]"></div>
                        <ActivityTable :transactions="props.transactions" />
                    </div>

                </div>
            </main>

            <Footer :is-sidebar-collapsed="isSidebarCollapsed" />
        </div>
    </div>
</template>
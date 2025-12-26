<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';
import { ref, watch } from 'vue'; // Tidak butuh computed untuk netFlow lagi

import ActivityForm from './Partials/ActivityForm.vue';
import ActivityTable from './Partials/ActivityTable.vue';

// 1. UPDATE PROPS (Terima data lengkap dari backend)
const props = defineProps<{
    accounts: any[];
    transactions: any[];
    filters: { range: string };
    stats: {
        total_balance: number;
        daily_change_pct: number;
        // Data Net Flow Baru
        net_flow: number;
        net_flow_pct: number;
        comparison_label: string; // Label dinamis (vs Yesterday, vs Last 30 Days)
    };
}>();

// 2. STATE FILTER
const selectedRange = ref(props.filters.range || 'all');

watch(selectedRange, (newRange) => {
    router.get(
        route('account.activity'), 
        { range: newRange }, 
        { preserveState: true, preserveScroll: true }
    );
});

// 3. HELPER FORMAT
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

    <div class="min-h-screen bg-[#0a0b0d] text-gray-300 font-sans flex">
        
        <Sidebar />

        <main class="flex-1 ml-[72px] lg:ml-64 flex flex-col min-h-screen">
            
            <Navbar />

            <div class="pt-8 px-6 lg:px-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-white tracking-tight">
                        Account Activity Log
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Track your deposits and withdrawals history.</p>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 font-medium uppercase tracking-wider">Timeframe:</span>
                    <select 
                        v-model="selectedRange"
                        class="bg-[#121317] border border-[#1f2128] text-sm text-white rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2 outline-none cursor-pointer hover:border-gray-600 transition"
                    >
                        <option value="all">All Time</option>
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="week">Last 7 Days</option>
                        <option value="month">Last 30 Days</option>
                        <option value="year">Last 1 Year</option>
                    </select>
                </div>
            </div>

            <div class="p-6 lg:p-8">
                
                <div class="mb-8 grid grid-cols-1 md:grid-cols-2 gap-5">
                    
                    <div class="bg-white dark:bg-[#121317] rounded-xl p-6 border border-gray-200 dark:border-[#1f2128] shadow-sm relative overflow-hidden transition-all hover:shadow-md">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-sm font-semibold text-gray-600 dark:text-gray-400">Total Balance</span>
                            <div class="group relative cursor-pointer">
                                <svg class="w-4 h-4 text-gray-400 hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 w-48 bg-black text-white text-xs rounded py-1 px-2 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10 text-center">
                                    Current value across all accounts
                                </div>
                            </div>
                        </div>

                        <div class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                            {{ formatCurrency(props.stats.total_balance) }}
                        </div>

                        <div class="flex items-center text-sm">
                            <span class="text-gray-500 mr-2">vs Previous Day</span>
                            <div class="flex items-center font-bold" 
                                :class="props.stats.daily_change_pct >= 0 ? 'text-green-500' : 'text-red-500'">
                                <svg v-if="props.stats.daily_change_pct >= 0" class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                                <svg v-else class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                <span>{{ Math.abs(props.stats.daily_change_pct) }}%</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-[#121317] rounded-xl p-6 border border-gray-200 dark:border-[#1f2128] shadow-sm relative overflow-hidden transition-all hover:shadow-md">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                                Net Flow
                                <span class="ml-2 bg-[#1a1b20] text-gray-400 px-1.5 py-0.5 rounded text-[10px] border border-[#2d2f36] uppercase">
                                    {{ selectedRange }}
                                </span>
                            </span>
                            
                            <div class="group relative cursor-pointer">
                                <svg class="w-4 h-4 text-gray-400 hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 w-48 bg-black text-white text-xs rounded py-1 px-2 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10 text-center">
                                    Total Deposits minus Withdrawals in selected period
                                </div>
                            </div>
                        </div>

                        <div class="text-3xl font-bold mb-4" :class="props.stats.net_flow >= 0 ? 'text-gray-900 dark:text-white' : 'text-red-500'">
                            {{ formatCurrency(props.stats.net_flow) }}
                        </div>

                        <div class="flex items-center text-sm" v-if="selectedRange !== 'all'">
                            <span class="text-gray-500 mr-2">{{ props.stats.comparison_label }}</span>
                            
                            <div class="flex items-center font-bold" 
                                :class="props.stats.net_flow_pct >= 0 ? 'text-green-500' : 'text-red-500'">
                                
                                <svg v-if="props.stats.net_flow_pct >= 0" class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                                <svg v-else class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                
                                <span>{{ Math.abs(props.stats.net_flow_pct) }}%</span>
                            </div>
                        </div>
                        
                        <div class="flex items-center text-sm text-gray-500" v-else>
                            Lifetime Net Flow
                        </div>

                    </div>

                </div>

                <ActivityForm :accounts="props.accounts" />
                
                <ActivityTable :transactions="props.transactions" />

            </div>
        </main>
    </div>
</template>
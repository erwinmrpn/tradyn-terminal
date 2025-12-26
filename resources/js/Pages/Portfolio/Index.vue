<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';
import VueApexCharts from 'vue3-apexcharts';
import { computed } from 'vue';

const props = defineProps<{
    accounts: any[];
    stats: {
        total_net_worth: number;
        total_accounts: number;
        allocation_series: number[];
        allocation_labels: string[];
    };
}>();

// Format Uang
const formatCurrency = (value: number, currency = 'USD') => {
    return new Intl.NumberFormat('en-US', { 
        style: 'currency', 
        currency: currency,
        minimumFractionDigits: 2 
    }).format(value);
};

// Chart Options (Allocation Donut)
const chartOptions = computed(() => ({
    chart: { type: 'donut', background: 'transparent', fontFamily: 'Inter, sans-serif' },
    labels: props.stats.allocation_labels,
    colors: ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b'],
    legend: { position: 'bottom', labels: { colors: '#9ca3af' } },
    plotOptions: { 
        pie: { 
            donut: { 
                size: '70%', 
                labels: { 
                    show: true, 
                    total: { 
                        show: true, 
                        label: 'Allocation', 
                        color: '#9ca3af',
                        formatter: () => '100%'
                    },
                    value: { color: '#ffffff' } 
                } 
            } 
        } 
    },
    dataLabels: { enabled: false }, 
    stroke: { width: 0 } 
}));

const chartSeries = computed(() => props.stats.allocation_series);
</script>

<template>
    <Head title="Portfolio" />

    <div class="min-h-screen bg-[#0a0b0d] text-gray-300 font-sans flex">
        <Sidebar />

        <main class="flex-1 ml-[72px] lg:ml-64 flex flex-col min-h-screen">
            <Navbar />

            <div class="pt-8 px-6 lg:px-8 flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-white tracking-tight">Your Portfolio</h2>
                    <p class="text-sm text-gray-500 mt-1">Manage and track your connected exchange accounts.</p>
                </div>
                
                <Link :href="route('trading-account.create')" class="hidden sm:flex bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-lg shadow-blue-500/20 items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add New Platform/Exchange
                </Link>
            </div>

            <div class="p-6 lg:p-8 space-y-6">

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <div class="bg-[#121317] border border-[#1f2128] rounded-xl p-6 shadow-sm flex flex-col justify-center relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-10">
                            <svg class="w-24 h-24 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z" /><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h14a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd" /></svg>
                        </div>
                        <span class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Total Net Worth</span>
                        <div class="text-4xl font-bold text-white mt-2 z-10">
                            {{ formatCurrency(props.stats.total_net_worth) }}
                        </div>
                        <div class="mt-4 flex items-center gap-2 z-10">
                            <span class="px-2 py-1 bg-[#1a1b20] text-gray-400 text-xs rounded border border-[#2d2f36]">
                                {{ props.stats.total_accounts }} Active Accounts
                            </span>
                        </div>
                    </div>

                    <div class="lg:col-span-2 bg-[#121317] border border-[#1f2128] rounded-xl p-6 shadow-sm flex flex-col sm:flex-row items-center justify-between">
                        <div class="mb-4 sm:mb-0">
                            <h3 class="text-lg font-bold text-white mb-1">Asset Allocation</h3>
                            <p class="text-sm text-gray-500">Distribution by Strategy Type</p>
                        </div>
                        <div class="h-48 w-full sm:w-64">
                            <div v-if="props.accounts.length === 0" class="h-full flex items-center justify-center text-gray-500 text-sm">
                                No data available
                            </div>
                            <VueApexCharts v-else type="donut" height="100%" :options="chartOptions" :series="chartSeries" />
                        </div>
                    </div>
                </div>

                <div class="bg-[#121317] border border-[#1f2128] rounded-xl overflow-hidden shadow-sm">
                    
                    <div class="p-6 border-b border-[#1f2128] flex justify-between items-center">
                        <h3 class="text-lg font-bold text-white">Connected Accounts</h3>
                        
                        <Link :href="route('trading-account.create')" class="text-sm text-blue-500 hover:text-blue-400 font-medium flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            Add Account
                        </Link>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left">
                            <thead class="bg-[#1a1b20] text-gray-400 uppercase text-xs">
                                <tr>
                                    <th class="px-6 py-4 font-medium tracking-wider">Account Name</th>
                                    <th class="px-6 py-4 font-medium tracking-wider">Exchange</th>
                                    <th class="px-6 py-4 font-medium tracking-wider">Strategy</th>
                                    <th class="px-6 py-4 font-medium tracking-wider text-right">Balance</th>
                                    <th class="px-6 py-4 font-medium tracking-wider text-right">Total PnL</th>
                                    <th class="px-6 py-4 font-medium tracking-wider text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#1f2128]">
                                <tr v-for="acc in props.accounts" :key="acc.id" class="hover:bg-[#1a1b20]/50 transition-colors group">
                                    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-bold text-white">{{ acc.name }}</div>
                                        <div class="text-xs text-gray-500">ID: {{ acc.id }}</div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                                            <span class="text-gray-300">{{ acc.exchange }}</span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded border"
                                            :class="acc.strategy_type === 'FUTURES' 
                                                ? 'bg-purple-900/30 text-purple-400 border-purple-500/20' 
                                                : 'bg-green-900/30 text-green-400 border-green-500/20'">
                                            {{ acc.strategy_type }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-right font-mono text-white">
                                        {{ formatCurrency(Number(acc.balance), acc.currency) }}
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-right font-bold"
                                        :class="Number(acc.total_pnl) >= 0 ? 'text-green-500' : 'text-red-500'">
                                        {{ Number(acc.total_pnl) >= 0 ? '+' : '' }}{{ formatCurrency(Number(acc.total_pnl), 'USD') }}
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <button class="text-gray-500 hover:text-white transition" title="View Details">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="props.accounts.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-500">
                                            <svg class="w-12 h-12 mb-3 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                            <p class="text-sm">You haven't added any trading accounts yet.</p>
                                            <Link :href="route('trading-account.create')" class="mt-3 text-blue-500 hover:underline font-medium">
                                                Setup your first account
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>
</template>
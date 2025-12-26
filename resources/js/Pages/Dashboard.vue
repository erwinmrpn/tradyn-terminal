<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';
import { ref, computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

// --- 1. TERIMA DATA DARI CONTROLLER ---
const props = defineProps<{
    stats: {
        total_balance: number;
        daily_change_pct: number; // Persentase perubahan harian
        net_profit: number;
        win_rate: number;
        active_positions: number;
    };
    charts: {
        growth: number[];
        performance: number[];
    };
    recentTrades: any[];
}>();

// --- 2. HELPER FUNCTIONS ---

// Ambil data user login
const page = usePage();
const user = computed(() => page.props.auth.user);

// Format Uang (USD)
const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-US', { 
        style: 'currency', 
        currency: 'USD',
        minimumFractionDigits: 2 
    }).format(value);
};

// --- 3. KONFIGURASI CHART ---

// A. Growth Chart Options
const growthChartOptions = ref({
    chart: { id: 'portfolio-growth', type: 'area', fontFamily: 'Inter, sans-serif', toolbar: { show: false }, zoom: { enabled: false }, background: 'transparent' },
    colors: ['#3b82f6'], 
    stroke: { curve: 'smooth', width: 2 },
    dataLabels: { enabled: false },
    xaxis: { categories: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Today'], labels: { style: { colors: '#6b7280' } }, axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: { labels: { style: { colors: '#6b7280' }, formatter: (val: number) => `$${val}` } },
    grid: { borderColor: '#1f2937', strokeDashArray: 4 },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 100] } },
    theme: { mode: 'dark' }
});

// Data Series Growth (Reactive dari Props)
const growthSeries = computed(() => [{ 
    name: 'Portfolio Value', 
    data: props.charts.growth 
}]);

// B. Performance Chart Options
const performanceChartOptions = ref({
    chart: { type: 'donut', background: 'transparent', fontFamily: 'Inter, sans-serif' },
    labels: ['Wins', 'Losses', 'Break Even'],
    colors: ['#10b981', '#ef4444', '#f59e0b'],
    legend: { position: 'bottom', labels: { colors: '#9ca3af' } },
    plotOptions: { 
        pie: { 
            donut: { 
                size: '75%', 
                labels: { 
                    show: true, 
                    total: { 
                        show: true, 
                        label: 'Total Trades', 
                        color: '#9ca3af', 
                        formatter: function (w: any) { 
                            return w.globals.seriesTotals.reduce((a: number, b: number) => a + b, 0) 
                        } 
                    }, 
                    value: { color: '#ffffff' } 
                } 
            } 
        } 
    },
    dataLabels: { enabled: false }, 
    stroke: { width: 0 } 
});

// Data Series Performance (Reactive dari Props)
const performanceSeries = computed(() => props.charts.performance); 

</script>

<template>
    <Head title="Dashboard" />

    <div class="min-h-screen bg-[#0a0b0d] text-gray-300 font-sans flex">
        
        <Sidebar />

        <main class="flex-1 ml-[72px] lg:ml-64 flex flex-col min-h-screen">
            
            <Navbar />

            <div class="pt-8 px-6 lg:px-8 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-white tracking-tight">
                        Dashboard Overview
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Welcome back, <span class="text-blue-400 font-medium">{{ user ? user.name : 'Trader' }}</span>!
                    </p>
                </div>
                <div>
                    <button class="bg-[#1a1b20] hover:bg-[#25262c] text-white text-sm px-4 py-2 rounded-lg border border-[#1f2128] transition">
                        Download Report
                    </button>
                </div>
            </div>

            <div class="p-6 lg:p-8 space-y-8">
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    
                    <div class="bg-white dark:bg-[#121317] rounded-xl p-6 border border-gray-200 dark:border-[#1f2128] shadow-sm relative overflow-hidden transition-all hover:shadow-md">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-sm font-semibold text-gray-600 dark:text-gray-400">Total Balance</span>
                            <div class="group relative cursor-pointer">
                                <svg class="w-4 h-4 text-gray-400 hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 w-48 bg-black text-white text-xs rounded py-1 px-2 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10 text-center">
                                    Total value of all accounts
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

                    <div class="bg-[#121317] rounded-xl p-6 border border-[#1f2128] shadow-sm relative overflow-hidden group hover:border-green-500/50 transition-colors">
                        <div class="absolute top-0 left-0 w-1 h-full bg-green-500"></div>
                        <div class="text-sm font-medium text-gray-400">Net Profit</div>
                        <div class="mt-2 text-3xl font-bold" :class="props.stats.net_profit >= 0 ? 'text-green-500' : 'text-red-500'">
                            {{ props.stats.net_profit >= 0 ? '+' : '' }}{{ formatCurrency(props.stats.net_profit) }}
                        </div>
                        <div class="mt-2 text-sm text-gray-500">All Time PnL</div>
                    </div>

                    <div class="bg-[#121317] rounded-xl p-6 border border-[#1f2128] shadow-sm relative overflow-hidden group hover:border-purple-500/50 transition-colors">
                        <div class="absolute top-0 left-0 w-1 h-full bg-purple-500"></div>
                        <div class="text-sm font-medium text-gray-400">Win Rate</div>
                        <div class="mt-2 text-3xl font-bold text-white">{{ props.stats.win_rate }}%</div>
                        <div class="mt-2 text-sm text-gray-500">Based on closed trades</div>
                    </div>

                    <div class="bg-[#121317] rounded-xl p-6 border border-[#1f2128] shadow-sm relative overflow-hidden group hover:border-yellow-500/50 transition-colors">
                        <div class="absolute top-0 left-0 w-1 h-full bg-yellow-500"></div>
                        <div class="text-sm font-medium text-gray-400">Active Positions</div>
                        <div class="mt-2 text-3xl font-bold text-white">{{ props.stats.active_positions }}</div>
                        <div class="mt-2 text-sm text-yellow-500 animate-pulse">Running...</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 bg-[#121317] border border-[#1f2128] rounded-xl p-6 shadow-sm">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-bold text-white">Portfolio Growth</h3>
                            <select class="bg-[#1a1b20] border border-[#2d2f36] text-gray-300 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2 outline-none">
                                <option>Last 7 Days</option>
                            </select>
                        </div>
                        <div class="h-80 w-full">
                            <VueApexCharts type="area" height="100%" :options="growthChartOptions" :series="growthSeries" />
                        </div>
                    </div>

                    <div class="bg-[#121317] border border-[#1f2128] rounded-xl p-6 shadow-sm flex flex-col justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-white mb-6">Performance Stats</h3>
                            <div class="h-56 flex items-center justify-center">
                                <VueApexCharts type="donut" width="100%" :options="performanceChartOptions" :series="performanceSeries" />
                            </div>
                        </div>
                        <div class="mt-6 space-y-3 pt-6 border-t border-[#1f2128]">
                            <div class="text-center text-sm text-gray-500">
                                Total Trades: <span class="text-white font-bold">{{ props.charts.performance.reduce((a, b) => a + b, 0) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-[#121317] border border-[#1f2128] rounded-xl overflow-hidden shadow-sm">
                    <div class="p-6 border-b border-[#1f2128] flex justify-between items-center">
                        <h3 class="text-lg font-bold text-white">Recent Trades</h3>
                        <button class="text-sm text-blue-500 hover:text-blue-400">View All History</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left">
                            <thead class="bg-[#1a1b20] text-gray-400 uppercase text-xs">
                                <tr>
                                    <th scope="col" class="px-6 py-4 font-medium tracking-wider">Pair</th>
                                    <th scope="col" class="px-6 py-4 font-medium tracking-wider">Type</th>
                                    <th scope="col" class="px-6 py-4 font-medium tracking-wider">Entry Date</th>
                                    <th scope="col" class="px-6 py-4 font-medium tracking-wider text-right">PnL</th>
                                    <th scope="col" class="px-6 py-4 font-medium tracking-wider text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#1f2128]">
                                <tr v-for="trade in props.recentTrades" :key="trade.id" class="hover:bg-[#1a1b20]/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-white">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 rounded-full bg-orange-500"></div>
                                            {{ trade.pair }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium" :class="trade.type === 'LONG' ? 'text-green-400' : 'text-red-400'">
                                        {{ trade.type }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-400">
                                        {{ trade.entry_date }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap font-bold text-right" :class="Number(trade.pnl) >= 0 ? 'text-green-400' : 'text-red-400'">
                                        {{ Number(trade.pnl) >= 0 ? '+' : '' }}{{ formatCurrency(trade.pnl) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded border"
                                            :class="trade.status === 'CLOSED' 
                                                ? 'bg-green-900/30 text-green-400 border-green-500/20' 
                                                : 'bg-yellow-900/30 text-yellow-400 border-yellow-500/20'">
                                            {{ trade.status }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="props.recentTrades.length === 0">
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                        No trades recorded yet.
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
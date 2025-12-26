<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue'; 
import Navbar from '@/Components/Navbar.vue'; // <--- Import Navbar Baru
import { ref } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

// --- KONFIGURASI CHART (TIDAK BERUBAH) ---
const growthChartOptions = ref({
    chart: { id: 'portfolio-growth', type: 'area', fontFamily: 'Inter, sans-serif', toolbar: { show: false }, zoom: { enabled: false }, background: 'transparent' },
    colors: ['#3b82f6'], stroke: { curve: 'smooth', width: 2 },
    dataLabels: { enabled: false },
    xaxis: { categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'], labels: { style: { colors: '#6b7280' } }, axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: { labels: { style: { colors: '#6b7280' }, formatter: (val) => `$${val}` } },
    grid: { borderColor: '#1f2937', strokeDashArray: 4 },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 100] } },
    theme: { mode: 'dark' }
});

const growthSeries = ref([{ name: 'Portfolio Value', data: [1000, 1200, 1150, 1400, 1800, 1750, 2100] }]);

const performanceChartOptions = ref({
    chart: { type: 'donut', background: 'transparent', fontFamily: 'Inter, sans-serif' },
    labels: ['Wins', 'Losses', 'Break Even'],
    colors: ['#10b981', '#ef4444', '#f59e0b'],
    legend: { position: 'bottom', labels: { colors: '#9ca3af' } },
    plotOptions: { pie: { donut: { size: '75%', labels: { show: true, total: { show: true, label: 'Total Trades', color: '#9ca3af', formatter: function (w) { return w.globals.seriesTotals.reduce((a, b) => a + b, 0) } }, value: { color: '#ffffff' } } } } },
    dataLabels: { enabled: false }, stroke: { width: 0 } 
});

const performanceSeries = ref([12, 5, 2]); 
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
                    <p class="text-sm text-gray-500 mt-1">Welcome back, Trader!</p>
                </div>
                <div>
                    <button class="bg-[#1a1b20] hover:bg-[#25262c] text-white text-sm px-4 py-2 rounded-lg border border-[#1f2128] transition">
                        Download Report
                    </button>
                </div>
            </div>

            <div class="p-6 lg:p-8 space-y-8">
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="bg-[#121317] rounded-xl p-6 border border-[#1f2128] shadow-sm relative overflow-hidden group hover:border-blue-500/50 transition-colors">
                        <div class="absolute top-0 left-0 w-1 h-full bg-blue-500"></div>
                        <div class="text-sm font-medium text-gray-400">Total Balance</div>
                        <div class="mt-2 text-3xl font-bold text-white">$2,100.00</div>
                        <div class="mt-2 text-sm text-green-400 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                            +15.3% <span class="text-gray-500 ml-1">(This Month)</span>
                        </div>
                    </div>

                    <div class="bg-[#121317] rounded-xl p-6 border border-[#1f2128] shadow-sm relative overflow-hidden group hover:border-green-500/50 transition-colors">
                        <div class="absolute top-0 left-0 w-1 h-full bg-green-500"></div>
                        <div class="text-sm font-medium text-gray-400">Net Profit</div>
                        <div class="mt-2 text-3xl font-bold text-green-500">+$650.00</div>
                        <div class="mt-2 text-sm text-gray-500">All Time PnL</div>
                    </div>

                    <div class="bg-[#121317] rounded-xl p-6 border border-[#1f2128] shadow-sm relative overflow-hidden group hover:border-purple-500/50 transition-colors">
                        <div class="absolute top-0 left-0 w-1 h-full bg-purple-500"></div>
                        <div class="text-sm font-medium text-gray-400">Win Rate</div>
                        <div class="mt-2 text-3xl font-bold text-white">63.2%</div>
                        <div class="mt-2 text-sm text-gray-500">Avg RR: <span class="text-gray-300">1:2.5</span></div>
                    </div>

                    <div class="bg-[#121317] rounded-xl p-6 border border-[#1f2128] shadow-sm relative overflow-hidden group hover:border-yellow-500/50 transition-colors">
                        <div class="absolute top-0 left-0 w-1 h-full bg-yellow-500"></div>
                        <div class="text-sm font-medium text-gray-400">Active Positions</div>
                        <div class="mt-2 text-3xl font-bold text-white">2</div>
                        <div class="mt-2 text-sm text-yellow-500 animate-pulse">Running...</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 bg-[#121317] border border-[#1f2128] rounded-xl p-6 shadow-sm">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-bold text-white">Portfolio Growth</h3>
                            <select class="bg-[#1a1b20] border border-[#2d2f36] text-gray-300 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2 outline-none">
                                <option>Last 7 Days</option>
                                <option selected>Last 30 Days</option>
                                <option>This Year</option>
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
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Profit Factor</span>
                                <span class="font-bold text-green-400">2.1</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Max Drawdown</span>
                                <span class="font-bold text-red-500">-5.4%</span>
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
                                <tr class="hover:bg-[#1a1b20]/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-white">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 rounded-full bg-orange-500"></div>
                                            BTC/USDT
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-green-400 font-medium">Long</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-400">2023-10-24</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-green-400 font-bold text-right">+$120.00</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded bg-green-900/30 text-green-400 border border-green-500/20">Closed</span>
                                    </td>
                                </tr>
                                <tr class="hover:bg-[#1a1b20]/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-white">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                                            ETH/USDT
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-red-400 font-medium">Short</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-400">2023-10-25</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-400 font-bold text-right">-$0.00</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded bg-yellow-900/30 text-yellow-400 border border-yellow-500/20">Running</span>
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
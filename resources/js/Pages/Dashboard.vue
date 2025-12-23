<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

// --- KONFIGURASI CHART (DUMMY DATA) ---

// 1. Chart Pertumbuhan Portofolio (Growth)
const growthChartOptions = ref({
    chart: {
        id: 'portfolio-growth',
        type: 'area',
        toolbar: { show: false },
        zoom: { enabled: false },
        background: 'transparent'
    },
    colors: ['#6366f1'], // Warna Indigo
    stroke: { curve: 'smooth', width: 2 },
    dataLabels: { enabled: false },
    xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
        labels: { style: { colors: '#9ca3af' } }, // Gray text
        axisBorder: { show: false },
        axisTicks: { show: false }
    },
    yaxis: {
        labels: { style: { colors: '#9ca3af' } }
    },
    grid: {
        borderColor: '#374151', // Dark border for grid
        strokeDashArray: 4,
    },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.7,
            opacityTo: 0.1,
            stops: [0, 90, 100]
        }
    },
    theme: { mode: 'dark' } // Set ke dark/light sesuai kebutuhan
});

const growthSeries = ref([{
    name: 'Portfolio Value',
    data: [1000, 1200, 1150, 1400, 1800, 1750, 2100] // Dummy saldo
}]);

// 2. Chart Performa (Win vs Loss)
const performanceChartOptions = ref({
    chart: { type: 'donut', background: 'transparent' },
    labels: ['Wins', 'Losses', 'Break Even'],
    colors: ['#10b981', '#ef4444', '#f59e0b'], // Green, Red, Amber
    legend: { position: 'bottom', labels: { colors: '#9ca3af' } },
    plotOptions: {
        pie: {
            donut: {
                size: '70%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Total Trades',
                        color: '#9ca3af',
                        formatter: function (w) {
                            return w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                        }
                    }
                }
            }
        }
    },
    dataLabels: { enabled: false }
});

const performanceSeries = ref([12, 5, 2]); // 12 Menang, 5 Kalah, 2 BEP

</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Trading Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                    <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 border-l-4 border-indigo-500">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Balance</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">$2,100.00</div>
                        <div class="mt-1 text-sm text-green-500 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                            +15.3% (This Month)
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 border-l-4 border-green-500">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Net Profit</div>
                        <div class="mt-2 text-3xl font-bold text-green-500">+$650.00</div>
                        <div class="mt-1 text-sm text-gray-500">All Time PnL</div>
                    </div>

                    <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 border-l-4 border-blue-500">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Win Rate</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">63.2%</div>
                        <div class="mt-1 text-sm text-gray-500">Avg RR: 1:2.5</div>
                    </div>

                    <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 border-l-4 border-yellow-500">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Positions</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">2</div>
                        <div class="mt-1 text-sm text-yellow-500">Running...</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    
                    <div class="lg:col-span-2 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Portfolio Growth</h3>
                            <select class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option>Last 7 Days</option>
                                <option selected>Last 30 Days</option>
                                <option>This Year</option>
                            </select>
                        </div>
                        <div class="h-80 w-full">
                            <VueApexCharts 
                                type="area" 
                                height="100%" 
                                :options="growthChartOptions" 
                                :series="growthSeries" 
                            />
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Performance</h3>
                        <div class="h-64 flex items-center justify-center">
                            <VueApexCharts 
                                type="donut" 
                                width="100%" 
                                :options="performanceChartOptions" 
                                :series="performanceSeries" 
                            />
                        </div>
                        <div class="mt-4 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Profit Factor</span>
                                <span class="font-bold text-gray-900 dark:text-gray-100">2.1</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Max Drawdown</span>
                                <span class="font-bold text-red-500">-5.4%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Recent Trades</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pair</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Entry Date</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">PnL</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 dark:text-gray-100">BTC/USDT</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-green-500">Long</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">2023-10-24</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-green-500">+$120.00</td>
                                        <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Closed</span></td>
                                    </tr>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 dark:text-gray-100">ETH/USDT</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-red-500">Short</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">2023-10-25</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">-$0.00</td>
                                        <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Running</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
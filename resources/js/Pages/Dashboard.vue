<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';
import { ref, onMounted, computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

// --- PROPS ---
const props = defineProps<{
    totalBalance: number;
    activePositions: number;
    metrics: any;
    charts: any; // Data chart dinamis dari controller
    recentTrades: any[];
}>();

// --- STATE ---
const isSidebarCollapsed = ref(false);
const selectedTimeframe = ref<'TODAY' | 'WEEK' | 'MONTH'>('TODAY');
const isChartInteractive = ref(false); // [BARU] Default: Chart tidak menangkap scroll

// --- COMPUTED: Data Metrics ---
const currentMetrics = computed(() => props.metrics[selectedTimeframe.value]);

const pnlComparison = computed(() => calculateChange(currentMetrics.value.gross_pnl, currentMetrics.value.prev_gross_pnl));
const netComparison = computed(() => calculateChange(currentMetrics.value.net_pnl, currentMetrics.value.prev_net_pnl));
const roiComparison = computed(() => currentMetrics.value.roi - currentMetrics.value.prev_roi);

const calculateChange = (curr: number, prev: number) => {
    if (prev === 0) return curr > 0 ? 100 : (curr < 0 ? -100 : 0);
    return ((curr - prev) / Math.abs(prev)) * 100;
};

// --- CHART DATA HANDLER ---
const currentChartData = computed(() => {
    return props.charts[selectedTimeframe.value] || { series: [], categories: [] };
});

const safeChartSeries = computed(() => {
    if (currentChartData.value.series && currentChartData.value.series.length > 0) {
        return currentChartData.value.series[0].data;
    }
    return [];
});

const safeChartCategories = computed(() => currentChartData.value.categories || []);

const chartSeriesComposite = computed(() => [
    { name: 'Net PnL', type: 'column', data: safeChartSeries.value },
    { name: 'Trend', type: 'line', data: safeChartSeries.value }
]);

// --- CHART OPTIONS ---
const chartOptions = computed(() => ({
    chart: {
        type: 'line',
        height: 350,
        // [SOLUSI] Zoom/Pan hanya aktif jika isChartInteractive = true
        zoom: {
            enabled: isChartInteractive.value, 
            type: 'x', 
            autoScaleYaxis: true
        },
        toolbar: { 
            show: isChartInteractive.value, // Toolbar sembunyi jika mode interaksi mati
            tools: {
                download: false,
                selection: true,
                zoom: true,
                zoomin: true,
                zoomout: true,
                pan: true,
                reset: true
            },
            autoSelected: 'pan' 
        },
        fontFamily: 'inherit',
        background: 'transparent',
        animations: { 
            enabled: true,
            easing: 'easeinout',
            speed: 800,
            animateGradually: { enabled: true, delay: 150 },
            dynamicAnimation: { enabled: true, speed: 350 }
        }
    },
    colors: [
        ({ value }: any) => value >= 0 ? '#10B981' : '#EF4444', 
        '#8c52ff'
    ],
    stroke: { width: [0, 3], curve: 'smooth' },
    plotOptions: { 
        bar: { borderRadius: 2, columnWidth: '50%' } 
    },
    dataLabels: { enabled: false },
    
    xaxis: {
        type: 'datetime',
        categories: safeChartCategories.value,
        axisBorder: { show: false },
        axisTicks: { show: false },
        tickAmount: 6, 
        labels: {
            style: { colors: '#6B7280', fontSize: '10px' },
            datetimeFormatter: {
                year: 'yyyy',
                month: "MMM 'yy",
                day: 'dd MMM',
                hour: 'HH:mm'
            }
        },
        tooltip: { enabled: false }
    },
    yaxis: {
        labels: { style: { colors: '#9CA3AF' }, formatter: (val: number) => formatCurrencyShort(val) }
    },
    grid: { 
        borderColor: '#1f2128', 
        strokeDashArray: 4,
        padding: { top: 0, right: 20, bottom: 10, left: 10 }
    },
    tooltip: {
        theme: 'dark',
        shared: true,
        intersect: false,
        x: {
            format: selectedTimeframe.value === 'TODAY' ? 'dd MMM HH:mm' : 'dd MMM yyyy'
        },
        y: { formatter: (val: number) => formatCurrency(val) }
    },
    markers: { size: 0, hover: { size: 5 } },
    legend: { show: false }
}));

// --- HELPERS ---
const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
    localStorage.setItem("sidebar_collapsed", String(isSidebarCollapsed.value));
}

onMounted(() => {
    const saved = localStorage.getItem("sidebar_collapsed");
    if (saved === "true") isSidebarCollapsed.value = true;
});

const formatCurrency = (value: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
const formatCurrencyShort = (value: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', notation: "compact" }).format(value);
const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="min-h-screen bg-[#0a0b0d] text-gray-300 font-sans relative">
        <Sidebar :is-collapsed="isSidebarCollapsed" @toggle="toggleSidebar" />

        <div class="transition-all duration-300 ease-in-out min-h-screen flex flex-col" :class="isSidebarCollapsed ? 'ml-[72px]' : 'ml-64'">
            <Navbar />

            <main class="p-6 lg:p-8 space-y-8 flex-1 pb-20">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="group relative p-[1px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-lg">
                        <div class="bg-[#121317] rounded-xl p-6 h-full relative overflow-hidden flex flex-col justify-center">
                            <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-15 transition-opacity">
                                <svg class="w-24 h-24 text-[#5ce1e6]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2 relative z-10">Total Portfolio Value</p>
                            <h3 class="text-4xl font-black text-white tracking-tight relative z-10">{{ formatCurrency(totalBalance) }}</h3>
                        </div>
                    </div>
                    <div class="group relative p-[1px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-lg">
                        <div class="bg-[#121317] rounded-xl p-6 h-full relative overflow-hidden flex flex-col justify-center">
                            <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-15 transition-opacity">
                                <svg class="w-24 h-24 text-[#8c52ff]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            </div>
                            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2 relative z-10">Active Positions</p>
                            <div class="flex items-center gap-3 relative z-10">
                                <h3 class="text-4xl font-black text-white tracking-tight">{{ activePositions }}</h3>
                                <span v-if="activePositions > 0" class="flex h-3 w-3 relative"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span><span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between items-center">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                        <span class="p-1.5 rounded bg-gradient-to-br from-[#8c52ff] to-[#5ce1e6] text-black"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg></span> Performance Overview
                    </h3>
                    <div class="p-[1px] rounded-lg bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6]">
                        <div class="flex bg-[#1a1b20] p-1 rounded-lg h-full">
                            <button v-for="tf in ['TODAY', 'WEEK', 'MONTH']" :key="tf" @click="selectedTimeframe = tf as any" class="px-4 py-1.5 text-[10px] font-bold uppercase rounded transition-all" :class="selectedTimeframe === tf ? 'bg-[#2d2f36] text-white shadow' : 'text-gray-500 hover:text-gray-300'">{{ tf }}</button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 animate-fade-in-down">
                    <div class="relative group p-[1px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-lg">
                        <div class="bg-[#121317] rounded-xl p-6 h-full relative overflow-hidden flex flex-col justify-between">
                            <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                                <svg class="w-20 h-20 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div class="relative z-10 mb-1">
                                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Gross PnL</p>
                            </div>
                            <div class="relative z-10 mb-3">
                                <h3 class="text-2xl font-black tracking-tight" :class="currentMetrics.gross_pnl >= 0 ? 'text-green-400' : 'text-red-500'">
                                    {{ currentMetrics.gross_pnl >= 0 ? '+' : '' }}{{ formatCurrency(currentMetrics.gross_pnl) }}
                                </h3>
                            </div>
                            <div class="relative z-10 flex items-center">
                                <span class="text-[9px] px-2 py-1 rounded font-bold flex items-center gap-1 bg-[#1a1b20] border border-[#2d2f36]" :class="pnlComparison >= 0 ? 'text-green-400' : 'text-red-400'">
                                    {{ pnlComparison >= 0 ? '▲' : '▼' }} {{ Math.abs(pnlComparison).toFixed(1) }}%
                                    <span class="text-gray-500 font-normal ml-1">vs prev.</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="relative group p-[1px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-[0_0_20px_rgba(140,82,255,0.2)]">
                        <div class="bg-[#121317] rounded-xl p-6 h-full relative overflow-hidden flex flex-col justify-between">
                            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <svg class="w-24 h-24 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div class="relative z-10 mb-1">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Net Profit (After Fee)</p>
                            </div>
                            <div class="relative z-10 mb-3">
                                <h3 class="text-3xl font-black tracking-tight" :class="currentMetrics.net_pnl >= 0 ? 'text-white' : 'text-red-400'">
                                    {{ currentMetrics.net_pnl >= 0 ? '+' : '' }}{{ formatCurrency(currentMetrics.net_pnl) }}
                                </h3>
                            </div>
                            <div class="relative z-10 flex items-center gap-3 flex-wrap">
                                <span class="text-[9px] px-2 py-1 rounded font-bold flex items-center gap-1 bg-[#1a1b20] border border-[#2d2f36]" :class="netComparison >= 0 ? 'text-green-400' : 'text-red-400'">
                                    {{ netComparison >= 0 ? '▲' : '▼' }} {{ Math.abs(netComparison).toFixed(1) }}%
                                </span>
                                <span class="text-[9px] text-gray-400 font-mono border-l border-gray-700 pl-3">Fee: <span class="text-yellow-500">{{ formatCurrency(currentMetrics.fee) }}</span></span>
                            </div>
                        </div>
                    </div>

                    <div class="relative group p-[1px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-lg">
                        <div class="bg-[#121317] rounded-xl p-6 h-full relative overflow-hidden flex flex-col justify-between">
                            <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                                <svg class="w-20 h-20 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                            </div>
                            <div class="relative z-10 mb-1">
                                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">ROI ({{ selectedTimeframe }})</p>
                            </div>
                            <div class="relative z-10 mb-3">
                                <h3 class="text-2xl font-black tracking-tight" :class="currentMetrics.roi >= 0 ? 'text-blue-400' : 'text-orange-500'">
                                    {{ currentMetrics.roi >= 0 ? '+' : '' }}{{ currentMetrics.roi.toFixed(2) }}%
                                </h3>
                            </div>
                            <div class="relative z-10 flex items-center">
                                <span class="text-[9px] px-2 py-1 rounded font-bold flex items-center gap-1 bg-[#1a1b20] border border-[#2d2f36]" :class="roiComparison >= 0 ? 'text-blue-400' : 'text-orange-500'">
                                    {{ roiComparison >= 0 ? '+' : '' }}{{ roiComparison.toFixed(2) }}% vs prev.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative p-[1px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-lg">
                    <div class="bg-[#121317] rounded-xl p-5 h-full">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#8c52ff]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>
                                Net PnL Analysis ({{ selectedTimeframe }})
                            </h3>
                            
                            <button 
                                @click="isChartInteractive = !isChartInteractive"
                                class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all border"
                                :class="isChartInteractive 
                                    ? 'bg-[#8c52ff] text-white border-[#8c52ff] shadow-[0_0_10px_rgba(140,82,255,0.4)]' 
                                    : 'bg-[#1a1b20] text-gray-400 border-[#2d2f36] hover:text-white'"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11" />
                                </svg>
                                {{ isChartInteractive ? 'Interactive: ON' : 'Enable Pan/Zoom' }}
                            </button>
                        </div>
                        
                        <div class="w-full h-[300px]">
                            <VueApexCharts v-if="safeChartSeries.length > 0" type="line" height="100%" :options="chartOptions" :series="chartSeriesComposite" />
                            <div v-else class="h-full flex items-center justify-center text-gray-600 text-xs">
                                No PnL data available for {{ selectedTimeframe }}.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative p-[1px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-sm">
                    <div class="bg-[#121317] rounded-xl overflow-hidden h-full">
                        <div class="p-5 border-b border-[#1f2128] flex justify-between items-center bg-[#1a1b20]/50">
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">Recent Activity</h3>
                            <a :href="route('trade.log')" class="text-[10px] bg-[#1f2128] hover:bg-[#2d2f36] px-3 py-1.5 rounded text-gray-400 hover:text-white font-bold uppercase transition-colors border border-[#2d2f36]">View Logs</a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left">
                                <thead class="bg-[#1a1b20] text-gray-500 uppercase text-[9px] tracking-wider font-bold">
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
                                    <tr v-for="trade in props.recentTrades" :key="trade.id" class="hover:bg-[#1a1b20]/50 transition-colors group text-xs">
                                        <td class="px-6 py-4">
                                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded border" :class="trade.category === 'SPOT' ? 'bg-blue-500/10 text-blue-400 border-blue-500/20' : 'bg-purple-500/10 text-purple-400 border-purple-500/20'">{{ trade.category }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-400 font-mono">{{ formatDate(trade.display_date) }}</td>
                                        <td class="px-6 py-4"><div class="font-bold text-white">{{ trade.symbol }}</div></td>
                                        <td class="px-6 py-4"><span class="font-bold uppercase" :class="['BUY', 'LONG'].includes(trade.type) ? 'text-green-500' : 'text-red-500'">{{ trade.type }}</span></td>
                                        <td class="px-6 py-4 text-right font-mono text-gray-300">{{ formatCurrency(trade.entry_price || trade.price) }}</td>
                                        <td class="px-6 py-4 text-right font-mono font-bold text-white">{{ formatCurrency(trade.margin || trade.margin) }}</td>
                                    </tr>
                                    <tr v-if="props.recentTrades.length === 0"><td colspan="6" class="px-6 py-12 text-center text-gray-500">No activity found.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </main>
            <Footer :is-sidebar-collapsed="isSidebarCollapsed" />
        </div>
    </div>
</template>

<style scoped>
@keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in-down { animation: fadeInDown 0.4s ease-out forwards; }
</style>
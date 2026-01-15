<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue'; 
// Import Partial MonthlyView (Hanya ini yang dibutuhkan)
import MonthlyView from './Partials/MonthlyView.vue';

// --- STATE SIDEBAR & MEMORY ---
const isSidebarOpen = ref(true); 

onMounted(() => {
    const savedState = localStorage.getItem('sidebarState');
    if (savedState === 'closed') {
        isSidebarOpen.value = false;
    }
});

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
    localStorage.setItem('sidebarState', isSidebarOpen.value ? 'open' : 'closed');
};

// --- PROPS DARI LARAVEL ---
const props = defineProps<{
    availableYears: number[];
    selectedYear: number;
    monthlyOverview: any[];
    dailyData: Record<string, { trades: number, pnl: number }>; // Data Harian
    accounts: any[];
    filters: {
        account_id: string;
        market_type: string;
    };
}>();

// --- STATE NAVIGASI (Yearly vs Monthly) ---
const currentView = ref<'yearly' | 'monthly'>('yearly');
const selectedMonthData = ref<any>(null);

// Fungsi: Masuk ke Detail Bulan
const openMonth = (month: any) => {
    selectedMonthData.value = month;
    currentView.value = 'monthly';
};

// Fungsi: Kembali ke Index (Tahun)
const backToYearly = () => {
    currentView.value = 'yearly';
    selectedMonthData.value = null;
};

// --- FILTER LOGIC ---
const filterForm = ref({
    year: props.selectedYear,
    account_id: props.filters.account_id,
    market_type: props.filters.market_type,
});

watch(filterForm, (newVal) => {
    // Kalau filter berubah, paksa reset ke yearly view
    currentView.value = 'yearly';
    router.get(route('trade-calendar.index'), newVal, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, { deep: true });

// Helper Currency
const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-US', { 
        style: 'currency', 
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};
</script>

<template>
    <Head title="Trade Calendar" />

    <div class="flex h-screen bg-[#0a0b0d] font-sans">
        
        <Sidebar 
            :isCollapsed="!isSidebarOpen" 
            @toggle="toggleSidebar"
        />

        <div 
            class="flex-1 flex flex-col overflow-hidden relative transition-all duration-300 ease-in-out"
            :class="isSidebarOpen ? 'ml-64' : 'ml-[72px]'"
        >
            
            <Navbar 
                :isSidebarOpen="isSidebarOpen" 
                @toggleSidebar="toggleSidebar" 
            />

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-[#0a0b0d] p-6">
                <div class="max-w-7xl mx-auto">
                    
                    <div v-if="currentView === 'yearly'" class="flex flex-col md:flex-row justify-between items-end md:items-center mb-8 gap-4">
                        <div>
                            <h2 class="text-2xl font-bold text-white tracking-tight">Trade Calendar</h2>
                            <p class="text-sm text-gray-500 mt-1">Consistency Tracker</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <div class="relative">
                                <select v-model="filterForm.year" class="bg-[#121317] border border-[#1f2128] text-white text-xs font-bold rounded-lg px-3 py-2 outline-none focus:border-[#8c52ff] cursor-pointer appearance-none pr-8">
                                    <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
                                </select>
                            </div>

                            <div class="relative">
                                <select v-model="filterForm.market_type" class="bg-[#121317] border border-[#1f2128] text-white text-xs font-bold rounded-lg px-3 py-2 outline-none focus:border-[#8c52ff] cursor-pointer appearance-none pr-8">
                                    <option value="all">All Markets</option>
                                    <option value="Spot">Spot</option>
                                    <option value="Futures">Futures</option>
                                </select>
                            </div>

                            <div class="relative">
                                <select v-model="filterForm.account_id" class="bg-[#121317] border border-[#1f2128] text-white text-xs font-bold rounded-lg px-3 py-2 outline-none focus:border-[#8c52ff] cursor-pointer appearance-none pr-8 max-w-[150px] truncate">
                                    <option value="all">All Accounts</option>
                                    <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div v-if="currentView === 'yearly'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-10">
                        <div v-for="month in monthlyOverview" :key="month.month_index" 
                             @click="openMonth(month)"
                             class="bg-[#121317] border border-[#1f2128] rounded-xl p-5 hover:border-[#8c52ff] transition-all duration-200 cursor-pointer group relative overflow-hidden">
                            
                            <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-lg font-bold text-white">{{ month.month_name }}</h3>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded border uppercase"
                                    :class="{
                                        'bg-green-500/10 text-green-500 border-green-500/20': month.status === 'PROFIT',
                                        'bg-red-500/10 text-red-500 border-red-500/20': month.status === 'LOSS',
                                        'bg-gray-800 text-gray-500 border-gray-700': month.status === 'NO_TRADE'
                                    }">
                                    {{ month.status === 'NO_TRADE' ? '-' : month.status }}
                                </span>
                            </div>

                            <div class="space-y-1">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-500">Total PnL</span>
                                    <span class="text-sm font-mono font-bold"
                                        :class="{
                                            'text-green-400': month.total_pnl > 0,
                                            'text-red-400': month.total_pnl < 0,
                                            'text-gray-500': month.total_pnl == 0
                                        }">
                                        {{ month.total_pnl > 0 ? '+' : '' }}{{ formatCurrency(month.total_pnl) }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-500">Trades</span>
                                    <span class="text-sm font-bold text-white">{{ month.total_trades }}</span>
                                </div>
                            </div>
                            
                            <div class="mt-4 opacity-0 group-hover:opacity-100 transition-opacity flex justify-end">
                                <span class="text-[10px] text-[#8c52ff] font-bold uppercase tracking-wider flex items-center gap-1">
                                    Open Calendar 
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </span>
                            </div>
                        </div>
                    </div>

                    <MonthlyView 
                        v-if="currentView === 'monthly'"
                        :year="selectedYear"
                        :month="selectedMonthData"
                        :dailyData="dailyData" 
                        :onBack="backToYearly"
                    />
                    
                    <Footer :isSidebarCollapsed="!isSidebarOpen" />

                </div>
            </main>
        </div>
    </div>
</template>
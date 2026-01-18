<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';

// --- IMPORT PARTIALS (Placeholder - Pastikan file ini ada atau buat file kosong dulu) ---
// import KpiCards from './Partials/KpiCards.vue';
// import EquityCurve from './Partials/EquityCurve.vue';
// import WinLossChart from './Partials/WinLossChart.vue';
// import AssetPerformance from './Partials/AssetPerformance.vue';

const props = defineProps<{
    // Props nanti diisi dari Controller
}>();

// --- STATE UI (Sidebar Logic) ---
const isSidebarCollapsed = ref(false);

// Cek LocalStorage saat mounted agar state sidebar persisten antar halaman
onMounted(() => {
    const saved = localStorage.getItem("sidebar_collapsed");
    if (saved === "true") isSidebarCollapsed.value = true;
});

// Fungsi Toggle Sidebar
const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
    localStorage.setItem("sidebar_collapsed", String(isSidebarCollapsed.value));
};

const filterForm = ref({
    year: new Date().getFullYear(),
    account_id: 'all',
    strategy_type: 'all'
});
</script>

<template>
    <Head title="Performance Report" />

    <div class="min-h-screen bg-[#0a0b0d] text-gray-300 font-sans relative">
        
        <Sidebar 
            :is-collapsed="isSidebarCollapsed" 
            @toggle="toggleSidebar" 
        />

        <div class="transition-all duration-300 ease-in-out min-h-screen flex flex-col"
            :class="isSidebarCollapsed ? 'ml-[72px]' : 'ml-64'">
            
            <Navbar 
                :is-sidebar-open="!isSidebarCollapsed" 
                @toggle-sidebar="toggleSidebar" 
            />

            <main class="p-6 lg:p-8 space-y-8 flex-1 pb-20">
                
                <div class="flex flex-col md:flex-row justify-between items-end md:items-center gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-white tracking-tight">Performance Report</h2>
                        <p class="text-sm text-gray-500 mt-1">Deep dive into your trading metrics.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <select v-model="filterForm.year" class="bg-[#1a1b20] border border-[#2d2f36] text-white text-xs font-bold rounded-lg px-4 py-2.5 outline-none focus:border-[#8c52ff] cursor-pointer">
                            <option value="2026">2026</option>
                            <option value="2025">2025</option>
                        </select>

                        <select v-model="filterForm.strategy_type" class="bg-[#1a1b20] border border-[#2d2f36] text-white text-xs font-bold rounded-lg px-4 py-2.5 outline-none focus:border-[#8c52ff] cursor-pointer">
                            <option value="all">All Strategies</option>
                            <option value="Spot">Spot</option>
                            <option value="Futures">Futures</option>
                        </select>

                        <select v-model="filterForm.account_id" class="bg-[#1a1b20] border border-[#2d2f36] text-white text-xs font-bold rounded-lg px-4 py-2.5 outline-none focus:border-[#8c52ff] cursor-pointer">
                            <option value="all">All Accounts</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-[#121317] border border-[#1f2128] rounded-xl p-6 h-32 flex flex-col justify-center">
                        <span class="text-gray-500 text-xs uppercase font-bold">Net PnL</span>
                        <span class="text-2xl font-bold text-white mt-1">$0.00</span>
                    </div>
                    <div class="bg-[#121317] border border-[#1f2128] rounded-xl p-6 h-32 flex flex-col justify-center">
                        <span class="text-gray-500 text-xs uppercase font-bold">Win Rate</span>
                        <span class="text-2xl font-bold text-white mt-1">0%</span>
                    </div>
                    <div class="bg-[#121317] border border-[#1f2128] rounded-xl p-6 h-32 flex flex-col justify-center">
                        <span class="text-gray-500 text-xs uppercase font-bold">Profit Factor</span>
                        <span class="text-2xl font-bold text-white mt-1">0.00</span>
                    </div>
                    <div class="bg-[#121317] border border-[#1f2128] rounded-xl p-6 h-32 flex flex-col justify-center">
                        <span class="text-gray-500 text-xs uppercase font-bold">Total Trades</span>
                        <span class="text-2xl font-bold text-white mt-1">0</span>
                    </div>
                </div>

                <div class="bg-[#121317] border border-[#1f2128] rounded-xl p-6 h-64 flex items-center justify-center text-gray-500">
                    Equity Curve Chart Placeholder
                </div>

            </main>

            <Footer :is-sidebar-collapsed="isSidebarCollapsed" />
        </div>
    </div>
</template>
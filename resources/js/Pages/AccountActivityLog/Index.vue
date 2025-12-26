<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue'; // <--- Import Navbar Baru
import { computed } from 'vue';

// Import Komponen Parsial
import ActivityForm from './Partials/ActivityForm.vue';
import ActivityTable from './Partials/ActivityTable.vue';

// --- TERIMA DATA DARI CONTROLLER (DATABASE) ---
const props = defineProps<{
    accounts: any[];      // Data akun (untuk dropdown form)
    transactions: any[];  // Riwayat transaksi (untuk tabel)
}>();

// --- LOGIC ---

// Hitung Net Flow berdasarkan data REAL dari database
const netFlow = computed(() => {
    return props.transactions.reduce((total, tx) => {
        // Pastikan angka desimal dihitung dengan benar
        const amount = Number(tx.amount);
        return tx.type === 'DEPOSIT' 
            ? total + amount 
            : total - amount;
    }, 0);
});
</script>

<template>
    <Head title="Account Activity Log" />

    <div class="min-h-screen bg-[#0a0b0d] text-gray-300 font-sans flex">
        
        <Sidebar />

        <main class="flex-1 ml-[72px] lg:ml-64 flex flex-col min-h-screen">
            <Navbar />
            <header class="h-16 flex items-center justify-between px-6 lg:px-8 border-b border-[#1f2128] bg-[#0a0b0d] sticky top-0 z-20">
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-bold text-white tracking-tight">Account Activity Log</h2>
                    <span class="px-2 py-0.5 rounded text-[10px] bg-blue-900/30 text-blue-400 border border-blue-500/20 font-mono">
                        TR-REGISTRY
                    </span>
                </div>
            </header>

            <div class="p-6 lg:p-8">
                
                <div class="mb-8 grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div class="bg-[#121317] border border-[#1f2128] rounded-xl p-5 flex items-center justify-between">
                        <div>
                            <div class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Net Flow (All Time)</div>
                            <div class="text-2xl font-bold mt-1" :class="netFlow >= 0 ? 'text-white' : 'text-red-500'">
                                ${{ netFlow.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                            </div>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-[#1a1b20] flex items-center justify-center text-gray-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                </div>

                <ActivityForm 
                    :accounts="props.accounts" 
                />

                <ActivityTable 
                    :transactions="props.transactions" 
                />

            </div>
        </main>
    </div>
</template>
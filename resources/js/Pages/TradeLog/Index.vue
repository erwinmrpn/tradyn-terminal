<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';
import { ref, watch } from 'vue';

// Props dari Controller
const props = defineProps<{
    trades: any[];
    activeType: string;         // 'SPOT' atau 'FUTURES'
    accounts: any[];            // List akun untuk dropdown
    totalBalance: number;       // Total saldo hasil filter
    selectedAccountId: string;  // ID akun yang sedang dipilih ('all' atau ID angka)
}>();

// State Lokal
const selectedAccount = ref(props.selectedAccountId);

// Fungsi Ganti Tab (SPOT/FUTURES)
// Saat ganti tab, reset filter akun ke 'all'
const switchTab = (type: string) => {
    router.get(route('trade.log'), { type: type, account_id: 'all' }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Fungsi Ganti Filter Akun (Dropdown)
// Mengirim request baru ke backend saat dropdown berubah
watch(selectedAccount, (newAccount) => {
    router.get(route('trade.log'), { 
        type: props.activeType, 
        account_id: newAccount 
    }, {
        preserveState: true,
        preserveScroll: true,
    });
});

// Helper Format Uang
const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
};
</script>

<template>
    <Head title="Trade Log" />

    <div class="min-h-screen bg-[#0a0b0d] text-gray-300 font-sans flex">
        <Sidebar />

        <main class="flex-1 ml-[72px] lg:ml-64 flex flex-col min-h-screen">
            <Navbar />

            <div class="pt-8 px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-white tracking-tight">Trade Log</h2>
                    <p class="text-sm text-gray-500 mt-1">Record and analyze your trading history.</p>
                </div>
                
                <button class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-lg shadow-blue-500/20 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    Record Trade
                </button>
            </div>

            <div class="p-6 lg:p-8">
                
                <div class="flex justify-center mb-8">
                    <div class="bg-[#1a1b20] p-1.5 rounded-full flex items-center w-full max-w-sm border border-[#2d2f36] relative shadow-inner">
                        <button @click="switchTab('SPOT')" class="flex-1 py-2 rounded-full text-sm font-bold transition-all duration-300 ease-out z-10 relative" :class="props.activeType === 'SPOT' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">SPOT</button>
                        <button @click="switchTab('FUTURES')" class="flex-1 py-2 rounded-full text-sm font-bold transition-all duration-300 ease-out z-10 relative" :class="props.activeType === 'FUTURES' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">FUTURES</button>
                        <div class="absolute top-1.5 bottom-1.5 w-[calc(50%-6px)] bg-emerald-500 rounded-full transition-all duration-300 ease-out shadow-[0_0_15px_rgba(16,185,129,0.4)]" :class="props.activeType === 'SPOT' ? 'left-1.5' : 'left-[calc(50%+3px)]'"></div>
                    </div>
                </div>

                <div class="bg-[#121317] border border-[#1f2128] rounded-xl p-6 mb-6 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm">
                    
                    <div>
                        <div class="text-xs text-gray-500 uppercase font-semibold tracking-wider flex items-center gap-2">
                            Total {{ props.activeType }} Balance
                            <span v-if="selectedAccount !== 'all'" class="bg-[#1a1b20] border border-[#2d2f36] px-2 py-0.5 rounded text-[10px] text-blue-400">
                                {{ props.accounts.find(a => a.id == selectedAccount)?.name }}
                            </span>
                        </div>
                        <div class="text-3xl font-bold text-white mt-1">
                            {{ formatCurrency(props.totalBalance) }}
                        </div>
                    </div>

                    <div class="w-full sm:w-auto">
                        <label class="text-xs text-gray-500 mb-1 block sm:hidden">Select Exchange</label>
                        <div class="relative">
                            <select 
                                v-model="selectedAccount"
                                class="bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full sm:w-64 p-2.5 pr-8 appearance-none cursor-pointer hover:border-gray-500 transition"
                            >
                                <option value="all">All Exchanges / Accounts</option>
                                <option v-for="acc in props.accounts" :key="acc.id" :value="acc.id">
                                    {{ acc.name }} ({{ acc.exchange }})
                                </option>
                            </select>
                            
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="bg-[#121317] border border-[#1f2128] rounded-xl overflow-hidden shadow-sm">
                    <div class="p-6 border-b border-[#1f2128]">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full" :class="props.activeType === 'SPOT' ? 'bg-emerald-500' : 'bg-purple-500'"></span>
                            {{ props.activeType }} History
                        </h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left">
                            <thead class="bg-[#1a1b20] text-gray-400 uppercase text-xs">
                                <tr>
                                    <th class="px-6 py-4 font-medium tracking-wider">Pair</th>
                                    <th class="px-6 py-4 font-medium tracking-wider">Date</th>
                                    <th class="px-6 py-4 font-medium tracking-wider">Position</th>
                                    <th class="px-6 py-4 font-medium tracking-wider text-right">Entry Price</th>
                                    <th class="px-6 py-4 font-medium tracking-wider text-right">Exit Price</th>
                                    <th class="px-6 py-4 font-medium tracking-wider text-right">PnL</th>
                                    <th class="px-6 py-4 font-medium tracking-wider text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#1f2128]">
                                <tr v-for="trade in props.trades" :key="trade.id" class="hover:bg-[#1a1b20]/50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-white">
                                        {{ trade.pair }}
                                        <div class="text-[10px] text-gray-500 font-normal">{{ trade.trading_account.name }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-400 text-sm">{{ trade.entry_date }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-0.5 text-xs font-bold rounded uppercase" :class="trade.type === 'LONG' ? 'text-green-400 bg-green-900/20' : 'text-red-400 bg-red-900/20'">{{ trade.type }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-gray-300 font-mono text-sm">{{ trade.entry_price }}</td>
                                    <td class="px-6 py-4 text-right text-gray-300 font-mono text-sm">{{ trade.exit_price || '-' }}</td>
                                    <td class="px-6 py-4 text-right font-bold" :class="Number(trade.pnl) >= 0 ? 'text-green-500' : 'text-red-500'">{{ Number(trade.pnl) >= 0 ? '+' : '' }}{{ formatCurrency(trade.pnl) }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded border" :class="trade.status === 'CLOSED' ? 'bg-gray-800 text-gray-400 border-gray-600' : 'bg-yellow-900/30 text-yellow-400 border-yellow-500/20'">{{ trade.status }}</span>
                                    </td>
                                </tr>
                                <tr v-if="props.trades.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">No {{ props.activeType.toLowerCase() }} trades recorded yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>
</template>
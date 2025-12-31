<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';
import { ref, watch, onMounted, computed } from 'vue';

// --- IMPORT ANAK-ANAK (PARTIALS) ---
import SpotForm from './Partials/SpotForm.vue';
import FuturesOpen from './Partials/FuturesOpen.vue';

// --- PROPS ---
const props = defineProps<{
    trades: any[];
    activeType: string;
    accounts: any[];
    totalBalance: number;
    selectedAccountId: string;
}>();

// --- FILTERED ACCOUNTS ---
const filteredAccounts = computed(() => {
    if (!props.accounts) return [];
    return props.accounts.filter(acc => acc.strategy_type === props.activeType);
});

// --- SIDEBAR ---
const isSidebarCollapsed = ref(false);
onMounted(() => {
    const saved = localStorage.getItem("sidebar_collapsed");
    if (saved === "true") isSidebarCollapsed.value = true;
});
const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
    localStorage.setItem("sidebar_collapsed", String(isSidebarCollapsed.value));
}

// --- LOGIC FILTER ---
const selectedAccount = ref(props.selectedAccountId);

const switchTab = (type: string) => {
    router.get(route('trade.log'), { type: type, account_id: 'all' }, { preserveState: true, preserveScroll: true });
};

watch(selectedAccount, (newAccount) => {
    if (newAccount && newAccount !== props.selectedAccountId) {
        router.get(route('trade.log'), { type: props.activeType, account_id: newAccount }, { preserveState: true, preserveScroll: true });
    }
});

const futuresTab = ref<'OPEN' | 'CLOSE' | 'RESULT'>('OPEN');

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
};
</script>

<template>
    <Head title="Trade Log" />

    <div class="min-h-screen bg-[#0a0b0d] text-gray-300 font-sans relative">
        <Sidebar :is-collapsed="isSidebarCollapsed" @toggle="toggleSidebar" />

        <div class="transition-all duration-300 ease-in-out min-h-screen flex flex-col"
            :class="isSidebarCollapsed ? 'ml-[72px]' : 'ml-64'">
            
            <Navbar />

            <main class="p-6 lg:p-8 space-y-8 flex-1 pb-20">
                
                <div class="flex flex-col items-center justify-center space-y-6">
                    <div class="bg-[#1a1b20] p-1.5 rounded-full flex items-center w-full max-w-sm border border-[#2d2f36] relative shadow-inner">
                        <button @click="switchTab('SPOT')" class="flex-1 py-2 rounded-full text-sm font-bold z-10 relative transition-colors" :class="props.activeType === 'SPOT' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">SPOT</button>
                        <button @click="switchTab('FUTURES')" class="flex-1 py-2 rounded-full text-sm font-bold z-10 relative transition-colors" :class="props.activeType === 'FUTURES' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">FUTURES</button>
                        <div class="absolute top-1.5 bottom-1.5 w-[calc(50%-6px)] bg-emerald-500 rounded-full transition-all duration-300 ease-out shadow-[0_0_15px_rgba(16,185,129,0.4)]" :class="props.activeType === 'SPOT' ? 'left-1.5' : 'left-[calc(50%+3px)]'"></div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-end justify-between gap-4 border-b border-[#1f2128] pb-4">
                    <div>
                        <div class="text-xs text-gray-500 uppercase font-semibold tracking-wider">Total {{ props.activeType }} Balance</div>
                        <div class="text-3xl font-bold text-white mt-1">{{ formatCurrency(props.totalBalance) }}</div>
                    </div>
                    <div class="relative w-full sm:w-64">
                         <select v-model="selectedAccount" class="bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 pr-8 appearance-none cursor-pointer">
                            <option value="all">All Accounts</option>
                            <option v-for="acc in filteredAccounts" :key="acc.id" :value="acc.id">{{ acc.name }} ({{ acc.exchange }})</option>
                        </select>
                         <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                    </div>
                </div>

                <SpotForm v-if="props.activeType === 'SPOT'" :accounts="filteredAccounts" />

                <div v-if="props.activeType === 'FUTURES'">
                    <div class="flex justify-center mb-8">
                        <div class="bg-[#1a1b20] p-1.5 rounded-full flex items-center w-full max-w-md border border-[#2d2f36] relative shadow-inner">
                            <button @click="futuresTab = 'OPEN'" class="flex-1 py-2 rounded-full text-xs sm:text-sm font-bold z-10 relative transition-colors" :class="futuresTab === 'OPEN' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">Open Position</button>
                            <button @click="futuresTab = 'CLOSE'" class="flex-1 py-2 rounded-full text-xs sm:text-sm font-bold z-10 relative transition-colors" :class="futuresTab === 'CLOSE' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">Close Position</button>
                            <button @click="futuresTab = 'RESULT'" class="flex-1 py-2 rounded-full text-xs sm:text-sm font-bold z-10 relative transition-colors" :class="futuresTab === 'RESULT' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">Result</button>
                            <div class="absolute top-1.5 bottom-1.5 w-[calc(33.33%-4px)] bg-blue-600 rounded-full transition-all duration-300 ease-out shadow-[0_0_15px_rgba(37,99,235,0.4)]" :class="{'left-1.5': futuresTab === 'OPEN', 'left-[calc(33.33%+2px)]': futuresTab === 'CLOSE', 'left-[calc(66.66%+2px)]': futuresTab === 'RESULT'}"></div>
                        </div>
                    </div>

                    <FuturesOpen v-if="futuresTab === 'OPEN'" :accounts="filteredAccounts" />

                    <div v-else-if="futuresTab === 'CLOSE'" class="text-center py-12 text-gray-500"><i class="fas fa-lock text-2xl mb-2"></i><p>Close Position Module Coming Soon</p></div>
                    <div v-else-if="futuresTab === 'RESULT'" class="text-center py-12 text-gray-500"><i class="fas fa-chart-line text-2xl mb-2"></i><p>Reflection & Result Module Coming Soon</p></div>
                </div>

                <div class="bg-[#121317] border border-[#1f2128] rounded-xl overflow-hidden shadow-sm min-h-[400px]">
                    <div class="p-4 border-b border-[#1f2128] bg-[#1a1b20]/50 flex justify-between items-center">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">History Log</h3>
                        <span class="text-[10px] text-gray-600">Recent activity</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left">
                            <thead class="bg-[#1a1b20] text-gray-400 uppercase text-[10px] tracking-wider font-semibold">
                                <tr>
                                    <th class="px-6 py-3">Asset</th>
                                    <th class="px-6 py-3">Date</th>
                                    <th class="px-6 py-3">Type</th>
                                    <th class="px-6 py-3 text-right">Price</th>
                                    <th class="px-6 py-3 text-right">Size/Qty</th>
                                    <template v-if="props.activeType === 'SPOT'"><th class="px-6 py-3 text-right">Total</th><th class="px-6 py-3 text-right">Fee</th></template>
                                    <template v-else><th class="px-6 py-3 text-right">Margin</th><th class="px-6 py-3 text-center">Lev</th><th class="px-6 py-3 text-right">Chart</th></template>
                                    <th class="px-6 py-3">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#1f2128]">
                                <tr v-for="trade in props.trades" :key="trade.id" class="hover:bg-[#1a1b20]/50 transition-colors group text-sm">
                                    <td class="px-6 py-3 font-bold text-white">{{ trade.symbol }}<span class="text-[10px] text-gray-500 font-normal ml-1 bg-[#1f2128] px-1 rounded">{{ trade.trading_account ? trade.trading_account.name : 'Account' }}</span></td>
                                    <td class="px-6 py-3 text-gray-400 text-xs">{{ props.activeType === 'SPOT' ? trade.date : trade.entry_date }}</td>
                                    <td class="px-6 py-3"><span class="px-2 py-0.5 text-[10px] font-bold rounded uppercase border" :class="['BUY', 'LONG'].includes(trade.type) ? 'text-green-400 bg-green-900/10 border-green-500/20' : 'text-red-400 bg-red-900/10 border-red-500/20'">{{ trade.type }}</span></td>
                                    <td class="px-6 py-3 text-right text-gray-300 font-mono">{{ formatCurrency(props.activeType === 'SPOT' ? trade.price : trade.entry_price) }}</td>
                                    <td class="px-6 py-3 text-right text-gray-300 font-mono">{{ Number(trade.quantity) }}</td>
                                    <template v-if="props.activeType === 'SPOT'">
                                        <td class="px-6 py-3 text-right text-blue-400 font-bold font-mono text-xs">{{ formatCurrency(trade.total) }}</td>
                                        <td class="px-6 py-3 text-right font-bold text-yellow-500 text-xs font-mono">{{ formatCurrency(trade.fee) }}</td>
                                    </template>
                                    <template v-else>
                                        <td class="px-6 py-3 text-right text-blue-400 font-bold font-mono text-xs">{{ formatCurrency(trade.margin) }}</td>
                                        <td class="px-6 py-3 text-center text-yellow-500 text-xs font-bold">{{ trade.leverage }}x</td>
                                        <td class="px-6 py-3 text-right font-bold text-yellow-500 text-xs font-mono">
                                            <a v-if="trade.entry_screenshot" :href="'/storage/' + trade.entry_screenshot" target="_blank" class="text-blue-400 hover:text-blue-300 underline">View</a>
                                            <span v-else class="text-gray-600">-</span>
                                        </td>
                                    </template>
                                    <td class="px-6 py-3 text-gray-500 text-xs italic truncate max-w-[150px]">{{ trade.notes }}</td>
                                </tr>
                                <tr v-if="props.trades.length === 0"><td colspan="8" class="px-6 py-12 text-center text-gray-500">No {{ props.activeType.toLowerCase() }} trades recorded yet.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
            <Footer :is-sidebar-collapsed="isSidebarCollapsed" />
        </div>
    </div>
</template>
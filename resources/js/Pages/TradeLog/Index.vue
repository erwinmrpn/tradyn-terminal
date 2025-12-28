<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';
import { ref, watch } from 'vue';

// --- PROPS ---
const props = defineProps<{
    trades: any[];
    activeType: string;
    accounts: any[];
    totalBalance: number;
    selectedAccountId: string;
}>();

// --- STATE & FILTER ---
const selectedAccount = ref(props.selectedAccountId);

const switchTab = (type: string) => {
    router.get(route('trade.log'), { type: type, account_id: 'all' }, { preserveState: true, preserveScroll: true });
};

watch(selectedAccount, (newAccount) => {
    router.get(route('trade.log'), { type: props.activeType, account_id: newAccount }, { preserveState: true, preserveScroll: true });
});

// --- FORM INPUT LOGIC ---
const form = useForm({
    trading_account_id: '',
    symbol: '',
    market_type: 'CRYPTO',
    type: 'BUY',
    date: new Date().toISOString().split('T')[0],
    price: '',
    quantity: '',
    fee: '', // <--- Ganti Total dengan Fee (Input Manual)
    notes: '',
    form_type: 'SPOT'
});

const submitTrade = () => {
    if (!form.trading_account_id || !form.symbol || !form.price || !form.quantity) {
        alert('Please fill in Account, Asset, Price, and Quantity');
        return;
    }

    form.post(route('trade.log.store'), {
        onSuccess: () => {
            // Reset form
            form.reset('symbol', 'price', 'quantity', 'fee', 'notes');
            document.getElementById('input-symbol')?.focus();
        },
        preserveScroll: true
    });
};

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

            <div class="p-6 lg:p-8 space-y-8">

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
                            <option v-for="acc in props.accounts" :key="acc.id" :value="acc.id">{{ acc.name }} ({{ acc.exchange }})</option>
                        </select>
                         <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                    </div>
                </div>

                <div v-if="props.activeType === 'SPOT'" class="bg-[#121317] border border-[#1f2128] rounded-xl p-5 shadow-lg">
                    <form @submit.prevent="submitTrade">
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3 mb-4">
                            
                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1 uppercase tracking-wider font-bold">Date</label>
                                <input v-model="form.date" type="date" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-xs rounded p-2.5 focus:border-blue-500 outline-none h-10">
                            </div>

                            <div class="lg:col-span-1">
                                <label class="block text-[10px] text-gray-500 mb-1 uppercase tracking-wider font-bold">Account</label>
                                <select v-model="form.trading_account_id" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-xs rounded p-2.5 focus:border-blue-500 outline-none h-10 appearance-none">
                                    <option value="" disabled>Select</option>
                                    <option v-for="acc in props.accounts.filter(a => a.strategy_type === 'SPOT')" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1 uppercase tracking-wider font-bold">Type</label>
                                <select v-model="form.type" 
                                    class="w-full bg-[#1a1b20] border border-[#2d2f36] text-xs rounded p-2.5 focus:border-blue-500 outline-none h-10 font-bold appearance-none text-center"
                                    :class="form.type === 'BUY' ? 'text-green-500' : 'text-red-500'"
                                >
                                    <option value="BUY" class="text-green-500">BUY</option>
                                    <option value="SELL" class="text-red-500">SELL</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1 uppercase tracking-wider font-bold">Asset</label>
                                <input id="input-symbol" v-model="form.symbol" type="text" placeholder="BTC" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-xs rounded p-2.5 focus:border-blue-500 outline-none uppercase font-bold h-10">
                            </div>

                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1 uppercase tracking-wider font-bold">Mkt</label>
                                <select v-model="form.market_type" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-gray-300 text-xs rounded p-2.5 focus:border-blue-500 outline-none h-10 appearance-none">
                                    <option value="CRYPTO">CRYPTO</option>
                                    <option value="STOCK">STOCK</option>
                                    <option value="COMMODITY">COMM</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1 uppercase tracking-wider font-bold">Price</label>
                                <input v-model="form.price" type="number" step="any" placeholder="0.00" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-xs rounded p-2.5 text-right focus:border-blue-500 outline-none font-mono h-10">
                            </div>

                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1 uppercase tracking-wider font-bold">Qty</label>
                                <input v-model="form.quantity" type="number" step="any" placeholder="0.00" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-xs rounded p-2.5 text-right focus:border-blue-500 outline-none font-mono h-10">
                            </div>

                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1 uppercase tracking-wider font-bold">Fee</label>
                                <input v-model="form.fee" type="number" step="any" placeholder="0.00" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-yellow-500 text-xs rounded p-2.5 text-right focus:border-blue-500 outline-none font-mono h-10">
                            </div>

                        </div>

                        <div class="mb-4">
                            <input 
                                v-model="form.notes" 
                                type="text" 
                                placeholder="Add optional notes here..." 
                                class="w-full bg-[#1a1b20] border border-[#2d2f36] text-gray-300 text-sm rounded-lg p-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all"
                            >
                        </div>

                        <div>
                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="w-full py-3 rounded-lg text-sm font-bold text-white shadow-lg transition-all flex items-center justify-center gap-2 tracking-wide uppercase"
                                :class="form.type === 'BUY' 
                                    ? 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/20' 
                                    : 'bg-red-600 hover:bg-red-700 shadow-red-500/20'"
                            >
                                <svg v-if="form.processing" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span v-else>Confirm {{ form.type }} Trade</span>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-[#121317] border border-[#1f2128] rounded-xl overflow-hidden shadow-sm min-h-[400px]">
                    <div class="p-4 border-b border-[#1f2128] bg-[#1a1b20]/50 flex justify-between items-center">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">History Log</h3>
                        <span class="text-[10px] text-gray-600">Showing recent trades</span>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left">
                            <thead class="bg-[#1a1b20] text-gray-400 uppercase text-[10px] tracking-wider font-semibold">
                                <tr>
                                    <th class="px-6 py-3">Asset</th>
                                    <th class="px-6 py-3">Date</th>
                                    <th class="px-6 py-3">Type</th>
                                    
                                    <template v-if="props.activeType === 'SPOT'">
                                        <th class="px-6 py-3 text-right">Price</th>
                                        <th class="px-6 py-3 text-right">Qty</th>
                                        <th class="px-6 py-3 text-right">Fee</th> <th class="px-6 py-3">Notes</th>
                                    </template>
                                    <template v-else>
                                        <th class="px-6 py-3 text-right">Entry</th>
                                        <th class="px-6 py-3 text-right">PnL</th>
                                        <th class="px-6 py-3 text-center">Status</th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#1f2128]">
                                <tr v-for="trade in props.trades" :key="trade.id" class="hover:bg-[#1a1b20]/50 transition-colors group text-sm">
                                    
                                    <td class="px-6 py-3 font-bold text-white">
                                        {{ trade.symbol }}
                                        <span class="text-[10px] text-gray-500 font-normal ml-1 bg-[#1f2128] px-1 rounded">{{ trade.trading_account.name }}</span>
                                    </td>
                                    
                                    <td class="px-6 py-3 text-gray-400 text-xs">
                                        {{ props.activeType === 'SPOT' ? trade.date : trade.entry_date }}
                                    </td>

                                    <td class="px-6 py-3">
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded uppercase border"
                                            :class="['BUY', 'LONG'].includes(trade.type) 
                                            ? 'text-green-400 bg-green-900/10 border-green-500/20' 
                                            : 'text-red-400 bg-red-900/10 border-red-500/20'">
                                            {{ trade.type }}
                                        </span>
                                    </td>

                                    <template v-if="props.activeType === 'SPOT'">
                                        <td class="px-6 py-3 text-right text-gray-300 font-mono">{{ formatCurrency(trade.price) }}</td>
                                        <td class="px-6 py-3 text-right text-gray-300 font-mono">{{ Number(trade.quantity) }}</td>
                                        
                                        <td class="px-6 py-3 text-right font-bold text-yellow-500 text-xs font-mono">
                                            {{ formatCurrency(trade.fee || 0) }}
                                        </td>
                                        
                                        <td class="px-6 py-3 text-gray-500 text-xs italic truncate max-w-[200px]">{{ trade.notes }}</td>
                                    </template>
                                    
                                    <template v-else>
                                        <td class="px-6 py-3 text-right font-mono">{{ formatCurrency(trade.entry_price) }}</td>
                                        <td class="px-6 py-3 text-right font-bold">{{ formatCurrency(trade.pnl) }}</td>
                                        <td class="px-6 py-3 text-center text-xs">{{ trade.status }}</td>
                                    </template>
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
<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';
import { ref, watch, onMounted, computed, nextTick } from 'vue';
import imageCompression from 'browser-image-compression';

import SpotForm from './Partials/SpotForm.vue';
import FuturesOpen from './Partials/FuturesOpen.vue';
import FuturesClose from './Partials/FuturesClose.vue';

const props = defineProps<{
    trades: any[];
    activeType: string;
    accounts: any[];
    totalBalance: number;
    selectedAccountId: string;
}>();

// --- LOGIKA FILTER AKUN ---
const filteredAccounts = computed(() => {
    if (!props.accounts) return [];
    let filterType = props.activeType === 'RESULT' ? 'FUTURES' : props.activeType;
    return props.accounts.filter(acc => acc.strategy_type === filterType);
});

// --- SMART DEFAULT ---
const applySmartDefaults = () => {
    if (filteredAccounts.value.length > 0) {
        const firstAccountID = filteredAccounts.value[0].id;
        form.trading_account_id = firstAccountID;
        const isCurrentSelectionValid = filteredAccounts.value.some(acc => acc.id === selectedAccount.value);
        if (selectedAccount.value === 'all' || !isCurrentSelectionValid) {
             selectedAccount.value = firstAccountID;
        }
    } else {
        form.trading_account_id = '';
    }
};

const isSidebarCollapsed = ref(false);
onMounted(() => {
    const saved = localStorage.getItem("sidebar_collapsed");
    if (saved === "true") isSidebarCollapsed.value = true;
    applySmartDefaults();
});
const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
    localStorage.setItem("sidebar_collapsed", String(isSidebarCollapsed.value));
}

const selectedAccount = ref(props.selectedAccountId);
const switchTab = (type: string) => {
    router.get(route('trade.log'), { type: type, account_id: 'all' }, { preserveState: true, preserveScroll: true });
};
watch(selectedAccount, (newAccount) => {
    if (newAccount && newAccount !== props.selectedAccountId) {
        router.get(route('trade.log'), { type: props.activeType, account_id: newAccount }, { preserveState: true, preserveScroll: true });
    }
});
watch(() => props.activeType, () => {
    form.form_type = props.activeType === 'RESULT' ? 'FUTURES' : props.activeType;
    form.type = (props.activeType === 'FUTURES' || props.activeType === 'RESULT') ? 'LONG' : 'BUY';
    futuresTab.value = 'OPEN';
    form.errors = {};
    nextTick(() => { applySmartDefaults(); });
});

const inputMode = ref<'ASSET' | 'TOTAL'>('ASSET'); 
const dynamicInput = ref(''); 
const isCompressing = ref(false);
const futuresTab = ref<'OPEN' | 'CLOSE'>('OPEN');

const form = useForm({
    trading_account_id: '',
    date: new Date().toISOString().split('T')[0],
    symbol: '',
    market_type: 'CRYPTO',
    price: '',
    quantity: '', 
    total: '',    
    fee: '', 
    notes: '',
    type: 'BUY', 
    form_type: props.activeType, 
    leverage: 10,
    margin_mode: 'CROSS',
    order_type: 'MARKET',
    tp_price: '',
    sl_price: '',
    screenshot: null as File | null,
});

const toggleInputMode = () => {
    dynamicInput.value = '';
    form.quantity = '';
    form.total = '';
    inputMode.value = inputMode.value === 'ASSET' ? 'TOTAL' : 'ASSET';
};

watch([() => form.price, dynamicInput, inputMode], ([newPrice, newVal, mode]) => {
    const price = parseFloat(newPrice as string) || 0;
    const inputVal = parseFloat(newVal as string) || 0;
    if (price > 0 && inputVal > 0) {
        if (mode === 'ASSET') {
            form.quantity = inputVal.toString();
            form.total = (price * inputVal).toFixed(8); 
        } else {
            form.total = inputVal.toString();
            form.quantity = (inputVal / price).toFixed(8);
        }
    } else {
        form.quantity = '';
        form.total = '';
    }
});

const handleFileChange = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files ? target.files[0] : null;
    if (file) {
        const options = { maxSizeMB: 0.8, maxWidthOrHeight: 1920, useWebWorker: true };
        try {
            isCompressing.value = true; 
            const compressedFile = await imageCompression(file, options);
            const newFile = new File([compressedFile], file.name, { type: file.type });
            form.screenshot = newFile;
        } catch (error) {
            alert("Gagal mengompres gambar.");
        } finally {
            isCompressing.value = false;
        }
    }
};

const submitTrade = () => {
    if (!form.trading_account_id) { alert("Please select a trading account."); return; }
    if (!form.symbol) { alert("Please enter an asset symbol."); return; }
    if (!form.price) { alert("Price is required."); return; }
    if (isCompressing.value) { alert("Please wait, compressing image..."); return; }
    
    form.post(route('trade.log.store'), {
        forceFormData: true, 
        onSuccess: () => {
            form.reset('symbol', 'price', 'quantity', 'total', 'fee', 'notes', 'tp_price', 'sl_price', 'screenshot');
            dynamicInput.value = ''; 
            const fileInput = document.getElementById('file-upload-futures') as HTMLInputElement;
            if(fileInput) fileInput.value = '';
            document.getElementById('input-symbol')?.focus();
            applySmartDefaults(); 
        },
        onError: (errors) => {
            console.error("Server Error:", errors);
            alert("Failed to save trade.");
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

    <div class="min-h-screen bg-[#0a0b0d] text-gray-300 font-sans relative">
        <Sidebar :is-collapsed="isSidebarCollapsed" @toggle="toggleSidebar" />

        <div class="transition-all duration-300 ease-in-out min-h-screen flex flex-col"
            :class="isSidebarCollapsed ? 'ml-[72px]' : 'ml-64'">
            
            <Navbar />

            <main class="p-6 lg:p-8 space-y-8 flex-1 pb-20">
                
                <div class="flex flex-col items-center justify-center space-y-6">
                    <div class="bg-[#1a1b20] p-1 rounded-full flex items-center w-full max-w-lg border border-[#2d2f36] relative shadow-inner">
                        
                        <div class="absolute top-1 bottom-1 w-[calc(33.33%_-_4px)] bg-emerald-500 rounded-full transition-all duration-300 ease-out shadow-[0_0_15px_rgba(16,185,129,0.4)] z-0" 
                            :class="{
                                'left-1': props.activeType === 'SPOT',
                                'left-[calc(33.33%_+_2px)]': props.activeType === 'FUTURES',
                                'left-[calc(66.66%_+_2px)]': props.activeType === 'RESULT'
                            }">
                        </div>

                        <button @click="switchTab('SPOT')" class="flex-1 py-2 rounded-full text-xs sm:text-sm font-bold z-10 relative transition-colors" :class="props.activeType === 'SPOT' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">SPOT</button>
                        <button @click="switchTab('FUTURES')" class="flex-1 py-2 rounded-full text-xs sm:text-sm font-bold z-10 relative transition-colors" :class="props.activeType === 'FUTURES' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">FUTURES</button>
                        <button @click="switchTab('RESULT')" class="flex-1 py-2 rounded-full text-xs sm:text-sm font-bold z-10 relative transition-colors" :class="props.activeType === 'RESULT' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">RESULT</button>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-end justify-between gap-4 border-b border-[#1f2128] pb-4">
                    <div>
                        <div class="text-xs text-gray-500 uppercase font-semibold tracking-wider">
                            Total {{ props.activeType === 'RESULT' ? 'FUTURES' : props.activeType }} Balance
                        </div>
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
                        <div class="bg-[#1a1b20] p-1 rounded-full flex items-center w-full max-w-md border border-[#2d2f36] relative shadow-inner">
                            
                            <div class="absolute top-1 bottom-1 w-[calc(50%_-_4px)] bg-blue-600 rounded-full transition-all duration-300 ease-out shadow-[0_0_15px_rgba(37,99,235,0.4)] z-0" 
                                :class="{
                                    'left-1': futuresTab === 'OPEN',
                                    'left-[calc(50%_+_2px)]': futuresTab === 'CLOSE'
                                }">
                            </div>

                            <button @click="futuresTab = 'OPEN'" class="flex-1 py-2 rounded-full text-xs sm:text-sm font-bold z-10 relative transition-colors" 
                                :class="futuresTab === 'OPEN' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">
                                Open Position
                            </button>
                            <button @click="futuresTab = 'CLOSE'" class="flex-1 py-2 rounded-full text-xs sm:text-sm font-bold z-10 relative transition-colors" 
                                :class="futuresTab === 'CLOSE' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">
                                Close Position
                            </button>
                        </div>
                    </div>

                    <FuturesOpen v-if="futuresTab === 'OPEN'" :accounts="filteredAccounts" />
                    <FuturesClose v-else-if="futuresTab === 'CLOSE'" :trades="props.trades" />
                </div>

                <div v-if="props.activeType === 'RESULT'" class="text-center py-20 bg-[#121317] border border-[#1f2128] rounded-xl">
                    <i class="fas fa-chart-line text-4xl text-gray-600 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-400">Trade Results</h3>
                    <p class="text-sm text-gray-600 mt-2">Comprehensive history and performance analysis coming soon.</p>
                    <p class="text-xs text-gray-700 mt-1">Data is already filtered for closed positions in the backend.</p>
                </div>

                <div v-if="props.activeType === 'SPOT' || (props.activeType === 'FUTURES' && futuresTab === 'OPEN')" class="bg-[#121317] border border-[#1f2128] rounded-xl overflow-hidden shadow-sm min-h-[400px]">
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
                                    <template v-if="props.activeType === 'SPOT'">
                                        <th class="px-6 py-3 text-right">Total</th>
                                        <th class="px-6 py-3 text-right">Fee</th>
                                    </template>
                                    <template v-else>
                                        <th class="px-6 py-3 text-right">Margin</th>
                                        <th class="px-6 py-3 text-center">Lev</th>
                                        <th class="px-6 py-3 text-right">Chart</th>
                                    </template>
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
                                <tr v-if="props.trades.length === 0"><td colspan="8" class="px-6 py-12 text-center text-gray-500">No trades recorded yet.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
            <Footer :is-sidebar-collapsed="isSidebarCollapsed" />
        </div>
    </div>
</template>

<style scoped>
.no-spinner::-webkit-outer-spin-button, .no-spinner::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.no-spinner { appearance: textfield; -moz-appearance: textfield; }
</style>
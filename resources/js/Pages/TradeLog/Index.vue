<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';
import { ref, watch, onMounted, computed, nextTick } from 'vue';

// --- IMPORT KOMPONEN ANAK ---
import SpotBuy from './Partials/SpotBuy.vue';
import SpotSell from './Partials/SpotSell.vue';
import FuturesOpen from './Partials/FuturesOpen.vue';
import FuturesClose from './Partials/FuturesClose.vue';
import ResultSection from './Partials/ResultSection.vue';

const props = defineProps<{
    trades: any[];
    activeType: string;
    accounts: any[];
    totalBalance: number;
    spotBalance: number;
    futuresBalance: number;
    selectedAccountId: string;
    // [BARU] Menerima info tab result dari controller
    currentResultTab?: 'SPOT' | 'FUTURES'; 
}>();

// --- STATE MODALS (FITUR LAMA TETAP ADA) ---
const showDeleteModal = ref(false);
const tradeToDelete = ref<any>(null);
const showNoteModal = ref(false);
const noteContent = ref('');
const noteTitle = ref('Note'); 
const showImageModal = ref(false);
const selectedImageUrl = ref('');
const selectedImageName = ref('');

// --- ACTIONS MODAL (FITUR LAMA TETAP ADA) ---
const confirmDelete = (trade: any) => { tradeToDelete.value = trade; showDeleteModal.value = true; };
const cancelDelete = () => { showDeleteModal.value = false; tradeToDelete.value = null; };
const proceedDelete = () => {
    if (!tradeToDelete.value) return;
    router.delete(route('trade.log.destroy', { id: tradeToDelete.value.id, type: props.activeType }), {
        onSuccess: () => { showDeleteModal.value = false; tradeToDelete.value = null; },
        preserveScroll: true
    });
};

const viewNote = (note: string, type: string) => {
    noteContent.value = note || 'No notes available.';
    noteTitle.value = type + ' Note';
    showNoteModal.value = true;
};
const closeNoteModal = () => { showNoteModal.value = false; };

const openImageModal = (imagePath: string, type: string) => {
    selectedImageUrl.value = imagePath.startsWith('http') ? imagePath : `/storage/${imagePath}`;
    selectedImageName.value = `${type}-Chart.png`;
    showImageModal.value = true;
};
const closeImageModal = () => { showImageModal.value = false; };
const downloadImage = () => {
    if (!selectedImageUrl.value) return;
    const link = document.createElement('a');
    link.href = selectedImageUrl.value;
    link.download = selectedImageName.value;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

// --- LOGIC UTAMA ---
const selectedAccount = ref(props.selectedAccountId);

// [UPDATE] Inisialisasi resultSubTab menggunakan props dari controller agar tidak reset saat refresh
const resultSubTab = ref<'SPOT' | 'FUTURES'>(props.currentResultTab || 'FUTURES');

const futuresTab = ref<'OPEN' | 'CLOSE'>('OPEN');
const spotTab = ref<'BUY' | 'SELL'>('BUY'); 

// [UPDATE] Filter akun agar sesuai dengan Tab Result yang sedang aktif (Spot/Futures)
const filteredAccounts = computed(() => {
    if (!props.accounts) return [];
    
    let filterType = props.activeType;
    if (props.activeType === 'RESULT') {
        // Jika di tab Result, ikuti sub-tab nya
        filterType = resultSubTab.value === 'SPOT' ? 'SPOT' : 'FUTURES';
    } else {
        // Jika tidak, ikuti activeType (SPOT atau FUTURES)
        filterType = props.activeType === 'RESULT' ? 'FUTURES' : props.activeType;
    }
    
    return props.accounts.filter(acc => acc.strategy_type === filterType);
});

// [UPDATE] Display balance mengambil langsung dari props.totalBalance 
// (karena controller sekarang sudah mengirim nilai yang benar sesuai tab)
const displayedBalance = computed(() => {
    return props.totalBalance;
});

// Auto Select Account
const applySmartDefaults = () => {
    if (filteredAccounts.value.length > 0) {
        const firstAccountID = filteredAccounts.value[0].id;
        const isCurrentSelectionValid = filteredAccounts.value.some(acc => acc.id === selectedAccount.value);
        if (selectedAccount.value === 'all' || !isCurrentSelectionValid) selectedAccount.value = firstAccountID;
    }
};

// [PENTING] Watcher ini yang memperbaiki masalah Result Spot kosong
// Saat tombol di ResultSection diklik -> resultSubTab berubah -> Request data baru ke server
watch(resultSubTab, (newVal) => { 
    if (props.activeType === 'RESULT') {
        router.get(
            route('trade.log'), 
            { 
                type: 'RESULT', 
                result_type: newVal, // Kirim parameter result_type (SPOT/FUTURES)
                account_id: selectedAccount.value 
            }, 
            { preserveState: true, preserveScroll: true }
        );
    }
    applySmartDefaults(); 
});

// Update watch selectedAccount untuk membawa parameter result_type
watch(selectedAccount, (newAccount) => {
    if (newAccount && newAccount !== props.selectedAccountId) {
        let params: any = { type: props.activeType, account_id: newAccount };
        if (props.activeType === 'RESULT') {
            params.result_type = resultSubTab.value;
        }
        router.get(route('trade.log'), params, { preserveState: true, preserveScroll: true });
    }
});

watch(() => props.activeType, () => {
    if (props.activeType === 'FUTURES') futuresTab.value = 'OPEN';
    if (props.activeType === 'SPOT') spotTab.value = 'BUY';
    nextTick(() => { applySmartDefaults(); });
});

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

// Update switchTab untuk membawa parameter result_type
const switchTab = (type: string) => {
    let params: any = { type: type, account_id: 'all' };
    if (type === 'RESULT') {
        params.result_type = resultSubTab.value;
    }
    router.get(route('trade.log'), params, { preserveState: true, preserveScroll: true });
};

// --- HELPERS / FORMATTERS ---
const formatCurrency = (value: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
const formatTimeShort = (timeString: string) => timeString ? timeString.slice(0, 5) : '';

// [BARU] Fungsi hitung total accumulative fee untuk history log
const calculateTotalFee = (trade: any) => {
    const initialFee = parseFloat(trade.fee || 0);
    const transactionFees = trade.transactions ? trade.transactions.reduce((sum: number, t: any) => sum + parseFloat(t.fee || 0), 0) : 0;
    return initialFee + transactionFees;
};

const getHoldingClass = (period: string) => {
    if (period === 'Short Term') return 'text-emerald-400 border-emerald-500/30 bg-emerald-500/10';
    if (period === 'Medium Term') return 'text-blue-400 border-blue-500/30 bg-blue-500/10';
    if (period === 'Long Term') return 'text-purple-400 border-purple-500/30 bg-purple-500/10';
    return 'text-gray-400 border-gray-600 bg-gray-800';
};
</script>

<template>
    <Head title="Trade Log" />

    <div class="min-h-screen bg-[#0a0b0d] text-gray-300 font-sans relative">
        <Sidebar :is-collapsed="isSidebarCollapsed" @toggle="toggleSidebar" />

        <div class="transition-all duration-300 ease-in-out min-h-screen flex flex-col" :class="isSidebarCollapsed ? 'ml-[72px]' : 'ml-64'">
            <Navbar />
            <main class="p-6 lg:p-8 space-y-8 flex-1 pb-20">
                
                <div class="flex flex-col items-center justify-center space-y-6">
                    <div class="bg-[#1a1b20] p-1 rounded-full flex items-center w-full max-w-lg border border-[#2d2f36] relative shadow-inner">
                        
                        <div class="absolute top-1 bottom-1 w-[calc(33.33%_-_4px)] rounded-full transition-all duration-300 ease-out z-0 bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-[0_0_20px_rgba(140,82,255,0.5)]" 
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
                            Total {{ props.activeType === 'RESULT' ? resultSubTab : props.activeType }} Balance
                        </div>
                        <div class="text-3xl font-bold text-white mt-1">{{ formatCurrency(displayedBalance) }}</div>
                    </div>
                    <div class="relative w-full sm:w-64">
                         <select v-model="selectedAccount" class="bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 pr-8 appearance-none cursor-pointer">
                            <option value="all">All Accounts</option>
                            <option v-for="acc in filteredAccounts" :key="acc.id" :value="acc.id">{{ acc.name }} ({{ acc.exchange }})</option>
                        </select>
                         <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-400"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg></div>
                    </div>
                </div>

                <div v-if="props.activeType === 'SPOT'">
                    <div class="flex justify-center mb-8">
                        <div class="bg-[#1a1b20] p-1 rounded-full flex items-center w-full max-w-md border border-[#2d2f36] relative shadow-inner">
                            <div class="absolute top-1 bottom-1 w-[calc(50%_-_4px)] rounded-full transition-all duration-300 ease-out shadow-lg z-0" 
                                :class="spotTab === 'BUY' ? 'left-1 bg-emerald-600 shadow-emerald-500/40' : 'left-[calc(50%_+_2px)] bg-red-600 shadow-red-500/40'">
                            </div>
                            <button @click="spotTab = 'BUY'" class="flex-1 py-2 rounded-full text-xs sm:text-sm font-bold z-10 relative transition-colors" :class="spotTab === 'BUY' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">Buy Asset</button>
                            <button @click="spotTab = 'SELL'" class="flex-1 py-2 rounded-full text-xs sm:text-sm font-bold z-10 relative transition-colors" :class="spotTab === 'SELL' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">Sell Asset</button>
                        </div>
                    </div>
                    <SpotBuy v-if="spotTab === 'BUY'" :accounts="filteredAccounts" />
                    <SpotSell v-else :trades="props.trades" @view-chart="openImageModal"/>
                </div>

                <div v-if="props.activeType === 'FUTURES'">
                    <div class="flex justify-center mb-8">
                        <div class="bg-[#1a1b20] p-1 rounded-full flex items-center w-full max-w-md border border-[#2d2f36] relative shadow-inner">
                            <div class="absolute top-1 bottom-1 w-[calc(50%_-_4px)] bg-blue-600 rounded-full transition-all duration-300 ease-out shadow-[0_0_15px_rgba(37,99,235,0.4)] z-0" :class="{'left-1': futuresTab === 'OPEN', 'left-[calc(50%_+_2px)]': futuresTab === 'CLOSE'}"></div>
                            <button @click="futuresTab = 'OPEN'" class="flex-1 py-2 rounded-full text-xs sm:text-sm font-bold z-10 relative transition-colors" :class="futuresTab === 'OPEN' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">Open Position</button>
                            <button @click="futuresTab = 'CLOSE'" class="flex-1 py-2 rounded-full text-xs sm:text-sm font-bold z-10 relative transition-colors" :class="futuresTab === 'CLOSE' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">Close Position</button>
                        </div>
                    </div>
                    <FuturesOpen v-if="futuresTab === 'OPEN'" :accounts="filteredAccounts" />
                    <FuturesClose v-else-if="futuresTab === 'CLOSE'" :trades="props.trades" />
                </div>

                <div v-if="props.activeType === 'RESULT'">
                    <ResultSection 
                        v-model:activeTab="resultSubTab" 
                        :trades="props.trades" 
                        @view-image="openImageModal" 
                    />
                </div>

                <div v-if="(props.activeType === 'SPOT' && spotTab === 'BUY') || (props.activeType === 'FUTURES' && futuresTab === 'OPEN')" class="bg-[#121317] border border-[#1f2128] rounded-xl overflow-hidden shadow-sm min-h-[400px]">
                    <div class="p-4 border-b border-[#1f2128] bg-[#1a1b20]/50 flex justify-between items-center">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">History Log</h3>
                        <span class="text-[10px] text-gray-600">Recent activity</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left">
                            <thead class="bg-[#1a1b20] text-gray-400 uppercase text-[10px] tracking-wider font-semibold">
                                <tr>
                                    <th class="px-6 py-3">Asset</th>
                                    <th class="px-6 py-3">{{ props.activeType === 'SPOT' ? 'Last Activity' : 'Entry / Exit Time' }}</th>
                                    <th class="px-6 py-3" v-if="props.activeType === 'FUTURES'">Type</th>
                                    
                                    <th class="px-6 py-3 text-right">{{ props.activeType === 'SPOT' ? 'Avg Price' : 'Price' }}</th>
                                    <th class="px-6 py-3 text-right">Size/Qty</th>
                                    
                                    <template v-if="props.activeType === 'SPOT'">
                                        <th class="px-6 py-3 text-center">Holding</th>
                                        <th class="px-6 py-3 text-right">Total Fee</th>
                                        <th class="px-6 py-3 text-right">Realized PnL</th>
                                    </template>
                                    <template v-else>
                                        <th class="px-6 py-3 text-right">Margin</th>
                                        <th class="px-6 py-3 text-center">Lev</th>
                                    </template>

                                    <th class="px-6 py-3 text-right">Chart</th>
                                    <th class="px-6 py-3 text-right">Notes</th>
                                    <th class="px-6 py-3 text-center">Status</th> 
                                    <th class="px-6 py-3 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#1f2128]">
                                <tr v-for="trade in props.trades" :key="trade.id" class="hover:bg-[#1a1b20]/50 transition-colors group text-sm">
                                    
                                    <td class="px-6 py-3 font-bold text-white">{{ trade.symbol }}<span class="text-[10px] text-gray-500 font-normal ml-1 bg-[#1f2128] px-1 rounded">{{ trade.trading_account ? trade.trading_account.name : 'Account' }}</span></td>
                                    
                                    <td class="px-6 py-3 text-xs">
                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[9px] uppercase font-bold text-blue-500 bg-blue-500/10 px-1 rounded">{{ props.activeType === 'SPOT' ? 'BUY' : 'IN' }}</span>
                                                <span class="text-gray-300 font-mono">{{ props.activeType === 'SPOT' ? trade.buy_date : trade.entry_date }}</span>
                                                <span class="text-gray-500 text-[10px]">{{ formatTimeShort(props.activeType === 'SPOT' ? trade.buy_time : trade.entry_time) }}</span>
                                            </div>
                                            <div v-if="(props.activeType === 'SPOT' && trade.sell_date) || (props.activeType === 'FUTURES' && trade.exit_date)" class="flex items-center gap-1.5">
                                                <span class="text-[9px] uppercase font-bold text-yellow-500 bg-yellow-500/10 px-1 rounded">{{ props.activeType === 'SPOT' ? 'SELL' : 'OUT' }}</span>
                                                <span class="text-gray-300 font-mono">{{ props.activeType === 'SPOT' ? trade.sell_date : trade.exit_date }}</span>
                                                <span class="text-gray-500 text-[10px]">{{ formatTimeShort(props.activeType === 'SPOT' ? trade.sell_time : trade.exit_time) }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-3" v-if="props.activeType === 'FUTURES'">
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded uppercase border" :class="['BUY', 'LONG'].includes(trade.type) ? 'text-green-400 bg-green-900/10 border-green-500/20' : 'text-red-400 bg-red-900/10 border-red-500/20'">{{ trade.type }}</span>
                                    </td>

                                    <td class="px-6 py-3 text-right text-gray-300 font-mono">{{ formatCurrency(props.activeType === 'SPOT' ? trade.price : trade.entry_price) }}</td>
                                    
                                    <td class="px-6 py-3 text-right text-xs font-mono">
                                        <div>{{ Number(trade.quantity) }}</div>
                                        <div class="text-[10px] text-gray-500" v-if="props.activeType === 'SPOT' && trade.price && trade.quantity">
                                            ≈ {{ formatCurrency(parseFloat(trade.price) * parseFloat(trade.quantity)) }}
                                        </div>
                                    </td>
                                    
                                    <template v-if="props.activeType === 'SPOT'">
                                        <td class="px-6 py-3 text-center text-xs">
                                            <span class="px-2 py-1 rounded border text-[10px] font-bold uppercase" :class="getHoldingClass(trade.holding_period)">
                                                {{ trade.holding_period?.replace(' Term', '') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 text-right text-xs font-mono text-emerald-500">
                                            {{ formatCurrency(calculateTotalFee(trade)) }}
                                        </td>
                                        <td class="px-6 py-3 text-right text-xs font-mono font-bold" :class="trade.pnl >= 0 ? 'text-green-400' : 'text-red-400'">
                                            {{ trade.pnl ? formatCurrency(trade.pnl) : '$0.00' }}
                                        </td>
                                    </template>
                                    <template v-else>
                                        <td class="px-6 py-3 text-right text-blue-400 font-bold font-mono text-xs">{{ formatCurrency(trade.margin) }}</td>
                                        <td class="px-6 py-3 text-center text-yellow-500 text-xs font-bold">{{ trade.leverage }}x</td>
                                    </template>

                                    <td class="px-6 py-3 text-right font-bold text-yellow-500 text-xs font-mono">
                                        <div class="flex flex-col items-end gap-1">
                                            <button v-if="trade.entry_screenshot || trade.buy_screenshot" @click.prevent="openImageModal(trade.entry_screenshot || trade.buy_screenshot, props.activeType === 'SPOT' ? 'Buy' : 'Entry')" class="text-blue-400 hover:text-blue-300 underline focus:outline-none text-[10px]">
                                                {{ props.activeType === 'SPOT' ? 'Buy Chart' : 'Entry Chart' }}
                                            </button>
                                            <button v-if="trade.exit_screenshot || trade.sell_screenshot" @click.prevent="openImageModal(trade.exit_screenshot || trade.sell_screenshot, props.activeType === 'SPOT' ? 'Sell' : 'Exit')" class="text-green-400 hover:text-green-300 underline focus:outline-none text-[10px]">
                                                {{ props.activeType === 'SPOT' ? 'Sell Chart' : 'Exit Chart' }}
                                            </button>
                                            <span v-if="!trade.entry_screenshot && !trade.buy_screenshot && !trade.exit_screenshot && !trade.sell_screenshot" class="text-gray-600">-</span>
                                        </div>
                                    </td>
                                    
                                    <td class="px-6 py-3 text-right font-bold text-xs font-mono">
                                        <div class="flex flex-col items-end gap-1">
                                            <button v-if="trade.entry_notes || trade.buy_notes" 
                                                @click="viewNote(trade.entry_notes || trade.buy_notes, props.activeType === 'SPOT' ? 'Buy' : 'Entry')" 
                                                class="text-blue-400 hover:text-blue-300 underline focus:outline-none text-[10px]">
                                                {{ props.activeType === 'SPOT' ? 'Buy Note' : 'Entry Note' }}
                                            </button>
                                            <button v-if="trade.exit_notes || trade.sell_notes" 
                                                @click="viewNote(trade.exit_notes || trade.sell_notes, props.activeType === 'SPOT' ? 'Sell' : (trade.status === 'CANCELLED' ? 'Cancel' : 'Close'))" 
                                                class="text-yellow-400 hover:text-yellow-300 underline focus:outline-none text-[10px]">
                                                {{ props.activeType === 'SPOT' ? 'Sell Note' : (trade.status === 'CANCELLED' ? 'Cancel Note' : 'Close Note') }}
                                            </button>
                                            <span v-if="!trade.entry_notes && !trade.buy_notes && !trade.exit_notes && !trade.sell_notes" class="text-gray-600">-</span>
                                        </div>
                                    </td>
                                    
                                    <td class="px-6 py-3 text-center">
                                        <span v-if="trade.status === 'OPEN' && props.activeType === 'SPOT'" 
                                              class="px-2 py-1 text-[10px] font-bold rounded uppercase bg-yellow-500/20 text-yellow-500 border border-yellow-500/20">
                                            HOLDING
                                        </span>
                                        <span v-else-if="trade.status === 'OPEN' && props.activeType === 'FUTURES'" 
                                              class="px-2 py-1 text-[10px] font-bold rounded uppercase bg-blue-900/20 text-blue-400 border border-blue-500/20">
                                            OPEN
                                        </span>
                                        <span v-else class="px-2 py-1 text-[10px] font-bold rounded uppercase" 
                                            :class="(trade.status === 'SOLD' || trade.status === 'CLOSED') ? 'bg-green-900/20 text-green-400 border border-green-500/20' : 'bg-red-900/20 text-red-400 border border-red-500/20'">
                                            {{ trade.status }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-3 text-center">
                                        <button @click="confirmDelete(trade)" class="text-gray-600 hover:text-red-500 transition-colors p-2 rounded-full hover:bg-red-500/10" title="Delete Trade"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                    </td>
                                </tr>
                                <tr v-if="props.trades.length === 0"><td colspan="12" class="px-6 py-12 text-center text-gray-500">No trades recorded yet.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
            <Footer :is-sidebar-collapsed="isSidebarCollapsed" />

            <div v-if="showDeleteModal" class="fixed inset-0 z-[50] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm animate-fade-in">
                <div class="bg-[#121317] border border-[#1f2128] rounded-xl w-full max-w-md p-6 shadow-2xl relative">
                    <div class="text-center mb-6">
                        <div class="w-12 h-12 bg-red-900/20 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4 border border-red-500/20"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></div>
                        <h3 class="text-lg font-bold text-white mb-2">Delete Trade?</h3>
                        <div class="bg-red-500/5 border border-red-500/10 rounded-lg p-4 text-left">
                            <p class="text-xs text-gray-400 leading-relaxed">This action will rollback your account balance. This process cannot be undone.</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button @click="cancelDelete" class="flex-1 py-3 rounded-lg text-xs font-bold text-gray-400 bg-[#1a1b20] hover:bg-[#25262c] border border-[#2d2f36] transition-all">Cancel</button>
                        <button @click="proceedDelete" class="flex-1 py-3 rounded-lg text-xs font-bold text-white bg-red-600 hover:bg-red-700 shadow-lg shadow-red-900/20 transition-all">Delete</button>
                    </div>
                </div>
            </div>

            <div v-if="showNoteModal" class="fixed inset-0 z-[50] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm animate-fade-in">
                <div class="bg-[#121317] border border-[#1f2128] rounded-xl w-full max-w-lg p-6 shadow-2xl relative">
                    <div class="flex justify-between items-center mb-4 border-b border-[#1f2128] pb-4">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2"><span class="text-yellow-500 font-bold text-xl mr-2">📝</span> {{ noteTitle }}</h3>
                        <button @click="closeNoteModal" class="text-gray-500 hover:text-white"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                    </div>
                    <div class="bg-[#0a0b0d] p-4 rounded-lg border border-[#2d2f36] text-sm text-gray-300 whitespace-pre-wrap leading-relaxed max-h-[60vh] overflow-y-auto">{{ noteContent }}</div>
                    <div class="mt-6 text-right">
                        <button @click="closeNoteModal" class="px-6 py-2 rounded-lg text-xs font-bold text-black bg-white hover:bg-gray-200 transition-all">Close</button>
                    </div>
                </div>
            </div>

            <div v-if="showImageModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-fade-in" @click.self="closeImageModal">
                <div class="bg-[#121317] border border-[#1f2128] rounded-xl w-full max-w-4xl shadow-2xl relative overflow-hidden flex flex-col max-h-[90vh]">
                    <div class="flex justify-between items-center p-4 border-b border-[#1f2128] bg-[#1a1b20]">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg> Chart Preview</h3>
                        <button @click="closeImageModal" class="text-gray-500 hover:text-white transition-colors p-1 rounded-full hover:bg-[#2d2f36]"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                    </div>
                    <div class="flex-1 p-4 overflow-auto flex justify-center items-center bg-[#0a0b0d]">
                        <img :src="selectedImageUrl" alt="Chart Screenshot" class="max-w-full max-h-[70vh] object-contain rounded-lg border border-[#2d2f36] shadow-lg" @error="selectedImageUrl = '/images/placeholder-chart.png'">
                    </div>
                    <div class="p-4 border-t border-[#1f2128] bg-[#1a1b20] flex justify-end gap-3">
                         <button @click="closeImageModal" class="px-4 py-2 rounded-lg text-xs font-bold text-gray-400 bg-transparent hover:bg-[#2d2f36] border border-transparent hover:border-[#2d2f36] transition-all">Close</button>
                        <button @click="downloadImage" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-900/20 transition-all"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg> Download Image</button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
.no-spinner::-webkit-outer-spin-button, .no-spinner::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.no-spinner { appearance: textfield; -moz-appearance: textfield; }
@keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
.animate-fade-in { animation: fadeIn 0.2s ease-out forwards; }
</style>
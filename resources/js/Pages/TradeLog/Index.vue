<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';
import { ref, watch, onMounted, computed, nextTick } from 'vue';

// --- [PENTING] IMPORT KOMPONEN ANAK ---
// Jika file ini merah/error, berarti file di folder Partials belum dibuat!
import SpotForm from './Partials/SpotForm.vue';
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
}>();

// --- 1. LOGIKA MODAL DELETE (Fitur Hapus) ---
const showDeleteModal = ref(false);
const tradeToDelete = ref<any>(null);

const confirmDelete = (trade: any) => {
    tradeToDelete.value = trade;
    showDeleteModal.value = true;
};
const cancelDelete = () => {
    showDeleteModal.value = false;
    tradeToDelete.value = null;
};
const proceedDelete = () => {
    if (!tradeToDelete.value) return;
    router.delete(route('trade.log.destroy', { id: tradeToDelete.value.id, type: props.activeType }), {
        onSuccess: () => { showDeleteModal.value = false; tradeToDelete.value = null; },
        preserveScroll: true
    });
};

// --- 2. LOGIKA MODAL VIEW NOTE (Fitur Lihat Catatan) ---
const showNoteModal = ref(false);
const noteContent = ref('');
const noteTitle = ref('Note'); 

const viewNote = (note: string, type: 'Entry' | 'Close' | 'Cancel' | 'Spot') => {
    noteContent.value = note || 'No notes available.';
    if (type === 'Spot') noteTitle.value = 'Trade Note';
    else if (type === 'Close') noteTitle.value = 'Close Position Note';
    else if (type === 'Cancel') noteTitle.value = 'Cancellation Reason';
    else noteTitle.value = 'Entry Note';
    showNoteModal.value = true;
};
const closeNoteModal = () => { showNoteModal.value = false; noteContent.value = ''; };

// --- 3. LOGIKA MODAL IMAGE & DOWNLOAD (Fitur Lihat Chart) ---
const showImageModal = ref(false);
const selectedImageUrl = ref('');
const selectedImageName = ref('');

const openImageModal = (imagePath: string, type: 'Entry' | 'Exit') => {
    selectedImageUrl.value = imagePath.startsWith('http') ? imagePath : `/storage/${imagePath}`;
    selectedImageName.value = `${type}-Chart-${Date.now()}.png`;
    showImageModal.value = true;
};
const closeImageModal = () => { showImageModal.value = false; selectedImageUrl.value = ''; };

const downloadImage = () => {
    if (!selectedImageUrl.value) return;
    const link = document.createElement('a');
    link.href = selectedImageUrl.value;
    link.download = selectedImageName.value;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

// --- 4. LOGIKA UTAMA (FILTER & BALANCE) ---
const filteredAccounts = computed(() => {
    if (!props.accounts) return [];
    let filterType = props.activeType === 'RESULT' ? 'FUTURES' : props.activeType;
    return props.accounts.filter(acc => acc.strategy_type === filterType);
});

const displayedBalance = computed(() => {
    if (props.activeType === 'SPOT') return props.spotBalance;
    if (props.activeType === 'FUTURES') return props.futuresBalance;
    if (props.activeType === 'RESULT') return resultSubTab.value === 'SPOT' ? props.spotBalance : props.futuresBalance;
    return 0;
});

const form = useForm({ trading_account_id: '' });
const resultSubTab = ref<'SPOT' | 'FUTURES'>('FUTURES');

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

watch(resultSubTab, () => { if (props.activeType === 'RESULT') applySmartDefaults(); });

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

const futuresTab = ref<'OPEN' | 'CLOSE'>('OPEN');
watch(() => props.activeType, () => {
    if (props.activeType === 'FUTURES') futuresTab.value = 'OPEN';
    nextTick(() => { applySmartDefaults(); });
});

// Helper Format Uang
const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
};
// Helper Format Tanggal (Potong Jam)
const formatDateOnly = (dateString: string) => {
    if (!dateString) return '-';
    return dateString.split(' ')[0];
};
// Helper Jam pendek (HH:mm)
const formatTimeShort = (timeString: string) => {
    if (!timeString) return '';
    return timeString.slice(0, 5); 
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
                            Total {{ props.activeType === 'RESULT' ? resultSubTab : props.activeType }} Balance
                        </div>
                        <div class="text-3xl font-bold text-white mt-1">{{ formatCurrency(displayedBalance) }}</div>
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
                            <button @click="futuresTab = 'OPEN'" class="flex-1 py-2 rounded-full text-xs sm:text-sm font-bold z-10 relative transition-colors" :class="futuresTab === 'OPEN' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">Open Position</button>
                            <button @click="futuresTab = 'CLOSE'" class="flex-1 py-2 rounded-full text-xs sm:text-sm font-bold z-10 relative transition-colors" :class="futuresTab === 'CLOSE' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">Close Position</button>
                        </div>
                    </div>
                    <FuturesOpen v-if="futuresTab === 'OPEN'" :accounts="filteredAccounts" />
                    <FuturesClose v-else-if="futuresTab === 'CLOSE'" :trades="props.trades" />
                </div>

                <div v-if="props.activeType === 'RESULT'">
                    <ResultSection v-model:activeTab="resultSubTab" :trades="props.trades" />
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
                                    <th class="px-6 py-3" v-if="props.activeType === 'FUTURES'">Entry / Exit Time</th>
                                    <th class="px-6 py-3" v-else>Date</th>

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
                                    <th class="px-6 py-3 text-right">Notes</th>
                                    <th class="px-6 py-3 text-center">Status</th> 
                                    <th class="px-6 py-3 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#1f2128]">
                                <tr v-for="trade in props.trades" :key="trade.id" class="hover:bg-[#1a1b20]/50 transition-colors group text-sm">
                                    <td class="px-6 py-3 font-bold text-white">{{ trade.symbol }}<span class="text-[10px] text-gray-500 font-normal ml-1 bg-[#1f2128] px-1 rounded">{{ trade.trading_account ? trade.trading_account.name : 'Account' }}</span></td>
                                    
                                    <td class="px-6 py-3 text-xs">
                                        <div v-if="props.activeType === 'SPOT'" class="text-gray-400">{{ trade.date }}</div>
                                        <div v-else class="flex flex-col gap-1">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[9px] uppercase font-bold text-blue-500 bg-blue-500/10 px-1 rounded">IN</span>
                                                <span class="text-gray-300 font-mono">{{ trade.entry_date }}</span>
                                                <span class="text-gray-500 text-[10px]">{{ formatTimeShort(trade.entry_time) }}</span>
                                            </div>
                                            <div v-if="trade.exit_date" class="flex items-center gap-1.5">
                                                <span class="text-[9px] uppercase font-bold text-yellow-500 bg-yellow-500/10 px-1 rounded">OUT</span>
                                                <span class="text-gray-300 font-mono">{{ trade.exit_date }}</span>
                                                <span class="text-gray-500 text-[10px]">{{ formatTimeShort(trade.exit_time) }}</span>
                                            </div>
                                            <div v-else class="text-gray-600 text-[10px] italic pl-8">In Position...</div>
                                        </div>
                                    </td>

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
                                            <div class="flex flex-col items-end gap-1">
                                                <button v-if="trade.entry_screenshot" @click.prevent="openImageModal(trade.entry_screenshot, 'Entry')" class="text-blue-400 hover:text-blue-300 underline focus:outline-none text-[10px]">Entry Chart</button>
                                                <button v-if="trade.exit_screenshot" @click.prevent="openImageModal(trade.exit_screenshot, 'Exit')" class="text-green-400 hover:text-green-300 underline focus:outline-none text-[10px]">Exit Chart</button>
                                                <span v-if="!trade.entry_screenshot && !trade.exit_screenshot" class="text-gray-600">-</span>
                                            </div>
                                        </td>
                                    </template>
                                    
                                    <td class="px-6 py-3 text-right font-bold text-xs font-mono">
                                        <div class="flex flex-col items-end gap-1">
                                            <button v-if="trade.entry_notes || (props.activeType === 'SPOT' && trade.notes)" 
                                                @click="viewNote(trade.entry_notes || trade.notes, props.activeType === 'SPOT' ? 'Spot' : 'Entry')" 
                                                class="text-blue-400 hover:text-blue-300 underline focus:outline-none text-[10px]">
                                                {{ props.activeType === 'SPOT' ? 'Trade Note' : 'Entry Note' }}
                                            </button>
                                            <button v-if="props.activeType === 'FUTURES' && trade.exit_notes" 
                                                @click="viewNote(trade.exit_notes, trade.status === 'CANCELLED' ? 'Cancel' : 'Close')" 
                                                class="text-yellow-400 hover:text-yellow-300 underline focus:outline-none text-[10px]">
                                                {{ trade.status === 'CANCELLED' ? 'Cancel Note' : 'Close Note' }}
                                            </button>
                                            <span v-if="!trade.entry_notes && !trade.exit_notes && !trade.notes" class="text-gray-600">-</span>
                                        </div>
                                    </td>
                                    
                                    <td class="px-6 py-3 text-center">
                                        <span class="px-2 py-1 text-[10px] font-bold rounded uppercase" :class="props.activeType === 'SPOT' ? 'bg-gray-800 text-gray-400' : (trade.status === 'OPEN' ? 'bg-blue-900/20 text-blue-400 border border-blue-500/20' : (trade.status === 'CANCELLED' ? 'bg-red-900/20 text-red-400 border border-red-500/20' : 'bg-gray-800 text-gray-400'))">
                                            {{ props.activeType === 'SPOT' ? 'FILLED' : trade.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-center">
                                        <button @click="confirmDelete(trade)" class="text-gray-600 hover:text-red-500 transition-colors p-2 rounded-full hover:bg-red-500/10" title="Delete Trade"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                    </td>
                                </tr>
                                <tr v-if="props.trades.length === 0"><td colspan="10" class="px-6 py-12 text-center text-gray-500">No trades recorded yet.</td></tr>
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
                            <p class="text-xs text-gray-400 mb-3 leading-relaxed">Notes, reflections, screenshots, cancel reasons, and all statistics will be <span class="text-red-400 font-bold">permanently removed after 30 days</span>.</p>
                            <p class="text-xs text-gray-400 mb-3 leading-relaxed">This data helps you (and future AI insights) learn from past trades.</p>
                            <p class="text-xs text-blue-400 font-semibold cursor-pointer hover:underline flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg> Consider exporting it first as a backup.</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button @click="cancelDelete" class="flex-1 py-3 rounded-lg text-xs font-bold text-gray-400 bg-[#1a1b20] hover:bg-[#25262c] border border-[#2d2f36] transition-all">[ Cancel ]</button>
                        <button @click="proceedDelete" class="flex-1 py-3 rounded-lg text-xs font-bold text-white bg-red-600 hover:bg-red-700 shadow-lg shadow-red-900/20 transition-all">[ Delete ]</button>
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
<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';
import { ref, watch, onMounted } from 'vue';

// --- PROPS ---
const props = defineProps<{
    trades: any[];
    activeType: string;
    accounts: any[];
    totalBalance: number;
    selectedAccountId: string;
}>();

// --- SIDEBAR STATE ---
const isSidebarCollapsed = ref(false);

onMounted(() => {
    const saved = localStorage.getItem("sidebar_collapsed");
    if (saved === "true") isSidebarCollapsed.value = true;
});

const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
    localStorage.setItem("sidebar_collapsed", String(isSidebarCollapsed.value));
}

// --- LOGIC UMUM ---
const selectedAccount = ref(props.selectedAccountId);

const switchTab = (type: string) => {
    router.get(route('trade.log'), { type: type, account_id: 'all' }, { preserveState: true, preserveScroll: true });
};

watch(selectedAccount, (newAccount) => {
    router.get(route('trade.log'), { type: props.activeType, account_id: newAccount }, { preserveState: true, preserveScroll: true });
});

// State Swap Input (Margin vs Qty)
const inputMode = ref<'ASSET' | 'TOTAL'>('ASSET'); 
const dynamicInput = ref(''); 

// State Tab Internal Futures (OPEN / CLOSE / RESULT)
const futuresTab = ref<'OPEN' | 'CLOSE' | 'RESULT'>('OPEN');

// --- FORM INIT ---
const form = useForm({
    trading_account_id: '',
    date: new Date().toISOString().split('T')[0],
    symbol: '',
    market_type: 'CRYPTO',
    price: '',
    quantity: '', 
    total: '',    
    fee: '', // Hanya untuk SPOT
    notes: '',
    type: 'BUY', 
    form_type: props.activeType, 
    leverage: 10,
    margin_mode: 'CROSS',
    order_type: 'MARKET',
    tp_price: '',
    sl_price: '',
    screenshot: null as File | null, // Hanya untuk FUTURES
});

// Reset saat tab utama berubah (Spot <-> Futures)
watch(() => props.activeType, (newType) => {
    form.form_type = newType;
    // Set default type: Futures = LONG, Spot = BUY
    form.type = newType === 'FUTURES' ? 'LONG' : 'BUY';
    futuresTab.value = 'OPEN'; // Reset ke Open Position saat pindah ke Futures
    form.errors = {}; // Clear errors
});

const toggleInputMode = () => {
    dynamicInput.value = '';
    form.quantity = '';
    form.total = '';
    inputMode.value = inputMode.value === 'ASSET' ? 'TOTAL' : 'ASSET';
};

// Kalkulator Otomatis
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

// Handle File Input Change (Futures Only)
const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.screenshot = target.files[0];
    }
};

const submitTrade = () => {
    if (!form.trading_account_id) { alert("Please select a trading account."); return; }
    if (!form.symbol) { alert("Please enter an asset symbol."); return; }
    if (!form.price) { alert("Price is required."); return; }
    
    // Inertia otomatis handle FormData untuk upload file
    form.post(route('trade.log.store'), {
        onSuccess: () => {
            // Reset form
            form.reset('symbol', 'price', 'quantity', 'total', 'fee', 'notes', 'tp_price', 'sl_price', 'screenshot');
            dynamicInput.value = ''; 
            
            // Reset input file visual secara manual
            const fileInput = document.getElementById('file-upload-futures') as HTMLInputElement;
            if(fileInput) fileInput.value = '';
            
            document.getElementById('input-symbol')?.focus();
        },
        onError: (errors) => {
            console.error("Server Error:", errors);
            alert("Failed to save trade. Check console for details.");
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
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3 mb-4 items-end"> 
                            
                            <div><label class="block text-[10px] text-gray-500 mb-2 font-bold uppercase">Date</label><input v-model="form.date" type="date" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-xs rounded p-2.5 focus:border-blue-500 outline-none h-10"></div>
                            <div class="lg:col-span-1"><label class="block text-[10px] text-gray-500 mb-2 font-bold uppercase">Account</label><select v-model="form.trading_account_id" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-xs rounded p-2.5 h-10"><option value="" disabled>Select</option><option v-for="acc in props.accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option></select></div>
                            
                            <div><label class="block text-[10px] text-gray-500 mb-2 font-bold uppercase">Type</label><select v-model="form.type" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-xs rounded p-2.5 h-10 font-bold text-center" :class="form.type === 'BUY' ? 'text-green-500' : 'text-red-500'"><option value="BUY">BUY</option><option value="SELL">SELL</option></select></div>
                            
                            <div><label class="block text-[10px] text-gray-500 mb-2 font-bold uppercase">Asset</label><input id="input-symbol" v-model="form.symbol" type="text" placeholder="BTC" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-xs rounded p-2.5 h-10 uppercase font-bold"></div>
                            <div><label class="block text-[10px] text-gray-500 mb-2 font-bold uppercase">Market</label><select v-model="form.market_type" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-gray-300 text-xs rounded p-2.5 h-10"><option value="CRYPTO">CRYPTO</option><option value="STOCK">STOCK</option></select></div>
                            
                            <div><label class="block text-[10px] text-gray-500 mb-2 font-bold uppercase">Price</label><input v-model="form.price" type="number" step="any" placeholder="0.00" class="no-spinner w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-xs rounded p-2.5 text-right font-mono h-10"></div>
                            
                            <div class="relative group lg:col-span-1">
                                <div class="flex justify-between items-center mb-2">
                                    <label class="block text-[10px] text-gray-500 font-bold uppercase">{{ inputMode === 'ASSET' ? 'Qty' : 'Total' }}</label>
                                    <button type="button" @click="toggleInputMode" class="text-[9px] font-bold text-blue-500 hover:text-blue-400 flex items-center gap-1 uppercase transition-all">
                                        SWAP <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                                    </button>
                                </div>
                                <input v-model="dynamicInput" type="number" step="any" class="no-spinner w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-xs rounded p-2.5 text-right font-mono h-10">
                                <div v-if="form.price && dynamicInput" class="absolute -bottom-5 right-0 text-[9px] text-gray-500">≈ {{ inputMode === 'ASSET' ? formatCurrency(Number(form.total)) : Number(form.quantity).toFixed(6) }}</div>
                            </div>

                            <div><label class="block text-[10px] text-gray-500 mb-2 font-bold uppercase">Fee</label><input v-model="form.fee" type="number" step="any" placeholder="0.00" class="no-spinner w-full bg-[#1a1b20] border border-[#2d2f36] text-yellow-500 text-xs rounded p-2.5 text-right font-mono h-10"></div>
                        </div>
                        
                        <div class="mb-4 mt-7"><input v-model="form.notes" type="text" placeholder="Notes..." class="w-full bg-[#1a1b20] border border-[#2d2f36] text-gray-300 text-sm rounded-lg p-3"></div>
                        
                        <div>
                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="w-full py-3 rounded-lg text-sm font-bold text-white shadow-lg transition-all flex items-center justify-center gap-2 uppercase tracking-wide"
                                :class="form.type === 'BUY' 
                                    ? 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/20' 
                                    : 'bg-red-600 hover:bg-red-700 shadow-red-500/20'"
                            >
                                CONFIRM {{ form.type }} TRADE
                            </button>
                        </div>
                    </form>
                </div>

                <div v-if="props.activeType === 'FUTURES'" class="bg-[#121317] border border-[#1f2128] rounded-xl p-6 shadow-lg">
                    
                    <div class="flex justify-center mb-8">
                        <div class="bg-[#1a1b20] p-1.5 rounded-full flex items-center w-full max-w-md border border-[#2d2f36] relative shadow-inner">
                            <button @click="futuresTab = 'OPEN'" class="flex-1 py-2 rounded-full text-xs sm:text-sm font-bold z-10 relative transition-colors" :class="futuresTab === 'OPEN' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">Open Position</button>
                            <button @click="futuresTab = 'CLOSE'" class="flex-1 py-2 rounded-full text-xs sm:text-sm font-bold z-10 relative transition-colors" :class="futuresTab === 'CLOSE' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">Close Position</button>
                            <button @click="futuresTab = 'RESULT'" class="flex-1 py-2 rounded-full text-xs sm:text-sm font-bold z-10 relative transition-colors" :class="futuresTab === 'RESULT' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">Result</button>
                            <div class="absolute top-1.5 bottom-1.5 w-[calc(33.33%-4px)] bg-blue-600 rounded-full transition-all duration-300 ease-out shadow-[0_0_15px_rgba(37,99,235,0.4)]"
                                :class="{
                                    'left-1.5': futuresTab === 'OPEN',
                                    'left-[calc(33.33%+2px)]': futuresTab === 'CLOSE',
                                    'left-[calc(66.66%+2px)]': futuresTab === 'RESULT'
                                }"
                            ></div>
                        </div>
                    </div>

                    <form v-if="futuresTab === 'OPEN'" @submit.prevent="submitTrade">
                        
                        <div class="grid grid-cols-2 gap-4 mb-5">
                            <div><label class="block text-[10px] text-gray-500 mb-1 font-bold tracking-wider uppercase">Date</label><input v-model="form.date" type="date" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-xs rounded p-3 focus:border-blue-500 outline-none"></div>
                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1 font-bold tracking-wider uppercase">Account</label>
                                <select v-model="form.trading_account_id" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-xs rounded p-3 focus:border-blue-500 outline-none">
                                    <option value="" disabled>Select Futures Account</option>
                                    <option v-for="acc in props.accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-12 gap-3 mb-5">
                            <div class="col-span-4">
                                <label class="block text-[10px] text-gray-500 mb-1 font-bold tracking-wider uppercase">Position</label>
                                <div class="flex bg-[#0a0b0d] rounded-lg p-1 border border-[#2d2f36]">
                                    <button type="button" @click="form.type = 'LONG'" class="flex-1 text-xs font-black rounded py-2 transition-all uppercase tracking-wide" :class="form.type === 'LONG' ? 'bg-green-600 text-white shadow-[0_0_15px_rgba(22,163,74,0.4)]' : 'text-gray-500 hover:text-gray-300'">Long</button>
                                    <button type="button" @click="form.type = 'SHORT'" class="flex-1 text-xs font-black rounded py-2 transition-all uppercase tracking-wide" :class="form.type === 'SHORT' ? 'bg-red-600 text-white shadow-[0_0_15px_rgba(220,38,38,0.4)]' : 'text-gray-500 hover:text-gray-300'">Short</button>
                                </div>
                            </div>
                            <div class="col-span-4">
                                <label class="block text-[10px] text-gray-500 mb-1 font-bold tracking-wider uppercase">Asset</label>
                                <input v-model="form.symbol" type="text" placeholder="BTC" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded p-2.5 uppercase font-bold text-center h-[38px] focus:border-blue-500 outline-none">
                            </div>
                            <div class="col-span-4">
                                <label class="block text-[10px] text-gray-500 mb-1 font-bold tracking-wider uppercase">Market</label>
                                <select v-model="form.market_type" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-gray-400 text-xs rounded p-2.5 h-[38px] text-center focus:border-blue-500 outline-none">
                                    <option value="CRYPTO">CRYPTO</option>
                                    <option value="STOCK">STOCK</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3 mb-5 p-4 bg-[#0a0b0d] rounded-lg border border-[#2d2f36]">
                            <div>
                                <label class="block text-[9px] text-gray-500 mb-1 font-bold uppercase">Leverage</label>
                                <div class="flex items-center"><span class="text-yellow-500 text-xs mr-1 font-bold">x</span><input v-model="form.leverage" type="number" min="1" max="125" class="no-spinner w-full bg-transparent border-none text-white text-lg font-bold p-0 focus:ring-0 placeholder-gray-700" placeholder="10"></div>
                            </div>
                            <div class="border-l border-[#2d2f36] pl-3">
                                <label class="block text-[9px] text-gray-500 mb-1 font-bold uppercase">Mode</label>
                                <select v-model="form.margin_mode" class="w-full bg-transparent border-none text-blue-400 text-xs font-bold p-0 focus:ring-0 cursor-pointer"><option value="CROSS">CROSS</option><option value="ISOLATED">ISOLATED</option></select>
                            </div>
                            <div class="border-l border-[#2d2f36] pl-3">
                                <label class="block text-[9px] text-gray-500 mb-1 font-bold uppercase">Order Type</label>
                                <select v-model="form.order_type" class="w-full bg-transparent border-none text-purple-400 text-xs font-bold p-0 focus:ring-0 cursor-pointer">
                                    <option value="MARKET">MARKET</option>
                                    <option value="LIMIT">LIMIT</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-5">
                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Entry Price ($)</label>
                                <input v-model="form.price" type="number" step="any" placeholder="0.00" class="no-spinner w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded p-3 text-right font-mono focus:border-blue-500 outline-none">
                            </div>
                            
                            <div class="relative group">
                                <div class="flex justify-between items-center mb-1">
                                    <label class="text-[10px] text-gray-500 font-bold transition-all text-blue-400 uppercase">
                                        {{ inputMode === 'ASSET' ? 'SIZE (Coins)' : 'MARGIN (Cost)' }}
                                    </label>
                                    <button type="button" @click="toggleInputMode" class="text-[9px] font-bold text-blue-500 hover:text-blue-400 flex items-center gap-1 uppercase transition-all">
                                        SWAP <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                                    </button>
                                </div>
                                <input v-model="dynamicInput" type="number" step="any" class="no-spinner w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded p-3 text-right font-mono focus:border-blue-500 outline-none">
                                <div v-if="form.price && dynamicInput" class="absolute -bottom-5 right-0 text-[10px] text-gray-500 font-mono">
                                    <span v-if="inputMode === 'ASSET'">Margin: <span class="text-blue-400 font-bold">${{ formatCurrency(Number(form.total)) }}</span></span>
                                    <span v-else>Size: <span class="text-blue-400 font-bold">{{ Number(form.quantity).toFixed(4) }} {{ form.symbol }}</span></span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-5 p-3 rounded border border-dashed border-[#2d2f36]">
                            <div><label class="block text-[10px] text-green-500 font-bold mb-1 uppercase">Take Profit</label><input v-model="form.tp_price" type="number" step="any" placeholder="Optional" class="no-spinner w-full bg-[#0a0b0d] border border-[#2d2f36] text-green-400 text-xs rounded p-2.5 text-right font-mono focus:border-green-500 outline-none"></div>
                            <div><label class="block text-[10px] text-red-500 font-bold mb-1 uppercase">Stop Loss</label><input v-model="form.sl_price" type="number" step="any" placeholder="Optional" class="no-spinner w-full bg-[#0a0b0d] border border-[#2d2f36] text-red-400 text-xs rounded p-2.5 text-right font-mono focus:border-red-500 outline-none"></div>
                        </div>

                        <div class="grid grid-cols-4 gap-3 items-end mb-6">
                            
                            <div class="col-span-1 relative group">
                                <label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Chart</label>
                                <label for="file-upload-futures" class="flex items-center justify-center w-full h-[42px] bg-[#1a1b20] border border-[#2d2f36] rounded cursor-pointer hover:border-gray-500 transition-colors text-gray-400 text-xs">
                                    <span v-if="!form.screenshot" class="flex items-center gap-1"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg> Upload</span>
                                    <span v-else class="truncate px-2 text-blue-400">{{ form.screenshot.name }}</span>
                                </label>
                                <input id="file-upload-futures" type="file" @change="handleFileChange" accept="image/*" class="hidden">
                            </div>

                            <div class="col-span-3"><label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Notes</label><input v-model="form.notes" type="text" placeholder="Setup reasoning..." class="w-full bg-[#1a1b20] border border-[#2d2f36] text-gray-300 text-xs rounded p-2.5 focus:border-blue-500 outline-none h-[42px]"></div>
                        </div>

                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="w-full py-4 rounded-lg text-sm font-black text-white shadow-xl transition-all tracking-widest uppercase border border-transparent hover:border-white/20"
                            :class="form.type === 'LONG' 
                                ? 'bg-gradient-to-r from-green-700 to-green-600 hover:from-green-600 hover:to-green-500 shadow-[0_0_20px_rgba(22,163,74,0.3)]' 
                                : 'bg-gradient-to-r from-red-700 to-red-600 hover:from-red-600 hover:to-red-500 shadow-[0_0_20px_rgba(220,38,38,0.3)]'"
                        >
                            OPEN {{ form.type }} POSITION
                        </button>
                    </form>

                    <div v-else-if="futuresTab === 'CLOSE'" class="text-center py-12 text-gray-500">
                        <i class="fas fa-lock text-2xl mb-2"></i>
                        <p>Close Position Module Coming Soon</p>
                    </div>

                    <div v-else-if="futuresTab === 'RESULT'" class="text-center py-12 text-gray-500">
                        <i class="fas fa-chart-line text-2xl mb-2"></i>
                        <p>Reflection & Result Module Coming Soon</p>
                    </div>
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

<style scoped>
.no-spinner::-webkit-outer-spin-button,
.no-spinner::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.no-spinner { appearance: textfield; -moz-appearance: textfield; }
</style>
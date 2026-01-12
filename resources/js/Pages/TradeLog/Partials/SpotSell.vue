<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import imageCompression from 'browser-image-compression';

// --- PROPS & EMITS ---
const props = defineProps<{ 
    trades: any[] 
}>();

const emit = defineEmits(['view-chart']);

// --- STATE ---
const expandedFormId = ref<number | null>(null); 
const expandedInfoIds = ref<Set<number>>(new Set()); 
const isCompressing = ref(false);

const transactionType = ref<'BUY' | 'SELL'>('BUY'); 
const inputMode = ref<'COIN' | 'USD'>('COIN');
// State untuk slider persentase sell
const sellPercentage = ref<number>(0);

const now = ref(new Date());
let timer: any;

onMounted(() => {
    timer = setInterval(() => { now.value = new Date(); }, 60000);
});
onUnmounted(() => {
    clearInterval(timer);
});

// --- HELPERS ---
const parseNumber = (val: any) => {
    if (!val) return 0;
    const cleanStr = String(val).replace(/[^0-9.-]/g, '');
    const num = parseFloat(cleanStr);
    return isNaN(num) ? 0 : num;
};

// Fungsi helper untuk menghitung akumulasi fee per trade
const getTotalFeePaid = (trade: any) => {
    const initialFee = parseNumber(trade.fee);
    const transactionFees = trade.transactions ? trade.transactions.reduce((sum: number, t: any) => {
        return sum + parseNumber(t.fee);
    }, 0) : 0;
    return initialFee + transactionFees;
};

// --- COMPUTED DATA ---
const holdingTrades = computed(() => {
    return props.trades
        .filter(t => t.status === 'OPEN') 
        .sort((a, b) => new Date(b.buy_date + 'T' + b.buy_time).getTime() - new Date(a.buy_date + 'T' + a.buy_time).getTime());
});

const activeTradeData = computed(() => {
    if (!expandedFormId.value) return null;
    return holdingTrades.value.find(t => t.id === expandedFormId.value);
});

const currentHoldingQty = computed(() => {
    return activeTradeData.value ? parseNumber(getQty(activeTradeData.value)) : 0;
});

const summaryMetrics = computed(() => {
    const trades = holdingTrades.value;
    const totalAssets = trades.length;
    
    const totalInvested = trades.reduce((sum, t) => {
        const currentQty = parseNumber(getQty(t));
        const avgPrice = parseNumber(getEntryPrice(t));
        const netCost = currentQty * avgPrice;
        const accumulativeFee = getTotalFeePaid(t);
        return sum + netCost + accumulativeFee;
    }, 0);
    
    return { totalAssets, totalInvested };
});

// --- DATA MAPPERS ---
const getEntryPrice = (t: any) => t.price || t.entry_price || 0;
const getQty = (t: any) => t.quantity || t.size || 0;
const getTargetTP = (t: any) => t.target_sell_price || t.tp_price || t.target_tp || 0;
const getTargetDCA = (t: any) => t.target_buy_price || t.dca_price || t.target_dca || 0;
const getNote = (t: any) => t.buy_notes || t.entry_notes || t.notes || t.note || t.strategy || null;
const getChartLink = (t: any) => t.buy_screenshot || t.buy_chart || t.entry_screenshot || null;

// --- FORM LOGIC ---
const form = useForm({
    type: 'BUY', 
    date: '',
    time: '',
    price: '',     
    quantity: '',  
    total_usd: '', 
    fee: 0,
    // [UPDATE 1] Tambahkan field ini agar controller menerimanya
    realized_pnl: null as number | null, 
    notes: '',
    screenshot: null as File | null,
});

// --- SMART INPUT LOGIC ---
// Watcher untuk Slider Persentase Sell (Fix: Konversi ke String)
watch(sellPercentage, (newPercent) => {
    if (transactionType.value === 'SELL' && currentHoldingQty.value > 0) {
        if (newPercent === 0) {
            form.quantity = '';
        } else if (newPercent === 100) {
            form.quantity = String(currentHoldingQty.value);
        } else {
            const calculateQty = (newPercent / 100) * currentHoldingQty.value;
            form.quantity = calculateQty.toFixed(8);
        }
    }
});

watch([transactionType, expandedFormId], () => {
    sellPercentage.value = 0;
});

watch(() => [form.price, form.quantity], () => {
    if (inputMode.value === 'COIN') {
        const p = parseNumber(form.price);
        const q = parseNumber(form.quantity);
        if (p && q) form.total_usd = (p * q).toFixed(2);
    }
});

watch(() => form.total_usd, (newVal) => {
    if (inputMode.value === 'USD') {
        const total = parseNumber(newVal);
        const p = parseNumber(form.price);
        if (total && p > 0) form.quantity = (total / p).toFixed(8); 
    }
});

// --- ESTIMASI KALKULASI ---
const calculationPreview = computed(() => {
    if (!expandedFormId.value) return null;
    const trade = holdingTrades.value.find(t => t.id === expandedFormId.value);
    if (!trade) return null;

    const currentQty = parseNumber(getQty(trade));
    const currentAvg = parseNumber(getEntryPrice(trade));
    const newPrice = parseNumber(form.price);
    const newQty = parseNumber(form.quantity);

    if (!newPrice || !newQty) return null;

    if (transactionType.value === 'BUY') {
        const totalCostOld = currentQty * currentAvg;
        const totalCostNew = newQty * newPrice;
        const totalQtyFinal = currentQty + newQty;
        const newAverage = (totalCostOld + totalCostNew) / totalQtyFinal;
        return { 
            type: 'BUY',
            label: 'New Avg Entry', 
            value: newAverage, 
            colorClass: 'text-emerald-400' 
        };
    } else {
        const revenue = newPrice * newQty;
        const cost = currentAvg * newQty;
        // Ini hanya preview visual
        const pnl = revenue - cost - parseNumber(form.fee); 
        return { 
            type: 'SELL',
            label: 'Est. Realized PnL', 
            pnlValue: pnl, 
            receivedValue: revenue, 
            colorClass: pnl >= 0 ? 'text-emerald-400' : 'text-red-400' 
        };
    }
});

// --- ACTIONS ---
const toggleInfo = (id: number) => {
    if (expandedInfoIds.value.has(id)) expandedInfoIds.value.delete(id);
    else expandedInfoIds.value.add(id);
};

const toggleManageForm = (trade: any) => {
    if (expandedFormId.value === trade.id) {
        expandedFormId.value = null; 
    } else {
        expandedFormId.value = trade.id; 
        form.reset();
        transactionType.value = 'BUY'; 
        form.type = 'BUY';
        form.price = String(getEntryPrice(trade));
        form.date = new Date().toISOString().split('T')[0];
        form.time = new Date().toTimeString().slice(0, 5);
    }
};

const switchMode = (mode: 'BUY' | 'SELL') => {
    transactionType.value = mode;
    form.type = mode;
    form.quantity = '';
    form.total_usd = '';
    form.realized_pnl = null; // Reset PnL
    if (mode === 'SELL' && activeTradeData.value) {
         form.price = String(getEntryPrice(activeTradeData.value));
    }
};

const handleFileChange = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files ? target.files[0] : null;
    if (file) {
        try {
            isCompressing.value = true;
            const compressedFile = await imageCompression(file, { maxSizeMB: 0.8, maxWidthOrHeight: 1920 });
            form.screenshot = new File([compressedFile], file.name, { type: file.type });
        } catch (e) { alert("Error compressing image"); } 
        finally { isCompressing.value = false; }
    }
};

// [UPDATE PENTING: Kalkulasi PnL sebelum Submit]
const submitTransaction = () => {
    if (!expandedFormId.value || isCompressing.value) return;

    // Hitung PnL secara manual jika tipe transaksi adalah SELL
    if (transactionType.value === 'SELL' && activeTradeData.value) {
        const sellPrice = parseNumber(form.price);
        const quantity = parseNumber(form.quantity);
        // Ambil harga beli rata-rata saat ini
        const avgEntryPrice = parseNumber(getEntryPrice(activeTradeData.value));
        
        // Rumus PnL Kotor: (Harga Jual - Harga Beli) * Jumlah
        // Fee biarkan terpisah (biar Dashboard yang kurangi sendiri)
        const grossPnL = (sellPrice - avgEntryPrice) * quantity;
        
        form.realized_pnl = parseFloat(grossPnL.toFixed(2));
    } else {
        form.realized_pnl = null; // BUY tidak punya Realized PnL
    }

    form.post(route('trade.log.transaction.spot', expandedFormId.value), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { expandedFormId.value = null; form.reset(); },
        onError: (errors) => { console.error("Submit Error:", errors); }
    });
};

const viewChart = (path: string) => {
    emit('view-chart', path, 'Buy');
};

const formatCurrency = (val: any) => {
    const num = parseNumber(val);
    if (num === 0) return '-';
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(num);
};
const formatNumber = (val: any) => {
    const num = parseNumber(val);
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 6 }).format(num);
};
const calculateTotalValue = (price: any, qty: any) => parseNumber(price) * parseNumber(qty);
const getHoldingDuration = (dateStr: string, timeStr: string) => {
    if (!dateStr) return '-';
    const dateTimeString = timeStr ? `${dateStr}T${timeStr}` : dateStr;
    const start = new Date(dateTimeString);
    if (isNaN(start.getTime())) return '-';
    const diffMs = now.value.getTime() - start.getTime();
    if (diffMs < 0) return 'Just started';
    const days = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
    let result = '';
    if (days > 0) result += `${days}d `;
    if (hours > 0) result += `${hours}h `;
    result += `${minutes}m`;
    return result || '0m';
};
</script>

<template>
    <div class="w-full">
        <div class="flex items-center gap-2 mb-6">
            <div class="w-1 h-4 bg-[#8c52ff] rounded-full"></div>
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Spot Active Holdings</h3>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-8">
            <div class="relative group h-full">
                <div class="p-[2px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-[0_0_15px_rgba(140,82,255,0.2)] h-full">
                    <div class="bg-[#121317] rounded-xl p-6 flex flex-col items-center justify-center h-full">
                        <span class="text-[10px] text-gray-400 font-black uppercase tracking-[0.15em] mb-2">Active Assets</span>
                        <span class="text-4xl font-black text-white tracking-tight">{{ summaryMetrics.totalAssets }}</span>
                    </div>
                </div>
            </div>
            <div class="relative group h-full">
                <div class="p-[2px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-[0_0_15px_rgba(140,82,255,0.2)] h-full">
                    <div class="bg-[#121317] rounded-xl p-6 flex flex-col items-center justify-center h-full">
                        <span class="text-[10px] text-gray-400 font-black uppercase tracking-[0.15em] mb-2">Total Invested (Inc. Fee)</span>
                        <span class="text-4xl font-black text-white tracking-tight">{{ formatCurrency(summaryMetrics.totalInvested) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-start">
            <div v-for="trade in holdingTrades" :key="trade.id" class="relative group">
                <div class="p-[2px] rounded-2xl bg-gradient-to-br from-[#8c52ff] to-[#5ce1e6] shadow-[0_0_15px_rgba(140,82,255,0.15)] hover:shadow-[0_0_25px_rgba(92,225,230,0.3)] transition-all duration-300 h-full">
                    <div class="bg-[#0f1012] rounded-2xl h-full flex flex-col justify-between overflow-hidden relative">
                        <div class="p-5">
                            <div class="flex justify-between items-start mb-6">
                                <div class="flex flex-col">
                                    <div class="flex items-baseline gap-2">
                                        <h3 class="text-3xl font-black text-white tracking-wide">{{ trade.symbol }}</h3>
                                        <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wide bg-[#1a1b20] px-2 py-0.5 rounded border border-[#2d2f36] -translate-y-[2px]">
                                            {{ trade.trading_account?.name || 'SPOT' }}
                                        </span>
                                    </div>
                                    <div class="mt-1 flex gap-1">
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded border border-gray-700 text-gray-400 uppercase">
                                            {{ trade.holding_period || 'SPOT' }}
                                        </span>
                                    </div>
                                </div>
                                <span class="bg-[#ffff00] text-black px-3 py-1 rounded-md text-xs font-black uppercase tracking-wider shadow-[0_0_10px_rgba(255,255,0,0.3)]">HOLDING</span>
                            </div>

                            <div class="grid grid-cols-3 gap-3 mb-4 text-center">
                                <div class="flex flex-col items-center justify-center p-3 rounded-xl bg-[#1a1b20] border border-[#2d2f36]">
                                    <span class="text-[9px] text-gray-500 uppercase font-bold mb-1">Average Price</span>
                                    <span class="text-sm font-mono text-white font-bold tracking-wide">{{ formatCurrency(getEntryPrice(trade)) }}</span>
                                </div>
                                <div class="flex flex-col items-center justify-center p-3 rounded-xl bg-[#1a1b20] border border-[#2d2f36]">
                                    <span class="text-[9px] text-green-500 uppercase font-bold mb-1">Target TP</span>
                                    <span class="text-sm font-mono text-green-400 font-bold tracking-wide">{{ formatCurrency(getTargetTP(trade)) }}</span>
                                </div>
                                <div class="flex flex-col items-center justify-center p-3 rounded-xl bg-[#1a1b20] border border-[#2d2f36]">
                                    <span class="text-[9px] text-purple-400 uppercase font-bold mb-1">Target DCA</span>
                                    <span class="text-sm font-mono text-purple-300 font-bold tracking-wide">{{ formatCurrency(getTargetDCA(trade)) }}</span>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="text-[10px] font-bold text-gray-400 tracking-wide flex items-center gap-1.5">
                                    <svg class="w-3 h-3 text-[#ffff00]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    Holding for: <span class="text-white ml-1">{{ getHoldingDuration(trade.buy_date, trade.buy_time) }}</span>
                                </div>
                                <div class="h-[1px] w-full bg-[#2d2f36] mt-3"></div>
                            </div>

                            <div class="flex justify-between items-end">
                                <div>
                                    <div class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mb-1">Total Value (Gross)</div>
                                    <div class="text-2xl font-black text-white tracking-tight">{{ formatCurrency(calculateTotalValue(getEntryPrice(trade), getQty(trade))) }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mb-1">Quantity</div>
                                    <div class="text-sm font-mono text-gray-300">{{ formatNumber(getQty(trade)) }} <span class="text-[10px] text-gray-500 font-bold ml-0.5">{{ trade.symbol }}</span></div>
                                </div>
                            </div>
                        </div>

                        <div v-if="expandedFormId === trade.id" class="bg-[#1a1b20] p-5 border-t border-b border-[#2d2f36] animate-fade-in-down relative">
                            <div class="flex p-1 bg-[#0f1012] rounded-lg mb-4 border border-[#2d2f36]">
                                <button @click="switchMode('BUY')" 
                                    class="flex-1 py-1.5 text-[10px] font-bold uppercase tracking-wider rounded transition-all"
                                    :class="transactionType === 'BUY' ? 'bg-emerald-600 text-white shadow-[0_0_10px_rgba(5,150,105,0.4)]' : 'text-gray-500 hover:text-gray-300'">
                                    Buy More / DCA
                                </button>
                                <button @click="switchMode('SELL')"
                                    class="flex-1 py-1.5 text-[10px] font-bold uppercase tracking-wider rounded transition-all"
                                    :class="transactionType === 'SELL' ? 'bg-red-600 text-white shadow-[0_0_10px_rgba(220,38,38,0.4)]' : 'text-gray-500 hover:text-gray-300'">
                                    Sell / Exit
                                </button>
                            </div>

                            <form @submit.prevent="submitTransaction">
                                <div class="grid grid-cols-2 gap-3 mb-3">
                                    <div>
                                        <label class="text-[9px] font-bold text-gray-500 uppercase block mb-1">
                                            {{ transactionType === 'BUY' ? 'Buy Price' : 'Sell Price' }}
                                        </label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-2 text-gray-500 text-xs">$</span>
                                            <input v-model="form.price" type="number" step="any" 
                                                class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-xs rounded p-2 pl-6 outline-none font-mono transition-colors"
                                                :class="transactionType === 'BUY' ? 'focus:border-emerald-500' : 'focus:border-red-500'"
                                                placeholder="0.00">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-[9px] font-bold text-gray-500 uppercase block mb-1">Fee ($)</label>
                                        <input v-model="form.fee" type="number" step="any" 
                                            class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-xs rounded p-2 outline-none font-mono transition-colors"
                                            :class="transactionType === 'BUY' ? 'focus:border-emerald-500' : 'focus:border-red-500'"
                                            placeholder="0.00">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="text-[9px] font-bold text-gray-500 uppercase">Amount</label>
                                        <button type="button" @click="inputMode = inputMode === 'COIN' ? 'USD' : 'COIN'" 
                                            class="text-[9px] hover:underline flex items-center gap-1 transition-colors"
                                            :class="transactionType === 'BUY' ? 'text-emerald-500' : 'text-red-500'">
                                            By {{ inputMode === 'COIN' ? 'USD Value' : 'Coin Qty' }} <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                                        </button>
                                    </div>
                                    <div class="flex gap-2">
                                        <div class="relative flex-1" v-if="inputMode === 'COIN'">
                                            <input v-model="form.quantity" type="number" step="any" 
                                                class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-xs rounded p-2 outline-none font-mono transition-colors"
                                                :class="transactionType === 'BUY' ? 'focus:border-emerald-500' : 'focus:border-red-500'"
                                                placeholder="Qty">
                                            <span class="absolute right-3 top-2 text-gray-500 text-[10px]">{{ trade.symbol }}</span>
                                        </div>
                                        <div class="relative flex-1" v-if="inputMode === 'USD'">
                                            <span class="absolute left-3 top-2 text-gray-500 text-xs">$</span>
                                            <input v-model="form.total_usd" type="number" step="any" 
                                                class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-xs rounded p-2 pl-6 outline-none font-mono transition-colors"
                                                :class="transactionType === 'BUY' ? 'focus:border-emerald-500' : 'focus:border-red-500'"
                                                placeholder="Total USD">
                                        </div>
                                    </div>
                                    <div class="text-[9px] text-gray-500 text-right mt-1 font-mono">
                                        <span v-if="inputMode === 'COIN'">≈ ${{ form.total_usd || '0.00' }}</span>
                                        <span v-else>≈ {{ form.quantity || '0' }} {{ trade.symbol }}</span>
                                    </div>
                                </div>

                                <div v-if="transactionType === 'SELL'" class="mb-4 animate-fade-in-down">
                                    <div class="flex justify-between items-center mb-2">
                                        <label class="text-[9px] font-bold text-gray-500 uppercase">Sell Percentage</label>
                                        <span class="text-xs font-bold text-red-400 font-mono">{{ sellPercentage }}%</span>
                                    </div>
                                    <div class="relative px-1">
                                        <input type="range" v-model.number="sellPercentage" min="0" max="100" step="1"
                                            class="w-full h-2 bg-[#0a0b0d] rounded-lg appearance-none cursor-pointer range-slider border border-[#2d2f36] relative z-10"
                                            :style="`background: linear-gradient(to right, #ef4444 ${sellPercentage}%, #0a0b0d ${sellPercentage}%)`">
                                        
                                        <div class="absolute top-[4px] left-0 w-full flex justify-between px-1 pointer-events-none z-0">
                                            <span v-for="point in [0, 25, 50, 75, 100]" :key="point"
                                                class="w-2 h-2 rounded-full border border-[#0a0b0d] transition-colors duration-300"
                                                :class="sellPercentage >= point ? 'bg-[#ef4444]' : 'bg-[#2d2f36]'">
                                            </span>
                                        </div>

                                        <div class="flex justify-between text-[8px] text-gray-600 font-bold uppercase mt-3 px-0.5 font-mono relative z-10">
                                            <span @click="sellPercentage = 0" class="cursor-pointer hover:text-gray-400">0%</span>
                                            <span @click="sellPercentage = 25" class="cursor-pointer hover:text-gray-400">25%</span>
                                            <span @click="sellPercentage = 50" class="cursor-pointer hover:text-gray-400">50%</span>
                                            <span @click="sellPercentage = 75" class="cursor-pointer hover:text-gray-400">75%</span>
                                            <span @click="sellPercentage = 100" class="cursor-pointer hover:text-gray-400">100%</span>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="calculationPreview" class="bg-[#0a0b0d] p-3 rounded border border-[#2d2f36] mb-3 space-y-2">
                                    <div v-if="calculationPreview.type === 'BUY'" class="flex justify-between items-center">
                                        <span class="text-[10px] text-gray-500 font-bold uppercase">{{ calculationPreview.label }}</span>
                                        <span class="text-sm font-black font-mono" :class="calculationPreview.colorClass">
                                            {{ formatCurrency(calculationPreview.value) }}
                                        </span>
                                    </div>

                                    <div v-else class="space-y-2">
                                        <div class="flex justify-between items-center border-b border-[#1f2128] pb-2">
                                            <span class="text-[10px] text-gray-500 font-bold uppercase">Total Received (USD)</span>
                                            <span class="text-sm font-black font-mono text-white">
                                                {{ formatCurrency(calculationPreview.receivedValue) }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-[10px] text-gray-500 font-bold uppercase">Net Profit (After Fee)</span>
                                            <span class="text-sm font-black font-mono" :class="calculationPreview.colorClass">
                                                {{ formatCurrency(calculationPreview.pnlValue) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex gap-2 mb-3">
                                    <input type="text" v-model="form.notes" :placeholder="transactionType === 'BUY' ? 'DCA Reason...' : 'Sell Reason...'" 
                                        class="flex-1 bg-[#0a0b0d] border border-[#2d2f36] text-gray-300 text-xs rounded p-2 outline-none transition-colors"
                                        :class="transactionType === 'BUY' ? 'focus:border-emerald-500' : 'focus:border-red-500'">
                                    
                                    <label class="flex items-center justify-center w-10 bg-[#0a0b0d] border border-[#2d2f36] rounded cursor-pointer text-gray-400 hover:text-white transition-colors hover:border-gray-500">
                                        <span class="text-xs">{{ form.screenshot ? '📷' : '+' }}</span>
                                        <input type="file" @change="handleFileChange" accept="image/*" class="hidden">
                                    </label>
                                </div>

                                <div class="grid grid-cols-2 gap-3 mb-3">
                                    <div>
                                        <label class="text-[9px] font-bold text-gray-500 uppercase block mb-1">Date</label>
                                        <input v-model="form.date" type="date" 
                                            class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-xs rounded p-2 outline-none transition-colors"
                                            :class="transactionType === 'BUY' ? 'focus:border-emerald-500' : 'focus:border-red-500'">
                                    </div>
                                    <div>
                                        <label class="text-[9px] font-bold text-gray-500 uppercase block mb-1">Time</label>
                                        <input v-model="form.time" type="time" 
                                            class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-xs rounded p-2 outline-none transition-colors"
                                            :class="transactionType === 'BUY' ? 'focus:border-emerald-500' : 'focus:border-red-500'">
                                    </div>
                                </div>

                                <button type="submit" :disabled="form.processing" 
                                    class="w-full py-2.5 rounded text-white text-xs font-black uppercase tracking-wider transition-colors shadow-lg"
                                    :class="transactionType === 'BUY' ? 'bg-emerald-600 hover:bg-emerald-500 shadow-[0_0_15px_rgba(5,150,105,0.3)]' : 'bg-red-600 hover:bg-red-500 shadow-[0_0_15px_rgba(220,38,38,0.3)]'">
                                    CONFIRM {{ transactionType }}
                                </button>
                            </form>
                        </div>

                        <div v-if="expandedInfoIds.has(trade.id)" class="bg-[#0a0b0d] p-5 border-t border-[#2d2f36] animate-fade-in-down">
                            <div class="mb-3">
                                <h4 class="text-[10px] text-emerald-400 uppercase font-bold mb-1">Total Accumulative Fees</h4>
                                <div class="text-xs text-white font-mono bg-[#1a1b20] p-2 rounded border border-[#2d2f36] flex justify-between">
                                    <span class="text-gray-400">Initial + DCA + Sells</span>
                                    <span class="text-emerald-300 font-bold">{{ formatCurrency(getTotalFeePaid(trade)) }}</span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <h4 class="text-[10px] text-blue-400 uppercase font-bold mb-1">Entry Notes</h4>
                                <div class="text-xs text-gray-300 italic leading-relaxed bg-[#1a1b20] p-2 rounded border border-[#2d2f36]">
                                    {{ getNote(trade) || 'No notes available.' }}
                                </div>
                            </div>

                            <div class="flex justify-between items-center mt-2 border-t border-[#2d2f36] pt-2">
                                <div>
                                    <h4 class="text-[9px] text-gray-500 uppercase font-bold mb-1">Chart Analysis</h4>
                                    <button v-if="getChartLink(trade)" 
                                       @click="viewChart(getChartLink(trade))"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded bg-[#5ce1e6]/10 text-[#5ce1e6] text-[10px] font-bold border border-[#5ce1e6]/30 hover:bg-[#5ce1e6]/20 transition-colors">
                                        View Chart <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                    </button>
                                    <span v-else class="text-[10px] text-gray-600 italic">No chart linked</span>
                                </div>
                                <div class="text-right">
                                    <h4 class="text-[9px] text-gray-500 uppercase font-bold">Buy Date</h4>
                                    <span class="text-[10px] text-gray-400 font-mono">{{ trade.buy_date }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-[#2d2f36]">
                            <button @click="toggleManageForm(trade)" 
                                class="w-full py-4 text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center gap-2 bg-[#121317] hover:bg-[#1a1b20] text-gray-400 hover:text-white group-hover:text-[#5ce1e6]">
                                {{ expandedFormId === trade.id ? 'CLOSE FORM' : 'MANAGE POSITION' }}
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                            </button>
                        </div>

                        <button @click="toggleInfo(trade.id)" class="w-full py-2.5 text-[10px] font-black uppercase tracking-[0.2em] text-black bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] hover:opacity-90 transition-opacity flex items-center justify-center gap-2">
                            {{ expandedInfoIds.has(trade.id) ? 'LESS INFO' : 'MORE INFO' }}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform duration-300" :class="{'rotate-180': expandedInfoIds.has(trade.id)}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="holdingTrades.length === 0" class="col-span-full py-12 flex flex-col items-center justify-center border border-dashed border-[#2d2f36] rounded-2xl text-gray-500 bg-[#0f1012]/50">
                <svg class="w-10 h-10 mb-3 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 12H4" /></svg>
                <span class="text-xs font-bold uppercase tracking-widest">No Active Spot Holdings Found.</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* HILANGKAN PANAH INPUT NUMBER */
input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
  -webkit-appearance: none; 
  margin: 0; 
}
input[type=number] {
  -webkit-appearance: none;
  -moz-appearance: textfield;
  appearance: textfield;
}

/* RANGE SLIDER STYLING */
.range-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 14px;
    height: 14px;
    background: #ef4444;
    border-radius: 50%;
    cursor: pointer;
    border: 3px solid #0a0b0d;
    box-shadow: 0 0 10px rgba(239, 68, 68, 0.4);
    position: relative;
    z-index: 20;
}

.range-slider::-moz-range-thumb {
    width: 14px;
    height: 14px;
    background: #ef4444;
    border-radius: 50%;
    cursor: pointer;
    border: 3px solid #0a0b0d;
    box-shadow: 0 0 10px rgba(239, 68, 68, 0.4);
    position: relative;
    z-index: 20;
}

@keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in-down { animation: fadeInDown 0.3s ease-out forwards; }
input[type="date"]::-webkit-calendar-picker-indicator,
input[type="time"]::-webkit-calendar-picker-indicator { filter: invert(1); cursor: pointer; }
</style>
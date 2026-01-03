<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import imageCompression from 'browser-image-compression';

const props = defineProps<{
    trades: any[]; 
}>();

// Filter & Sort Open Trades
// Menggunakan entry_date + entry_time untuk sort
const openTrades = computed(() => {
    return props.trades
        .filter(t => t.status === 'OPEN')
        .sort((a, b) => {
            const dateA = new Date(a.entry_date + 'T' + a.entry_time).getTime();
            const dateB = new Date(b.entry_date + 'T' + b.entry_time).getTime();
            return dateB - dateA; // Newest first
        });
});

const expandedTradeId = ref<number | null>(null);
const activeAction = ref<'CLOSE' | 'CANCEL' | null>(null);
const isCompressing = ref(false);

// Helper Jam
const getCurrentTime = () => new Date().toTimeString().slice(0, 5);

// Form Close
const formClose = useForm({
    // [UPDATE] Split Date & Time
    exit_date: new Date().toISOString().split('T')[0],
    exit_time: getCurrentTime(),
    
    exit_price: '',
    fee: 0,
    exit_reason: 'Hit Take Profit (TP)',
    notes: '',
    exit_screenshot: null as File | null,
});

// Form Cancel
const formCancel = useForm({
    cancellation_note: '',
});

const formatCurrency = (val: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val);

const estimatedPnL = computed(() => {
    if (!expandedTradeId.value || !formClose.exit_price) return 0;
    const trade = openTrades.value.find(t => t.id === expandedTradeId.value);
    if (!trade) return 0;

    const exit = parseFloat(formClose.exit_price);
    const entry = parseFloat(trade.entry_price);
    const qty = parseFloat(trade.quantity);
    const fee = parseFloat(formClose.fee.toString()) || 0;

    let gross = 0;
    if (trade.type === 'LONG') {
        gross = (exit - entry) * qty;
    } else {
        gross = (entry - exit) * qty;
    }
    return gross - fee;
});

const estimatedROE = computed(() => {
    if (!expandedTradeId.value) return 0;
    const trade = openTrades.value.find(t => t.id === expandedTradeId.value);
    if (!trade || !trade.margin) return 0;
    return (estimatedPnL.value / parseFloat(trade.margin)) * 100;
});

const toggleExpand = (trade: any, action: 'CLOSE' | 'CANCEL') => {
    if (expandedTradeId.value === trade.id && activeAction.value === action) {
        expandedTradeId.value = null;
        activeAction.value = null;
    } else {
        expandedTradeId.value = trade.id;
        activeAction.value = action;
        
        if (action === 'CLOSE') {
            formClose.reset();
            formClose.exit_date = new Date().toISOString().split('T')[0];
            formClose.exit_time = getCurrentTime(); // Reset Time to now
            formClose.exit_price = parseFloat(trade.entry_price).toString(); 
        } else {
            formCancel.reset();
        }
    }
};

const handleFileChange = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files ? target.files[0] : null;
    if (file) {
        const options = { maxSizeMB: 0.8, maxWidthOrHeight: 1920, useWebWorker: true };
        try {
            isCompressing.value = true;
            const compressedFile = await imageCompression(file, options);
            formClose.exit_screenshot = new File([compressedFile], file.name, { type: file.type });
        } catch (error) {
            alert("Compression failed.");
        } finally {
            isCompressing.value = false;
        }
    }
};

const submitClose = () => {
    if (!expandedTradeId.value) return;
    if (isCompressing.value) { alert("Uploading image..."); return; }

    formClose.post(route('trade.log.close', expandedTradeId.value), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            expandedTradeId.value = null;
            activeAction.value = null;
            formClose.reset();
        },
        onError: (e) => console.error(e)
    });
};

const submitCancel = () => {
    if (!expandedTradeId.value) return;
    if (!formCancel.cancellation_note) {
        alert("Please provide a reason for cancellation.");
        return;
    }
    formCancel.post(route('trade.log.cancel', expandedTradeId.value), {
        preserveScroll: true,
        onSuccess: () => {
            expandedTradeId.value = null;
            activeAction.value = null;
            formCancel.reset();
        }
    });
};
</script>

<template>
    <div class="space-y-4">
        
        <div v-if="openTrades.length === 0" class="text-center py-12 bg-[#121317] border border-[#1f2128] border-dashed rounded-xl">
            <p class="text-gray-500 text-sm">No open positions found.</p>
            <p class="text-xs text-gray-600 mt-1">Go to "Open Position" tab to start a new trade.</p>
        </div>

        <div v-else class="space-y-3">
            <div v-for="trade in openTrades" :key="trade.id" class="bg-[#121317] border border-[#1f2128] rounded-xl overflow-hidden transition-all hover:border-gray-600">
                
                <div class="p-4 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="flex items-center gap-4 w-full md:w-auto">
                        <div class="px-3 py-1.5 rounded text-[10px] font-black uppercase border tracking-wider"
                            :class="trade.type === 'LONG' ? 'bg-green-900/10 text-green-500 border-green-500/20' : 'bg-red-900/10 text-red-500 border-red-500/20'">
                            {{ trade.type }}
                        </div>
                        <div>
                            <div class="font-bold text-white flex items-center gap-2">
                                {{ trade.symbol }} 
                                <span class="text-[10px] text-gray-500 bg-[#1f2128] px-1.5 rounded">{{ trade.leverage }}x</span>
                                <span class="text-[10px] text-blue-400 bg-blue-500/10 border border-blue-500/20 px-1.5 rounded ml-1">{{ trade.trading_account ? trade.trading_account.name : 'Unknown' }}</span>
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ trade.entry_date }} <span class="text-[10px] opacity-70">{{ trade.entry_time.slice(0,5) }}</span> &bull; Entry: {{ formatCurrency(trade.entry_price) }}
                            </div>
                        </div>
                    </div>

                    <div class="hidden lg:block text-center min-w-[140px]">
                        <div class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mb-0.5">TP / SL</div>
                        <div class="text-xs font-mono">
                            <span :class="trade.tp_price ? 'text-green-400' : 'text-gray-600'">{{ trade.tp_price ? formatCurrency(trade.tp_price) : '-' }}</span>
                            <span class="text-gray-600 mx-1">/</span>
                            <span :class="trade.sl_price ? 'text-red-400' : 'text-gray-600'">{{ trade.sl_price ? formatCurrency(trade.sl_price) : '-' }}</span>
                        </div>
                    </div>

                    <div class="text-right hidden md:block">
                        <div class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mb-0.5">Margin</div>
                        <div class="text-sm font-bold text-white">{{ formatCurrency(trade.margin) }}</div>
                    </div>

                    <div class="flex items-center gap-2 w-full md:w-auto">
                        <button @click="toggleExpand(trade, 'CANCEL')" class="px-4 py-2 text-xs font-bold uppercase rounded transition-all border border-red-900/30 hover:bg-red-900/20 text-red-500" :class="{'bg-red-900/30': expandedTradeId === trade.id && activeAction === 'CANCEL'}">
                            {{ (expandedTradeId === trade.id && activeAction === 'CANCEL') ? 'Cancel' : 'Cancel' }}
                        </button>
                        <button @click="toggleExpand(trade, 'CLOSE')" class="px-4 py-2 text-xs font-bold uppercase rounded transition-all flex items-center justify-center gap-2" :class="(expandedTradeId === trade.id && activeAction === 'CLOSE') ? 'bg-gray-700 text-white' : 'bg-yellow-600 hover:bg-yellow-500 text-black shadow-lg shadow-yellow-500/20'">
                            {{ (expandedTradeId === trade.id && activeAction === 'CLOSE') ? 'Close' : 'Close Position' }}
                        </button>
                    </div>
                </div>

                <div v-if="expandedTradeId === trade.id" class="border-t border-[#1f2128] bg-[#1a1b20]/30 p-5 animate-fade-in-down">
                    
                    <form v-if="activeAction === 'CLOSE'" @submit.prevent="submitClose">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                            <div class="col-span-1 md:col-span-1">
                                <label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Exit Price ($)</label>
                                <input v-model="formClose.exit_price" type="number" step="any" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-sm rounded p-2.5 font-mono focus:border-yellow-500 outline-none" placeholder="0.00" required>
                            </div>
                            
                            <div class="col-span-1">
                                <label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Exit Date & Time</label>
                                <div class="flex gap-2">
                                    <input v-model="formClose.exit_date" type="date" class="w-2/3 bg-[#0a0b0d] border border-[#2d2f36] text-gray-400 text-xs rounded p-2.5 focus:border-yellow-500 outline-none">
                                    <input v-model="formClose.exit_time" type="time" class="w-1/3 bg-[#0a0b0d] border border-[#2d2f36] text-gray-400 text-xs rounded p-2.5 focus:border-yellow-500 outline-none">
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Exit Reason</label>
                                <select v-model="formClose.exit_reason" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-xs rounded p-2.5 focus:border-yellow-500 outline-none">
                                    <option value="Hit Take Profit (TP)">Hit Take Profit (TP)</option>
                                    <option value="Hit Stop Loss (SL)">Hit Stop Loss (SL)</option>
                                    <option value="Manual Profit">Manual Close (Profit)</option>
                                    <option value="Manual Loss">Manual Close (Loss)</option>
                                    <option value="Trailing Stop">Trailing Stop</option>
                                    <option value="Market Structure">Market Structure Change</option>
                                    <option value="News">News / Event</option>
                                    <option value="Panic">Panic / Emotional</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Total Fee ($)</label>
                                <input v-model="formClose.fee" type="number" step="any" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-sm rounded p-2.5 font-mono focus:border-yellow-500 outline-none" placeholder="0.00">
                            </div>
                        </div>

                        <div class="bg-[#0a0b0d] border border-[#2d2f36] rounded-lg p-4 mb-4 flex justify-between items-center">
                            <div>
                                <div class="text-[10px] text-gray-500 uppercase font-bold">Est. Net PnL</div>
                                <div class="text-xl font-black font-mono" :class="estimatedPnL >= 0 ? 'text-green-500' : 'text-red-500'">
                                    {{ estimatedPnL >= 0 ? '+' : '' }}{{ formatCurrency(estimatedPnL) }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] text-gray-500 uppercase font-bold">ROE %</div>
                                <div class="text-lg font-bold" :class="estimatedROE >= 0 ? 'text-green-500' : 'text-red-500'">
                                    {{ estimatedROE.toFixed(2) }}%
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-4 gap-3 mb-4">
                            <div class="col-span-3">
                                <label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Lesson / Reflection</label>
                                <input v-model="formClose.notes" type="text" placeholder="Why did I close here?" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-gray-300 text-sm rounded p-2.5 focus:border-yellow-500 outline-none">
                            </div>
                            <div class="col-span-1 relative">
                                <label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Exit Chart (Optional)</label>
                                <label class="flex items-center justify-center w-full h-[40px] bg-[#0a0b0d] border border-[#2d2f36] rounded cursor-pointer hover:border-gray-500 text-gray-400 text-xs overflow-hidden">
                                    <span v-if="isCompressing" class="animate-pulse text-yellow-500">...</span>
                                    <span v-else-if="formClose.exit_screenshot" class="text-blue-400 truncate px-1">{{ formClose.exit_screenshot.name }}</span>
                                    <span v-else>+ Upload</span>
                                    <input type="file" @change="handleFileChange" accept="image/*" class="hidden">
                                </label>
                            </div>
                        </div>

                        <button type="submit" :disabled="formClose.processing || isCompressing" class="w-full py-3 rounded text-sm font-black bg-yellow-600 hover:bg-yellow-500 text-black uppercase tracking-wider shadow-lg transition-all">
                            CONFIRM CLOSE
                        </button>
                    </form>

                    <form v-if="activeAction === 'CANCEL'" @submit.prevent="submitCancel">
                        <div class="bg-red-900/10 border border-red-900/30 rounded p-4 mb-4">
                            <p class="text-red-400 text-xs font-bold uppercase mb-1 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                Warning: Cancelling Trade
                            </p>
                            <p class="text-gray-400 text-xs leading-relaxed">
                                This action will <strong>VOID</strong> the trade. No PnL will be recorded.
                            </p>
                        </div>
                        <div class="mb-4">
                            <label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Reason for Cancellation</label>
                            <input v-model="formCancel.cancellation_note" type="text" placeholder="e.g. Wrong entry price, accidental click..." class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-sm rounded p-3 focus:border-red-500 outline-none" required>
                        </div>
                        <button type="submit" :disabled="formCancel.processing" class="w-full py-3 rounded text-sm font-bold bg-red-600 hover:bg-red-500 text-white uppercase tracking-wider shadow-lg transition-all">
                            CONFIRM CANCEL
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</template>

<style>
@keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in-down { animation: fadeInDown 0.3s ease-out forwards; }
</style>
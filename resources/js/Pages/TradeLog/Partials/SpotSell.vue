<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import imageCompression from 'browser-image-compression';

// [FIX] Hapus 'accounts' dari sini karena SpotSell tidak butuh data akun
const props = defineProps<{ 
    trades: any[] 
}>();

// Filter Holding (Status OPEN)
const holdingTrades = computed(() => {
    return props.trades
        .filter(t => t.status === 'OPEN')
        .sort((a, b) => new Date(b.buy_date + 'T' + b.buy_time).getTime() - new Date(a.buy_date + 'T' + a.buy_time).getTime());
});

const expandedTradeId = ref<number | null>(null);
const isCompressing = ref(false);
const getCurrentTime = () => new Date().toTimeString().slice(0, 5);

const formSell = useForm({
    sell_date: new Date().toISOString().split('T')[0],
    sell_time: getCurrentTime(),
    sell_price: '',
    fee: 0,
    notes: '',
    sell_screenshot: null as File | null,
});

const formatCurrency = (val: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val);

const toggleExpand = (trade: any) => {
    if (expandedTradeId.value === trade.id) {
        expandedTradeId.value = null;
    } else {
        expandedTradeId.value = trade.id;
        formSell.reset();
        formSell.sell_date = new Date().toISOString().split('T')[0];
        formSell.sell_time = getCurrentTime();
    }
};

const estimatedPnL = computed(() => {
    if (!expandedTradeId.value || !formSell.sell_price) return 0;
    const trade = holdingTrades.value.find(t => t.id === expandedTradeId.value);
    if (!trade) return 0;
    const revenue = parseFloat(formSell.sell_price) * parseFloat(trade.quantity);
    const cost = parseFloat(trade.price) * parseFloat(trade.quantity);
    return revenue - cost - parseFloat(formSell.fee.toString() || '0');
});

const handleFileChange = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files ? target.files[0] : null;
    if (file) {
        try {
            isCompressing.value = true;
            const compressedFile = await imageCompression(file, { maxSizeMB: 0.8, maxWidthOrHeight: 1920 });
            formSell.sell_screenshot = new File([compressedFile], file.name, { type: file.type });
        } catch (e) { alert("Error"); } 
        finally { isCompressing.value = false; }
    }
};

const submitSell = () => {
    if (!expandedTradeId.value || isCompressing.value) return;
    formSell.post(route('trade.log.sell.spot', expandedTradeId.value), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { expandedTradeId.value = null; formSell.reset(); }
    });
};
</script>

<template>
    <div class="space-y-4">
        <div v-if="holdingTrades.length === 0" class="text-center py-12 bg-[#121317] border border-[#1f2128] border-dashed rounded-xl text-gray-500 text-sm">
            No assets currently held in Spot.
        </div>

        <div v-else class="space-y-3">
            <div v-for="trade in holdingTrades" :key="trade.id" class="bg-[#121317] border border-[#1f2128] rounded-xl overflow-hidden hover:border-gray-600 transition-colors">
                
                <div class="p-4 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="flex items-center gap-4 w-full md:w-auto">
                        <div class="px-3 py-1.5 rounded text-[10px] font-black uppercase border tracking-wider bg-emerald-900/10 text-emerald-500 border-emerald-500/20">SPOT</div>
                        <div>
                            <div class="font-bold text-white flex items-center gap-2">
                                {{ trade.symbol }} <span class="text-[10px] text-gray-500 bg-[#1f2128] px-1.5 rounded">{{ trade.holding_period }}</span>
                            </div>
                            <div class="text-xs text-gray-500">{{ trade.buy_date }} &bull; Qty: {{ trade.quantity }}</div>
                        </div>
                    </div>

                    <div class="hidden lg:block text-center min-w-[140px]">
                        <div class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mb-0.5">Target Sell</div>
                        <div class="text-xs font-mono text-emerald-400">{{ trade.target_sell_price ? formatCurrency(trade.target_sell_price) : '-' }}</div>
                    </div>

                    <button @click="toggleExpand(trade)" class="px-6 py-2 text-xs font-bold uppercase rounded transition-all bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-900/20">
                        {{ expandedTradeId === trade.id ? 'Cancel' : 'SELL' }}
                    </button>
                </div>

                <div v-if="expandedTradeId === trade.id" class="border-t border-[#1f2128] bg-[#1a1b20]/30 p-5 animate-fade-in-down">
                    <form @submit.prevent="submitSell">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Sell Price ($)</label>
                                <input v-model="formSell.sell_price" type="number" step="any" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-sm rounded p-2.5 focus:border-blue-500 outline-none font-mono" placeholder="0.00" required>
                            </div>
                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Sell Date & Time</label>
                                <div class="flex gap-2">
                                    <input v-model="formSell.sell_date" type="date" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-gray-400 text-xs rounded p-2.5 focus:border-blue-500 outline-none">
                                    <input v-model="formSell.sell_time" type="time" class="w-1/3 bg-[#0a0b0d] border border-[#2d2f36] text-gray-400 text-xs rounded p-2.5 focus:border-blue-500 outline-none">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Fee ($)</label>
                                <input v-model="formSell.fee" type="number" step="any" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-sm rounded p-2.5 focus:border-blue-500 outline-none" placeholder="0.00">
                            </div>
                        </div>

                        <div class="flex justify-between items-center bg-[#0a0b0d] p-3 rounded-lg border border-[#2d2f36] mb-4">
                            <span class="text-[10px] text-gray-500 font-bold uppercase">Est. PnL</span>
                            <span class="text-lg font-black font-mono" :class="estimatedPnL >= 0 ? 'text-emerald-500' : 'text-red-500'">{{ estimatedPnL >= 0 ? '+' : '' }}{{ formatCurrency(estimatedPnL) }}</span>
                        </div>

                        <div class="flex gap-4 mb-4">
                            <input type="text" v-model="formSell.notes" placeholder="Sell Reason..." class="flex-1 bg-[#0a0b0d] border border-[#2d2f36] text-gray-300 text-xs rounded p-2.5 focus:border-blue-500 outline-none">
                            <label class="flex items-center justify-center w-32 bg-[#0a0b0d] border border-[#2d2f36] rounded cursor-pointer hover:border-gray-500">
                                <span class="text-[9px] text-gray-500">{{ formSell.sell_screenshot ? 'Image Selected' : '+ Sell Chart' }}</span>
                                <input type="file" @change="handleFileChange" accept="image/*" class="hidden">
                            </label>
                        </div>

                        <button type="submit" :disabled="formSell.processing" class="w-full py-3 rounded text-sm font-black bg-blue-600 hover:bg-blue-500 text-white uppercase tracking-wider">CONFIRM SELL</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.no-spinner { appearance: textfield; -moz-appearance: textfield; }
.no-spinner::-webkit-outer-spin-button, .no-spinner::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
input[type="time"]::-webkit-calendar-picker-indicator { filter: invert(1); cursor: pointer; }
@keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: fadeIn 0.2s ease-out forwards; }
</style>
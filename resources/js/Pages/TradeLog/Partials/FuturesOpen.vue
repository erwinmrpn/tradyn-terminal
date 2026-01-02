<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import imageCompression from 'browser-image-compression';

const props = defineProps<{
    accounts: any[];
}>();

// Helper Jam saat ini (HH:mm)
const getCurrentTime = () => {
    const now = new Date();
    return now.toTimeString().slice(0, 5);
};

const isCompressing = ref(false);
const inputMode = ref<'ASSET' | 'TOTAL'>('ASSET'); // Input dalam Koin atau USD
const dynamicInput = ref(''); 

const form = useForm({
    trading_account_id: props.accounts.length > 0 ? props.accounts[0].id : '',
    
    // [UPDATE] Date & Time Terpisah
    date: new Date().toISOString().split('T')[0],
    time: getCurrentTime(),

    symbol: '',
    market_type: 'CRYPTO',
    type: 'LONG',
    leverage: 10,
    margin_mode: 'CROSS',
    order_type: 'MARKET',
    price: '',
    quantity: '', 
    total: '',    
    tp_price: '',
    sl_price: '',
    notes: '', // Ini masuk entry_notes
    screenshot: null as File | null,
    form_type: 'FUTURES'
});

// Auto-calc Quantity/Total
watch([() => form.price, dynamicInput, inputMode], ([newPrice, newVal, mode]) => {
    const price = parseFloat(newPrice as string) || 0;
    const inputVal = parseFloat(newVal as string) || 0;
    if (price > 0 && inputVal > 0) {
        if (mode === 'ASSET') {
            form.quantity = inputVal.toString();
            form.total = (price * inputVal / form.leverage).toFixed(2); // Estimasi Margin
        } else {
            form.total = inputVal.toString();
            form.quantity = (inputVal * form.leverage / price).toFixed(8);
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
            form.screenshot = new File([compressedFile], file.name, { type: file.type });
        } catch (error) {
            alert("Compression failed.");
        } finally {
            isCompressing.value = false;
        }
    }
};

const submit = () => {
    if (isCompressing.value) return;
    form.post(route('trade.log.store'), {
        forceFormData: true,
        onSuccess: () => {
            form.reset('symbol', 'price', 'quantity', 'total', 'tp_price', 'sl_price', 'notes', 'screenshot');
            form.time = getCurrentTime(); // Reset jam ke sekarang
            dynamicInput.value = '';
            // Reset file input manual
            const fileInput = document.getElementById('file-upload-futures') as HTMLInputElement;
            if(fileInput) fileInput.value = '';
        },
        preserveScroll: true
    });
};
</script>

<template>
    <form @submit.prevent="submit" class="bg-[#121317] border border-[#1f2128] rounded-xl p-6 shadow-sm relative overflow-hidden animate-fade-in-down">
        
        <div class="flex justify-between items-start mb-6">
            <div>
                <h3 class="text-lg font-bold text-white">Open Futures Position</h3>
                <p class="text-xs text-gray-500 mt-1">Entry new trade manually.</p>
            </div>
            <div class="bg-[#0a0b0d] p-1 rounded-lg flex border border-[#2d2f36]">
                <button type="button" @click="form.type = 'LONG'" class="px-6 py-1.5 rounded text-xs font-bold transition-all" :class="form.type === 'LONG' ? 'bg-green-600 text-white shadow-lg shadow-green-900/20' : 'text-gray-500 hover:text-gray-300'">LONG</button>
                <button type="button" @click="form.type = 'SHORT'" class="px-6 py-1.5 rounded text-xs font-bold transition-all" :class="form.type === 'SHORT' ? 'bg-red-600 text-white shadow-lg shadow-red-900/20' : 'text-gray-500 hover:text-gray-300'">SHORT</button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <div class="lg:col-span-8 space-y-4">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Date</label>
                            <input v-model="form.date" type="date" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-sm rounded p-2.5 focus:border-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Time</label>
                            <input v-model="form.time" type="time" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-sm rounded p-2.5 focus:border-blue-500 outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Account</label>
                        <select v-model="form.trading_account_id" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-sm rounded p-2.5 focus:border-blue-500 outline-none">
                            <option v-for="acc in props.accounts" :key="acc.id" :value="acc.id">{{ acc.name }} ({{ acc.exchange }}) - ${{ acc.balance }}</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Asset / Symbol</label>
                        <input v-model="form.symbol" type="text" placeholder="BTC" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-sm rounded p-2.5 focus:border-blue-500 outline-none uppercase font-bold">
                    </div>
                    <div>
                        <label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Market Type</label>
                        <select v-model="form.market_type" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-sm rounded p-2.5 focus:border-blue-500 outline-none">
                            <option value="CRYPTO">Crypto</option>
                            <option value="FOREX">Forex</option>
                            <option value="STOCKS">Stocks</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Lev (x)</label>
                        <input v-model="form.leverage" type="number" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-sm rounded p-2.5 focus:border-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Mode</label>
                        <select v-model="form.margin_mode" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-xs rounded p-2.5 focus:border-blue-500 outline-none">
                            <option value="CROSS">Cross</option>
                            <option value="ISOLATED">Isolated</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Order</label>
                        <select v-model="form.order_type" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-xs rounded p-2.5 focus:border-blue-500 outline-none">
                            <option value="MARKET">Market</option>
                            <option value="LIMIT">Limit</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Entry Price ($)</label>
                        <input v-model="form.price" type="number" step="any" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-sm rounded p-2.5 focus:border-blue-500 outline-none font-mono" placeholder="0.00">
                    </div>
                    <div>
                        <div class="flex justify-between mb-1">
                            <label class="block text-[10px] text-gray-500 font-bold uppercase">Size</label>
                            <button type="button" @click="inputMode = inputMode === 'ASSET' ? 'TOTAL' : 'ASSET'" class="text-[9px] text-blue-400 hover:underline uppercase font-bold">
                                Switch to {{ inputMode === 'ASSET' ? 'Margin ($)' : 'Qty (Coin)' }}
                            </button>
                        </div>
                        <div class="relative">
                            <input v-model="dynamicInput" type="number" step="any" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-sm rounded p-2.5 focus:border-blue-500 outline-none font-mono" :placeholder="inputMode === 'ASSET' ? 'Qty (e.g 0.1)' : 'Margin (e.g 100)'">
                            <span class="absolute right-3 top-2.5 text-xs text-gray-500 font-bold">{{ inputMode === 'ASSET' ? 'COIN' : 'USD' }}</span>
                        </div>
                        <div class="text-[10px] text-gray-600 mt-1 text-right">
                            {{ inputMode === 'ASSET' ? `Est. Margin: $${form.total}` : `Est. Qty: ${form.quantity}` }}
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 border-t border-[#1f2128] pt-4 border-dashed">
                    <div>
                        <label class="block text-[10px] text-green-500 mb-1 font-bold uppercase">Take Profit</label>
                        <input v-model="form.tp_price" type="number" step="any" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-sm rounded p-2.5 focus:border-green-500 outline-none placeholder-gray-700" placeholder="Optional">
                    </div>
                    <div>
                        <label class="block text-[10px] text-red-500 mb-1 font-bold uppercase">Stop Loss</label>
                        <input v-model="form.sl_price" type="number" step="any" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-sm rounded p-2.5 focus:border-red-500 outline-none placeholder-gray-700" placeholder="Optional">
                    </div>
                </div>

            </div>

            <div class="lg:col-span-4 flex flex-col gap-4">
                
                <div class="flex-1">
                    <label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Entry Chart</label>
                    <div class="border-2 border-dashed border-[#2d2f36] rounded-xl h-full min-h-[150px] flex flex-col justify-center items-center relative hover:border-gray-500 transition-colors bg-[#0a0b0d]">
                        <input id="file-upload-futures" type="file" @change="handleFileChange" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <div v-if="!form.screenshot" class="text-center p-4">
                            <i class="fas fa-image text-2xl text-gray-600 mb-2"></i>
                            <p class="text-xs text-gray-500 font-bold">Click or Drop Chart</p>
                        </div>
                        <div v-else class="text-center p-4">
                            <span class="text-green-500 text-xs font-bold block mb-1">Image Selected</span>
                            <span class="text-gray-500 text-[10px] truncate max-w-[150px] block">{{ form.screenshot.name }}</span>
                        </div>
                        <div v-if="isCompressing" class="absolute inset-0 bg-black/50 flex items-center justify-center">
                            <span class="text-xs font-bold text-white animate-pulse">Compressing...</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Setup Reasoning</label>
                    <textarea v-model="form.notes" rows="4" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-gray-300 text-xs rounded-lg p-3 focus:border-blue-500 outline-none resize-none" placeholder="Why did you take this trade?"></textarea>
                </div>

                <button type="submit" :disabled="form.processing || isCompressing" class="w-full py-3 rounded-lg text-sm font-black uppercase tracking-wider transition-all shadow-lg"
                    :class="form.type === 'LONG' ? 'bg-green-600 hover:bg-green-500 text-white shadow-green-900/20' : 'bg-red-600 hover:bg-red-500 text-white shadow-red-900/20'">
                    {{ form.processing ? 'Opening...' : `OPEN ${form.type} POSITION` }}
                </button>

            </div>
        </div>
    </form>
</template>
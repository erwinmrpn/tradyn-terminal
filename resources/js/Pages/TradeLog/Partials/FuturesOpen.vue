<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted } from 'vue';
import imageCompression from 'browser-image-compression';

const props = defineProps<{
    accounts: any[];
}>();

const getCurrentTime = () => {
    const now = new Date();
    return now.toTimeString().slice(0, 5);
};

const isCompressing = ref(false);
const inputMode = ref<'ASSET' | 'TOTAL'>('ASSET'); 
const dynamicInput = ref(''); 

// 1. Ambil Market Type Unik dari akun yang tersedia
const availableMarketTypes = computed(() => {
    const types = new Set(props.accounts.map(acc => acc.market_type));
    return types.size > 0 ? Array.from(types) : ['Crypto'];
});

// 2. Filter Akun berdasarkan Market Type yang dipilih
const filteredAccounts = computed(() => {
    return props.accounts.filter(acc => acc.market_type === form.market_type);
});

const form = useForm({
    // Default ID akan di-set di onMounted atau watcher
    trading_account_id: '',
    date: new Date().toISOString().split('T')[0],
    time: getCurrentTime(),
    symbol: '',
    
    // Default Market Type sesuai data akun pertama
    market_type: props.accounts.length > 0 ? props.accounts[0].market_type : 'Crypto',
    
    type: 'LONG',
    leverage: 10,
    margin_mode: 'CROSS',
    order_type: 'MARKET',
    price: '',
    quantity: '', 
    total: '',    
    tp_price: '',
    sl_price: '',
    notes: '', 
    screenshot: null as File | null,
    form_type: 'FUTURES'
});

// Set default account ID saat komponen dimuat
onMounted(() => {
    if (filteredAccounts.value.length > 0) {
        form.trading_account_id = filteredAccounts.value[0].id;
    }
});

// Watcher: Saat Market Type berubah, auto-pilih akun pertama yang sesuai
watch(() => form.market_type, (newType) => {
    const firstAccount = props.accounts.find(acc => acc.market_type === newType);
    if (firstAccount) {
        form.trading_account_id = firstAccount.id;
    } else {
        form.trading_account_id = '';
    }
});

// [LOGIC] Kalkulasi Otomatis (Realtime) - TETAP UTUH
watch([() => form.price, dynamicInput, inputMode, () => form.leverage], ([newPrice, newVal, mode, newLev]) => {
    const price = parseFloat(String(newPrice)) || 0;
    const inputVal = parseFloat(String(newVal)) || 0;
    const lev = parseFloat(String(newLev)) || 1; 

    if (price > 0 && inputVal > 0) {
        if (mode === 'ASSET') {
            // User input QTY (Coin) -> Hitung Margin ($)
            form.quantity = inputVal.toString();
            form.total = ((price * inputVal) / lev).toFixed(2); 
        } else {
            // User input MARGIN ($) -> Hitung Qty (Coin)
            form.total = inputVal.toString();
            form.quantity = ((inputVal * lev) / price).toFixed(8);
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
            form.time = getCurrentTime(); 
            dynamicInput.value = ''; 
            const fileInput = document.getElementById('file-upload-futures') as HTMLInputElement;
            if(fileInput) fileInput.value = '';
        },
        preserveScroll: true
    });
};
</script>

<template>
    <form @submit.prevent="submit" class="bg-[#121317] border border-[#1f2128] rounded-xl overflow-hidden shadow-lg animate-fade-in-down">
        
        <div class="px-6 py-3 border-b border-[#1f2128] bg-[#1a1b20] flex justify-between items-center">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                <span class="w-2 h-2 rounded-full" :class="form.type === 'LONG' ? 'bg-green-500' : 'bg-red-500'"></span> 
                New {{ form.type }} Entry
            </h3>
        </div>

        <div class="p-5 grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <div class="lg:col-span-8 space-y-4">
                
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-4 md:col-span-3">
                        <label class="block text-[9px] text-gray-500 mb-1 font-bold uppercase tracking-wider">Symbol</label>
                        <input v-model="form.symbol" type="text" placeholder="BTC" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-lg font-black rounded-lg py-2 px-3 focus:border-blue-500 outline-none uppercase placeholder-gray-700">
                    </div>

                    <div class="col-span-4 md:col-span-3">
                        <label class="block text-[9px] text-gray-500 mb-1 font-bold uppercase tracking-wider">Market</label>
                        <div class="relative">
                            <select v-model="form.market_type" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-xs font-bold rounded-lg h-[46px] px-2 focus:border-blue-500 outline-none cursor-pointer uppercase appearance-none">
                                <option v-for="type in availableMarketTypes" :key="type" :value="type">
                                    {{ type }}
                                </option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-span-4 md:col-span-6">
                        <label class="block text-[9px] text-gray-500 mb-1 font-bold uppercase tracking-wider">Position</label>
                        <div class="flex bg-[#0a0b0d] p-1 rounded-lg border border-[#2d2f36] h-[46px]">
                            <button type="button" @click="form.type = 'LONG'" class="flex-1 rounded-md text-xs font-black transition-all tracking-wider" :class="form.type === 'LONG' ? 'bg-green-600 text-white shadow-lg' : 'text-gray-500 hover:text-gray-300 hover:bg-[#1f2128]'">LONG</button>
                            <button type="button" @click="form.type = 'SHORT'" class="flex-1 rounded-md text-xs font-black transition-all tracking-wider" :class="form.type === 'SHORT' ? 'bg-red-600 text-white shadow-lg' : 'text-gray-500 hover:text-gray-300 hover:bg-[#1f2128]'">SHORT</button>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 p-2 bg-[#0a0b0d] rounded-lg border border-[#2d2f36] items-center">
                    <div class="flex-1 flex flex-col px-2">
                        <label class="text-[8px] text-gray-500 font-bold uppercase">Leverage</label>
                        <div class="flex items-center">
                            <span class="text-[10px] text-yellow-500 mr-1 font-bold">x</span>
                            <input v-model="form.leverage" type="number" class="w-full bg-transparent border-none text-white text-xs p-0 focus:ring-0 font-bold h-4 no-spinner" placeholder="10">
                        </div>
                    </div>
                    <div class="w-px h-6 bg-[#2d2f36]"></div>
                    <div class="flex-1 flex flex-col px-2">
                        <label class="text-[8px] text-gray-500 font-bold uppercase">Margin</label>
                        <select v-model="form.margin_mode" class="w-full bg-transparent border-none text-white text-xs p-0 focus:ring-0 cursor-pointer font-semibold h-4">
                            <option value="CROSS">Cross</option>
                            <option value="ISOLATED">Isolated</option>
                        </select>
                    </div>
                    <div class="w-px h-6 bg-[#2d2f36]"></div>
                    <div class="flex-1 flex flex-col px-2">
                        <label class="text-[8px] text-gray-500 font-bold uppercase">Order</label>
                        <select v-model="form.order_type" class="w-full bg-transparent border-none text-white text-xs p-0 focus:ring-0 cursor-pointer font-semibold h-4">
                            <option value="MARKET">Market</option>
                            <option value="LIMIT">Limit</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[9px] text-gray-500 mb-1 font-bold uppercase tracking-wider">Entry Price ($)</label>
                        <input v-model="form.price" type="number" step="any" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-sm rounded-lg p-2.5 focus:border-blue-500 outline-none font-mono no-spinner" placeholder="0.00">
                    </div>
                    <div>
                        <div class="flex justify-between mb-1">
                            <label class="block text-[9px] text-gray-500 font-bold uppercase tracking-wider">Position Size</label>
                            <button type="button" @click="inputMode = inputMode === 'ASSET' ? 'TOTAL' : 'ASSET'" class="text-[8px] text-blue-400 hover:text-blue-300 uppercase font-bold tracking-wide">
                                {{ inputMode === 'ASSET' ? 'SWITCH TO USD' : 'SWITCH TO COIN' }}
                            </button>
                        </div>
                        <div class="relative">
                            <input v-model="dynamicInput" type="number" step="any" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-sm rounded-lg p-2.5 focus:border-blue-500 outline-none font-mono no-spinner" :placeholder="inputMode === 'ASSET' ? 'Qty' : 'Margin $'">
                            <span class="absolute right-3 top-3 text-[10px] text-gray-500 font-bold">{{ inputMode === 'ASSET' ? form.symbol || 'COIN' : 'USD' }}</span>
                        </div>
                        
                        <div class="flex justify-between mt-1 px-1">
                            <span class="text-[9px] text-gray-600">
                                Total Pos: ${{ form.price && form.quantity ? (parseFloat(String(form.price)) * parseFloat(String(form.quantity))).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0' }}
                            </span>
                            <span class="text-[9px] text-gray-400 font-mono">
                                {{ inputMode === 'ASSET' ? `Est. Margin: $${form.total}` : `Est. Qty: ${form.quantity}` }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="relative">
                        <label class="block text-[9px] text-green-500 mb-1 font-bold uppercase tracking-wider">Take Profit</label>
                        <input v-model="form.tp_price" type="number" step="any" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-sm rounded-lg p-2.5 focus:border-green-500 outline-none placeholder-gray-700 font-mono no-spinner" placeholder="Optional">
                    </div>
                    <div class="relative">
                        <label class="block text-[9px] text-red-500 mb-1 font-bold uppercase tracking-wider">Stop Loss</label>
                        <input v-model="form.sl_price" type="number" step="any" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-sm rounded-lg p-2.5 focus:border-red-500 outline-none placeholder-gray-700 font-mono no-spinner" placeholder="Optional">
                    </div>
                </div>

            </div>

            <div class="lg:col-span-4 flex flex-col gap-4 border-l border-[#1f2128] pl-0 lg:pl-6">
                
                <div class="space-y-3">
                    <div>
                        <label class="block text-[9px] text-gray-500 mb-1 font-bold uppercase tracking-wider">Trading Account</label>
                        <div class="relative">
                            <select v-model="form.trading_account_id" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-xs rounded-lg p-2.5 focus:border-blue-500 outline-none appearance-none">
                                <option v-for="acc in filteredAccounts" :key="acc.id" :value="acc.id">
                                    {{ acc.name }} ({{ acc.exchange }}) - ${{ acc.balance }}
                                </option>
                                <option v-if="filteredAccounts.length === 0" value="" disabled>No accounts for {{ form.market_type }}</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="block text-[9px] text-gray-500 mb-1 font-bold uppercase tracking-wider">Date</label>
                            <input v-model="form.date" type="date" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-gray-400 text-xs rounded-lg p-2.5 focus:border-blue-500 outline-none cursor-pointer">
                        </div>
                        
                        <div class="w-1/3">
                            <label class="block text-[9px] text-gray-500 mb-1 font-bold uppercase tracking-wider">Time</label>
                            <input v-model="form.time" type="time" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-gray-400 text-xs rounded-lg p-2.5 focus:border-blue-500 outline-none cursor-pointer">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-[9px] text-gray-500 mb-1 font-bold uppercase tracking-wider">Entry Chart</label>
                    <div class="border border-dashed border-[#2d2f36] rounded-lg flex items-center justify-between px-3 py-2.5 relative hover:border-gray-500 transition-colors bg-[#0a0b0d] group">
                        <input id="file-upload-futures" type="file" @change="handleFileChange" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                        <div class="flex items-center gap-3">
                            <div class="bg-[#1f2128] p-1.5 rounded">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[10px] font-bold text-gray-300 group-hover:text-white transition-colors">
                                    {{ form.screenshot ? 'Change Image' : 'Upload Screenshot' }}
                                </span>
                                <span v-if="form.screenshot" class="text-[9px] text-green-500 truncate max-w-[120px]">{{ form.screenshot.name }}</span>
                                <span v-else class="text-[9px] text-gray-600">No file selected</span>
                            </div>
                        </div>
                        <div v-if="isCompressing" class="text-[9px] text-blue-400 animate-pulse font-bold">Compressing...</div>
                    </div>
                </div>

                <div class="flex-1">
                    <label class="block text-[9px] text-gray-500 mb-1 font-bold uppercase tracking-wider">Notes / Setup</label>
                    <textarea v-model="form.notes" class="w-full h-full min-h-[80px] bg-[#0a0b0d] border border-[#2d2f36] text-gray-300 text-xs rounded-lg p-3 focus:border-blue-500 outline-none resize-none" placeholder="Reason for entry..."></textarea>
                </div>

                <button type="submit" :disabled="form.processing || isCompressing" class="w-full py-3 rounded-lg text-sm font-black uppercase tracking-wider transition-all shadow-lg transform active:scale-95 border border-transparent hover:border-white/10"
                    :class="form.type === 'LONG' ? 'bg-green-600 hover:bg-green-500 text-white shadow-green-900/20' : 'bg-red-600 hover:bg-red-500 text-white shadow-red-900/20'">
                    {{ form.processing ? 'Executing...' : `OPEN ${form.type}` }}
                </button>

            </div>
        </div>
    </form>
</template>

<style scoped>
/* Hilangkan tombol panah angka */
.no-spinner {
  appearance: textfield;
  -moz-appearance: textfield;
}
.no-spinner::-webkit-outer-spin-button,
.no-spinner::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* [PERBAIKAN] Paksa Ikon Jam DAN Tanggal (Picker) Jadi Putih & Muncul */
input[type="time"]::-webkit-calendar-picker-indicator,
input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
    cursor: pointer;
    opacity: 1;
}
</style>
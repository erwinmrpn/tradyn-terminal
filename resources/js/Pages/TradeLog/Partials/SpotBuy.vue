<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted } from 'vue';
import imageCompression from 'browser-image-compression';

// Props: accounts data passed from parent/controller
const props = defineProps<{ accounts: any[] }>();

const getCurrentTime = () => new Date().toTimeString().slice(0, 5);
const isSubmitting = ref(false);
const isCompressing = ref(false);
const inputMode = ref<'ASSET' | 'TOTAL'>('TOTAL'); 
const dynamicInput = ref('');

// 1. Get Unique Market Types from available accounts for the Market Dropdown
const availableMarketTypes = computed(() => {
    // Extract unique market_types from accounts, default to ['CRYPTO'] if empty
    const types = new Set(props.accounts.map(acc => acc.market_type));
    return types.size > 0 ? Array.from(types) : ['Crypto'];
});

// 2. Filter Accounts based on selected Market Type
const filteredAccounts = computed(() => {
    return props.accounts.filter(acc => acc.market_type === form.market_type);
});

const form = useForm({
    form_type: 'SPOT',
    // Default account ID will be set in onMounted or watcher
    trading_account_id: '', 
    date: new Date().toISOString().split('T')[0],
    time: getCurrentTime(),
    
    symbol: '',
    // Default market type to the first available one or 'Crypto'
    market_type: props.accounts.length > 0 ? props.accounts[0].market_type : 'Crypto',
    
    price: '',
    quantity: '',
    total: '',
    fee: '', 
    target_sell: '',
    target_buy: '',
    holding_period: 'Short Term',
    notes: '',
    screenshot: null as File | null,
});

// Initialize default account ID on mount
onMounted(() => {
    if (filteredAccounts.value.length > 0) {
        form.trading_account_id = filteredAccounts.value[0].id;
    }
});

// Watcher: When Market Type changes, auto-select the first available account in that market
watch(() => form.market_type, (newType) => {
    const firstAccount = props.accounts.find(acc => acc.market_type === newType);
    if (firstAccount) {
        form.trading_account_id = firstAccount.id;
    } else {
        form.trading_account_id = ''; // Reset if no account found
    }
});

// Smart Input Logic (Price * Qty = Total)
watch([() => form.price, dynamicInput, inputMode], ([newPrice, newVal, mode]) => {
    const price = parseFloat(String(newPrice)) || 0;
    const inputVal = parseFloat(String(newVal)) || 0;

    if (price > 0 && inputVal > 0) {
        if (mode === 'ASSET') { 
            form.quantity = inputVal.toString();
            form.total = (price * inputVal).toFixed(2);
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
        try {
            isCompressing.value = true;
            const compressedFile = await imageCompression(file, { maxSizeMB: 0.8, maxWidthOrHeight: 1920 });
            form.screenshot = new File([compressedFile], file.name, { type: file.type });
        } catch (error) { 
            alert("Compression failed."); 
        } finally { 
            isCompressing.value = false; 
        }
    }
};

const submit = () => {
    if (isCompressing.value || isSubmitting.value) return;
    
    isSubmitting.value = true;

    form.post(route('trade.log.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('symbol', 'price', 'quantity', 'total', 'fee', 'target_sell', 'target_buy', 'notes', 'screenshot');
            form.time = getCurrentTime();
            dynamicInput.value = '';
            
            const fileInput = document.getElementById('file-upload-spot') as HTMLInputElement;
            if(fileInput) fileInput.value = '';
        },
        onError: (errors) => {
            console.error("Validation Errors:", errors);
        },
        onFinish: () => {
            isSubmitting.value = false; 
        }
    });
};
</script>

<template>
    <div class="bg-[#121317] border border-[#1f2128] rounded-xl overflow-hidden shadow-lg animate-fade-in-down">
        
        <div class="px-6 py-3 border-b border-[#1f2128] bg-[#1a1b20] flex justify-between items-center">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> New Spot Entry
            </h3>
        </div>

        <div v-if="Object.keys(form.errors).length > 0" class="px-5 pt-4">
            <div class="bg-red-500/10 border border-red-500/20 rounded-lg p-3">
                <p class="text-red-400 text-xs font-bold mb-1">⚠️ Submission Failed</p>
                <ul class="list-disc list-inside text-[10px] text-gray-400">
                    <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                </ul>
            </div>
        </div>

        <form @submit.prevent="submit">
            <div class="p-5 grid grid-cols-1 lg:grid-cols-12 gap-6">
                
                <div class="lg:col-span-8 space-y-4">
                    
                    <div class="grid grid-cols-12 gap-3">
                        <div class="col-span-4 md:col-span-3">
                            <label class="block text-[9px] text-gray-500 mb-1 font-bold uppercase tracking-wider">Symbol</label>
                            <input 
                                :value="form.symbol"
                                @input="form.symbol = ($event.target as HTMLInputElement).value.toUpperCase()"
                                type="text" 
                                placeholder="BTC" 
                                class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-lg font-black rounded-lg py-2 px-3 focus:border-emerald-500 outline-none uppercase placeholder-gray-700" 
                                :class="{'border-red-500': form.errors.symbol}"
                            >
                            <div v-if="form.errors.symbol" class="text-red-500 text-[9px] mt-1">{{ form.errors.symbol }}</div>
                        </div>

                        <div class="col-span-4 md:col-span-3">
                            <label class="block text-[9px] text-gray-500 mb-1 font-bold uppercase tracking-wider">Market</label>
                            <div class="relative">
                                <select v-model="form.market_type" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-xs font-bold rounded-lg h-[46px] px-2 focus:border-emerald-500 outline-none cursor-pointer uppercase appearance-none">
                                    <option v-for="type in availableMarketTypes" :key="type" :value="type">
                                        {{ type }}
                                    </option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                    <svg class="fill-current h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                                </div>
                            </div>
                        </div>

                        <div class="col-span-4 md:col-span-6">
                            <label class="block text-[9px] text-gray-500 mb-1 font-bold uppercase tracking-wider">Holding Period</label>
                            <div class="flex bg-[#0a0b0d] p-1 rounded-lg border border-[#2d2f36] h-[46px]">
                                <button type="button" @click.prevent="form.holding_period = 'Short Term'" class="flex-1 rounded-md text-[10px] font-bold transition-all uppercase" :class="form.holding_period === 'Short Term' ? 'bg-emerald-600 text-white shadow-lg' : 'text-gray-500 hover:text-gray-300 hover:bg-[#1f2128]'">Short Term</button>
                                <button type="button" @click.prevent="form.holding_period = 'Medium Term'" class="flex-1 rounded-md text-[10px] font-bold transition-all uppercase" :class="form.holding_period === 'Medium Term' ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-500 hover:text-gray-300 hover:bg-[#1f2128]'">Medium Term</button>
                                <button type="button" @click.prevent="form.holding_period = 'Long Term'" class="flex-1 rounded-md text-[10px] font-bold transition-all uppercase" :class="form.holding_period === 'Long Term' ? 'bg-purple-600 text-white shadow-lg' : 'text-gray-500 hover:text-gray-300 hover:bg-[#1f2128]'">Long Term</button>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[9px] text-gray-500 mb-1 font-bold uppercase tracking-wider">Buy Price ($)</label>
                            <input v-model="form.price" type="number" step="any" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-sm rounded-lg p-2.5 focus:border-emerald-500 outline-none font-mono no-spinner" placeholder="0.00" :class="{'border-red-500': form.errors.price}">
                            <div v-if="form.errors.price" class="text-red-500 text-[9px] mt-1">{{ form.errors.price }}</div>
                        </div>
                        <div>
                            <div class="flex justify-between mb-1">
                                <label class="block text-[9px] text-gray-500 font-bold uppercase tracking-wider">Quantity</label>
                                <button type="button" @click.prevent="inputMode = inputMode === 'ASSET' ? 'TOTAL' : 'ASSET'" class="text-[8px] text-blue-400 hover:text-blue-300 uppercase font-bold tracking-wide">
                                    {{ inputMode === 'ASSET' ? 'SWITCH TO USD' : 'SWITCH TO COIN' }}
                                </button>
                            </div>
                            <div class="relative">
                                <input v-model="dynamicInput" type="number" step="any" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-sm rounded-lg p-2.5 focus:border-emerald-500 outline-none font-mono no-spinner" :placeholder="inputMode === 'ASSET' ? 'Qty' : 'Total $'" :class="{'border-red-500': form.errors.quantity}">
                                <span class="absolute right-3 top-3 text-[10px] text-gray-500 font-bold">{{ inputMode === 'ASSET' ? form.symbol || 'COIN' : 'USD' }}</span>
                            </div>
                            <div v-if="form.errors.quantity" class="text-red-500 text-[9px] mt-1">{{ form.errors.quantity }}</div>
                            <div class="text-[9px] text-gray-600 mt-1 text-right font-mono">
                                {{ inputMode === 'ASSET' ? `Total Buy: $${form.total}` : `Qty: ${form.quantity}` }}
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[9px] text-emerald-500 mb-1 font-bold uppercase tracking-wider">Target Sell (TP)</label>
                            <input v-model="form.target_sell" type="number" step="any" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-sm rounded-lg p-2.5 focus:border-emerald-500 outline-none placeholder-gray-700 font-mono no-spinner" placeholder="Profit Target">
                        </div>
                        <div>
                            <label class="block text-[9px] text-blue-500 mb-1 font-bold uppercase tracking-wider">Target Buy / SL</label>
                            <input v-model="form.target_buy" type="number" step="any" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-sm rounded-lg p-2.5 focus:border-blue-500 outline-none placeholder-gray-700 font-mono no-spinner" placeholder="Re-entry / Stop">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[9px] text-gray-500 mb-1 font-bold uppercase tracking-wider">Entry Fee ($)</label>
                        <input v-model="form.fee" type="number" step="any" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-sm rounded-lg p-2.5 focus:border-emerald-500 outline-none font-mono no-spinner" placeholder="0.00">
                    </div>

                </div>

                <div class="lg:col-span-4 flex flex-col gap-4 border-l border-[#1f2128] pl-0 lg:pl-6">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-[9px] text-gray-500 mb-1 font-bold uppercase tracking-wider">Account</label>
                            <div class="relative">
                                <select v-model="form.trading_account_id" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-xs rounded-lg p-2.5 focus:border-emerald-500 outline-none appearance-none" :class="{'border-red-500': form.errors.trading_account_id}">
                                    <option v-for="acc in filteredAccounts" :key="acc.id" :value="acc.id">
                                        {{ acc.name }} ({{ acc.exchange }}) - ${{ acc.balance }}
                                    </option>
                                    <option v-if="filteredAccounts.length === 0" value="" disabled>No accounts for {{ form.market_type }}</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                                </div>
                            </div>
                            <div v-if="form.errors.trading_account_id" class="text-red-500 text-[9px] mt-1">{{ form.errors.trading_account_id }}</div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[9px] text-gray-500 mb-1 font-bold uppercase tracking-wider">Date</label>
                                <input v-model="form.date" type="date" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-gray-400 text-xs rounded-lg p-2.5 focus:border-emerald-500 outline-none cursor-pointer">
                            </div>
                            <div>
                                <label class="block text-[9px] text-gray-500 mb-1 font-bold uppercase tracking-wider">Time</label>
                                <input v-model="form.time" type="time" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-gray-400 text-xs rounded-lg p-2.5 focus:border-emerald-500 outline-none cursor-pointer">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[9px] text-gray-500 mb-1 font-bold uppercase tracking-wider">Buy Chart</label>
                        <div class="border border-dashed border-[#2d2f36] rounded-lg flex items-center justify-between px-3 py-2.5 relative hover:border-gray-500 transition-colors bg-[#0a0b0d] group">
                            <input id="file-upload-spot" type="file" @change="handleFileChange" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                            <div class="flex items-center gap-3">
                                <div class="bg-[#1f2128] p-1.5 rounded"><svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg></div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-bold text-gray-300 group-hover:text-white transition-colors">{{ form.screenshot ? 'Change Image' : 'Upload Screenshot' }}</span>
                                    <span v-if="form.screenshot" class="text-[9px] text-emerald-500 truncate max-w-[120px]">{{ form.screenshot.name }}</span>
                                    <span v-else class="text-[9px] text-gray-600">No file selected</span>
                                </div>
                            </div>
                            <div v-if="isCompressing" class="text-[9px] text-blue-400 animate-pulse font-bold">Compressing...</div>
                        </div>
                    </div>

                    <div class="flex-1">
                        <label class="block text-[9px] text-gray-500 mb-1 font-bold uppercase tracking-wider">Buy Notes</label>
                        <textarea v-model="form.notes" class="w-full h-full min-h-[80px] bg-[#0a0b0d] border border-[#2d2f36] text-gray-300 text-xs rounded-lg p-3 focus:border-emerald-500 outline-none resize-none" placeholder="Fundamental / Technical reason..."></textarea>
                    </div>
                </div>
            </div>

            <div class="px-5 pb-5">
                <button type="submit" 
                    :disabled="isSubmitting || isCompressing" 
                    class="w-full py-4 rounded-lg text-sm font-black uppercase tracking-wider transition-all shadow-lg transform active:scale-[0.99] border border-transparent hover:border-white/10 bg-emerald-600 hover:bg-emerald-500 text-white shadow-emerald-900/20 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span v-if="isCompressing">Compressing Image...</span>
                    <span v-else-if="isSubmitting">Processing...</span>
                    <span v-else>BUY ASSET</span>
                </button>
            </div>
        </form>
    </div>
</template>

<style scoped>
.no-spinner { appearance: textfield; -moz-appearance: textfield; }
.no-spinner::-webkit-outer-spin-button, .no-spinner::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
input[type="time"]::-webkit-calendar-picker-indicator, input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1); cursor: pointer; opacity: 1; }
@keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: fadeIn 0.2s ease-out forwards; }
</style>
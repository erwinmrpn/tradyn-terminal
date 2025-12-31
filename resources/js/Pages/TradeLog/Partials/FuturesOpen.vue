<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import imageCompression from 'browser-image-compression';

const props = defineProps<{
    accounts: any[];
}>();

const inputMode = ref<'ASSET' | 'TOTAL'>('ASSET');
const dynamicInput = ref('');
const isCompressing = ref(false);

const form = useForm({
    trading_account_id: '',
    date: new Date().toISOString().split('T')[0],
    symbol: '',
    market_type: 'CRYPTO',
    price: '',
    quantity: '',
    total: '',
    notes: '',
    type: 'LONG',
    form_type: 'FUTURES', // Hardcode FUTURES
    leverage: 10,
    margin_mode: 'CROSS',
    order_type: 'MARKET',
    tp_price: '',
    sl_price: '',
    screenshot: null as File | null,
});

const applyDefaultAccount = () => {
    if (props.accounts.length > 0 && !form.trading_account_id) {
        form.trading_account_id = props.accounts[0].id;
    }
};

onMounted(() => {
    applyDefaultAccount();
});

watch(() => props.accounts, () => {
    applyDefaultAccount();
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

const submit = () => {
    if (!form.trading_account_id) { alert("Please select a trading account."); return; }
    if (isCompressing.value) { alert("Compressing..."); return; }
    
    form.post(route('trade.log.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('symbol', 'price', 'quantity', 'total', 'notes', 'tp_price', 'sl_price', 'screenshot');
            dynamicInput.value = '';
            const fileInput = document.getElementById('file-upload-futures') as HTMLInputElement;
            if(fileInput) fileInput.value = '';
            applyDefaultAccount();
        },
        onError: (e) => console.error(e)
    });
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
};
</script>

<template>
    <div class="bg-[#121317] border border-[#1f2128] rounded-xl p-6 shadow-lg">
        <form @submit.prevent="submit">
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
                <div class="col-span-4"><label class="block text-[10px] text-gray-500 mb-1 font-bold tracking-wider uppercase">Asset</label><input v-model="form.symbol" type="text" placeholder="BTC" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded p-2.5 uppercase font-bold text-center h-[38px] focus:border-blue-500 outline-none"></div>
                <div class="col-span-4"><label class="block text-[10px] text-gray-500 mb-1 font-bold tracking-wider uppercase">Market</label><select v-model="form.market_type" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-gray-400 text-xs rounded p-2.5 h-[38px] text-center focus:border-blue-500 outline-none"><option value="CRYPTO">CRYPTO</option><option value="STOCK">STOCK</option></select></div>
            </div>

            <div class="grid grid-cols-3 gap-3 mb-5 p-4 bg-[#0a0b0d] rounded-lg border border-[#2d2f36]">
                <div><label class="block text-[9px] text-gray-500 mb-1 font-bold uppercase">Leverage</label><div class="flex items-center"><span class="text-yellow-500 text-xs mr-1 font-bold">x</span><input v-model="form.leverage" type="number" min="1" max="125" class="no-spinner w-full bg-transparent border-none text-white text-lg font-bold p-0 focus:ring-0 placeholder-gray-700" placeholder="10"></div></div>
                <div class="border-l border-[#2d2f36] pl-3"><label class="block text-[9px] text-gray-500 mb-1 font-bold uppercase">Mode</label><select v-model="form.margin_mode" class="w-full bg-transparent border-none text-blue-400 text-xs font-bold p-0 focus:ring-0 cursor-pointer"><option value="CROSS">CROSS</option><option value="ISOLATED">ISOLATED</option></select></div>
                <div class="border-l border-[#2d2f36] pl-3"><label class="block text-[9px] text-gray-500 mb-1 font-bold uppercase">Order Type</label><select v-model="form.order_type" class="w-full bg-transparent border-none text-purple-400 text-xs font-bold p-0 focus:ring-0 cursor-pointer"><option value="MARKET">MARKET</option><option value="LIMIT">LIMIT</option></select></div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-5">
                <div><label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Entry Price ($)</label><input v-model="form.price" type="number" step="any" placeholder="0.00" class="no-spinner w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded p-3 text-right font-mono focus:border-blue-500 outline-none"></div>
                <div class="relative group">
                    <div class="flex justify-between items-center mb-1"><label class="text-[10px] text-gray-500 font-bold transition-all text-blue-400 uppercase">{{ inputMode === 'ASSET' ? 'SIZE (Coins)' : 'MARGIN (Cost)' }}</label><button type="button" @click="toggleInputMode" class="text-[9px] font-bold text-blue-500 hover:text-blue-400 flex items-center gap-1 uppercase transition-all">SWAP <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg></button></div>
                    <input v-model="dynamicInput" type="number" step="any" class="no-spinner w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded p-3 text-right font-mono focus:border-blue-500 outline-none">
                    <div v-if="form.price && dynamicInput" class="absolute -bottom-5 right-0 text-[10px] text-gray-500 font-mono"><span v-if="inputMode === 'ASSET'">Margin: <span class="text-blue-400 font-bold">${{ formatCurrency(Number(form.total)) }}</span></span><span v-else>Size: <span class="text-blue-400 font-bold">{{ Number(form.quantity).toFixed(4) }} {{ form.symbol }}</span></span></div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-5 p-3 rounded border border-dashed border-[#2d2f36]">
                <div><label class="block text-[10px] text-green-500 font-bold mb-1 uppercase">Take Profit</label><input v-model="form.tp_price" type="number" step="any" placeholder="Optional" class="no-spinner w-full bg-[#0a0b0d] border border-[#2d2f36] text-green-400 text-xs rounded p-2.5 text-right font-mono focus:border-green-500 outline-none"></div>
                <div><label class="block text-[10px] text-red-500 font-bold mb-1 uppercase">Stop Loss</label><input v-model="form.sl_price" type="number" step="any" placeholder="Optional" class="no-spinner w-full bg-[#0a0b0d] border border-[#2d2f36] text-red-400 text-xs rounded p-2.5 text-right font-mono focus:border-red-500 outline-none"></div>
            </div>

            <div class="grid grid-cols-4 gap-3 items-end mb-6">
                <div class="col-span-1 relative group">
                    <label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Chart</label>
                    <label for="file-upload-futures" class="flex items-center justify-center w-full h-[42px] bg-[#1a1b20] border border-[#2d2f36] rounded cursor-pointer hover:border-gray-500 transition-colors text-gray-400 text-xs overflow-hidden">
                        <span v-if="isCompressing" class="text-yellow-500 animate-pulse font-bold">Compressing...</span>
                        <span v-else-if="form.screenshot" class="truncate px-2 text-blue-400">{{ form.screenshot.name }}</span>
                        <span v-else class="flex items-center gap-1"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg> Upload</span>
                    </label>
                    <input id="file-upload-futures" type="file" @change="handleFileChange" accept="image/*" class="hidden">
                </div>
                <div class="col-span-3"><label class="block text-[10px] text-gray-500 mb-1 font-bold uppercase">Notes</label><input v-model="form.notes" type="text" placeholder="Setup reasoning..." class="w-full bg-[#1a1b20] border border-[#2d2f36] text-gray-300 text-xs rounded p-2.5 focus:border-blue-500 outline-none h-[42px]"></div>
            </div>

            <button type="submit" :disabled="form.processing || isCompressing" class="w-full py-4 rounded-lg text-sm font-black text-white shadow-xl transition-all tracking-widest uppercase border border-transparent hover:border-white/20 disabled:opacity-50 disabled:cursor-not-allowed" :class="form.type === 'LONG' ? 'bg-gradient-to-r from-green-700 to-green-600 hover:from-green-600 hover:to-green-500 shadow-[0_0_20px_rgba(22,163,74,0.3)]' : 'bg-gradient-to-r from-red-700 to-red-600 hover:from-red-600 hover:to-red-500 shadow-[0_0_20px_rgba(220,38,38,0.3)]'">OPEN {{ form.type }} POSITION</button>
        </form>
    </div>
</template>

<style scoped>
.no-spinner::-webkit-outer-spin-button, .no-spinner::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.no-spinner { appearance: textfield; -moz-appearance: textfield; }
</style>
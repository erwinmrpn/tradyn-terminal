<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';

// Terima data akun yang sudah difilter dari Index.vue
const props = defineProps<{
    accounts: any[];
}>();

const inputMode = ref<'ASSET' | 'TOTAL'>('ASSET');
const dynamicInput = ref('');
const isCustomMarket = ref(false); // State untuk mode input manual

const form = useForm({
    trading_account_id: '',
    date: new Date().toISOString().split('T')[0],
    symbol: '',
    market_type: 'CRYPTO',
    price: '',
    quantity: '',
    total: '',
    fee: '',
    notes: '',
    type: 'BUY',
    form_type: 'SPOT',
});

// Smart Default: Pilih akun pertama otomatis
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

// --- LOGIKA CUSTOM MARKET (OTHERS) ---
// Pantau perubahan dropdown. Jika user pilih 'OTHERS', aktifkan mode custom
watch(() => form.market_type, (newVal) => {
    if (newVal === 'OTHERS') {
        isCustomMarket.value = true;
        form.market_type = ''; // Kosongkan agar user bisa mengetik
    }
});

// Fungsi untuk membatalkan custom input dan kembali ke dropdown
const cancelCustomMarket = () => {
    isCustomMarket.value = false;
    form.market_type = 'CRYPTO'; // Reset ke default
};

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

const submit = () => {
    if (!form.trading_account_id) { alert("Please select a trading account."); return; }
    // Validasi Market Type tidak boleh kosong
    if (!form.market_type) { alert("Please specify the market type."); return; }

    form.post(route('trade.log.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('symbol', 'price', 'quantity', 'total', 'fee', 'notes');
            dynamicInput.value = '';
            // Reset mode custom jika aktif
            isCustomMarket.value = false;
            form.market_type = 'CRYPTO';
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
    <div class="bg-[#121317] border border-[#1f2128] rounded-xl p-5 shadow-lg">
        <form @submit.prevent="submit">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3 mb-4 items-end">
                <div><label class="block text-[10px] text-gray-500 mb-2 font-bold uppercase">Date</label><input v-model="form.date" type="date" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-xs rounded p-2.5 focus:border-blue-500 outline-none h-10"></div>
                
                <div class="lg:col-span-1">
                    <label class="block text-[10px] text-gray-500 mb-2 font-bold uppercase">Account</label>
                    <select v-model="form.trading_account_id" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-xs rounded p-2.5 h-10">
                        <option value="" disabled>Select</option>
                        <option v-for="acc in props.accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
                    </select>
                </div>

                <div><label class="block text-[10px] text-gray-500 mb-2 font-bold uppercase">Type</label><select v-model="form.type" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-xs rounded p-2.5 h-10 font-bold text-center" :class="form.type === 'BUY' ? 'text-green-500' : 'text-red-500'"><option value="BUY">BUY</option><option value="SELL">SELL</option></select></div>
                <div><label class="block text-[10px] text-gray-500 mb-2 font-bold uppercase">Asset</label><input v-model="form.symbol" type="text" placeholder="BTC" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-xs rounded p-2.5 h-10 uppercase font-bold"></div>
                
                <div>
                    <label class="block text-[10px] text-gray-500 mb-2 font-bold uppercase">Market</label>
                    
                    <select v-if="!isCustomMarket" v-model="form.market_type" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-gray-300 text-xs rounded p-2.5 h-10">
                        <option value="CRYPTO">CRYPTO</option>
                        <option value="STOCK">STOCK</option>
                        <option value="COMMODITY">COMMODITY</option>
                        <option value="OTHERS">OTHERS</option>
                    </select>

                    <div v-else class="relative">
                        <input 
                            v-model="form.market_type" 
                            type="text" 
                            placeholder="Type..." 
                            class="w-full bg-[#1a1b20] border border-blue-500 text-white text-xs rounded p-2.5 h-10 pr-7 focus:ring-1 focus:ring-blue-500 outline-none"
                            autofocus
                        >
                        <button 
                            type="button" 
                            @click="cancelCustomMarket"
                            class="absolute right-2 top-2.5 text-gray-500 hover:text-red-500 transition-colors"
                            title="Back to list"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>

                <div><label class="block text-[10px] text-gray-500 mb-2 font-bold uppercase">Price</label><input v-model="form.price" type="number" step="any" placeholder="0.00" class="no-spinner w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-xs rounded p-2.5 text-right font-mono h-10"></div>
                
                <div class="relative group lg:col-span-1">
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-[10px] text-gray-500 font-bold uppercase">{{ inputMode === 'ASSET' ? 'Qty' : 'Total' }}</label>
                        <button type="button" @click="toggleInputMode" class="text-[9px] font-bold text-blue-500 hover:text-blue-400 flex items-center gap-1 uppercase transition-all">SWAP <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg></button>
                    </div>
                    <input v-model="dynamicInput" type="number" step="any" class="no-spinner w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-xs rounded p-2.5 text-right font-mono h-10">
                    <div v-if="form.price && dynamicInput" class="absolute -bottom-5 right-0 text-[9px] text-gray-500">≈ {{ inputMode === 'ASSET' ? formatCurrency(Number(form.total)) : Number(form.quantity).toFixed(6) }}</div>
                </div>

                <div><label class="block text-[10px] text-gray-500 mb-2 font-bold uppercase">Fee</label><input v-model="form.fee" type="number" step="any" placeholder="0.00" class="no-spinner w-full bg-[#1a1b20] border border-[#2d2f36] text-yellow-500 text-xs rounded p-2.5 text-right font-mono h-10"></div>
            </div>
            
            <div class="mb-4 mt-7"><input v-model="form.notes" type="text" placeholder="Notes..." class="w-full bg-[#1a1b20] border border-[#2d2f36] text-gray-300 text-sm rounded-lg p-3"></div>
            
            <div><button type="submit" :disabled="form.processing" class="w-full py-3 rounded-lg text-sm font-bold text-white shadow-lg transition-all flex items-center justify-center gap-2 uppercase tracking-wide" :class="form.type === 'BUY' ? 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/20' : 'bg-red-600 hover:bg-red-700 shadow-red-500/20'">CONFIRM {{ form.type }} TRADE</button></div>
        </form>
    </div>
</template>

<style scoped>
.no-spinner::-webkit-outer-spin-button, .no-spinner::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.no-spinner { appearance: textfield; -moz-appearance: textfield; }
</style>
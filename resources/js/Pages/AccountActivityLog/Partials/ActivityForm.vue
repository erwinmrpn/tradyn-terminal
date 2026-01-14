<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    accounts: any[];
}>();

const form = useForm({
    account_id: '',
    type: 'DEPOSIT',
    amount: '',
    date: new Date().toISOString().split('T')[0],
    notes: '', 
});

const balanceTimeframe = ref('Today');

const selectedAccount = computed(() => {
    return props.accounts.find(acc => acc.id === form.account_id);
});

// COMPUTED: Menentukan persentase mana yang ditampilkan berdasarkan dropdown
const displayedPercentage = computed(() => {
    if (!selectedAccount.value) return 0;
    switch (balanceTimeframe.value) {
        case 'Today': return selectedAccount.value.pct_today;
        case 'Week':  return selectedAccount.value.pct_week;
        case 'Month': return selectedAccount.value.pct_month;
        case 'Year':  return selectedAccount.value.pct_year;
        default: return 0;
    }
});

// COMPUTED: Label perbandingan yang benar
const comparisonLabel = computed(() => {
    switch (balanceTimeframe.value) {
        case 'Today': return 'vs Yesterday';
        case 'Week':  return 'vs 7 Days Ago';
        case 'Month': return 'vs 30 Days Ago';
        case 'Year':  return 'vs Last Year';
        default: return '';
    }
});

const formatCurrency = (value: number, currency: string) => {
    return new Intl.NumberFormat('en-US', { 
        style: 'currency', 
        currency: currency || 'USD' 
    }).format(value);
};

const submit = () => {
    form.post(route('account.activity.store'), {
        onSuccess: () => {
            form.reset('amount', 'notes');
        },
        preserveScroll: true
    });
};
</script>

<template>
    <div class="relative bg-[#121317] border border-[#1f2128] rounded-xl shadow-lg mb-8 overflow-hidden flex flex-col md:flex-row group">
        
        <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] z-10"></div>

        <div class="w-full md:w-[200px] p-6 flex flex-col justify-center relative bg-[#121317] shrink-0">
            
            <div v-if="selectedAccount" class="space-y-3">
                <div class="flex flex-col gap-1">
                    <span class="text-[9px] font-bold text-gray-500 uppercase tracking-wider">Current Balance</span>
                    
                    <div class="relative w-fit">
                        <select v-model="balanceTimeframe" class="clean-select bg-transparent text-[10px] text-gray-400 font-medium py-0 pr-4 outline-none cursor-pointer hover:text-white transition-colors border-none p-0 focus:ring-0">
                            <option value="Today">Today</option>
                            <option value="Week">Week</option>
                            <option value="Month">Month</option>
                            <option value="Year">Year</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center text-gray-500">
                            <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="text-xl font-black text-white tracking-tight break-all leading-none">
                        {{ formatCurrency(selectedAccount.balance, selectedAccount.currency) }}
                    </h2>
                    <p class="text-[10px] text-gray-500 mt-2 font-medium flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        {{ selectedAccount.name }}
                    </p>
                </div>

                <div class="flex items-center gap-2 mt-2">
                    <span class="text-[9px] px-1.5 py-0.5 rounded font-bold border"
                        :class="displayedPercentage >= 0 
                            ? 'bg-green-500/10 text-green-500 border-green-500/20' 
                            : 'bg-red-500/10 text-red-500 border-red-500/20'">
                        {{ displayedPercentage > 0 ? '+' : '' }}{{ displayedPercentage }}%
                    </span>
                    <span class="text-[9px] text-gray-600">{{ comparisonLabel }}</span>
                </div>
            </div>

            <div v-else class="text-center opacity-30 py-4">
                <svg class="w-8 h-8 mx-auto mb-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                <p class="text-[9px] font-bold uppercase text-gray-500">Select Account</p>
            </div>
        </div>

        <div class="flex-1 p-6 bg-[#121317]">
            
            <div class="flex items-center gap-2 mb-5">
                <span class="w-1.5 h-1.5 rounded-full animate-pulse" :class="form.type === 'DEPOSIT' ? 'bg-emerald-500' : 'bg-red-500'"></span>
                <h3 class="text-[10px] font-bold text-white uppercase tracking-wider">Record Transaction</h3>
            </div>

            <form @submit.prevent="submit">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-x-5 gap-y-5">
                    
                    <div class="md:col-span-4">
                        <label class="block text-[9px] font-bold text-gray-500 mb-2 uppercase tracking-wider">Target Account</label>
                        <div class="relative">
                            <select v-model="form.account_id" class="clean-select w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-xs font-bold rounded-lg pl-3 pr-8 py-2.5 focus:border-[#8c52ff] outline-none cursor-pointer hover:bg-[#15161b] transition-colors focus:ring-0">
                                <option value="" disabled>Select Account...</option>
                                <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.name }} ({{ acc.exchange }})</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>
                        <div v-if="form.errors.account_id" class="text-red-500 text-[9px] mt-1">{{ form.errors.account_id }}</div>
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-[9px] font-bold text-gray-500 mb-2 uppercase tracking-wider">Type</label>
                        <div class="flex bg-[#0a0b0d] rounded-lg p-1 border border-[#2d2f36]">
                            <button type="button" @click="form.type = 'DEPOSIT'" :class="form.type === 'DEPOSIT' ? 'bg-emerald-600 text-white shadow' : 'text-gray-500 hover:text-gray-300 hover:bg-[#1f2128]'" class="flex-1 py-1.5 text-[9px] font-bold uppercase rounded transition-all">Deposit</button>
                            <button type="button" @click="form.type = 'WITHDRAW'" :class="form.type === 'WITHDRAW' ? 'bg-red-600 text-white shadow' : 'text-gray-500 hover:text-gray-300 hover:bg-[#1f2128]'" class="flex-1 py-1.5 text-[9px] font-bold uppercase rounded transition-all">Withdraw</button>
                        </div>
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-[9px] font-bold text-gray-500 mb-2 uppercase tracking-wider">Amount ($)</label>
                        <input v-model="form.amount" type="number" step="any" placeholder="0.00" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-xs font-mono font-bold rounded-lg p-2.5 focus:border-[#8c52ff] outline-none placeholder-gray-700 no-spinner focus:ring-0" />
                        <div v-if="form.errors.amount" class="text-red-500 text-[9px] mt-1">{{ form.errors.amount }}</div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-[9px] font-bold text-gray-500 mb-2 uppercase tracking-wider">Date</label>
                        <input v-model="form.date" type="date" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-gray-400 text-xs font-bold rounded-lg p-2.5 focus:border-[#8c52ff] outline-none cursor-pointer focus:ring-0" />
                    </div>

                    <div class="md:col-span-9">
                        <label class="block text-[9px] font-bold text-gray-500 mb-2 uppercase tracking-wider">Notes (Optional)</label>
                        <input v-model="form.notes" type="text" placeholder="e.g. Monthly Savings..." class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-xs rounded-lg p-2.5 focus:border-[#8c52ff] outline-none placeholder-gray-700 transition-colors hover:bg-[#15161b] focus:ring-0" />
                    </div>

                    <div class="md:col-span-3 flex items-end">
                        <button 
                            type="submit" 
                            :disabled="form.processing" 
                            class="w-full text-white text-[10px] font-bold py-2.5 rounded-lg shadow-lg transition-all uppercase tracking-wider border border-transparent disabled:opacity-50 disabled:cursor-not-allowed h-[38px]"
                            :class="form.type === 'DEPOSIT' 
                                ? 'bg-emerald-600 hover:bg-emerald-500 shadow-emerald-900/20 hover:border-emerald-400' 
                                : 'bg-red-600 hover:bg-red-500 shadow-red-900/20 hover:border-red-400'"
                        >
                            {{ form.processing ? 'Saving...' : `CONFIRM ${form.type}` }}
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.clean-select {
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    appearance: none !important;
    background-image: none !important;
    background-color: transparent;
}
.clean-select::-ms-expand {
    display: none;
}
.no-spinner::-webkit-outer-spin-button, .no-spinner::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1); cursor: pointer; opacity: 0.5; width: 12px; height: 12px; }
input[type="date"]::-webkit-calendar-picker-indicator:hover { opacity: 1; }
</style>
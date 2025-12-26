<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'; // Gunakan useForm Inertia

defineProps(['accounts']);

// Gunakan useForm agar bisa post ke Laravel
const form = useForm({
    account_id: '',
    type: 'DEPOSIT',
    amount: '',
    date: new Date().toISOString().split('T')[0],
});

const submit = () => {
    // Post ke route Laravel yang baru kita buat
    form.post(route('account.activity.store'), {
        onSuccess: () => {
            form.reset('amount'); // Reset amount kalau sukses
        },
    });
};
</script>

<template>
    <div class="bg-[#121317] border border-[#1f2128] rounded-xl p-6 shadow-sm">
        <h3 class="text-lg font-bold text-white mb-5 border-b border-[#1f2128] pb-3">
            Record New Transaction
        </h3>
        
        <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-5 items-end">
            
            <div class="lg:col-span-1">
                <label class="block text-xs font-semibold text-gray-400 mb-2">Select Account</label>
                <select v-model="form.account_id" class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 outline-none cursor-pointer">
                    <option value="" disabled>Choose...</option>
                    <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                        {{ acc.name }}
                    </option>
                </select>
            </div>

            <div class="lg:col-span-1">
                <label class="block text-xs font-semibold text-gray-400 mb-2">Transaction Type</label>
                <div class="flex bg-[#1a1b20] rounded-lg p-1 border border-[#2d2f36]">
                    <button 
                        type="button"
                        @click="form.type = 'DEPOSIT'"
                        :class="form.type === 'DEPOSIT' ? 'bg-green-600 text-white shadow-md' : 'text-gray-500 hover:text-gray-300'"
                        class="flex-1 py-2 text-xs font-bold rounded transition-all duration-200"
                    >
                        DEPOSIT
                    </button>
                    <button 
                        type="button"
                        @click="form.type = 'WITHDRAW'"
                        :class="form.type === 'WITHDRAW' ? 'bg-red-600 text-white shadow-md' : 'text-gray-500 hover:text-gray-300'"
                        class="flex-1 py-2 text-xs font-bold rounded transition-all duration-200"
                    >
                        WITHDRAW
                    </button>
                </div>
            </div>

            <div class="lg:col-span-1">
                <label class="block text-xs font-semibold text-gray-400 mb-2">Amount ($)</label>
                <input 
                    v-model="form.amount" 
                    type="number" 
                    min="0"
                    step="0.01"
                    placeholder="0.00"
                    class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 outline-none" 
                />
            </div>

            <div class="lg:col-span-1">
                <label class="block text-xs font-semibold text-gray-400 mb-2">Date</label>
                <input 
                    v-model="form.date" 
                    type="date" 
                    class="w-full bg-[#1a1b20] border border-[#2d2f36] text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 outline-none [color-scheme:dark]" 
                />
            </div>

            <div class="lg:col-span-1">
                <button 
                    type="submit" 
                    class="w-full text-white bg-blue-600 hover:bg-blue-700 font-bold rounded-lg text-sm px-5 py-2.5 text-center transition-colors duration-200 shadow-lg shadow-blue-500/20"
                >
                    Save Record
                </button>
            </div>
        </form>
    </div>
</template>
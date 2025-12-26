<script setup lang="ts">
// Terima data transaksi dari Parent
defineProps(['transactions']);

// Helper format currency
const formatMoney = (amount: number, currency: string) => {
    return Number(amount).toLocaleString('en-US', {
        style: 'currency',
        currency: currency || 'USD', // Default USD jika null
    });
};
</script>

<template>
    <div class="bg-[#121317] border border-[#1f2128] rounded-xl overflow-hidden shadow-sm mt-8">
        <div class="p-6 border-b border-[#1f2128] bg-[#1a1b20]/40 flex justify-between items-center">
            <h3 class="text-lg font-bold text-white">Activity History</h3>
            <div class="text-xs text-gray-500">
                Latest transactions
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead class="bg-[#1a1b20] text-gray-500 uppercase text-xs font-semibold tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4">Account</th>
                        <th class="px-6 py-4">Type</th>
                        <th class="px-6 py-4 text-right">Amount</th>
                        <th class="px-6 py-4 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1f2128]">
                    <tr v-for="tx in transactions" :key="tx.id" class="hover:bg-[#1a1b20]/50 transition-colors duration-150 group">
                        
                        <td class="px-6 py-4 whitespace-nowrap text-gray-300 text-sm">
                            {{ tx.date }}
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap text-white font-medium">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full" 
                                    :class="tx.account_name.toLowerCase().includes('binance') ? 'bg-orange-500' : 'bg-blue-500'">
                                </div>
                                {{ tx.account_name }}
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 rounded text-[10px] font-bold border uppercase tracking-wide"
                                :class="tx.type === 'DEPOSIT' 
                                    ? 'bg-green-500/10 text-green-500 border-green-500/20' 
                                    : 'bg-red-500/10 text-red-500 border-red-500/20'">
                                {{ tx.type }}
                            </span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-right font-bold text-sm"
                            :class="tx.type === 'DEPOSIT' ? 'text-green-400' : 'text-red-400'">
                            {{ tx.type === 'DEPOSIT' ? '+' : '-' }} 
                            {{ formatMoney(tx.amount, tx.currency) }}
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <span class="flex justify-end items-center text-xs text-gray-500">
                                <svg class="w-3 h-3 mr-1 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Completed
                            </span>
                        </td>
                    </tr>

                    <tr v-if="transactions.length === 0">
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <svg class="w-10 h-10 mb-2 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p>No transaction history found.</p>
                                <p class="text-xs mt-1">Start by recording a deposit above.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
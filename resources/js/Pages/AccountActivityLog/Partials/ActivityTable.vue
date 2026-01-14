<script setup lang="ts">
defineProps(['transactions']);

const formatMoney = (amount: number, currency: string) => {
    return Number(amount).toLocaleString('en-US', {
        style: 'currency',
        currency: currency || 'USD',
    });
};
</script>

<template>
    <div class="bg-[#121317] border border-[#1f2128] rounded-xl overflow-hidden shadow-sm">
        <div class="p-4 border-b border-[#1f2128] bg-[#1a1b20]/50 flex justify-between items-center">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Transaction History</h3>
            <div class="text-[10px] text-gray-600 font-mono">
                LATEST ENTRIES
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead class="bg-[#1a1b20] text-gray-500 uppercase text-[10px] font-bold tracking-wider">
                    <tr>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Account / Wallet</th>
                        <th class="px-6 py-3">Notes</th> <th class="px-6 py-3 text-center">Type</th>
                        <th class="px-6 py-3 text-right">Amount</th>
                        <th class="px-6 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1f2128]">
                    <tr v-for="tx in transactions" :key="tx.id" class="hover:bg-[#1a1b20]/30 transition-colors duration-150 group text-sm">
                        
                        <td class="px-6 py-4 whitespace-nowrap text-gray-400 font-mono text-xs">
                            {{ tx.date }}
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap text-white font-bold text-xs">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full" 
                                    :class="{
                                        'bg-orange-500': tx.market_type === 'Crypto',
                                        'bg-blue-500': tx.market_type === 'Stock',
                                        'bg-yellow-500': tx.market_type === 'Commodity',
                                        'bg-gray-500': !tx.market_type
                                    }">
                                </div>
                                {{ tx.account_name }}
                            </div>
                        </td>

                        <td class="px-6 py-4 text-xs">
                            <div class="max-w-[200px] truncate text-gray-400" :title="tx.notes">
                                {{ tx.notes || '-' }}
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="px-2 py-1 rounded text-[9px] font-bold border uppercase tracking-wider"
                                :class="tx.type === 'DEPOSIT' 
                                    ? 'bg-green-500/10 text-green-500 border-green-500/20' 
                                    : 'bg-red-500/10 text-red-500 border-red-500/20'">
                                {{ tx.type }}
                            </span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-right font-mono font-bold text-sm"
                            :class="tx.type === 'DEPOSIT' ? 'text-green-400' : 'text-red-400'">
                            {{ tx.type === 'DEPOSIT' ? '+' : '-' }} 
                            {{ formatMoney(tx.amount, tx.currency) }}
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="inline-flex items-center text-[10px] font-bold text-gray-500 bg-[#1f2128] px-2 py-0.5 rounded border border-[#2d2f36]">
                                <svg class="w-2.5 h-2.5 mr-1 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                SUCCESS
                            </span>
                        </td>
                    </tr>

                    <tr v-if="transactions.length === 0">
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center opacity-50">
                                <svg class="w-10 h-10 mb-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p class="text-xs font-bold uppercase tracking-wider">No Transaction History</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
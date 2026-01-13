<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';
import { ref, onMounted, computed, watch } from 'vue';

const props = defineProps<{
    accounts: any[];
    totalBalance: number;
}>();

// --- STATE ---
const isSidebarCollapsed = ref(false);
const activeDropdown = ref<number | null>(null);
const showDeleteModal = ref(false);
const accountToDelete = ref<any>(null);

// --- SIDEBAR ---
onMounted(() => {
    const saved = localStorage.getItem("sidebar_collapsed");
    if (saved === "true") isSidebarCollapsed.value = true;
});

const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
    localStorage.setItem("sidebar_collapsed", String(isSidebarCollapsed.value));
}

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
};

// --- FILTER ---
const selectedMarket = ref('All');
const selectedExchange = ref('All');

const availableExchanges = computed(() => {
    let accounts = props.accounts;
    if (selectedMarket.value !== 'All') {
        accounts = accounts.filter(acc => acc.market_type === selectedMarket.value);
    }
    const exchanges = [...new Set(accounts.map(acc => acc.exchange))];
    return exchanges.sort();
});

watch(selectedMarket, () => selectedExchange.value = 'All');

const filteredAccounts = computed(() => {
    return props.accounts.filter(account => {
        const matchMarket = selectedMarket.value === 'All' || account.market_type === selectedMarket.value;
        const matchExchange = selectedExchange.value === 'All' || account.exchange === selectedExchange.value;
        return matchMarket && matchExchange;
    });
});

// --- ACTIONS ---
const toggleDropdown = (id: number) => {
    activeDropdown.value = activeDropdown.value === id ? null : id;
};

const closeDropdown = () => {
    activeDropdown.value = null;
};

const confirmDelete = (account: any) => {
    accountToDelete.value = account;
    showDeleteModal.value = true;
    activeDropdown.value = null;
};

const deleteAccount = () => {
    if (accountToDelete.value) {
        router.delete(route('trading-account.destroy', accountToDelete.value.id), {
            onSuccess: () => {
                showDeleteModal.value = false;
                accountToDelete.value = null;
            },
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Portfolio" />

    <div v-if="activeDropdown !== null" @click="closeDropdown" class="fixed inset-0 z-40 bg-transparent cursor-default"></div>

    <div class="min-h-screen bg-[#0a0b0d] text-gray-300 font-sans relative">
        
        <Sidebar :is-collapsed="isSidebarCollapsed" @toggle="toggleSidebar" />

        <div class="transition-all duration-300 ease-in-out min-h-screen flex flex-col"
            :class="isSidebarCollapsed ? 'ml-[72px]' : 'ml-64'">
            
            <Navbar />

            <main class="p-6 lg:p-8 space-y-8 flex-1 pb-20">
                
                <div class="flex flex-col sm:flex-row justify-between items-end gap-4 border-b border-[#1f2128] pb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-white mb-1">Portfolio</h1>
                        <p class="text-xs text-gray-500">Create and manage your portfolio</p>
                    </div>
                    <Link :href="route('trading-account.setup')" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2.5 px-5 rounded-lg shadow-lg flex items-center gap-2 transition-all relative z-10">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        ADD NEW PORTFOLIO
                    </Link>
                </div>

                <div class="relative bg-[#121317] border border-[#1f2128] rounded-xl p-8 text-center shadow-lg overflow-hidden">
                    <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6]"></div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Net Worth</p>
                    <h2 class="text-4xl md:text-5xl font-black text-white tracking-tight">{{ formatCurrency(props.totalBalance) }}</h2>
                </div>

                <div class="flex flex-col md:flex-row gap-4 justify-between items-center bg-[#121317] border border-[#1f2128] p-2 rounded-xl relative z-10">
                    <div class="flex gap-1 w-full md:w-auto overflow-x-auto pb-1 md:pb-0 scrollbar-hide">
                        <button 
                            v-for="type in ['All', 'Crypto', 'Stock', 'Commodity']" 
                            :key="type"
                            @click="selectedMarket = type"
                            class="px-4 py-2 text-xs font-bold rounded-lg transition-all whitespace-nowrap"
                            :class="selectedMarket === type ? 'bg-[#1f2128] text-white shadow border border-[#2d2f36]' : 'text-gray-500 hover:text-gray-300 hover:bg-[#1a1b20]'"
                        >
                            {{ type }}
                        </button>
                    </div>
                    <div class="w-full md:w-64 relative">
                        <select v-model="selectedExchange" class="w-full bg-[#0a0b0d] border border-[#2d2f36] text-white text-xs font-bold rounded-lg pl-3 pr-8 py-2.5 focus:border-[#8c52ff] focus:ring-1 focus:ring-[#8c52ff] outline-none appearance-none cursor-pointer hover:bg-[#1a1b20] transition-colors">
                            <option value="All">All Exchanges</option>
                            <option v-for="exchange in availableExchanges" :key="exchange" :value="exchange">{{ exchange }}</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-500">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                    </div>
                </div>

                <div v-if="filteredAccounts.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 relative">
                    
                    <div 
                        v-for="account in filteredAccounts" 
                        :key="account.id" 
                        class="relative bg-[#121317] border rounded-xl p-5 transition-all duration-300 group"
                        :class="[
                            // Jika aktif: Z-Index 50 (Di atas overlay), Border ungu, Glow halus
                            activeDropdown === account.id 
                                ? 'z-50 border-[#8c52ff] shadow-[0_0_15px_rgba(140,82,255,0.15)]' 
                                : 'z-0 border-[#1f2128] hover:border-gray-600'
                        ]"
                    >
                        <div class="absolute top-0 inset-x-0 h-[3px] bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] rounded-t-[10px]"></div>

                        <div class="flex justify-between items-start mb-4 pt-2">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-[#1a1b20] border border-[#2d2f36] flex items-center justify-center text-lg font-bold text-gray-400">
                                    {{ account.exchange.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-white leading-tight">{{ account.name }}</h3>
                                    <div class="flex flex-wrap gap-1.5 mt-1.5">
                                        <span class="text-[9px] uppercase font-bold px-1.5 py-0.5 rounded border"
                                            :class="account.strategy_type === 'SPOT' ? 'text-blue-400 border-blue-500/30 bg-blue-500/10' : 'text-purple-400 border-purple-500/30 bg-purple-500/10'">
                                            {{ account.strategy_type }}
                                        </span>
                                        <span class="text-[9px] uppercase font-bold px-1.5 py-0.5 rounded border border-gray-700 bg-gray-800 text-gray-400">
                                            {{ account.market_type }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="relative">
                                <button 
                                    @click.stop="toggleDropdown(account.id)" 
                                    class="p-2 rounded-lg transition-colors cursor-pointer relative z-50"
                                    :class="activeDropdown === account.id ? 'bg-[#2d2f36] text-white' : 'text-gray-400 hover:text-white hover:bg-[#2d2f36]'"
                                >
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                    </svg>
                                </button>
                                
                                <Transition
                                    enter-active-class="transition duration-200 ease-out"
                                    enter-from-class="transform scale-95 opacity-0 -translate-y-2"
                                    enter-to-class="transform scale-100 opacity-100 translate-y-0"
                                    leave-active-class="transition duration-75 ease-in"
                                    leave-from-class="transform scale-100 opacity-100 translate-y-0"
                                    leave-to-class="transform scale-95 opacity-0 -translate-y-2"
                                >
                                    <div 
                                        v-if="activeDropdown === account.id" 
                                        class="absolute right-0 top-full mt-2 w-48 bg-[#1a1b20] border border-[#2d2f36] rounded-xl shadow-2xl z-50 overflow-hidden"
                                    >
                                        <Link 
                                            :href="route('trading-account.edit', account.id)" 
                                            class="flex items-center w-full px-4 py-3 text-xs font-bold text-gray-300 hover:text-white hover:bg-[#2d2f36] transition-colors text-left"
                                        >
                                            <svg class="w-3.5 h-3.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                            Edit Account
                                        </Link>
                                        
                                        <button 
                                            @click="confirmDelete(account)" 
                                            class="flex items-center w-full px-4 py-3 text-xs font-bold text-red-500 hover:text-red-400 hover:bg-[#2d2f36] transition-colors text-left border-t border-[#2d2f36]"
                                        >
                                            <svg class="w-3.5 h-3.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            Delete Account
                                        </button>
                                    </div>
                                </Transition>
                            </div>
                        </div>

                        <div class="space-y-1 mb-5 pl-1">
                            <p class="text-[11px] text-gray-500 font-bold uppercase tracking-wide">Balance</p>
                            <p class="text-2xl font-black text-white font-mono tracking-tight">{{ formatCurrency(account.balance) }}</p>
                        </div>

                        <div class="pt-4 border-t border-[#1f2128] flex justify-between items-center">
                            <span class="bg-yellow-500/10 text-yellow-500 text-[10px] font-bold px-2 py-1 rounded border border-yellow-500/20 uppercase tracking-wider">
                                {{ account.exchange }}
                            </span>
                            <span class="text-[10px] font-bold text-gray-500">{{ account.currency }}</span>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-20 bg-[#121317] border border-[#1f2128] border-dashed rounded-xl">
                    <svg class="w-12 h-12 mx-auto text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                    <h3 class="text-gray-400 font-bold mb-1">No Accounts Found</h3>
                    <p class="text-xs text-gray-600 mb-4">Try adjusting your filters or add a new portfolio.</p>
                    <Link :href="route('trading-account.setup')" class="text-blue-500 hover:text-blue-400 text-xs font-bold uppercase">ADD NEW PORTFOLIO &rarr;</Link>
                </div>

            </main>
            
            <Footer :is-sidebar-collapsed="isSidebarCollapsed" />
        </div>

        <div v-if="showDeleteModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="bg-[#121317] border border-[#2d2f36] rounded-xl p-6 w-full max-w-sm shadow-2xl">
                <div class="text-center">
                    <div class="w-12 h-12 rounded-full bg-red-500/10 flex items-center justify-center mx-auto mb-4 text-red-500">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Delete Portfolio?</h3>
                    <p class="text-sm text-gray-500 mb-6">
                        Are you sure you want to delete <span class="text-white font-bold">"{{ accountToDelete?.name }}"</span>? This action cannot be undone.
                    </p>
                    <div class="flex gap-3">
                        <button @click="showDeleteModal = false" class="flex-1 px-4 py-2 bg-[#1f2128] hover:bg-[#2d2f36] text-gray-300 text-xs font-bold rounded-lg transition-colors">
                            CANCEL
                        </button>
                        <button @click="deleteAccount" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-lg transition-colors">
                            CONFIRM DELETE
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
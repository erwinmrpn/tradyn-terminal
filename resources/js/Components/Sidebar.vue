<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

// Menerima status collapse dari Parent (Index.vue)
const props = defineProps<{
    isCollapsed: boolean;
}>();

// Mengirim sinyal klik tombol ke Parent
const emit = defineEmits(['toggle']);

const menuItems = [
    { name: 'Home', route: 'dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { name: 'Portfolio', route: 'portfolio', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
    { name: 'Account Activity Log', route: 'account.activity', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' },
    { name: 'Trade Log', route: 'trade.log', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4' },
    { name: 'Trade Calendar', route: 'trade.calendar', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { name: 'Watchlist Assets', route: 'watchlist', icon: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' },
];
</script>

<template>
    <aside
        class="fixed left-0 top-0 h-screen bg-[#0a0b0d] border-r border-[#1f2128] flex flex-col z-50 transition-all duration-300 ease-in-out"
        :class="props.isCollapsed ? 'w-[72px]' : 'w-64'"
    >
        <div class="h-16 flex items-center justify-between px-4 relative flex-shrink-0">
            <div class="flex items-center space-x-3 overflow-hidden whitespace-nowrap w-full">
                <img src="/images/logo_nobg.png" class="w-8 h-8 rounded object-contain flex-shrink-0" />
                <span 
                    class="font-bold text-gray-100 text-lg tracking-wide transition-opacity duration-200"
                    :class="props.isCollapsed ? 'opacity-0 w-0 hidden' : 'opacity-100 w-auto block'"
                >
                    Tradyn Terminal
                </span>
            </div>
        </div>

        <button
            @click="emit('toggle')"
            class="absolute -right-3 top-1/2 -translate-y-1/2
                    w-6 h-6 rounded-full flex items-center justify-center
                    bg-[#1a1b20] border border-[#2a2c34]
                    text-gray-300 hover:text-white transition shadow-lg cursor-pointer z-50"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path v-if="props.isCollapsed" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>

        <nav class="flex-1 py-6 px-2 space-y-1 overflow-y-auto overflow-x-hidden">
            <template v-for="item in menuItems" :key="item.name">
                <Link
                    :href="route(item.route)"
                    class="group flex items-center px-3 py-2.5 rounded-lg transition-all duration-200 whitespace-nowrap"
                    :class="route().current(item.route)
                        ? 'bg-[#1a1b20] text-blue-500'
                        : 'text-gray-500 hover:text-gray-200 hover:bg-[#1a1b20]'"
                >
                    <svg class="w-6 h-6 flex-shrink-0 transition-colors duration-200"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                    </svg>

                    <span
                        class="ml-3 font-medium transition-all duration-200"
                        :class="props.isCollapsed ? 'opacity-0 w-0 hidden' : 'opacity-100 w-auto block'"
                    >
                        {{ item.name }}
                    </span>
                </Link>
            </template>
        </nav>

        <div class="p-4 border-t border-[#1f2128] flex-shrink-0">
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="w-full flex items-center justify-center lg:justify-start text-gray-500 hover:text-red-400 transition group whitespace-nowrap"
            >
                <svg class="w-6 h-6 group-hover:text-red-400 flex-shrink-0"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span 
                    class="ml-3 text-sm font-medium transition-all duration-200"
                    :class="props.isCollapsed ? 'opacity-0 w-0 hidden' : 'opacity-100 w-auto block'"
                >
                    Log Out
                </span>
            </Link>
        </div>
    </aside>
</template>
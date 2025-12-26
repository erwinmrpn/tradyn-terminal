<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

// Daftar Menu Navigasi
// Pastikan nama 'route' di sini SAMA PERSIS dengan ->name(...) di web.php
const menuItems = [
    { name: 'Home', route: 'dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { name: 'Portfolio', route: 'portfolio', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
    { name: 'Account Activity Log', route: 'account.activity', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' },
    // Menu Research & Signal (Placeholder)
    { name: 'Research', route: 'research', icon: 'M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z' },
    { name: 'Signal', route: 'signal', icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
];
</script>

<template>
    <aside class="w-[72px] lg:w-64 bg-[#0a0b0d] border-r border-[#1f2128] flex flex-col h-screen fixed left-0 top-0 z-50 transition-all duration-300">
        
        <div class="h-16 flex items-center justify-center lg:justify-start lg:px-6 border-b border-[#1f2128]">
            <img 
                src="/images/logo.png" 
                alt="Logo" 
                class="w-8 h-8 rounded flex-shrink-0 object-contain" 
            />
            <span class="ml-3 font-bold text-gray-100 text-lg hidden lg:block tracking-wide">Tradyn Terminal</span>
        </div>

        <nav class="flex-1 py-6 px-3 space-y-1 overflow-y-auto">
            <template v-for="item in menuItems" :key="item.name">
                <Link :href="route(item.route)" 
                    class="group flex items-center px-2 lg:px-3 py-2.5 rounded-lg transition-all duration-200"
                    :class="route().current(item.route) 
                        ? 'bg-[#1a1b20] text-blue-500' 
                        : 'text-gray-500 hover:text-gray-200 hover:bg-[#1a1b20]'"
                >
                    <svg class="w-6 h-6 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                    </svg>
                    <span class="ml-3 font-medium hidden lg:block">{{ item.name }}</span>
                </Link>
            </template>
        </nav>

        <div class="p-4 border-t border-[#1f2128]">
             <Link :href="route('logout')" method="post" as="button" class="w-full flex items-center justify-center lg:justify-start text-gray-500 hover:text-red-400 transition group">
                <svg class="w-6 h-6 group-hover:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                <span class="ml-3 text-sm font-medium hidden lg:block">Log Out</span>
            </Link>
        </div>
    </aside>
</template>
<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <nav class="h-16 flex items-center justify-between px-6 lg:px-8 border-b border-[#1f2128] bg-[#0a0b0d] sticky top-0 z-30 w-full">
        
        <div class="flex-1 max-w-xl">
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-500 group-focus-within:text-blue-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input 
                    type="text" 
                    class="block w-full bg-[#121317] border border-[#1f2128] rounded-lg py-1.5 pl-10 pr-3 text-sm text-gray-200 placeholder-gray-600 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:outline-none transition-all" 
                    placeholder="Search assets, symbols, or actions..." 
                />
            </div>
        </div>

        <div class="flex items-center gap-4 ml-4">
            <button class="text-gray-400 hover:text-white transition relative">
                <div class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full border border-[#0a0b0d]"></div>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
            </button>

            <button class="hidden sm:block text-sm text-gray-400 hover:text-white transition font-medium">
                Settings asu
            </button>
            
            <div class="h-8 w-8 rounded-full bg-gradient-to-tr from-blue-600 to-purple-600 border border-gray-600 cursor-pointer hover:border-white transition"></div>
        </div>
    </nav>
</template>
<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
// Mengambil data user, fallback ke dummy jika belum login
const user = computed(() => page.props.auth.user);
</script>

<template>
    <nav class="sticky top-0 z-30 h-16 flex items-center justify-between px-6 lg:px-8 border-b border-[#1f2128] bg-[#0a0b0d] transition-colors duration-300">
        
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
                    placeholder="Search assets..." 
                />
            </div>
        </div>

        <div class="flex items-center gap-4 ml-4">
            
            <button class="text-gray-400 hover:text-white transition relative p-1 rounded-md hover:bg-[#1a1b20]">
                <div class="absolute top-1 right-1.5 w-2 h-2 bg-red-500 rounded-full border border-[#0a0b0d]"></div>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
            </button>
            
            <div class="relative group h-full flex items-center">
                
                <div class="h-8 w-8 rounded-full bg-gradient-to-tr from-blue-600 to-purple-600 border border-gray-600 cursor-pointer group-hover:border-white transition shadow-lg shadow-blue-500/20"></div>

                <div class="absolute top-full right-0 mt-2 w-64 bg-[#1a1b20] border border-[#2d2f36] rounded-xl shadow-2xl 
                            invisible opacity-0 transform translate-y-2 
                            group-hover:visible group-hover:opacity-100 group-hover:translate-y-0 
                            transition-all duration-200 ease-out origin-top-right z-50">
                    
                    <div class="px-4 py-4 border-b border-[#2d2f36] flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-gradient-to-tr from-blue-600 to-purple-600 flex-shrink-0"></div>
                        <div class="overflow-hidden">
                            <h4 class="text-sm font-bold text-white truncate">
                                {{ user ? user.name : 'User' }}
                            </h4>
                            <p class="text-xs text-gray-500 truncate">
                                {{ user ? user.email : 'email@example.com' }}
                            </p>
                        </div>
                    </div>

                    <div class="py-2">
                        <Link :href="route('profile.edit')" class="flex items-center px-4 py-2.5 text-sm text-gray-300 hover:bg-[#25262c] hover:text-white transition-colors">
                            <svg class="w-4 h-4 mr-3 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Account & Settings
                        </Link>

                        <div class="border-t border-[#2d2f36] my-1"></div>

                        <Link :href="route('logout')" method="post" as="button" class="w-full text-left flex items-center px-4 py-2.5 text-sm text-gray-300 hover:bg-[#25262c] hover:text-red-400 transition-colors">
                            <svg class="w-4 h-4 mr-3 text-gray-500 hover:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                            Log Out
                        </Link>

                    </div>
                </div>
            </div>
        </div>
    </nav>
</template>
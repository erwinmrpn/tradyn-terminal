<script setup lang="ts">
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';
</script>

<template>
    <div class="min-h-screen bg-[#0a0b0d] text-gray-300 font-sans flex">
        
        <Sidebar />

        <main class="flex-1 ml-[72px] lg:ml-64 flex flex-col min-h-screen transition-all duration-300">
            
            <Navbar />

            <div v-if="$slots.header" class="pt-8 px-6 lg:px-8">
                <slot name="header" />
            </div>

            <div class="p-6 lg:p-8">
                <slot />
            </div>

        </main>
    </div>
</template>
<script setup lang="ts">
import { defineAsyncComponent } from 'vue';

// Import Komponen dari folder ResultPartials
import ResultFutures from './ResultPartials/ResultFutures.vue';
import ResultSpot from './ResultPartials/ResultSpot.vue';

// 1. Terima Props 'activeTab' dan 'trades' dari Index.vue
const props = defineProps<{
    activeTab: 'SPOT' | 'FUTURES';
    trades: any[]; 
}>();

// 2. Definisi Event agar bisa mengubah state di Index.vue
const emit = defineEmits(['update:activeTab']);
</script>

<template>
    <div class="animate-fade-in-down">
        
        <div class="flex justify-center mb-8">
            <div class="bg-[#1a1b20] p-1 rounded-full flex items-center w-full max-w-md border border-[#2d2f36] relative shadow-inner">
                
                <div class="absolute top-1 bottom-1 w-[calc(50%_-_4px)] rounded-full transition-all duration-300 ease-out shadow-[0_0_15px_rgba(255,255,255,0.1)] z-0" 
                    :class="[
                        activeTab === 'SPOT' ? 'left-1 bg-emerald-600 shadow-[0_0_15px_rgba(16,185,129,0.4)]' : 'left-[calc(50%_+_2px)] bg-blue-600 shadow-[0_0_15px_rgba(37,99,235,0.4)]'
                    ]">
                </div>

                <button @click="$emit('update:activeTab', 'SPOT')" 
                    class="flex-1 py-2 rounded-full text-xs sm:text-sm font-bold z-10 relative transition-colors" 
                    :class="activeTab === 'SPOT' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">
                    Result SPOT
                </button>

                <button @click="$emit('update:activeTab', 'FUTURES')" 
                    class="flex-1 py-2 rounded-full text-xs sm:text-sm font-bold z-10 relative transition-colors" 
                    :class="activeTab === 'FUTURES' ? 'text-white' : 'text-gray-500 hover:text-gray-300'">
                    Result FUTURES
                </button>
            </div>
        </div>

        <div class="min-h-[300px]">
            
            <div v-if="activeTab === 'FUTURES'">
                <ResultFutures :trades="props.trades" />
            </div>

            <div v-if="activeTab === 'SPOT'">
                <ResultSpot :trades="props.trades" />
            </div>

        </div>

    </div>
</template>

<style scoped>
@keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in-down { animation: fadeInDown 0.3s ease-out forwards; }
</style>
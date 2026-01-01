<script setup lang="ts">
// Terima props 'activeTab' dari Index.vue
defineProps<{
    activeTab: 'SPOT' | 'FUTURES';
}>();

// Emit event update ke Index.vue saat tombol diklik
defineEmits(['update:activeTab']);
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

        <div class="bg-[#121317] border border-[#1f2128] rounded-xl p-8 shadow-lg min-h-[300px] flex flex-col items-center justify-center text-gray-500">
            <div v-if="activeTab === 'SPOT'" class="text-center">
                <i class="fas fa-coins text-4xl mb-4 text-emerald-600"></i>
                <h3 class="text-lg font-bold text-gray-300">Spot Trade History</h3>
                <p class="text-xs mt-2">Menampilkan hasil trading Spot yang sudah selesai.</p>
            </div>

            <div v-if="activeTab === 'FUTURES'" class="text-center">
                <i class="fas fa-chart-area text-4xl mb-4 text-blue-600"></i>
                <h3 class="text-lg font-bold text-gray-300">Futures Trade History</h3>
                <p class="text-xs mt-2">Menampilkan posisi Futures yang sudah CLOSED.</p>
            </div>
        </div>

    </div>
</template>

<style scoped>
@keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in-down { animation: fadeInDown 0.3s ease-out forwards; }
</style>
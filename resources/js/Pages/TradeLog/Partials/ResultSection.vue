<script setup lang="ts">
// Import komponen anak
import ResultFutures from './ResultPartials/ResultFutures.vue';
import ResultSpot from './ResultPartials/ResultSpot.vue';

// --- PROPS ---
// Menerima data dari Parent (Index.vue)
const props = defineProps<{
    trades?: any[]; 
    activeTab: 'SPOT' | 'FUTURES'; // [DIKEMBALIKAN] Terima status tab dari parent
    metrics?: any;                 // [WAJIB ADA] Data summary untuk ResultSpot
    selectedPeriod?: string;       // [WAJIB ADA] Filter periode untuk ResultSpot
}>();

// --- EMITS ---
// Memberitahu Parent jika tab berubah
const emit = defineEmits(['update:activeTab']);

// Fungsi helper untuk ganti tab
const switchTab = (tab: 'SPOT' | 'FUTURES') => {
    emit('update:activeTab', tab); // Kirim sinyal ke Index.vue untuk handle logic data
};
</script>

<template>
    <div class="w-full mt-4">
        
        <div class="flex justify-center mb-6">
            <div class="bg-[#0f1012] p-1.5 rounded-full inline-flex border border-[#2d2f36] shadow-inner relative z-0">
                
                <button 
                    @click="switchTab('SPOT')" 
                    class="relative px-6 md:px-8 py-2 md:py-2.5 rounded-full text-xs md:text-sm font-bold overflow-hidden transition-all duration-500 group"
                    :class="activeTab === 'SPOT' 
                        ? 'text-white shadow-[0_0_20px_rgba(140,82,255,0.4)]' 
                        : 'text-gray-500 hover:text-gray-300'"
                >
                    <div 
                        class="absolute inset-0 bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] transition-opacity duration-500 ease-in-out -z-10"
                        :class="activeTab === 'SPOT' ? 'opacity-100' : 'opacity-0'"
                    ></div>
                    
                    <span class="relative z-10 transition-colors duration-500">Result SPOT</span>
                </button>

                <button 
                    @click="switchTab('FUTURES')" 
                    class="relative px-6 md:px-8 py-2 md:py-2.5 rounded-full text-xs md:text-sm font-bold overflow-hidden transition-all duration-500 group"
                    :class="activeTab === 'FUTURES' 
                        ? 'text-white shadow-[0_0_20px_rgba(140,82,255,0.4)]' 
                        : 'text-gray-500 hover:text-gray-300'"
                >
                    <div 
                        class="absolute inset-0 bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] transition-opacity duration-500 ease-in-out -z-10"
                        :class="activeTab === 'FUTURES' ? 'opacity-100' : 'opacity-0'"
                    ></div>

                    <span class="relative z-10 transition-colors duration-500">Result FUTURES</span>
                </button>

            </div>
        </div>

        <div class="relative min-h-[300px]"> 
            <Transition name="fade-slide" mode="out-in">
                
                <ResultSpot 
                    v-if="activeTab === 'SPOT'" 
                    :trades="props.trades || []" 
                    :metrics="props.metrics"
                    :selected-period="props.selectedPeriod || 'all'"
                />
                
                <ResultFutures 
                    v-else-if="activeTab === 'FUTURES'" 
                    :trades="props.trades || []" 
                />
            </Transition>
        </div>

    </div>
</template>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(15px);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-15px);
}
</style>
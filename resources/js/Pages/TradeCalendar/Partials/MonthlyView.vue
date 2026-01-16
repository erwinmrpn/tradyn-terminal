<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    year: number;
    month: any; 
    dailyData: Record<string, { trades: number, pnl: number }>; 
    onBack: () => void;
}>();

// Definisi Emit untuk komunikasi ke Index.vue
const emit = defineEmits(['view-day']);

// --- LOGIC GENERATE KALENDER ---
const calendarDays = computed(() => {
    const days = [];
    const daysInMonth = new Date(props.year, props.month.month_index, 0).getDate();
    const firstDayIndex = new Date(props.year, props.month.month_index - 1, 1).getDay();

    // 1. Padding Awal Bulan
    for (let i = 0; i < firstDayIndex; i++) {
        days.push({ date: '', active: false, pnl: 0, trades: 0 });
    }

    // 2. Isi Tanggal
    for (let i = 1; i <= daysInMonth; i++) {
        const dateKey = `${props.year}-${String(props.month.month_index).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
        const data = props.dailyData[dateKey] || { pnl: 0, trades: 0 };

        days.push({ 
            date: i, 
            active: true, 
            pnl: Number(data.pnl), 
            trades: data.trades 
        });
    }

    // 3. Padding Akhir Bulan
    const totalDays = days.length;
    const remainingDays = 7 - (totalDays % 7);
    if (remainingDays < 7) {
        for (let i = 0; i < remainingDays; i++) {
            days.push({ date: '', active: false, pnl: 0, trades: 0 });
        }
    }

    return days;
});

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-US', { 
        style: 'currency', 
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};

// Fungsi Update: Emit event dengan tanggal lengkap YYYY-MM-DD
const viewDetails = (date: number | string) => {
    if (typeof date === 'string') return; 
    
    // Format tanggal jadi string penuh YYYY-MM-DD untuk dikirim ke API
    const fullDate = `${props.year}-${String(props.month.month_index).padStart(2, '0')}-${String(date).padStart(2, '0')}`;
    
    // Kirim sinyal ke Index.vue untuk fetch data
    emit('view-day', fullDate);
};

// Style khusus untuk memaksa gradient text
const gradientTextStyle = {
    background: 'linear-gradient(to right, #8c52ff, #5ce1e6)',
    '-webkit-background-clip': 'text',
    'background-clip': 'text',
    '-webkit-text-fill-color': 'transparent',
    'color': 'transparent'
};
</script>

<template>
    <div class="animate-fade-in-up">
        
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-4">
                <button @click="props.onBack" class="p-2 rounded-lg bg-[#1f2128] hover:bg-[#2d3039] text-gray-400 hover:text-white transition-all border border-[#2d3039]">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <div>
                    <h2 class="text-2xl font-bold text-white tracking-tight">{{ month.month_name }} {{ year }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Daily Performance Breakdown</p>
                </div>
            </div>
            
            <div class="text-right">
                <p class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mb-0.5">Total PnL</p>
                <p class="text-xl font-mono font-bold" :class="month.total_pnl >= 0 ? 'text-green-400' : 'text-red-400'">
                    {{ formatCurrency(month.total_pnl) }}
                </p>
            </div>
        </div>

        <div class="bg-[#1f2128] border border-[#1f2128] rounded-xl overflow-hidden shadow-2xl">
            
            <div class="grid grid-cols-7 gap-px bg-[#1f2128] border-b border-[#1f2128]">
                <div v-for="day in ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']" :key="day" 
                     class="bg-[#121317] text-gray-500 text-[10px] font-bold uppercase tracking-wider text-center py-3">
                    {{ day }}
                </div>
            </div>

            <div class="grid grid-cols-7 gap-px bg-[#1f2128]">
                <div v-for="(day, index) in calendarDays" :key="index" 
                    class="relative min-h-[110px] p-3 transition-colors duration-200 group"
                    :class="[
                        day.active 
                            ? 'bg-[#0a0b0d] hover:bg-[#14151a] cursor-pointer' 
                            : 'bg-[#0f1013]' 
                    ]"
                >
                    <template v-if="day.active">
                        <div class="text-2xl font-bold leading-none w-fit" :style="gradientTextStyle">
                            {{ String(day.date).padStart(2, '0') }}
                        </div>

                        <div v-if="day.trades > 0" class="absolute top-3 right-3">
                            <button @click.stop="viewDetails(day.date)" 
                                    class="text-[#8c52ff] hover:text-[#5ce1e6] transition-colors"
                                    title="View Trades">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </button>
                        </div>

                        <div v-if="day.trades > 0" class="absolute bottom-3 left-3 right-3">
                            <div class="text-sm font-bold font-mono tracking-tight mb-0.5" 
                                :class="day.pnl >= 0 ? 'text-green-400' : 'text-red-400'">
                                {{ day.pnl > 0 ? '+' : '' }}{{ formatCurrency(day.pnl) }}
                            </div>
                            
                            <div class="text-[10px] font-medium w-fit" :style="gradientTextStyle">
                                {{ day.trades }} trades
                            </div>
                        </div>

                        <div v-else class="absolute bottom-3 left-3 font-mono text-sm w-fit" :style="gradientTextStyle">
                            $0
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in-up { 
    animation: fadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1); 
}

@keyframes fadeInUp { 
    from { opacity: 0; transform: translateY(10px); } 
    to { opacity: 1; transform: translateY(0); } 
}
</style>
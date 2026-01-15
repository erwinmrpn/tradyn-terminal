<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    year: number;
    month: any; // Object: { month_name, month_index, ... }
    dailyData: Record<string, { trades: number, pnl: number }>; // Data Real dari Controller
    onBack: () => void;
}>();

// LOGIC GENERATE KALENDER YANG BENAR
const calendarDays = computed(() => {
    const days = [];
    
    // 1. Tentukan jumlah hari dalam bulan ini
    const daysInMonth = new Date(props.year, props.month.month_index, 0).getDate();
    
    // 2. Tentukan hari pertama bulan ini jatuh di hari apa (0=Minggu, 1=Senin, dst)
    // Note: month_index di JS mulai dari 0 (Jan) s/d 11 (Des). 
    // Tapi data dari Laravel month_index kita mulai dari 1. Jadi perlu dikurang 1.
    const firstDayIndex = new Date(props.year, props.month.month_index - 1, 1).getDay();

    // 3. Tambahkan "Kotak Kosong" untuk padding di awal bulan
    for (let i = 0; i < firstDayIndex; i++) {
        days.push({ date: '', active: false, pnl: 0, trades: 0 });
    }

    // 4. Isi Tanggal 1 s/d Akhir Bulan + Cocokkan dengan Data DB
    for (let i = 1; i <= daysInMonth; i++) {
        // Format tanggal YYYY-MM-DD agar cocok dengan key array dari Laravel
        // Contoh: "2026-01-05"
        const dateKey = `${props.year}-${String(props.month.month_index).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
        
        // Ambil data jika ada, kalau tidak ada default 0
        const data = props.dailyData[dateKey] || { pnl: 0, trades: 0 };

        days.push({ 
            date: i, 
            active: true, 
            pnl: Number(data.pnl), // Pastikan jadi number
            trades: data.trades 
        });
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
</script>

<template>
    <div class="animate-fade-in-up">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-4">
                <button @click="props.onBack" class="p-2 rounded-lg bg-[#1f2128] hover:bg-[#2d3039] text-gray-400 hover:text-white transition-colors border border-[#2d3039]">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </button>
                <div>
                    <h2 class="text-2xl font-bold text-white">{{ month.month_name }} {{ year }}</h2>
                    <p class="text-sm text-gray-500">Detailed Daily View</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-500 uppercase">Monthly PnL</p>
                <p class="font-mono font-bold" :class="month.total_pnl >= 0 ? 'text-green-400' : 'text-red-400'">{{ formatCurrency(month.total_pnl) }}</p>
            </div>
        </div>

        <div class="bg-[#121317] border border-[#1f2128] rounded-xl p-6">
            <div class="grid grid-cols-7 mb-4 text-center">
                <div v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day" class="text-gray-500 text-xs font-bold uppercase tracking-wider">{{ day }}</div>
            </div>

            <div class="grid grid-cols-7 gap-2">
                <div v-for="(day, index) in calendarDays" :key="index" 
                    class="aspect-square border border-[#1f2128] rounded-lg p-2 relative group hover:border-[#8c52ff] transition-colors cursor-pointer flex flex-col items-center justify-start"
                    :class="day.active ? 'bg-[#0a0b0d]' : 'bg-transparent border-none'">
                    
                    <template v-if="day.active">
                        <span class="text-xs text-gray-400 font-bold mb-1">{{ day.date }}</span>
                        
                        <div v-if="day.trades > 0" class="flex flex-col items-center justify-center w-full h-full pb-2">
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded whitespace-nowrap" 
                                :class="day.pnl >= 0 ? 'text-green-400 bg-green-500/10' : 'text-red-400 bg-red-500/10'">
                                {{ formatCurrency(day.pnl) }}
                            </span>
                            <span class="text-[9px] text-gray-600 mt-1">{{ day.trades }} Trades</span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in-up { animation: fadeInUp 0.3s ease-out; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
<script setup lang="ts">
import { computed, ref } from 'vue';

// --- PROPS ---
const props = defineProps<{
    trades: any[]; 
}>();

// --- STATE ---
// [UPDATE] Default ke 'ALL'
const timeFrame = ref<'ALL' | 'TODAY' | 'WEEK' | 'MONTH'>('ALL');
const expandedCards = ref<Set<number>>(new Set());

// --- STATE: MODALS ---
const showImageModal = ref(false);
const selectedImageUrl = ref('');
const selectedImageTitle = ref('');

const showNoteModal = ref(false);
const selectedNoteContent = ref('');
const selectedNoteTitle = ref('');

// --- HELPERS: DATE ---
const getStartOfDay = (date: Date) => { const d = new Date(date); d.setHours(0, 0, 0, 0); return d; };
const getStartOfWeek = (date: Date) => { const d = new Date(date); const day = d.getDay() || 7; if (day !== 1) d.setHours(-24 * (day - 1)); d.setHours(0, 0, 0, 0); return d; };
const getStartOfMonth = (date: Date) => { const d = new Date(date); d.setDate(1); d.setHours(0, 0, 0, 0); return d; };

// --- HELPERS: FORMATTERS ---
const formatCurrency = (val: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val);

// [UPDATE] Format Durasi Lebih Pintar (Hide 0s)
const formatDurationSmart = (ms: number) => {
    if (!ms || ms <= 0) return '-';
    
    const seconds = Math.floor((ms / 1000) % 60);
    const minutes = Math.floor((ms / (1000 * 60)) % 60);
    const hours = Math.floor((ms / (1000 * 60 * 60)) % 24);
    const days = Math.floor(ms / (1000 * 60 * 60 * 24));

    // > 1 Hari: Tampilkan Hari, Jam, Menit
    if (days > 0) return `${days}d ${hours}h ${minutes}m`;
    
    // > 1 Jam: Tampilkan Jam, Menit
    if (hours > 0) return `${hours}h ${minutes}m`;
    
    // > 1 Menit: Tampilkan Menit. Detik hanya jika > 0
    if (minutes > 0) return seconds > 0 ? `${minutes}m ${seconds}s` : `${minutes}m`;
    
    // Hanya Detik
    return `${seconds}s`;
};

const getHoldingPeriod = (trade: any) => {
    if (!trade.buy_date || !trade.buy_time || !trade.sell_date || !trade.sell_time) return '-';
    
    const start = new Date(trade.buy_date + 'T' + trade.buy_time);
    const end = new Date(trade.sell_date + 'T' + trade.sell_time);
    
    const diffMs = end.getTime() - start.getTime();
    return formatDurationSmart(diffMs);
};

// --- HELPER: HOLDING STATUS ---
const getHoldingStatus = (trade: any) => {
    if (!trade.buy_date || !trade.buy_time || !trade.sell_date || !trade.sell_time) return { label: '-', class: 'text-gray-500' };
    
    const start = new Date(trade.buy_date + 'T' + trade.buy_time);
    const end = new Date(trade.sell_date + 'T' + trade.sell_time);
    const diffDays = (end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24);

    if (diffDays <= 7) return { label: 'Short Term', class: 'text-blue-400 bg-blue-400/10 border-blue-400/20' };
    if (diffDays <= 30) return { label: 'Medium Term', class: 'text-yellow-400 bg-yellow-400/10 border-yellow-400/20' };
    return { label: 'Long Term', class: 'text-purple-400 bg-purple-400/10 border-purple-400/20' };
};

// --- HELPERS: FEE CALCULATOR (PER TRADE) ---
const getTradeTotalFee = (trade: any) => {
    let f = parseFloat(trade.fee || 0);
    if (trade.transactions && Array.isArray(trade.transactions)) {
        f += trade.transactions.reduce((acc: number, cur: any) => acc + parseFloat(cur.fee || 0), 0);
    }
    return f;
};

// --- HELPERS: UI ---
const toggleExpand = (id: number) => {
    if (expandedCards.value.has(id)) expandedCards.value.delete(id);
    else expandedCards.value.add(id);
};

// --- HELPERS: MODAL FUNCTIONS ---
const openImageModal = (path: string, title: string) => {
    selectedImageUrl.value = '/storage/' + path; 
    selectedImageTitle.value = title;
    showImageModal.value = true;
};

const closeImageModal = () => {
    showImageModal.value = false;
    selectedImageUrl.value = '';
};

const downloadImage = () => {
    if (!selectedImageUrl.value) return;
    const link = document.createElement('a');
    link.href = selectedImageUrl.value;
    link.download = `${selectedImageTitle.value.replace(/\s+/g, '_')}.png`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const viewNote = (note: string, title: string) => {
    selectedNoteContent.value = note;
    selectedNoteTitle.value = title;
    showNoteModal.value = true;
};

const closeNoteModal = () => {
    showNoteModal.value = false;
    selectedNoteContent.value = '';
};

// --- LOGIC: FILTER TRADES ---
const filterTradesByPeriod = (allTrades: any[], period: string, offset: number = 0) => {
    if (!allTrades || !Array.isArray(allTrades)) return [];

    // [UPDATE] Logika untuk ALL Time Frame
    if (period === 'ALL') {
        if (offset > 0) return []; 
        return [...allTrades].sort((a, b) => {
            const timeA = new Date(a.sell_date + 'T' + (a.sell_time || '00:00')).getTime();
            const timeB = new Date(b.sell_date + 'T' + (b.sell_time || '00:00')).getTime();
            return timeB - timeA;
        });
    }

    const now = new Date();
    let startDate: Date, endDate: Date;

    if (period === 'TODAY') {
        startDate = getStartOfDay(now);
        startDate.setDate(startDate.getDate() - offset);
        endDate = new Date(startDate);
        endDate.setHours(23, 59, 59, 999);
    } 
    else if (period === 'WEEK') {
        startDate = getStartOfWeek(now);
        startDate.setDate(startDate.getDate() - (offset * 7));
        endDate = new Date(startDate);
        endDate.setDate(endDate.getDate() + 6);
        endDate.setHours(23, 59, 59, 999);
    } 
    else { // MONTH
        startDate = getStartOfMonth(now);
        startDate.setMonth(startDate.getMonth() - offset);
        endDate = new Date(startDate);
        endDate.setMonth(endDate.getMonth() + 1);
        endDate.setDate(0); 
        endDate.setHours(23, 59, 59, 999);
    }

    return allTrades.filter(t => {
        if (!t.sell_date) return false;
        const tradeDate = new Date(t.sell_date + 'T' + (t.sell_time || '00:00'));
        return tradeDate >= startDate && tradeDate <= endDate;
    }).sort((a, b) => {
        const timeA = new Date(a.sell_date + 'T' + (a.sell_time || '00:00')).getTime();
        const timeB = new Date(b.sell_date + 'T' + (b.sell_time || '00:00')).getTime();
        return timeB - timeA;
    });
};

// --- LOGIC: CALCULATE METRICS ---
const calculateMetrics = (trades: any[]) => {
    let netPnL = 0;
    let totalInvested = 0; 
    let totalFee = 0;
    let wins = 0;
    let totalDurationMs = 0;

    if (!trades) return { netPnL:0, roi:0, totalTrades:0, winRate:0, avgDuration:0, totalFee:0 };

    trades.forEach(t => {
        const pnl = parseFloat(t.pnl || 0);
        
        // Hitung Total Fee
        const tradeFee = getTradeTotalFee(t);
        totalFee += tradeFee;

        // [UPDATE] Net PnL = Gross PnL - Total Fee
        netPnL += (pnl - tradeFee);

        if (pnl > 0) wins++;

        // Hitung Modal (Invested) untuk ROI
        let revenue = 0;
        if (t.transactions && Array.isArray(t.transactions)) {
            t.transactions.forEach((tx: any) => {
                if (tx.type === 'SELL') {
                    revenue += (parseFloat(tx.price) * parseFloat(tx.quantity));
                }
            });
        }
        if (revenue === 0 && t.price && t.quantity) {
            revenue = parseFloat(t.price) * parseFloat(t.quantity);
        }

        // Cost Basis estimasi: Revenue - Profit - Fee
        const costBasis = revenue - pnl - tradeFee;
        totalInvested += (costBasis > 0 ? costBasis : 0);

        if (t.buy_date && t.sell_date) {
            const start = new Date(t.buy_date + 'T' + t.buy_time).getTime();
            const end = new Date(t.sell_date + 'T' + t.sell_time).getTime();
            totalDurationMs += (end - start);
        }
    });

    const count = trades.length;
    let roi = 0;
    if (count > 0 && totalInvested > 0) {
        roi = (netPnL / totalInvested) * 100;
    }

    return {
        netPnL,
        roi,
        totalTrades: count,
        winRate: count > 0 ? (wins / count) * 100 : 0,
        avgDuration: count > 0 ? totalDurationMs / count : 0,
        totalFee
    };
};

const currentTrades = computed(() => filterTradesByPeriod(props.trades, timeFrame.value, 0));
const previousTrades = computed(() => filterTradesByPeriod(props.trades, timeFrame.value, 1));
const currentMetrics = computed(() => calculateMetrics(currentTrades.value));
const previousMetrics = computed(() => calculateMetrics(previousTrades.value));

const getComparisonLabel = () => {
    if (timeFrame.value === 'TODAY') return 'vs Yest.';
    if (timeFrame.value === 'WEEK') return 'vs Last Wk.';
    return 'vs Last Mo.';
};
</script>

<template>
    <div class="space-y-8 animate-fade-in-down">
        
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                <span class="p-1.5 rounded bg-gradient-to-br from-[#8c52ff] to-[#5ce1e6] text-black">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                </span>
                Spot Result Performance
            </h3>
            
            <div class="p-[1px] rounded-lg bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6]">
                <div class="flex bg-[#1a1b20] p-1 rounded-lg h-full">
                    <button @click="timeFrame = 'ALL'" class="px-4 py-1.5 text-[10px] font-bold uppercase rounded transition-all" :class="timeFrame === 'ALL' ? 'bg-[#2d2f36] text-white shadow' : 'text-gray-500 hover:text-gray-300'">All</button>
                    <button @click="timeFrame = 'TODAY'" class="px-4 py-1.5 text-[10px] font-bold uppercase rounded transition-all" :class="timeFrame === 'TODAY' ? 'bg-[#2d2f36] text-white shadow' : 'text-gray-500 hover:text-gray-300'">Today</button>
                    <button @click="timeFrame = 'WEEK'" class="px-4 py-1.5 text-[10px] font-bold uppercase rounded transition-all" :class="timeFrame === 'WEEK' ? 'bg-[#2d2f36] text-white shadow' : 'text-gray-500 hover:text-gray-300'">Week</button>
                    <button @click="timeFrame = 'MONTH'" class="px-4 py-1.5 text-[10px] font-bold uppercase rounded transition-all" :class="timeFrame === 'MONTH' ? 'bg-[#2d2f36] text-white shadow' : 'text-gray-500 hover:text-gray-300'">Month</button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 items-stretch">
            <div class="col-span-1 relative group p-[1px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-lg">
                <div class="bg-[#121317] rounded-xl p-5 h-full relative overflow-hidden flex flex-col justify-between text-left">
                    <div class="text-[9px] text-gray-500 font-bold uppercase tracking-wider mb-1">Net PnL (After Fee)</div>
                    <div class="absolute top-0 right-0 p-3 opacity-5 group-hover:opacity-10 transition-opacity">
                        <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                    </div>
                    <div class="flex-1 flex flex-col justify-center">
                        <div class="text-xl font-black tracking-tight" :class="currentMetrics.netPnL >= 0 ? 'text-green-400' : 'text-red-500'">
                            {{ currentMetrics.netPnL >= 0 ? '+' : '' }}{{ formatCurrency(currentMetrics.netPnL) }}
                        </div>
                    </div>
                    <div v-if="timeFrame !== 'ALL'" class="flex items-center gap-1.5 mt-1">
                        <span class="text-[9px] px-1.5 py-0.5 rounded font-bold flex items-center gap-1 bg-[#1a1b20] border border-[#2d2f36]" :class="currentMetrics.netPnL >= previousMetrics.netPnL ? 'text-green-400' : 'text-red-400'">
                            <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" :d="currentMetrics.netPnL >= previousMetrics.netPnL ? 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' : 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6'" /></svg>
                            {{ previousMetrics.netPnL === 0 ? '100%' : Math.abs(((currentMetrics.netPnL - previousMetrics.netPnL) / (Math.abs(previousMetrics.netPnL) || 1)) * 100).toFixed(0) + '%' }}
                        </span>
                        <span class="text-[9px] text-gray-500 font-medium whitespace-nowrap">{{ getComparisonLabel() }}</span>
                    </div>
                </div>
            </div>

            <div class="col-span-1 p-[1px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-lg">
                <div class="bg-[#121317] rounded-xl p-5 h-full flex flex-col justify-between text-left relative overflow-hidden">
                    <div class="text-[9px] text-gray-500 font-bold uppercase tracking-wider mb-1">ROI</div>
                    <div class="flex-1 flex items-center">
                        <div class="text-xl font-black" :class="currentMetrics.roi >= 0 ? 'text-green-400' : 'text-red-500'">
                            {{ currentMetrics.roi >= 0 ? '+' : '' }}{{ currentMetrics.roi.toFixed(2) }}%
                        </div>
                    </div>
                    <div v-if="timeFrame !== 'ALL'" class="text-[9px] font-medium text-gray-500 flex items-center gap-1">
                        <span :class="currentMetrics.roi >= previousMetrics.roi ? 'text-green-500' : 'text-red-500'">{{ currentMetrics.roi >= previousMetrics.roi ? '▲' : '▼' }}</span> from prev.
                    </div>
                </div>
            </div>

            <div class="col-span-1 p-[1px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-lg">
                <div class="bg-[#121317] rounded-xl p-5 h-full flex flex-col justify-between text-left">
                    <div class="text-[9px] text-gray-500 font-bold uppercase tracking-wider mb-1">Trades</div>
                    <div class="flex-1 flex items-center">
                        <div class="text-2xl font-black text-white">{{ currentMetrics.totalTrades }}</div>
                    </div>
                    <div class="text-[9px] text-gray-600 font-medium">Closed Positions</div>
                </div>
            </div>

            <div class="col-span-1 p-[1px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-lg">
                <div class="bg-[#121317] rounded-xl p-5 h-full flex flex-col justify-between text-left">
                    <div class="text-[9px] text-gray-500 font-bold uppercase tracking-wider mb-1">Win Rate</div>
                    <div class="flex-1 flex flex-col justify-center gap-2">
                        <div class="text-xl font-black" :class="currentMetrics.winRate >= 50 ? 'text-blue-400' : 'text-yellow-500'">
                            {{ currentMetrics.winRate.toFixed(0) }}%
                        </div>
                        <div class="w-full bg-[#1f2128] h-1 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500 transition-all duration-500" :style="{ width: currentMetrics.winRate + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-1 p-[1px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-lg">
                <div class="bg-[#121317] rounded-xl p-5 h-full flex flex-col justify-between text-left">
                    <div class="text-[9px] text-gray-500 font-bold uppercase tracking-wider mb-1">Avg Hold</div>
                    <div class="flex-1 flex items-center">
                        <div class="text-lg font-black text-purple-400">{{ formatDurationSmart(currentMetrics.avgDuration) }}</div>
                    </div>
                    <div class="text-[9px] text-gray-600 font-medium">Duration</div>
                </div>
            </div>

            <div class="col-span-1 p-[1px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-lg">
                <div class="bg-[#121317] rounded-xl p-5 h-full flex flex-col justify-between text-left">
                    <div class="text-[9px] text-gray-500 font-bold uppercase tracking-wider mb-1">Total Fees</div>
                    <div class="flex-1 flex items-center">
                        <div class="text-lg font-black text-white font-mono">{{ formatCurrency(currentMetrics.totalFee) }}</div>
                    </div>
                    <div class="text-[9px] text-gray-600 font-medium">All Trades</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="trade in currentTrades" :key="trade.id" class="relative group">
                
                <div class="p-[2px] rounded-2xl bg-gradient-to-br from-[#8c52ff] to-[#5ce1e6] shadow-[0_0_15px_rgba(140,82,255,0.1)] hover:shadow-[0_0_25px_rgba(92,225,230,0.3)] transition-all duration-300">
                    <div class="bg-[#121317] rounded-2xl h-full flex flex-col justify-between overflow-hidden">
                        
                        <div class="p-5">
                            
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-xl font-black text-white tracking-wide">{{ trade.symbol }}</h3>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded text-white uppercase border border-white/20"
                                            :class="parseFloat(trade.pnl) > 0 ? 'bg-green-600' : 'bg-red-600'">
                                            {{ parseFloat(trade.pnl) > 0 ? 'WIN' : 'LOSS' }}
                                        </span>
                                    </div>
                                    <div class="text-[10px] text-gray-400 mt-1 flex flex-wrap items-center gap-2">
                                        <span class="bg-[#1f2128] px-1.5 py-0.5 rounded border border-gray-700 font-mono">
                                            {{ trade.market_type || 'SPOT' }}
                                        </span>
                                        <span>{{ trade.trading_account?.name }}</span>
                                    </div>
                                </div>
                                <div class="text-right flex flex-col items-end">
                                    <div class="text-xs text-gray-300 font-mono">{{ trade.sell_date }}</div>
                                    <div class="text-[10px] text-blue-400 mt-1 font-bold">
                                        Hold: {{ getHoldingPeriod(trade) }}
                                    </div>
                                    <span class="mt-1 text-[9px] font-black uppercase px-1.5 py-0.5 rounded border" 
                                          :class="getHoldingStatus(trade).class">
                                        {{ getHoldingStatus(trade).label }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex justify-between items-end border-t border-[#2d2f36] pt-3">
                                 <div class="flex flex-col">
                                    <span class="text-[9px] text-gray-500 uppercase font-bold">Net PnL (After Fee)</span>
                                    <span class="font-bold font-mono text-lg" 
                                          :class="(parseFloat(trade.pnl) - getTradeTotalFee(trade)) > 0 ? 'text-green-400' : 'text-red-500'">
                                        {{ (parseFloat(trade.pnl) - getTradeTotalFee(trade)) > 0 ? '+' : '' }}{{ formatCurrency(parseFloat(trade.pnl) - getTradeTotalFee(trade)) }}
                                    </span>
                                 </div>
                                 <div class="flex flex-col items-end">
                                    <span class="text-[9px] text-gray-500 uppercase font-bold">Fee</span>
                                    <span class="font-mono text-xs text-gray-300">
                                        {{ formatCurrency(getTradeTotalFee(trade)) }}
                                    </span>
                                 </div>
                            </div>

                        </div>

                        <div v-show="expandedCards.has(trade.id)" class="bg-[#0a0b0d] p-5 border-t border-[#2d2f36] space-y-4 animate-fade-in-down">
                            
                            <div v-if="trade.buy_notes" class="space-y-1">
                                <div class="text-[9px] text-blue-500 uppercase font-bold">Entry Notes</div>
                                <p class="text-[10px] text-gray-400 italic leading-relaxed">"{{ trade.buy_notes }}"</p>
                            </div>

                            <div v-if="trade.buy_screenshot" class="pt-2">
                                <button @click="openImageModal(trade.buy_screenshot, 'Entry Chart')" 
                                    class="w-full text-center text-[10px] py-2 rounded border border-blue-500/30 text-blue-400 hover:bg-blue-500/10 transition-colors font-bold uppercase tracking-wider">
                                    Entry Chart
                                </button>
                            </div>

                            <div v-if="trade.transactions && trade.transactions.length > 0">
                                <h4 class="text-[9px] text-gray-500 uppercase font-bold mb-2">History Details</h4>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left text-[9px] text-gray-400">
                                        <thead class="text-gray-500 font-bold border-b border-gray-800">
                                            <tr>
                                                <th class="pb-1">Date</th>
                                                <th class="pb-1">Type</th>
                                                <th class="pb-1 text-right">Price</th>
                                                <th class="pb-1 text-right">Qty</th>
                                                <th class="pb-1 text-right">Value</th>
                                                <th class="pb-1 text-right text-red-400">Fee</th>
                                                <th class="pb-1 text-center">Chart</th>
                                                <th class="pb-1 text-center">Note</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800">
                                            <tr v-for="tx in trade.transactions" :key="tx.id" class="hover:bg-gray-800/50">
                                                <td class="py-1.5 whitespace-nowrap">
                                                    <div class="text-gray-300">{{ tx.transaction_date }}</div>
                                                </td>
                                                <td class="py-1.5 font-bold" :class="tx.type === 'BUY' ? 'text-emerald-400' : 'text-red-400'">
                                                    {{ tx.type }}
                                                </td>
                                                <td class="py-1.5 text-right font-mono">{{ formatCurrency(tx.price) }}</td>
                                                <td class="py-1.5 text-right font-mono">{{ parseFloat(tx.quantity).toFixed(6) }}</td>
                                                <td class="py-1.5 text-right text-gray-300 font-mono">{{ formatCurrency(tx.price * tx.quantity) }}</td>
                                                <td class="py-1.5 text-right text-red-400 font-mono">
                                                    {{ tx.fee > 0 ? formatCurrency(tx.fee) : '-' }}
                                                </td>
                                                <td class="py-1.5 text-center">
                                                    <button v-if="tx.chart_image" @click="openImageModal(tx.chart_image, tx.type + ' Chart')" class="px-1.5 py-0.5 rounded border border-blue-500/30 text-blue-400 hover:bg-blue-500/10 transition-colors font-bold uppercase tracking-wider text-[8px]">
                                                        VIEW
                                                    </button>
                                                    <span v-else>-</span>
                                                </td>
                                                <td class="py-1.5 text-center">
                                                    <button v-if="tx.notes" @click="viewNote(tx.notes, tx.type + ' Note')" class="text-blue-400 hover:text-blue-300 underline focus:outline-none text-[9px]">
                                                        READ
                                                    </button>
                                                    <span v-else>-</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <button @click="toggleExpand(trade.id)" 
                            class="w-full py-3 text-xs font-black uppercase tracking-widest text-black bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] hover:opacity-90 transition-opacity flex items-center justify-center gap-2 group-hover:from-[#9d6bff] group-hover:to-[#7afbff] mt-auto">
                            {{ expandedCards.has(trade.id) ? 'LESS INFO' : 'MORE INFO' }}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-300" :class="{'rotate-180': expandedCards.has(trade.id)}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                    </div>
                </div>
            </div>

            <div v-if="currentTrades.length === 0" class="col-span-full py-12 text-center border border-dashed border-[#2d2f36] rounded-xl text-gray-500">
                <p class="text-xs font-bold uppercase">No closed trades found for this period.</p>
            </div>
        </div>

        <div v-if="showImageModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-fade-in" @click.self="closeImageModal">
            <div class="bg-[#121317] border border-[#1f2128] rounded-xl w-full max-w-4xl shadow-2xl relative overflow-hidden flex flex-col max-h-[90vh]">
                <div class="flex justify-between items-center p-4 border-b border-[#1f2128] bg-[#1a1b20]">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg> 
                        {{ selectedImageTitle }}
                    </h3>
                    <button @click="closeImageModal" class="text-gray-500 hover:text-white transition-colors p-1 rounded-full hover:bg-[#2d2f36]"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                </div>
                <div class="flex-1 p-4 overflow-auto flex justify-center items-center bg-[#0a0b0d]">
                    <img :src="selectedImageUrl" alt="Chart Screenshot" class="max-w-full max-h-[70vh] object-contain rounded-lg border border-[#2d2f36] shadow-lg" @error="selectedImageUrl = '/images/placeholder-chart.png'">
                </div>
                <div class="p-4 border-t border-[#1f2128] bg-[#1a1b20] flex justify-end gap-3">
                     <button @click="closeImageModal" class="px-4 py-2 rounded-lg text-xs font-bold text-gray-400 bg-transparent hover:bg-[#2d2f36] border border-transparent hover:border-[#2d2f36] transition-all">Close</button>
                    <button @click="downloadImage" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-900/20 transition-all"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg> Download Image</button>
                </div>
            </div>
        </div>

        <div v-if="showNoteModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-fade-in" @click.self="closeNoteModal">
            <div class="bg-[#121317] border border-[#1f2128] rounded-xl w-full max-w-2xl shadow-2xl relative overflow-hidden flex flex-col">
                <div class="flex justify-between items-center p-4 border-b border-[#1f2128] bg-[#1a1b20]">
                    <h3 class="text-lg font-bold text-[#5ce1e6] flex items-center gap-2">
                        <span class="text-xl">📝</span> {{ selectedNoteTitle }}
                    </h3>
                    <button @click="closeNoteModal" class="text-gray-500 hover:text-white transition-colors p-1 rounded-full hover:bg-[#2d2f36]"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                </div>
                <div class="p-6 bg-[#0a0b0d]">
                    <div class="bg-[#1a1b20] p-4 rounded text-gray-300 text-sm leading-relaxed whitespace-pre-wrap border border-[#2d2f36]">
                        {{ selectedNoteContent }}
                    </div>
                </div>
                <div class="p-4 border-t border-[#1f2128] bg-[#1a1b20] flex justify-end">
                    <button @click="closeNoteModal" class="px-4 py-2 rounded-lg text-xs font-bold text-gray-400 bg-[#2d2f36] hover:bg-[#3d404a] border border-transparent transition-all">Close</button>
                </div>
            </div>
        </div>

    </div>
</template>

<style scoped>
@keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in-down { animation: fadeInDown 0.3s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
.animate-fade-in { animation: fadeIn 0.2s ease-out forwards; }
</style>
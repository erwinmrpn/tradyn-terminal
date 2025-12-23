<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const form = useForm({
    currency: 'USD',
    balance: '',
    name: '', // Nama Broker
    market_type: 'Crypto',
    strategy_type: 'Spot',
});

const submit = () => {
    form.post(route('trading-account.store'));
};

const logout = () => {
    // Menggunakan router Inertia untuk logout
    router.post(route('logout'));
};
</script>

<template>
    <Head title="Setup Trading Account" />

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
        
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold mb-2">Welcome to Tradyne</h1>
            <p class="text-gray-500 dark:text-gray-400">Let's set up your trading journal first.</p>
        </div>

        <div class="w-full sm:max-w-lg mt-6 px-8 py-8 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg border border-gray-200 dark:border-gray-700">
            <form @submit.prevent="submit" class="space-y-6">
                
                <div>
                    <InputLabel value="Base Currency" />
                    <div class="mt-1 flex gap-4">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="radio" v-model="form.currency" value="USD" class="text-indigo-600 focus:ring-indigo-500" />
                            <span>USD ($)</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="radio" v-model="form.currency" value="IDR" class="text-indigo-600 focus:ring-indigo-500" />
                            <span>IDR (Rp)</span>
                        </label>
                    </div>
                    <InputError class="mt-2" :message="form.errors.currency" />
                </div>

                <div>
                    <InputLabel for="balance" value="Initial Balance (Modal Awal)" />
                    <TextInput
                        id="balance"
                        type="number"
                        class="mt-1 block w-full"
                        v-model="form.balance"
                        placeholder="e.g. 1000"
                        required
                        autofocus
                    />
                    <InputError class="mt-2" :message="form.errors.balance" />
                </div>

                <div>
                    <InputLabel for="name" value="Exchange / Broker Name" />
                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.name"
                        placeholder="e.g. Binance, Bybit, Pluang, Tokocrypto"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel value="Market Preference" />
                    <select v-model="form.market_type" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="Crypto">Cryptocurrency</option>
                        <option value="Stock">Stock Market (Saham)</option>
                        <option value="Forex">Forex</option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.market_type" />
                </div>

                <div>
                    <InputLabel value="Trading Type" />
                    <div class="grid grid-cols-2 gap-4 mt-1">
                        <div 
                            @click="form.strategy_type = 'Spot'"
                            :class="form.strategy_type === 'Spot' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-300 dark:border-gray-700'"
                            class="cursor-pointer border rounded-md p-4 text-center hover:border-indigo-400 transition"
                        >
                            <span class="font-bold block">SPOT</span>
                            <span class="text-xs text-gray-500">Asset Ownership</span>
                        </div>
                        <div 
                            @click="form.strategy_type = 'Futures'"
                            :class="form.strategy_type === 'Futures' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-300 dark:border-gray-700'"
                            class="cursor-pointer border rounded-md p-4 text-center hover:border-indigo-400 transition"
                        >
                            <span class="font-bold block">FUTURES</span>
                            <span class="text-xs text-gray-500">Contract / Leverage</span>
                        </div>
                    </div>
                    <InputError class="mt-2" :message="form.errors.strategy_type" />
                </div>

                <div class="mt-8 space-y-3">
                    <PrimaryButton class="w-full justify-center py-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Create Journal Space &rarr;
                    </PrimaryButton>
                    
                    <div class="text-center">
                         <button 
                            type="button" 
                            @click="logout" 
                            class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 underline transition"
                        >
                            Not you? Log Out
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</template>
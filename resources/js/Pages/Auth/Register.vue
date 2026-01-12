<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

// State untuk toggle password (terpisah untuk password utama & konfirmasi)
const showPassword = ref(false);
const showConfirmPassword = ref(false);

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <Head title="Register" />

    <div class="min-h-screen flex bg-[#0a0b0d] text-gray-300 font-sans selection:bg-[#8c52ff] selection:text-white">
        
        <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-20 xl:px-24 w-full lg:w-[40%] relative z-20 bg-[#0a0b0d]">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                
                <div class="mb-10">
                    <h2 class="text-5xl font-black text-white tracking-tight leading-tight">
                        Create Account
                    </h2>
                    <p class="mt-3 text-sm text-gray-500 font-medium">
                        Start your journey to professional trading.
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    
                    <div class="space-y-2">
                        <InputLabel for="name" value="Full Name" class="text-gray-500 text-[10px] font-bold uppercase tracking-widest" />
                        <div class="relative">
                            <TextInput
                                id="name"
                                type="text"
                                class="block w-full rounded-xl border border-[#2d2f36] bg-[#121317] px-4 py-4 text-white placeholder-gray-700 focus:border-[#8c52ff] focus:ring-1 focus:ring-[#8c52ff] transition-all text-sm font-medium"
                                v-model="form.name"
                                required
                                autofocus
                                autocomplete="name"
                                placeholder="John Doe"
                            />
                        </div>
                        <InputError class="mt-1" :message="form.errors.name" />
                    </div>

                    <div class="space-y-2">
                        <InputLabel for="email" value="Email" class="text-gray-500 text-[10px] font-bold uppercase tracking-widest" />
                        <div class="relative">
                            <TextInput
                                id="email"
                                type="email"
                                class="block w-full rounded-xl border border-[#2d2f36] bg-[#121317] px-4 py-4 text-white placeholder-gray-700 focus:border-[#8c52ff] focus:ring-1 focus:ring-[#8c52ff] transition-all text-sm font-medium"
                                v-model="form.email"
                                required
                                autocomplete="username"
                                placeholder="name@tradyn.com"
                            />
                        </div>
                        <InputError class="mt-1" :message="form.errors.email" />
                    </div>

                    <div class="space-y-2">
                        <InputLabel for="password" value="Password" class="text-gray-500 text-[10px] font-bold uppercase tracking-widest" />
                        <div class="relative">
                            <TextInput
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                class="block w-full rounded-xl border border-[#2d2f36] bg-[#121317] px-4 py-4 text-white placeholder-gray-700 focus:border-[#8c52ff] focus:ring-1 focus:ring-[#8c52ff] transition-all text-sm font-medium pr-12"
                                v-model="form.password"
                                required
                                autocomplete="new-password"
                                placeholder="Create a strong password"
                            />
                            
                            <button 
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-600 hover:text-white transition-colors focus:outline-none flex items-center justify-center"
                                tabindex="-1"
                            >
                                <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                        <InputError class="mt-1" :message="form.errors.password" />
                    </div>

                    <div class="space-y-2">
                        <InputLabel for="password_confirmation" value="Confirm Password" class="text-gray-500 text-[10px] font-bold uppercase tracking-widest" />
                        <div class="relative">
                            <TextInput
                                id="password_confirmation"
                                :type="showConfirmPassword ? 'text' : 'password'"
                                class="block w-full rounded-xl border border-[#2d2f36] bg-[#121317] px-4 py-4 text-white placeholder-gray-700 focus:border-[#8c52ff] focus:ring-1 focus:ring-[#8c52ff] transition-all text-sm font-medium pr-12"
                                v-model="form.password_confirmation"
                                required
                                autocomplete="new-password"
                                placeholder="Re-enter your password"
                            />

                            <button 
                                type="button"
                                @click="showConfirmPassword = !showConfirmPassword"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-600 hover:text-white transition-colors focus:outline-none flex items-center justify-center"
                                tabindex="-1"
                            >
                                <svg v-if="!showConfirmPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                        <InputError class="mt-1" :message="form.errors.password_confirmation" />
                    </div>

                    <PrimaryButton
                        class="w-full flex justify-center items-center py-4 px-4 border border-transparent rounded-xl shadow-lg shadow-purple-900/20 text-sm font-bold text-white bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] hover:opacity-90 hover:scale-[1.01] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#8c52ff] focus:ring-offset-[#0a0b0d] transition-all uppercase tracking-widest mt-6"
                        :class="{ 'opacity-75 cursor-not-allowed': form.processing }"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing">Creating Account...</span>
                        <span v-else>Sign Up</span>
                    </PrimaryButton>
                </form>

                <div class="mt-8 text-center">
                    <p class="text-sm text-gray-600">
                        Already have an account? 
                        <Link :href="route('login')" class="font-bold text-gray-400 hover:text-white transition-colors">
                            Log in
                        </Link>
                    </p>
                </div>

                <div class="mt-10">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-[#1f2128]"></div>
                        </div>
                        <div class="relative flex justify-center text-xs uppercase tracking-widest">
                            <span class="px-2 bg-[#0a0b0d] text-gray-500 font-bold"> Join us on</span>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-center gap-4">
                        <a href="https://twitter.com" target="_blank" class="w-12 h-12 flex items-center justify-center rounded-xl bg-[#121317] border border-[#2d2f36] text-gray-400 hover:text-white hover:border-[#8c52ff] hover:bg-[#1a1b20] hover:scale-110 transition-all duration-300 group">
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" /></svg>
                        </a>
                        
                        <a href="https://discord.gg/25SSBmqcNE" target="_blank" class="w-12 h-12 flex items-center justify-center rounded-xl bg-[#121317] border border-[#2d2f36] text-gray-400 hover:text-white hover:border-[#5865F2] hover:bg-[#1a1b20] hover:scale-110 transition-all duration-300 group">
                            <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>
                        </a>
                    </div>
                </div>

            </div>
        </div>

        <div class="hidden lg:flex flex-1 items-center justify-center relative bg-[#0a0b0d] overflow-hidden">
            
            <div class="absolute inset-0 flex items-center justify-center z-0 pointer-events-none">
                <div class="w-[300px] h-[500px] bg-gradient-to-tr from-[#8c52ff] to-[#5ce1e6] rounded-full blur-[100px] opacity-20 animate-pulse"></div>
            </div>
            
            <div class="relative z-10 flex flex-col items-center justify-center p-12 text-center max-w-2xl">
                <div class="relative mb-10">
                    <img 
                        src="/images/logo_for_login.png" 
                        alt="Tradyn Illustration" 
                        class="relative w-[300px] h-auto object-contain drop-shadow-2xl"
                    >
                </div>

                <div class="space-y-4">
                    <h2 class="text-4xl font-bold text-white tracking-tight">
                        Your Professional Trading Journal
                    </h2>
                    <p class="text-gray-400 text-lg leading-relaxed font-light px-8">
                        Record, Analyze, Improve
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { User, Lock, Eye, EyeOff, Search } from 'lucide-vue-next';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    login: '',
    senha: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('senha'),
    });
};

const closeApp = () => {
    window.location.href = '/';
};
</script>

<template>
    <Head title="EMUTE - Login" />
    
    <!-- Top Bar -->
    <div class="fixed top-0 left-0 w-full h-[30px] bg-[#006BB3] z-50"></div>

    <div class="min-h-screen bg-[#E9EDF1] flex flex-col items-center justify-center font-sans">
        
        <!-- Main Card -->
        <div class="w-full max-w-[850px] flex bg-white shadow-2xl overflow-hidden rounded-sm">
            
            <!-- Left Side: Login Form -->
            <div class="w-1/2 p-12 flex flex-col items-center justify-center">
                <!-- Logo and TITLE -->
                <div class="flex items-center space-x-2 mb-10">
                    <img src="/images/logo.png" class="h-10 w-auto" alt="Logo" />
                    <h1 class="text-3xl font-bold text-gray-700 tracking-tight">LOGIN</h1>
                </div>

                <div v-if="form.errors.login" class="w-full mb-4 text-center text-red-600 text-xs font-bold">
                    {{ form.errors.login }}
                </div>

                <form @submit.prevent="submit" class="w-full max-w-[300px] space-y-4">
                    <!-- Login Input -->
                    <div class="relative">
                        <label class="text-[10px] text-gray-400 absolute left-2 top-0 pointer-events-none">Login</label>
                        <div class="flex items-center border border-gray-300 rounded px-2 pt-3 pb-1 focus-within:border-blue-400 transition-colors">
                            <input
                                type="text"
                                v-model="form.login"
                                class="w-full bg-transparent border-none p-0 text-[13px] focus:ring-0 text-gray-700 font-medium"
                                placeholder="EMUTE"
                                required
                            />
                            <Search class="w-4 h-4 text-gray-400" />
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div class="relative">
                        <label class="text-[10px] text-gray-400 absolute left-2 top-0 pointer-events-none">Senha</label>
                        <div class="flex items-center border border-gray-300 rounded px-2 pt-3 pb-1 focus-within:border-blue-400 transition-colors">
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                v-model="form.senha"
                                class="w-full bg-transparent border-none p-0 text-[13px] focus:ring-0 text-gray-700 font-medium"
                                required
                            />
                            <button @click="showPassword = !showPassword" type="button" class="text-gray-400 focus:outline-none">
                                <Eye v-if="!showPassword" class="w-4 h-4" />
                                <EyeOff v-else class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex space-x-4 pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 bg-[#0091FF] hover:bg-[#007EE6] text-white py-2 rounded-md text-sm font-medium transition-colors"
                        >
                            Entrar
                        </button>
                        <button
                            type="button"
                            @click="closeApp"
                            class="flex-1 bg-[#BE0000] hover:bg-[#A60000] text-white py-2 rounded-md text-sm font-medium transition-colors"
                        >
                            Encerrar
                        </button>
                    </div>

                    <!-- Bottom Links -->
                    <div class="flex flex-col items-center pt-6 space-y-2">
                        <Link href="#" class="text-[13px] font-bold text-blue-600 hover:underline">Esqueceu a Palavra Passe?</Link>
                        <Link href="#" class="text-[13px] font-bold text-blue-600 hover:underline">Configurar Servidor</Link>
                        <div class="pt-4">
                            <span class="text-xs font-bold text-gray-800">Versão 1.0.0</span>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Right Side: Brand Banner -->
            <div class="w-1/2 bg-gradient-to-br from-[#12F2FF] via-[#247BFF] to-[#2D5BFF] flex flex-col items-center justify-center p-10">
                <img src="/images/logo_full.png" class="w-full max-w-[280px] h-auto" alt="Emute Software" />
            </div>
        </div>

        <!-- External Footer Controls (Overlaying at bottom) -->
        <div class="fixed bottom-4 left-4 flex flex-col items-start">
            <span class="text-[11px] font-bold text-red-600 mb-1">Licença válida por 6 dias</span>
            <button class="bg-[#0091FF] hover:bg-[#007EE6] text-white text-[11px] font-bold px-4 py-1.5 rounded-sm shadow-md transition-all">
                Solicitar Licença
            </button>
        </div>

        <div class="fixed bottom-2 left-1/2 -translate-x-1/2">
            <p class="text-[11px] font-bold text-gray-700">
                © 2025 MUTECODE. Todos os direitos reservados.
            </p>
        </div>
    </div>
</template>

<style scoped>
/* No specific styles needed, using Tailwind for accuracy */
</style>



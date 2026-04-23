<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { User, Lock, Eye, EyeOff, X } from 'lucide-vue-next';

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
    <div class="min-h-screen bg-[#F0F4F8] flex flex-col items-center justify-center p-4 font-sans selection:bg-blue-100">
        <Head title="Login - EMUTE" />

        <!-- Container Principal Slim -->
        <div class="w-full max-w-[800px] flex bg-white rounded-[40px] shadow-[0_40px_100px_-20px_rgba(0,0,0,0.12)] overflow-hidden animate-fadeScale border border-white">
            
            <!-- Painel Esquerdo: Autenticação -->
            <div class="w-full md:w-[45%] p-12 flex flex-col justify-center">
                
                <!-- Logo e Título -->
                <div class="flex flex-col items-center mb-10">
                    <img src="/images/logo.png" class="h-10 w-auto mb-3" alt="Logo" />
                    <h1 class="text-2xl font-black text-gray-800 tracking-tighter uppercase italic">Login</h1>
                    <div class="h-1 w-6 bg-blue-500 rounded-full mt-1"></div>
                </div>

                <div v-if="form.errors.login" class="mb-6 p-3 bg-red-50 border-l-2 border-red-500 text-red-600 text-[10px] font-black uppercase tracking-widest rounded-r animate-shake">
                    {{ form.errors.login }}
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-4">
                        <!-- Input Login -->
                        <div class="relative group">
                            <label class="absolute -top-2 left-3 bg-white px-1 text-[9px] font-black text-gray-400 uppercase tracking-widest transition-all group-focus-within:text-blue-500">Utilizador</label>
                            <div class="flex items-center border border-gray-200 rounded-2xl px-4 py-3 group-focus-within:border-blue-500 group-focus-within:ring-4 group-focus-within:ring-blue-50 transition-all">
                                <User class="w-4 h-4 text-gray-300 mr-3 group-focus-within:text-blue-500" />
                                <input
                                    type="text"
                                    v-model="form.login"
                                    class="w-full bg-transparent border-none p-0 text-sm focus:ring-0 placeholder:text-gray-200 font-medium text-gray-700"
                                    placeholder="Nome de utilizador"
                                    required
                                />
                            </div>
                        </div>

                        <!-- Input Senha -->
                        <div class="relative group">
                            <label class="absolute -top-2 left-3 bg-white px-1 text-[9px] font-black text-gray-400 uppercase tracking-widest transition-all group-focus-within:text-blue-500">Senha</label>
                            <div class="flex items-center border border-gray-200 rounded-2xl px-4 py-3 group-focus-within:border-blue-500 group-focus-within:ring-4 group-focus-within:ring-blue-50 transition-all">
                                <Lock class="w-4 h-4 text-gray-300 mr-3 group-focus-within:text-blue-500" />
                                <input
                                    :type="showPassword ? 'text' : 'password'"
                                    v-model="form.senha"
                                    class="w-full bg-transparent border-none p-0 text-sm focus:ring-0 placeholder:text-gray-200 font-medium text-gray-700"
                                    placeholder="••••••••"
                                    required
                                />
                                <button @click="showPassword = !showPassword" type="button" class="text-gray-300 hover:text-blue-500 transition-colors">
                                    <Eye v-if="!showPassword" class="w-4 h-4" />
                                    <EyeOff v-else class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Botões -->
                    <div class="flex space-x-3 pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-500/20 active:scale-95 transition-all"
                        >
                            Entrar
                        </button>
                        <button
                            type="button"
                            @click="closeApp"
                            class="flex-1 bg-red-600 hover:bg-red-700 text-white py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-red-500/20 active:scale-95 transition-all"
                        >
                            Encerrar
                        </button>
                    </div>

                    <div class="flex flex-col items-center space-y-3 pt-4">
                        <Link href="#" class="text-[9px] font-black text-blue-500 hover:text-blue-700 uppercase tracking-tighter">Esqueceu a palavra passe?</Link>
                        <Link href="#" class="text-[9px] font-bold text-gray-300 hover:text-gray-400">Configurar Servidor</Link>
                        <div class="pt-2">
                            <span class="text-[8px] font-black bg-gray-50 text-gray-400 px-3 py-1 rounded-full uppercase tracking-tighter">Versão 1.0.0</span>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Painel Direito: Design Elegante da Marca -->
            <div class="hidden md:flex flex-grow bg-gradient-to-br from-[#00A3FF] to-[#0066FF] items-center justify-center relative overflow-hidden">
                <!-- Elementos Abstratos de Fundo -->
                <div class="absolute -top-20 -right-20 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-20 -left-20 w-80 h-80 bg-blue-900/20 rounded-full blur-3xl"></div>
                
                <div class="relative z-10 flex flex-col items-center transform scale-90 lg:scale-100">
                    <div class="p-8 bg-white/10 backdrop-blur-md rounded-[50px] border border-white/20 shadow-2xl transition-transform hover:-rotate-1 duration-700">
                        <img src="/images/logo_full.png" class="h-44 w-auto drop-shadow-[0_20px_40px_rgba(0,0,0,0.3)]" alt="Emute Logo" />
                    </div>
                    <div class="mt-8 text-center text-white/40">
                         <p class="text-[8px] font-black uppercase tracking-[1em]">Controle Operacional Eficiente</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rodapé Minimalista -->
        <div class="mt-12 flex flex-col md:flex-row items-center justify-between w-full max-w-[800px] text-gray-400 px-4">
            <div class="flex items-center space-x-2 bg-white px-5 py-2 rounded-2xl shadow-sm border border-white">
                <span class="w-1.5 h-1.5 bg-red-500 rounded-full animate-pulse"></span>
                <span class="text-[9px] font-black uppercase tracking-widest">Licença válida: 6 Dias</span>
                <button class="ml-4 text-[9px] font-black text-blue-600 hover:underline">Solicitar Licença</button>
            </div>
            <div class="mt-4 md:mt-0 text-[8px] font-bold uppercase tracking-widest opacity-60">
                © 2025 MUTECODE. Todos os direitos reservados.
            </div>
        </div>
    </div>
</template>

<style>
@keyframes fadeScale {
    from { opacity: 0; transform: scale(0.98) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.animate-fadeScale {
    animation: fadeScale 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-4px); }
    75% { transform: translateX(4px); }
}
.animate-shake {
    animation: shake 0.3s ease-in-out;
}
</style>

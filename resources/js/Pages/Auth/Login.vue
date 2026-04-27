<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import { User, Lock, Eye, EyeOff, Search, CheckCircle, X, Server, Database, Settings, Loader2, Save } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();
const flashSuccess = ref(page.props.flash?.success || null);

// Show Toast
const showToast = ref(!!flashSuccess.value);
const toastMessage = ref(flashSuccess.value);
const toastType = ref('success');

const triggerToast = (message, type = 'success') => {
    toastMessage.value = message;
    toastType.value = type;
    showToast.value = true;
    setTimeout(() => { showToast.value = false }, 5000);
};

watch(() => page.props.flash?.success, (newVal) => {
    if (newVal) {
        triggerToast(newVal, 'success');
    }
});

const form = useForm({
    login: '',
    senha: '',
    remember: false,
    db_host: '',
    db_port: '',
    db_database: '',
    db_username: '',
    db_password: '',
});

onMounted(() => {
    if (showToast.value) {
        setTimeout(() => { showToast.value = false }, 5000);
    }
    
    // Load saved settings from localStorage
    const savedSettings = localStorage.getItem('emute_server_settings');
    if (savedSettings) {
        try {
            const settings = JSON.parse(savedSettings);
            form.db_host = settings.db_host || '';
            form.db_port = settings.db_port || '';
            form.db_database = settings.db_database || '';
            form.db_username = settings.db_username || '';
            form.db_password = settings.db_password || '';
            // If settings were found, show the panel by default
            showServerSettings.value = true;
        } catch (e) {
            console.error('Falha ao carregar definições:', e);
        }
    }
})

const showPassword = ref(false);
const showServerSettings = ref(false);
const testingConnection = ref(false);

const saveSettings = () => {
    const settings = {
        db_host: form.db_host,
        db_port: form.db_port,
        db_database: form.db_database,
        db_username: form.db_username,
        db_password: form.db_password,
    };
    localStorage.setItem('emute_server_settings', JSON.stringify(settings));
    triggerToast('Configurações gravadas localmente!', 'success');
};

const testConnection = async () => {
    if (!form.db_host || !form.db_database || !form.db_username) {
        triggerToast('Por favor, preencha os dados do servidor.', 'error');
        return;
    }

    testingConnection.value = true;
    try {
        const response = await axios.post(route('db.test'), {
            db_host: form.db_host,
            db_port: form.db_port,
            db_database: form.db_database,
            db_username: form.db_username,
            db_password: form.db_password,
        });

        triggerToast(response.data.message, 'success');
    } catch (error) {
        const message = error.response?.data?.message || 'Erro ao testar conexão.';
        triggerToast(message, 'error');
    } finally {
        testingConnection.value = false;
    }
};

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('senha'),
    });
};

</script>

<template>
    <Head title="EMUTE - Login" />
    
    <!-- Top Bar Elegance -->
    <div class="fixed top-0 left-0 w-full h-1 bg-gradient-to-r from-[#12F2FF] via-[#247BFF] to-[#006BB3] z-50"></div>

    <!-- Elegant Toast Notification -->
    <transition enter-active-class="transform transition ease-out duration-300" enter-from-class="translate-y-[-100%] opacity-0" enter-to-class="translate-y-0 opacity-100" leave-active-class="transform transition ease-in duration-200" leave-from-class="translate-y-0 opacity-100" leave-to-class="translate-y-[-100%] opacity-0">
        <div v-if="showToast" class="fixed top-6 right-6 z-50 flex items-center bg-white shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-gray-100 p-4 rounded-2xl min-w-[300px]">
            <div :class="[
                'flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center mr-3',
                toastType === 'success' ? 'bg-green-50' : 'bg-red-50'
            ]">
                <CheckCircle v-if="toastType === 'success'" class="w-5 h-5 text-green-500" />
                <X v-else class="w-5 h-5 text-red-500" />
            </div>
            <div class="flex-grow">
                <p class="text-sm font-bold text-gray-800">{{ toastType === 'success' ? 'Sucesso' : 'Erro' }}</p>
                <p class="text-xs text-gray-500 mt-0.5">{{ toastMessage }}</p>
            </div>
            <button @click="showToast = false" class="text-gray-400 hover:text-gray-600 transition-colors ml-3"><X class="w-4 h-4"/></button>
        </div>
    </transition>

    <div class="min-h-screen relative flex flex-col items-center justify-center font-sans overflow-hidden bg-[#F8FAFC]">
        
        <!-- Abstract Background Orbs -->
        <div class="absolute top-[-10%] right-[-5%] w-[500px] h-[500px] bg-blue-400/20 rounded-full blur-[100px] mix-blend-multiply pointer-events-none"></div>
        <div class="absolute bottom-[-10%] left-[-5%] w-[600px] h-[600px] bg-[#006BB3]/10 rounded-full blur-[120px] mix-blend-multiply pointer-events-none"></div>

        <!-- Main Card -->
        <div class="w-full max-w-[900px] flex bg-white/80 backdrop-blur-3xl shadow-[0_20px_60px_rgba(0,107,179,0.08)] border border-white overflow-hidden rounded-[2rem] z-10 transition-all duration-500 hover:shadow-[0_20px_60px_rgba(0,107,179,0.12)] relative">
            
            <!-- Left Side: Login Form -->
            <div class="w-1/2 p-14 flex flex-col items-center justify-center relative bg-white/50">
                <!-- Logo and TITLE -->
                <div class="flex flex-col items-center mb-10 w-full relative">
                    <div class="bg-white p-3 rounded-2xl shadow-sm mb-4 border border-gray-50">
                        <img src="/images/logo.png" class="h-10 w-auto" alt="Logo" />
                    </div>
                    <h1 class="text-[26px] font-extrabold text-gray-800 tracking-tight">Bem-vindo de volta</h1>
                    <p class="text-xs text-gray-500 mt-2 font-medium">Acesso seguro ao EMUTE Software</p>
                </div>

                <div v-if="form.errors.login || status" class="w-full mb-6 p-4 rounded-xl bg-red-50 border border-red-100 flex items-start space-x-3">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <div>
                        <h3 class="text-xs font-bold text-red-800">Falha na Autenticação</h3>
                        <p class="text-[11px] text-red-600 mt-0.5">{{ form.errors.login || status }}</p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="w-full max-w-[320px] space-y-5">
                    <!-- Login Input -->
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider pl-1">Utilizador</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <User class="h-4 w-4 text-gray-400 group-focus-within:text-blue-500 transition-colors" />
                            </div>
                            <input
                                type="text"
                                v-model="form.login"
                                class="w-full pl-10 pr-4 py-3.5 bg-gray-50/50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 focus:bg-white transition-all font-medium placeholder-gray-400"
                                placeholder="Insira o seu login"
                                required
                            />
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div class="space-y-1.5">
                        <div class="flex justify-between items-center pl-1">
                            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Palavra Palavra-passe</label>
                            <Link href="#" class="text-[10px] font-bold text-blue-600 hover:text-blue-700 transition-colors">Esqueceu?</Link>
                        </div>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <Lock class="h-4 w-4 text-gray-400 group-focus-within:text-blue-500 transition-colors" />
                            </div>
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                v-model="form.senha"
                                class="w-full pl-10 pr-12 py-3.5 bg-gray-50/50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 focus:bg-white transition-all font-medium placeholder-gray-400"
                                placeholder="••••••••"
                                required
                            />
                            <button @click="showPassword = !showPassword" type="button" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none transition-colors">
                                <Eye v-if="!showPassword" class="w-4 h-4" />
                                <EyeOff v-else class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Server Settings Toggle -->
                    <div class="pt-2">
                        <button 
                            type="button" 
                            @click="showServerSettings = !showServerSettings"
                            class="flex items-center text-[11px] font-bold text-gray-400 hover:text-blue-500 transition-colors uppercase tracking-wider space-x-2"
                        >
                            <Settings class="w-3.5 h-3.5" :class="{'rotate-90': showServerSettings}" />
                            <span>{{ showServerSettings ? 'Ocultar Servidor' : 'Configurar Servidor' }}</span>
                        </button>
                    </div>

                    <!-- Server Settings Fields -->
                    <transition 
                        enter-active-class="transition duration-300 ease-out"
                        enter-from-class="transform scale-95 opacity-0"
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-200 ease-in"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0"
                    >
                        <div v-if="showServerSettings" class="space-y-4 p-4 rounded-xl bg-gray-50/80 border border-gray-100">
                            <!-- Host & Port -->
                            <div class="grid grid-cols-3 gap-3">
                                <div class="col-span-2 space-y-1.5">
                                    <label class="text-[10px] font-bold text-gray-500 uppercase">Servidor</label>
                                    <div class="relative group">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <Server class="h-3.5 w-3.5 text-gray-400" />
                                        </div>
                                        <input v-model="form.db_host" type="text" placeholder="127.0.0.1" class="w-full pl-8 pr-3 py-2 bg-white border border-gray-200 text-xs rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" />
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-bold text-gray-500 uppercase">Porta</label>
                                    <input v-model="form.db_port" type="text" placeholder="3306" class="w-full px-3 py-2 bg-white border border-gray-200 text-xs rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" />
                                </div>
                            </div>

                            <!-- Database Name -->
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-gray-500 uppercase">Banco de Dados</label>
                                <div class="relative group">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <Database class="h-3.5 w-3.5 text-gray-400" />
                                    </div>
                                    <input v-model="form.db_database" type="text" placeholder="emute_db" class="w-full pl-8 pr-3 py-2 bg-white border border-gray-200 text-xs rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" />
                                </div>
                            </div>

                            <!-- DB Credentials -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-bold text-gray-500 uppercase">Utilizador DB</label>
                                    <input v-model="form.db_username" type="text" placeholder="root" class="w-full px-3 py-2 bg-white border border-gray-200 text-xs rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" />
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-bold text-gray-500 uppercase">Senha DB</label>
                                    <input v-model="form.db_password" type="password" placeholder="••••" class="w-full px-3 py-2 bg-white border border-gray-200 text-xs rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <button
                                    type="button"
                                    @click="testConnection"
                                    :disabled="testingConnection"
                                    class="w-full flex items-center justify-center space-x-2 py-2.5 bg-blue-50 text-blue-600 rounded-xl text-[10px] font-bold hover:bg-blue-100 transition-all border border-blue-100 disabled:opacity-50"
                                >
                                    <Loader2 v-if="testingConnection" class="w-3 h-3 animate-spin" />
                                    <span>{{ testingConnection ? 'A Testar...' : 'Testar Conexão' }}</span>
                                </button>

                                <button
                                    type="button"
                                    @click="saveSettings"
                                    class="w-full flex items-center justify-center space-x-2 py-2.5 bg-emerald-50 text-emerald-600 rounded-xl text-[10px] font-bold hover:bg-emerald-100 transition-all border border-emerald-100"
                                >
                                    <Save class="w-3 h-3" />
                                    <span>Gravar</span>
                                </button>
                            </div>
                        </div>
                    </transition>

                    <!-- Buttons -->
                    <div class="pt-4">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full bg-gradient-to-r from-[#006BB3] to-[#0091FF] hover:from-[#005a96] hover:to-[#007EE6] text-white py-3 rounded-xl text-sm font-bold shadow-lg shadow-blue-500/25 transition-all hover:-translate-y-0.5 focus:ring-4 focus:ring-blue-500/20 active:translate-y-0 disabled:opacity-70 disabled:hover:translate-y-0 disabled:active:scale-100 active:scale-95 flex items-center justify-center"
                        >
                            Entrar no Sistema
                        </button>
                    </div>

                    <!-- Bottom Links -->
                    <div class="flex flex-col items-center pt-8 space-y-3">
                        <Link :href="route('empresa.register')" class="group flex items-center text-[12px] font-bold text-gray-500 hover:text-blue-600 transition-colors">
                            <span class="bg-gray-100 group-hover:bg-blue-100 p-1.5 rounded-lg mr-2 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </span>
                            Criar Organização / Configurar
                        </Link>
                    </div>
                </form>
            </div>

            <!-- Right Side: Brand Banner -->
            <div class="w-1/2 bg-gradient-to-br from-[#006BB3] via-[#004d80] to-[#002b4d] relative overflow-hidden flex flex-col justify-center items-center p-12">
                <!-- Inner Graphic Elements -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-[#12F2FF]/20 rounded-full blur-3xl translate-y-1/3 -translate-x-1/3"></div>
                
                <!-- Floating Glass Card effect -->
                <div class="relative z-10 w-full max-w-[320px] bg-white/10 backdrop-blur-md rounded-3xl border border-white/20 p-10 flex flex-col items-center transform transition-transform duration-700 hover:scale-105 hover:rotate-1 shadow-2xl">
                    <img src="/images/logo_official.png" class="w-full h-auto drop-shadow-lg" alt="Emute Software" />
                </div>
                
                <div class="relative z-10 mt-14 text-center">
                    <span class="inline-block px-3 py-1 bg-white/10 border border-white/20 rounded-full text-[10px] font-bold text-white/90 tracking-widest uppercase backdrop-blur-sm mb-4">
                        Solução Hospitalar Premium
                    </span>
                    <h2 class="text-white text-xl font-medium tracking-wide">Inove o seu processo.</h2>
                </div>
            </div>
        </div>

        <!-- External Footer Controls -->
        <div class="absolute bottom-6 left-8 flex flex-col items-start z-10">
            <div class="flex items-center space-x-2 mb-2">
                <span class="relative flex h-2.5 w-2.5">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
                </span>
                <span class="text-[11px] font-bold text-gray-500 uppercase tracking-widest">Licença: 6 Dias</span>
            </div>
            <button class="text-xs font-bold text-[#006BB3] hover:text-blue-800 transition-colors uppercase tracking-wider relative group">
                Renovar Licença Agora
                <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-[#006BB3] transition-all group-hover:w-full"></span>
            </button>
        </div>

        <div class="absolute bottom-6 w-full text-center z-10">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest">
                © 2026 MUTECODE. Versão 1.0.0.
            </p>
        </div>
    </div>
</template>

<style scoped>
/* Scoped overrides if necessary */
</style>

<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import { User, Lock, Eye, EyeOff, CheckCircle, X, Server, Database, Settings, Loader2, Save, Key, Building, Stethoscope, Activity, HelpCircle } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
    licencaValida: { type: Boolean, default: false },
    dataInicio: { type: String, default: null },
    dataFim: { type: String, default: null },
    plano: { type: String, default: null },
});

const page = usePage();
const flashSuccess = ref(page.props.flash?.success || null);
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
    if (newVal) triggerToast(newVal, 'success');
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
    if (showToast.value) setTimeout(() => { showToast.value = false }, 5000);
    const savedSettings = localStorage.getItem('emute_server_settings');
    if (savedSettings) {
        try {
            const settings = JSON.parse(savedSettings);
            form.db_host = settings.db_host || '';
            form.db_port = settings.db_port || '';
            form.db_database = settings.db_database || '';
            form.db_username = settings.db_username || '';
            form.db_password = settings.db_password || '';
            showServerSettings.value = true;
        } catch (e) {}
    }
});

const showPassword = ref(false);
const showServerSettings = ref(false);
const testingConnection = ref(false);

const saveSettings = async () => {
    if (!form.db_host || !form.db_database || !form.db_username) {
        triggerToast('Preencha os dados do servidor.', 'error');
        return;
    }
    try {
        const settings = { db_host: form.db_host, db_port: form.db_port, db_database: form.db_database, db_username: form.db_username, db_password: form.db_password };
        await axios.post(route('db.save'), settings);
        localStorage.setItem('emute_server_settings', JSON.stringify(settings));
        triggerToast('Configurações sincronizadas!', 'success');
    } catch (error) {
        triggerToast('Erro ao gravar configurações.', 'error');
    }
};

const testConnection = async () => {
    if (!form.db_host || !form.db_database || !form.db_username) {
        triggerToast('Preencha os dados do servidor.', 'error');
        return;
    }
    testingConnection.value = true;
    try {
        const response = await axios.post(route('db.test'), { db_host: form.db_host, db_port: form.db_port, db_database: form.db_database, db_username: form.db_username, db_password: form.db_password });
        triggerToast(response.data.message, 'success');
    } catch (error) {
        triggerToast(error.response?.data?.message || 'Erro ao testar conexão.', 'error');
    } finally {
        testingConnection.value = false;
    }
};

const submit = () => {
    form.post(route('login'), { onFinish: () => form.reset('senha') });
};
</script>

<template>
    <Head title="EMUTE - Login" />

    <!-- Toast -->
    <transition enter-active-class="transform transition ease-out duration-300" enter-from-class="translate-y-[-100%] opacity-0" enter-to-class="translate-y-0 opacity-100" leave-active-class="transform transition ease-in duration-200" leave-from-class="translate-y-0 opacity-100" leave-to-class="translate-y-[-100%] opacity-0">
        <div v-if="showToast" class="fixed top-5 right-5 z-50 flex items-center bg-white/95 backdrop-blur-xl shadow-xl border border-gray-100/80 px-5 py-3.5 rounded-2xl min-w-[280px]">
            <div :class="['w-8 h-8 rounded-full flex items-center justify-center mr-3', toastType === 'success' ? 'bg-emerald-50' : 'bg-red-50']">
                <CheckCircle v-if="toastType === 'success'" class="w-4 h-4 text-emerald-500" />
                <X v-else class="w-4 h-4 text-red-500" />
            </div>
            <div class="flex-grow">
                <p class="text-[13px] font-semibold text-gray-800">{{ toastMessage }}</p>
            </div>
            <button @click="showToast = false" class="text-gray-300 hover:text-gray-500 ml-3"><X class="w-3.5 h-3.5" /></button>
        </div>
    </transition>

    <div class="min-h-screen flex font-['Inter',sans-serif] bg-[#f5f7fb]">

        <!-- LEFT: Form Side -->
        <div class="w-full lg:w-[480px] xl:w-[520px] flex flex-col justify-between p-8 sm:p-12 lg:p-14 bg-white relative z-10 shadow-[20px_0_50px_rgba(0,0,0,0.02)]">

            <!-- Form Area -->
            <div class="flex-grow flex flex-col justify-center max-w-[360px] mx-auto w-full py-8">
                
                <!-- Centered Logo and Header -->
                <div class="text-center mb-8 flex flex-col items-center">
                    <div class="inline-flex items-center justify-center p-3 bg-slate-50 border border-slate-100 shadow-sm mb-5 transition-transform duration-300 hover:scale-105">
                        <img src="/images/logo.png" class="h-16 w-auto object-contain" alt="Logo" />
                    </div>
                    <h1 class="text-[26px] font-extrabold text-gray-900 tracking-tight leading-tight">Bem-vindo de volta</h1>
                    <p class="text-[12px] text-gray-400 mt-2 font-medium leading-relaxed">Acesso seguro ao EMUTE Software</p>
                </div>

                <!-- License Expired Alert -->
                <div v-if="!props.licencaValida" class="mb-6 p-4 rounded-2xl bg-rose-50/80 border border-rose-100 flex items-start space-x-3 shadow-sm shadow-rose-100/5">
                    <div class="w-8 h-8 rounded-full bg-rose-100 flex items-center justify-center flex-shrink-0 mt-0.5 animate-pulse">
                        <X class="w-4 h-4 text-rose-500 font-extrabold" />
                    </div>
                    <div>
                        <p class="text-[12px] font-bold text-rose-800">Sistema Bloqueado!</p>
                        <p class="text-[11px] text-rose-600 mt-0.5 leading-relaxed font-medium">A sua licença expirou ou não está ativa. Por favor, ative uma licença válida para continuar.</p>
                    </div>
                </div>

                <!-- Error Alert -->
                <div v-if="form.errors.login || status" class="mb-5 px-4 py-3 rounded-xl bg-red-50/80 border border-red-100 flex items-center space-x-2.5">
                    <div class="w-6 h-6 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                        <X class="w-3 h-3 text-red-500" />
                    </div>
                    <p class="text-[12px] text-red-600 font-medium">{{ form.errors.login || status }}</p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Utilizador -->
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest pl-0.5">Utilizador</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <User class="h-4 w-4 text-gray-300 group-focus-within:text-emerald-500 transition-colors" />
                            </div>
                            <input v-model="form.login" type="text" placeholder="O seu utilizador" required :disabled="!props.licencaValida"
                                class="w-full pl-10 pr-4 py-3 bg-[#f8f9fc] border border-gray-200/50 text-gray-800 text-[13px] rounded-xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 focus:bg-white transition-all font-medium placeholder-gray-300 disabled:opacity-50 disabled:bg-gray-50/50 disabled:cursor-not-allowed" />
                        </div>
                    </div>

                    <!-- Palavra-passe -->
                    <div class="space-y-1.5">
                        <div class="flex justify-between items-center pl-0.5">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Palavra-passe</label>
                            <span v-if="!props.licencaValida" class="text-[10px] font-semibold text-gray-300">Esqueceu?</span>
                            <a v-else href="#" class="text-[10px] font-semibold text-blue-500 hover:text-blue-600 transition-colors">Esqueceu?</a>
                        </div>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <Lock class="h-4 w-4 text-gray-300 group-focus-within:text-emerald-500 transition-colors" />
                            </div>
                            <input :type="showPassword ? 'text' : 'password'" v-model="form.senha" placeholder="••••••••" required :disabled="!props.licencaValida"
                                class="w-full pl-10 pr-11 py-3 bg-[#f8f9fc] border border-gray-200/50 text-gray-800 text-[13px] rounded-xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 focus:bg-white transition-all font-medium placeholder-gray-300 disabled:opacity-50 disabled:bg-gray-50/50 disabled:cursor-not-allowed" />
                            <button @click="showPassword = !showPassword" type="button" :disabled="!props.licencaValida" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500 transition-colors disabled:opacity-30">
                                <Eye v-if="!showPassword" class="w-4 h-4" />
                                <EyeOff v-else class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Server Config Toggle -->
                    <button type="button" @click="showServerSettings = !showServerSettings" :disabled="!props.licencaValida"
                        class="flex items-center text-[9px] font-bold text-gray-400 hover:text-emerald-500 transition-colors uppercase tracking-wider space-x-1.5 disabled:opacity-50 disabled:hover:text-gray-300 pl-0.5">
                        <Settings class="w-3.5 h-3.5 transition-transform duration-300" :class="{ 'rotate-90 text-emerald-500': showServerSettings }" />
                        <span>{{ showServerSettings ? 'Ocultar Servidor' : 'Configurar Servidor' }}</span>
                    </button>

                    <!-- Server Settings Card -->
                    <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0 -translate-y-2">
                        <div v-if="showServerSettings" class="space-y-3 p-4 rounded-xl bg-[#f8f9fc]/80 border border-gray-100 shadow-inner">
                            <div class="grid grid-cols-3 gap-2">
                                <div class="col-span-2">
                                    <label class="text-[9px] font-bold text-gray-400 uppercase mb-1 block">Servidor</label>
                                    <div class="relative">
                                        <Server class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-300" />
                                        <input v-model="form.db_host" placeholder="127.0.0.1" class="w-full pl-8 pr-2 py-2 bg-white border border-gray-200 text-[11px] rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium" />
                                    </div>
                                </div>
                                <div>
                                    <label class="text-[9px] font-bold text-gray-400 uppercase mb-1 block">Porta</label>
                                    <input v-model="form.db_port" placeholder="3306" class="w-full px-2.5 py-2 bg-white border border-gray-200 text-[11px] rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium text-center" />
                                </div>
                            </div>
                            <div>
                                <label class="text-[9px] font-bold text-gray-400 uppercase mb-1 block">Base de Dados</label>
                                <div class="relative">
                                    <Database class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-300" />
                                    <input v-model="form.db_database" placeholder="emute_db" class="w-full pl-8 pr-2 py-2 bg-white border border-gray-200 text-[11px] rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[9px] font-bold text-gray-400 uppercase mb-1 block">Utilizador</label>
                                    <input v-model="form.db_username" placeholder="root" class="w-full px-2.5 py-2 bg-white border border-gray-200 text-[11px] rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium" />
                                </div>
                                <div>
                                    <label class="text-[9px] font-bold text-gray-400 uppercase mb-1 block">Senha</label>
                                    <input v-model="form.db_password" type="password" placeholder="••••" class="w-full px-2.5 py-2 bg-white border border-gray-200 text-[11px] rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 pt-1.5">
                                <button type="button" @click="testConnection" :disabled="testingConnection"
                                    class="py-2 bg-blue-50/50 text-blue-600 rounded-lg text-[10px] font-extrabold hover:bg-blue-100 transition-all border border-blue-100 disabled:opacity-50 flex items-center justify-center space-x-1">
                                    <Loader2 v-if="testingConnection" class="w-3.5 h-3.5 animate-spin" />
                                    <span>{{ testingConnection ? 'A Testar...' : 'Testar Conexão' }}</span>
                                </button>
                                <button type="button" @click="saveSettings"
                                    class="py-2 bg-emerald-50/50 text-emerald-600 rounded-lg text-[10px] font-extrabold hover:bg-emerald-100 transition-all border border-emerald-100 flex items-center justify-center space-x-1">
                                    <Save class="w-3.5 h-3.5" />
                                    <span>Sincronizar</span>
                                </button>
                            </div>
                        </div>
                    </transition>

                    <!-- Login Button (Azul/Verde Médico em Gradiente) -->
                    <button type="submit" :disabled="form.processing || !props.licencaValida"
                        class="w-full bg-gradient-to-r from-emerald-500 via-teal-500 to-blue-500 hover:from-emerald-600 hover:via-teal-600 hover:to-blue-600 text-white py-3.5 rounded-xl text-[13px] font-bold shadow-lg shadow-emerald-500/10 transition-all hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] disabled:opacity-50 disabled:from-gray-400 disabled:to-gray-500 disabled:shadow-none disabled:hover:translate-y-0 disabled:cursor-not-allowed flex items-center justify-center space-x-2">
                        <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                        <Activity v-else class="w-4 h-4" />
                        <span>{{ props.licencaValida ? 'Entrar no Sistema' : 'Acesso Bloqueado' }}</span>
                    </button>
                </form>

                <!-- Links Secundários -->
                <div class="flex items-center justify-center space-x-4 mt-6 pt-5 border-t border-gray-100">
                    <Link :href="route('licenca.index')" class="flex items-center text-[10px] font-bold text-gray-400 hover:text-emerald-500 transition-colors space-x-1 group uppercase tracking-wider">
                        <Key class="w-3.5 h-3.5 text-gray-300 group-hover:text-emerald-500 transition-colors" />
                        <span>Ativar Licença</span>
                    </Link>
                    <span class="w-px h-3 bg-gray-200"></span>
                    <Link :href="route('empresa.register')" class="flex items-center text-[10px] font-bold text-gray-400 hover:text-emerald-500 transition-colors space-x-1 group uppercase tracking-wider">
                        <Building class="w-3.5 h-3.5 text-gray-300 group-hover:text-emerald-500 transition-colors" />
                        <span>Config. Empresa</span>
                    </Link>
                    <span class="w-px h-3 bg-gray-200"></span>
                    <a href="https://wa.me/244923101044?text=Olá,%20gostaria%20de%20solicitar%20suporte%20para%20o%20EMUTE%20Software." target="_blank" class="flex items-center text-[10px] font-bold text-gray-400 hover:text-emerald-500 transition-colors space-x-1 group uppercase tracking-wider">
                        <HelpCircle class="w-3.5 h-3.5 text-gray-300 group-hover:text-emerald-500 transition-colors" />
                        <span>Suporte Técnico</span>
                    </a>
                </div>
            </div>

            <!-- Bottom: License + Copyright -->
            <div class="flex items-center justify-between mt-6 pt-5 border-t border-gray-100">
                <div class="flex items-center space-x-2">
                    <span class="relative flex h-2 w-2">
                        <span :class="['absolute inline-flex h-full w-full rounded-full opacity-75', props.licencaValida ? 'animate-ping bg-emerald-400' : 'bg-rose-400']"></span>
                        <span :class="['relative inline-flex rounded-full h-2 w-2', props.licencaValida ? 'bg-emerald-500' : 'bg-rose-500']"></span>
                    </span>
                    <span v-if="props.licencaValida" class="text-[10px] font-bold text-gray-500 uppercase tracking-wider leading-none">
                        Licença {{ props.plano }}<br>
                        <span class="text-[9px] font-semibold text-gray-400 normal-case">{{ props.dataInicio }} até {{ props.dataFim }}</span>
                    </span>
                    <span v-else class="text-[10px] font-extrabold text-rose-500 uppercase tracking-wider">
                        Licença Expirada / Inativa
                    </span>
                </div>
                <Link :href="route('licenca.index')" class="text-[9px] font-extrabold text-emerald-600 hover:text-emerald-700 bg-emerald-50 border border-emerald-100 hover:bg-emerald-100 transition-all uppercase tracking-wider px-3 py-1.5 rounded-lg">
                    {{ props.licencaValida ? 'Renovar' : 'Ativar' }}
                </Link>
            </div>

        </div>

        <!-- RIGHT: Brand Panel -->
        <div class="hidden lg:flex flex-1 bg-gradient-to-br from-[#0a1628] via-[#0d2347] to-[#061a3a] relative overflow-hidden items-center justify-center p-16">
            <!-- Decorative elements -->
            <div class="absolute top-0 right-0 w-96 h-96 bg-blue-500/8 rounded-full blur-[100px]"></div>
            <div class="absolute bottom-0 left-0 w-80 h-80 bg-cyan-400/10 rounded-full blur-[80px]"></div>
            <div class="absolute top-1/3 left-1/4 w-40 h-40 bg-blue-400/5 rounded-full blur-[60px]"></div>

            <!-- Subtle grid -->
            <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 40px 40px;"></div>

            <div class="relative z-10 text-center max-w-[400px]">
                <!-- Glass Card -->
                <div class="bg-white/[0.06] backdrop-blur-md rounded-3xl border border-white/[0.08] p-12 mb-10 transition-transform duration-700 hover:scale-[1.02]">
                    <img src="/images/logo_official.png" class="w-full h-auto drop-shadow-2xl" alt="Emute Software" />
                </div>

                <span class="inline-block px-4 py-1.5 bg-white/[0.06] border border-white/[0.08] rounded-full text-[10px] font-semibold text-white/50 tracking-[0.2em] uppercase backdrop-blur-sm mb-5">
                    Solução Hospitalar Premium
                </span>
                <h2 class="text-white/80 text-xl font-light tracking-wide">Inove o seu processo.</h2>
                <p class="text-white/25 text-[12px] mt-3 font-medium leading-relaxed">
                    Gestão clínica integrada. Recepção, consultório, laboratório, faturação e muito mais.
                </p>
            </div>

            <!-- Version -->
            <div class="absolute bottom-8 left-0 w-full text-center">
                <p class="text-[10px] font-medium text-white/15 uppercase tracking-[0.2em]">© 2026 MUTECODE · v1.0.0</p>
            </div>
        </div>
    </div>
</template>

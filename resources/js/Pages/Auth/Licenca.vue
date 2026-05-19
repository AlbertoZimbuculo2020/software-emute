<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { Mail, Building2, FileText, Shield, Key, ArrowLeft, CheckCircle, X, Loader2, Sparkles, Crown, Zap } from 'lucide-vue-next';

const page = usePage();

// Toast
const showToast = ref(false);
const toastMessage = ref('');
const toastType = ref('success');

const triggerToast = (message, type = 'success') => {
    toastMessage.value = message;
    toastType.value = type;
    showToast.value = true;
    setTimeout(() => { showToast.value = false }, 6000);
};

// Check flash messages
watch(() => page.props.flash?.success, (val) => {
    if (val) triggerToast(val, 'success');
}, { immediate: true });

// Solicitation form
const formSolicitar = useForm({
    email: '',
    empresa: '',
    nif: '',
    plano: 'mensal',
});

// Activation form
const formAtivar = useForm({
    codigo: '',
});

const selectedPlan = ref('mensal');

const plans = [
    {
        id: 'mensal',
        name: 'Mensal',
        duration: '1 Mês',
        price: '15.000 Kz',
        icon: 'Zap',
        color: 'from-blue-500 to-cyan-400',
        border: 'border-blue-200 hover:border-blue-400',
        bg: 'bg-blue-50',
        badge: null,
    },
    {
        id: 'semestral',
        name: 'Semestral',
        duration: '6 Meses',
        price: '75.000 Kz',
        icon: 'Sparkles',
        color: 'from-violet-500 to-purple-400',
        border: 'border-violet-200 hover:border-violet-400',
        bg: 'bg-violet-50',
        badge: 'Popular',
    },
    {
        id: 'anual',
        name: 'Anual',
        duration: '12 Meses',
        price: '120.000 Kz',
        icon: 'Crown',
        color: 'from-amber-500 to-orange-400',
        border: 'border-amber-200 hover:border-amber-400',
        bg: 'bg-amber-50',
        badge: 'Melhor Valor',
    },
];

watch(selectedPlan, (val) => {
    formSolicitar.plano = val;
});

const solicitar = () => {
    formSolicitar.plano = selectedPlan.value;
    formSolicitar.post(route('licenca.solicitar'), {
        preserveScroll: true,
        onSuccess: () => {
            showActivationSection.value = true;
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            triggerToast(firstError, 'error');
        },
    });
};

const ativar = () => {
    formAtivar.post(route('licenca.ativar'), {
        preserveScroll: true,
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            triggerToast(firstError, 'error');
        },
    });
};

const showActivationSection = ref(false);
</script>

<template>
    <Head title="EMUTE - Solicitar Licença" />

    <!-- Top Gradient Bar -->
    <div class="fixed top-0 left-0 w-full h-1 bg-gradient-to-r from-[#12F2FF] via-[#247BFF] to-[#006BB3] z-50"></div>

    <!-- Toast Notification -->
    <transition enter-active-class="transform transition ease-out duration-300" enter-from-class="translate-y-[-100%] opacity-0" enter-to-class="translate-y-0 opacity-100" leave-active-class="transform transition ease-in duration-200" leave-from-class="translate-y-0 opacity-100" leave-to-class="translate-y-[-100%] opacity-0">
        <div v-if="showToast" class="fixed top-6 right-6 z-50 flex items-center bg-white shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-gray-100 p-4 rounded-2xl min-w-[300px] max-w-[420px]">
            <div :class="['flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center mr-3', toastType === 'success' ? 'bg-green-50' : 'bg-red-50']">
                <CheckCircle v-if="toastType === 'success'" class="w-5 h-5 text-green-500" />
                <X v-else class="w-5 h-5 text-red-500" />
            </div>
            <div class="flex-grow">
                <p class="text-sm font-bold text-gray-800">{{ toastType === 'success' ? 'Sucesso' : 'Erro' }}</p>
                <p class="text-xs text-gray-500 mt-0.5">{{ toastMessage }}</p>
            </div>
            <button @click="showToast = false" class="text-gray-400 hover:text-gray-600 transition-colors ml-3"><X class="w-4 h-4" /></button>
        </div>
    </transition>

    <div class="min-h-screen relative flex flex-col items-center justify-center font-sans overflow-hidden bg-[#F8FAFC] p-4 sm:p-8">

        <!-- Background Orbs -->
        <div class="absolute top-[-10%] right-[-5%] w-[500px] h-[500px] bg-violet-400/20 rounded-full blur-[100px] mix-blend-multiply pointer-events-none"></div>
        <div class="absolute bottom-[-10%] left-[-5%] w-[600px] h-[600px] bg-[#006BB3]/10 rounded-full blur-[120px] mix-blend-multiply pointer-events-none"></div>
        <div class="absolute top-[30%] left-[20%] w-[300px] h-[300px] bg-amber-300/10 rounded-full blur-[80px] mix-blend-multiply pointer-events-none"></div>

        <!-- Back to Login -->
        <div class="absolute top-6 left-6 z-20">
            <Link :href="route('login')" class="flex items-center space-x-2 text-sm font-bold text-gray-500 hover:text-blue-600 transition-colors group">
                <ArrowLeft class="w-4 h-4 group-hover:-translate-x-1 transition-transform" />
                <span>Voltar ao Login</span>
            </Link>
        </div>

        <!-- Main Container -->
        <div class="w-full max-w-[1100px] z-10">

            <!-- Header -->
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center bg-white p-3 rounded-2xl shadow-sm border border-gray-50 mb-4">
                    <img src="/images/logo.png" class="h-10 w-auto" alt="Logo" />
                </div>
                <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Solicitar Licença</h1>
                <p class="text-sm text-gray-500 mt-2 font-medium max-w-md mx-auto">Ative o EMUTE Software na sua clínica. Escolha o plano, preencha os dados e receba o código de ativação por email.</p>
            </div>

            <!-- Plans -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-10">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    @click="selectedPlan = plan.id"
                    :class="[
                        'relative cursor-pointer rounded-2xl border-2 p-6 transition-all duration-300 bg-white/80 backdrop-blur-xl',
                        selectedPlan === plan.id
                            ? 'border-blue-500 shadow-[0_8px_30px_rgba(0,107,179,0.15)] scale-[1.02]'
                            : plan.border + ' shadow-sm hover:shadow-md'
                    ]"
                >
                    <!-- Badge -->
                    <div v-if="plan.badge" class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <span class="px-3 py-1 text-[10px] font-bold text-white uppercase tracking-wider rounded-full bg-gradient-to-r" :class="plan.color">{{ plan.badge }}</span>
                    </div>

                    <!-- Selection Indicator -->
                    <div class="absolute top-4 right-4">
                        <div :class="[
                            'w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all',
                            selectedPlan === plan.id ? 'border-blue-500 bg-blue-500' : 'border-gray-300'
                        ]">
                            <CheckCircle v-if="selectedPlan === plan.id" class="w-4 h-4 text-white" />
                        </div>
                    </div>

                    <!-- Icon -->
                    <div :class="['w-12 h-12 rounded-xl flex items-center justify-center mb-4 bg-gradient-to-br', plan.color]">
                        <Zap v-if="plan.icon === 'Zap'" class="w-6 h-6 text-white" />
                        <Sparkles v-else-if="plan.icon === 'Sparkles'" class="w-6 h-6 text-white" />
                        <Crown v-else class="w-6 h-6 text-white" />
                    </div>

                    <h3 class="text-lg font-bold text-gray-800">{{ plan.name }}</h3>
                    <p class="text-xs text-gray-500 mt-1">{{ plan.duration }}</p>
                    <p class="text-2xl font-extrabold text-gray-900 mt-3">{{ plan.price }}</p>

                    <ul class="mt-4 space-y-2">
                        <li class="flex items-center text-xs text-gray-600"><CheckCircle class="w-3.5 h-3.5 text-green-500 mr-2 flex-shrink-0" /> Todos os módulos</li>
                        <li class="flex items-center text-xs text-gray-600"><CheckCircle class="w-3.5 h-3.5 text-green-500 mr-2 flex-shrink-0" /> Suporte técnico</li>
                        <li class="flex items-center text-xs text-gray-600"><CheckCircle class="w-3.5 h-3.5 text-green-500 mr-2 flex-shrink-0" /> Atualizações gratuitas</li>
                    </ul>
                </div>
            </div>

            <!-- Forms Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Solicitation Form -->
                <div class="bg-white/80 backdrop-blur-xl rounded-2xl border border-white shadow-[0_8px_30px_rgba(0,0,0,0.06)] p-8">
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#006BB3] to-[#0091FF] flex items-center justify-center">
                            <Mail class="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-800">Solicitar Código</h2>
                            <p class="text-[11px] text-gray-500">Preencha os dados da empresa</p>
                        </div>
                    </div>

                    <form @submit.prevent="solicitar" class="space-y-4">
                        <!-- Email -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider pl-1">Email</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <Mail class="h-4 w-4 text-gray-400 group-focus-within:text-blue-500 transition-colors" />
                                </div>
                                <input
                                    v-model="formSolicitar.email"
                                    type="email"
                                    class="w-full pl-10 pr-4 py-3 bg-gray-50/50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 focus:bg-white transition-all font-medium placeholder-gray-400"
                                    placeholder="empresa@email.com"
                                    required
                                />
                            </div>
                        </div>

                        <!-- Empresa -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider pl-1">Nome da Empresa</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <Building2 class="h-4 w-4 text-gray-400 group-focus-within:text-blue-500 transition-colors" />
                                </div>
                                <input
                                    v-model="formSolicitar.empresa"
                                    type="text"
                                    class="w-full pl-10 pr-4 py-3 bg-gray-50/50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 focus:bg-white transition-all font-medium placeholder-gray-400"
                                    placeholder="Nome da sua empresa ou clínica"
                                    required
                                />
                            </div>
                        </div>

                        <!-- NIF -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider pl-1">NIF</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <FileText class="h-4 w-4 text-gray-400 group-focus-within:text-blue-500 transition-colors" />
                                </div>
                                <input
                                    v-model="formSolicitar.nif"
                                    type="text"
                                    class="w-full pl-10 pr-4 py-3 bg-gray-50/50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 focus:bg-white transition-all font-medium placeholder-gray-400"
                                    placeholder="Número de Contribuinte"
                                    required
                                />
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="formSolicitar.processing"
                            class="w-full bg-gradient-to-r from-[#006BB3] to-[#0091FF] hover:from-[#005a96] hover:to-[#007EE6] text-white py-3 rounded-xl text-sm font-bold shadow-lg shadow-blue-500/25 transition-all hover:-translate-y-0.5 focus:ring-4 focus:ring-blue-500/20 active:translate-y-0 disabled:opacity-70 flex items-center justify-center space-x-2"
                        >
                            <Loader2 v-if="formSolicitar.processing" class="w-4 h-4 animate-spin" />
                            <Mail v-else class="w-4 h-4" />
                            <span>{{ formSolicitar.processing ? 'A Enviar...' : 'Solicitar Licença' }}</span>
                        </button>
                    </form>
                </div>

                <!-- Activation Form -->
                <div class="bg-white/80 backdrop-blur-xl rounded-2xl border border-white shadow-[0_8px_30px_rgba(0,0,0,0.06)] p-8">
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-green-400 flex items-center justify-center">
                            <Key class="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-800">Ativar Licença</h2>
                            <p class="text-[11px] text-gray-500">Insira o código recebido por email</p>
                        </div>
                    </div>

                    <!-- Visual Guide -->
                    <div class="mb-6 p-4 rounded-xl bg-gradient-to-br from-gray-50 to-blue-50/30 border border-gray-100">
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <Shield class="w-4 h-4 text-blue-600" />
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-700">Como funciona?</p>
                                <ol class="mt-2 space-y-1.5 text-[11px] text-gray-500">
                                    <li class="flex items-start"><span class="w-4 h-4 rounded-full bg-blue-100 text-blue-600 font-bold flex items-center justify-center text-[9px] mr-2 flex-shrink-0 mt-0.5">1</span> Preencha os dados e solicite a licença</li>
                                    <li class="flex items-start"><span class="w-4 h-4 rounded-full bg-blue-100 text-blue-600 font-bold flex items-center justify-center text-[9px] mr-2 flex-shrink-0 mt-0.5">2</span> Receba o código de 4 dígitos por email</li>
                                    <li class="flex items-start"><span class="w-4 h-4 rounded-full bg-blue-100 text-blue-600 font-bold flex items-center justify-center text-[9px] mr-2 flex-shrink-0 mt-0.5">3</span> Digite o código abaixo e ative a licença</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="ativar" class="space-y-4">
                        <!-- Code Input -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider pl-1">Código de Ativação</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <Key class="h-4 w-4 text-gray-400 group-focus-within:text-emerald-500 transition-colors" />
                                </div>
                                <input
                                    v-model="formAtivar.codigo"
                                    type="text"
                                    maxlength="4"
                                    class="w-full pl-10 pr-4 py-4 bg-gray-50/50 border border-gray-200 text-gray-900 text-2xl font-extrabold text-center tracking-[0.5em] rounded-xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 focus:bg-white transition-all placeholder-gray-300"
                                    placeholder="• • • •"
                                    required
                                />
                            </div>
                            <p v-if="formAtivar.errors.codigo" class="text-xs text-red-500 font-medium pl-1">{{ formAtivar.errors.codigo }}</p>
                        </div>

                        <button
                            type="submit"
                            :disabled="formAtivar.processing || formAtivar.codigo.length < 4"
                            class="w-full bg-gradient-to-r from-emerald-500 to-green-400 hover:from-emerald-600 hover:to-green-500 text-white py-3 rounded-xl text-sm font-bold shadow-lg shadow-emerald-500/25 transition-all hover:-translate-y-0.5 focus:ring-4 focus:ring-emerald-500/20 active:translate-y-0 disabled:opacity-50 flex items-center justify-center space-x-2"
                        >
                            <Loader2 v-if="formAtivar.processing" class="w-4 h-4 animate-spin" />
                            <Shield v-else class="w-4 h-4" />
                            <span>{{ formAtivar.processing ? 'A Ativar...' : 'Ativar Licença' }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-10 text-center z-10">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest">
                © 2026 MUTECODE. Software EMUTE.
            </p>
        </div>
    </div>
</template>

<style scoped>
/* Override letter spacing for input placeholder */
input::placeholder {
    letter-spacing: 0.1em;
}
</style>

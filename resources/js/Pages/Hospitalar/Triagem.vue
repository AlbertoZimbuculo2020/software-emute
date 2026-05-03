<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { 
    ClipboardList, Search, Activity, User, 
    Thermometer, Weight, HeartPulse, Wind, 
    Clock, Check, ChevronRight, Save, History, Beaker
} from 'lucide-vue-next';

const props = defineProps({
    aguardando: Array,
    historico: Array
});

const searchTerm = ref('');
const selectedPaciente = ref(null);
const notification = ref({ show: false, message: '', type: 'success' });
const confirmModal = ref({ isOpen: false, title: '', message: '', onConfirm: null });

const showNotification = (message, type = 'success') => {
    notification.value = { show: true, message, type };
    setTimeout(() => {
        notification.value.show = false;
    }, 4000);
};

const form = useForm({
    IdAgenda: '',
    IdPaciente: '',
    peso: '',
    temperatura: '',
    tensao: '',
    pulso: '',
    f_respiratoria: '',
    oximetria: '',
    obs: '',
});

const filteredAguardando = computed(() => {
    if (!searchTerm.value) return props.aguardando;
    return props.aguardando.filter(p => 
        p.PacienteNome.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
        p.Codigo.toLowerCase().includes(searchTerm.value.toLowerCase())
    );
});

const selecionarPaciente = (paciente) => {
    selectedPaciente.value = paciente;
    form.IdAgenda = paciente.Codigo;
    form.IdPaciente = paciente.IdPaciente;
    form.peso = '';
    form.temperatura = '';
    form.tensao = '';
    form.pulso = '';
    form.f_respiratoria = '';
    form.oximetria = '';
    form.obs = '';
};

const enviarTriage = () => {
    if (!selectedPaciente.value) {
        showNotification('Selecione um paciente na lista primeiro.', 'error');
        return;
    }

    confirmModal.value = {
        isOpen: true,
        title: 'Enviar para Consultório',
        message: `Deseja enviar o paciente ${selectedPaciente.value.PacienteNome} para o consultório médico com os sinais vitais registrados?`,
        onConfirm: () => {
            confirmModal.value.isOpen = false;
            form.post(route('hospitalar.triagem.store'), {
                onSuccess: () => {
                    showNotification('Triagem realizada e paciente enviado para o consultório!');
                    selectedPaciente.value = null;
                    form.reset();
                },
                onError: () => {
                    showNotification('Erro ao processar triagem.', 'error');
                }
            });
        }
    };
};

const calcularIdade = (dataNascimento) => {
    if (!dataNascimento) return 'N/D';
    const birthDate = new Date(dataNascimento);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return age + ' anos';
};
</script>

<template>
    <Head title="Triagem Hospitalar" />

    <DashboardLayout>
        <div class="min-h-screen bg-[#f8fafc] p-4 lg:p-6 font-sans">
            <!-- Selected Patient Floating Header -->
            <div class="max-w-[1700px] mx-auto mb-6">
                <div class="bg-white p-4 rounded-3xl shadow-sm border border-slate-200/60 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-600 rounded-2xl text-white shadow-lg shadow-blue-200">
                            <Activity class="w-6 h-6" />
                        </div>
                        <div>
                            <h1 class="text-lg font-black text-slate-900 tracking-tight uppercase">Triagem Hospitalar</h1>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ selectedPaciente?.Codigo || 'Nenhum paciente selecionado' }}</p>
                        </div>
                    </div>
                    <div class="hidden md:flex items-center gap-4 bg-slate-50 px-6 py-2 rounded-2xl border border-slate-100">
                        <div class="text-right">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-tighter">Aguardando</p>
                            <p class="text-sm font-black text-blue-600">{{ aguardando.length }} Pacientes</p>
                        </div>
                        <div class="w-px h-6 bg-slate-200"></div>
                        <Clock class="w-5 h-5 text-blue-400" />
                    </div>
                </div>
            </div>

            <div class="max-w-[1700px] mx-auto grid grid-cols-1 lg:grid-cols-12 gap-6 lg:h-[calc(100vh-180px)]">
                
                <!-- LEFT COLUMN: VITALS FORM -->
                <div class="col-span-1 lg:col-span-5 flex flex-col h-full animate-fadeIn">
                    <div class="bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/40 border border-slate-100 flex flex-col h-full overflow-hidden">
                        <div class="p-6 border-b border-slate-50 bg-slate-50/50 flex items-center justify-between">
                            <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-3">
                                <Activity class="w-4 h-4 text-blue-500" /> Sinais Vitais
                            </h2>
                            <div v-if="selectedPaciente" class="px-3 py-1 bg-blue-100 text-blue-600 rounded-full text-[9px] font-black uppercase">
                                Em atendimento
                            </div>
                        </div>
                        
                        <div class="p-8 flex flex-col flex-grow overflow-y-auto custom-scrollbar bg-white">
                            <!-- Patient Quick Card -->
                            <div class="mb-8 p-6 bg-blue-600 rounded-[2rem] text-white shadow-xl shadow-blue-100 relative overflow-hidden group transition-all">
                                <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-2xl group-hover:bg-white/20 transition-all"></div>
                                <div class="relative z-10">
                                    <h3 class="text-base font-black uppercase tracking-tight">{{ selectedPaciente?.PacienteNome || 'Selecione um Paciente' }}</h3>
                                    <div class="flex items-center gap-3 mt-2 opacity-80">
                                        <span class="text-[10px] font-bold bg-white/20 px-2 py-0.5 rounded uppercase">Idade: {{ calcularIdade(selectedPaciente?.DataNascimento) }}</span>
                                        <span class="text-[10px] font-bold bg-white/20 px-2 py-0.5 rounded uppercase">{{ selectedPaciente?.Codigo || '---' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Vitals Inputs Grid -->
                            <div class="grid grid-cols-1 gap-6">
                                <div v-for="v in [
                                    { label: 'Peso Corporal', icon: Weight, model: 'peso', placeholder: '0.0', unit: 'kg' },
                                    { label: 'Temperatura', icon: Thermometer, model: 'temperatura', placeholder: '36.5', unit: '°C' },
                                    { label: 'Pressão Arterial', icon: Activity, model: 'tensao', placeholder: '12/8', unit: 'mmHg' },
                                    { label: 'Freq. Cardíaca', icon: HeartPulse, model: 'pulso', placeholder: '80', unit: 'bpm' },
                                    { label: 'Freq. Respiratória', icon: Wind, model: 'f_respiratoria', placeholder: '18', unit: 'rpm' },
                                    { label: 'Saturação O2', icon: Beaker, model: 'oximetria', placeholder: '98', unit: '%' }
                                ]" :key="v.model" class="group">
                                    <div class="flex items-center justify-between mb-2 px-1">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-2 group-focus-within:text-blue-500 transition-colors">
                                            <component :is="v.icon" class="w-3.5 h-3.5" /> {{ v.label }}
                                        </label>
                                        <span class="text-[10px] font-bold text-slate-300 uppercase">{{ v.unit }}</span>
                                    </div>
                                    <input v-model="form[v.model]" :placeholder="v.placeholder" 
                                           class="w-full bg-slate-50 border-transparent border-2 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 rounded-2xl px-5 py-3.5 text-xs font-black text-slate-700 transition-all placeholder:text-slate-300" />
                                </div>
                            </div>

                            <!-- Observation -->
                            <div class="mt-8">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 mb-3 block">Observações Clínicas</label>
                                <textarea v-model="form.obs" rows="4" placeholder="Alguma observação relevante..." 
                                          class="w-full bg-slate-50 border-transparent border-2 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 rounded-[2rem] px-6 py-4 text-xs font-medium text-slate-700 transition-all placeholder:text-slate-300 resize-none"></textarea>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div class="p-6 bg-slate-50/50 border-t border-slate-50">
                            <button @click="enviarTriage" :disabled="form.processing" 
                                    class="w-full bg-blue-600 text-white py-5 rounded-[1.5rem] font-black uppercase text-[11px] tracking-[0.2em] hover:bg-blue-700 transition-all shadow-xl shadow-blue-200 active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-3">
                                <Save class="w-4 h-4" /> ENVIAR PARA CONSULTÓRIO
                            </button>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: TABLES -->
                <div class="col-span-1 lg:col-span-7 flex flex-col gap-6 h-full overflow-hidden">
                    
                    <!-- Waitlist Section -->
                    <div class="h-1/2 bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/40 border border-slate-100 flex flex-col overflow-hidden animate-fadeIn [animation-delay:200ms]">
                        <div class="p-6 border-b border-slate-50 bg-slate-50/50 flex items-center justify-between">
                            <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-3">
                                <User class="w-4 h-4 text-blue-500" /> Pacientes para Triagem
                            </h2>
                            <div class="relative w-64">
                                <input v-model="searchTerm" placeholder="Pesquisar..." class="w-full bg-white border border-slate-200 px-4 py-2 rounded-xl text-[10px] font-bold focus:ring-2 focus:ring-blue-500/20 transition-all" />
                                <Search class="absolute right-3 top-2.5 w-3 h-3 text-slate-300" />
                            </div>
                        </div>

                        <div class="flex-grow overflow-auto custom-scrollbar p-2">
                            <table class="w-full border-separate border-spacing-y-2">
                                <thead>
                                    <tr class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                                        <th class="px-4 pb-2 text-left">Agenda</th>
                                        <th class="px-4 pb-2 text-left">Paciente</th>
                                        <th class="px-4 pb-2 text-left">Especialidade</th>
                                        <th class="px-4 pb-2 text-right">Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in filteredAguardando" :key="p.Codigo" 
                                        @click="selecionarPaciente(p)"
                                        :class="selectedPaciente?.Codigo === p.Codigo ? 'bg-blue-600 text-white shadow-xl shadow-blue-200 scale-[1.01]' : 'bg-slate-50/50 hover:bg-slate-100/80 text-slate-600'"
                                        class="cursor-pointer transition-all rounded-2xl group">
                                        <td class="px-4 py-4 rounded-l-2xl font-black text-[10px]">{{ p.Codigo }}</td>
                                        <td class="px-4 py-4 uppercase font-black text-[10px]">{{ p.PacienteNome }}</td>
                                        <td class="px-4 py-4">
                                            <span :class="selectedPaciente?.Codigo === p.Codigo ? 'bg-white/20 text-white' : 'bg-blue-100 text-blue-600'" 
                                                  class="px-2 py-0.5 rounded-lg text-[8px] font-black uppercase tracking-tighter">{{ p.Consulta }}</span>
                                        </td>
                                        <td class="px-4 py-4 text-right rounded-r-2xl">
                                            <ChevronRight class="w-4 h-4 inline-block group-hover:translate-x-1 transition-transform" />
                                        </td>
                                    </tr>
                                    <tr v-if="filteredAguardando.length === 0">
                                        <td colspan="4" class="py-20 text-center">
                                            <p class="text-[10px] font-bold text-slate-300 uppercase tracking-[0.2em]">Nenhum paciente aguardando</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- History Section -->
                    <div class="h-1/2 bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/40 border border-slate-100 flex flex-col overflow-hidden animate-fadeIn [animation-delay:400ms]">
                        <div class="p-6 border-b border-slate-50 bg-slate-50/50">
                            <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-3">
                                <History class="w-4 h-4 text-slate-400" /> Histórico Recente
                            </h2>
                        </div>
                        
                        <div class="flex-grow overflow-auto custom-scrollbar p-2">
                            <table class="w-full border-separate border-spacing-y-2">
                                <thead>
                                    <tr class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                                        <th class="px-4 pb-2 text-left">Hora</th>
                                        <th class="px-4 pb-2 text-left">Paciente</th>
                                        <th class="px-4 pb-2 text-left">Sinais</th>
                                        <th class="px-4 pb-2 text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="h in historico" :key="h.Id" class="bg-slate-50/50 hover:bg-slate-50 text-slate-600 font-bold text-[9px] uppercase transition-all rounded-2xl">
                                        <td class="px-4 py-4 rounded-l-2xl text-slate-400">{{ new Date(h.CREATED_AT).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</td>
                                        <td class="px-4 py-4 font-black text-slate-700 text-[10px]">{{ h.PacienteNome }}</td>
                                        <td class="px-4 py-4">
                                            <div class="flex gap-2 text-[8px]">
                                                <span class="text-blue-500">{{ h.Peso }}kg</span>
                                                <span class="text-emerald-500">{{ h.Temperatura }}°C</span>
                                                <span class="text-amber-500">{{ h.PressaoArterial }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-right rounded-r-2xl">
                                            <span class="bg-emerald-100 text-emerald-600 px-3 py-1 rounded-full text-[8px] font-black tracking-tighter">Concluído</span>
                                        </td>
                                    </tr>
                                    <tr v-if="historico.length === 0">
                                        <td colspan="4" class="py-20 text-center">
                                            <p class="text-[10px] font-bold text-slate-300 uppercase tracking-[0.2em]">Sem histórico hoje</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- REFINED NOTIFICATION -->
        <Transition enter-active-class="duration-300 ease-out" enter-from-class="translate-y-4 opacity-0" enter-to-class="translate-y-0 opacity-100" leave-active-class="duration-200 ease-in" leave-to-class="translate-y-4 opacity-0">
            <div v-if="notification.show" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-[1000] bg-slate-900/90 backdrop-blur-xl text-white px-8 py-4 rounded-[2rem] shadow-2xl border border-white/10 flex items-center gap-4 min-w-[300px] justify-center">
                <div :class="notification.type === 'success' ? 'bg-emerald-500' : 'bg-red-500'" class="p-1 rounded-lg">
                    <Check v-if="notification.type === 'success'" class="w-4 h-4 text-white" />
                    <Beaker v-else class="w-4 h-4 text-white" />
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest">{{ notification.message }}</span>
            </div>
        </Transition>
    </DashboardLayout>

    <!-- PREMIUM CONFIRM MODAL -->
    <Transition enter-active-class="duration-300 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="duration-200 ease-in" leave-to-class="opacity-0 scale-95">
        <div v-if="confirmModal.isOpen" class="fixed inset-0 z-[2000] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-md" @click="confirmModal.isOpen = false"></div>
            <div class="relative bg-white rounded-[2.5rem] shadow-2xl p-10 max-w-md w-full border border-white/20 animate-fadeIn text-center">
                <div class="flex flex-col items-center">
                    <div class="w-20 h-20 bg-blue-50 rounded-[2rem] flex items-center justify-center mb-6 shadow-inner">
                        <Activity class="w-10 h-10 text-blue-600" />
                    </div>
                    <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight mb-2">{{ confirmModal.title }}</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest leading-relaxed mb-8 px-4">
                        {{ confirmModal.message }}
                    </p>
                    
                    <div class="grid grid-cols-2 gap-4 w-full text-[10px] font-black uppercase tracking-widest">
                        <button @click="confirmModal.isOpen = false" class="py-4 bg-slate-100 text-slate-500 rounded-2xl hover:bg-slate-200 transition-all">
                            Cancelar
                        </button>
                        <button @click="confirmModal.onConfirm" class="py-4 bg-blue-600 text-white rounded-2xl hover:bg-blue-700 transition-all shadow-xl shadow-blue-200">
                            Confirmar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

input, textarea { outline: none; }
button:active { transform: scale(0.98); }

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
    animation: fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}
</style>

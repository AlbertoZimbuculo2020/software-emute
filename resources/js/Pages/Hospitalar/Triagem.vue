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
        <div class="min-h-screen bg-[#f8fafc] p-4 lg:p-8">
            <div class="max-w-[1700px] mx-auto space-y-8">
                
                <!-- Header Moderno -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl shadow-sm border border-slate-200/60">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                            <div class="p-2 bg-emerald-600 rounded-xl text-white shadow-lg shadow-emerald-200">
                                <Activity class="w-6 h-6" />
                            </div>
                            TRIAGEM HOSPITALAR
                        </h1>
                        <p class="text-slate-500 text-sm font-medium mt-1 ml-11">Monitorização de Sinais Vitais e Encaminhamento</p>
                    </div>
                    
                    <div class="flex items-center gap-4 bg-slate-50 p-2 rounded-2xl border border-slate-100">
                        <div class="text-right px-4">
                            <p class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Aguardando Triagem</p>
                            <p class="text-lg font-black text-emerald-600">{{ aguardando.length }} Pacientes</p>
                        </div>
                        <div class="p-2 bg-white rounded-xl shadow-sm">
                            <Clock class="w-5 h-5 text-emerald-600" />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    
                    <!-- Coluna Esquerda: Fila de Espera -->
                    <div class="lg:col-span-4 space-y-6">
                        <div class="bg-white p-5 rounded-[2.5rem] shadow-xl shadow-slate-200/40 border border-slate-100 h-full">
                            <div class="flex items-center justify-between mb-6 px-2">
                                <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                                    <History class="w-4 h-4 text-emerald-600" /> FILA DE ESPERA
                                </h2>
                            </div>

                            <div class="relative mb-6">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <Search class="w-4 h-4 text-slate-300" />
                                </div>
                                <input v-model="searchTerm" placeholder="Pesquisar na fila..." class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 rounded-2xl pl-11 pr-4 py-3 text-xs font-bold text-slate-700 transition-all placeholder:text-slate-300" />
                            </div>

                            <div class="space-y-3 max-h-[600px] overflow-y-auto custom-scrollbar pr-2">
                                <div v-if="filteredAguardando.length === 0" class="p-10 text-center">
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Ninguém aguardando</p>
                                </div>
                                <div v-for="p in filteredAguardando" :key="p.Id" 
                                     @click="selecionarPaciente(p)"
                                     :class="selectedPaciente?.Codigo === p.Codigo ? 'ring-2 ring-emerald-500 bg-emerald-50/50' : 'bg-slate-50 hover:bg-slate-100/80'"
                                     class="p-4 rounded-2xl cursor-pointer transition-all group relative overflow-hidden border border-transparent">
                                    <div class="flex items-center gap-4 relative z-10">
                                        <div class="w-12 h-12 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-emerald-600 font-black shadow-sm group-hover:scale-105 transition-transform">
                                            {{ p.PacienteNome.substring(0, 2).toUpperCase() }}
                                        </div>
                                        <div class="flex-grow">
                                            <h3 class="text-[11px] font-black text-slate-800 uppercase tracking-tight">{{ p.PacienteNome }}</h3>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-tighter">{{ p.Codigo }}</span>
                                                <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                                                <span class="text-[9px] font-black text-emerald-500 uppercase tracking-tighter">{{ p.Consulta }}</span>
                                            </div>
                                        </div>
                                        <ChevronRight class="w-4 h-4 text-slate-300 group-hover:text-emerald-500 transition-all" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Coluna Direita: Formulário de Triagem -->
                    <div class="lg:col-span-8 space-y-8">
                        <div v-if="selectedPaciente" class="bg-white p-8 rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-slate-100 animate-fadeIn">
                            <div class="flex items-center justify-between mb-10 border-b border-slate-100 pb-8">
                                <div class="flex items-center gap-5">
                                    <div class="w-16 h-16 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-2xl font-black shadow-lg shadow-emerald-200">
                                        {{ selectedPaciente.PacienteNome.substring(0, 2).toUpperCase() }}
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-black text-slate-900 uppercase tracking-tight">{{ selectedPaciente.PacienteNome }}</h2>
                                        <div class="flex items-center gap-3 mt-1.5">
                                            <span class="text-[10px] font-black bg-slate-100 text-slate-500 px-2.5 py-1 rounded-lg uppercase tracking-wider">{{ selectedPaciente.Codigo }}</span>
                                            <span class="text-xs font-bold text-slate-400">ID: {{ selectedPaciente.IdPaciente }}</span>
                                            <span class="text-xs font-bold text-slate-400">•</span>
                                            <span class="text-xs font-bold text-slate-400">{{ calcularIdade(selectedPaciente.DataNascimento) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Destino Clínico</p>
                                    <p class="text-sm font-black text-emerald-600 mt-1 uppercase">{{ selectedPaciente.Consulta }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                                <!-- Peso -->
                                <div class="space-y-3">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 flex items-center gap-2">
                                        <Weight class="w-3 h-3" /> Peso (kg)
                                    </label>
                                    <input v-model="form.peso" placeholder="00.0" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 rounded-2xl px-5 py-4 text-sm font-black text-slate-700 transition-all placeholder:text-slate-300" />
                                </div>

                                <!-- Temperatura -->
                                <div class="space-y-3">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 flex items-center gap-2">
                                        <Thermometer class="w-3 h-3" /> Temp. (°C)
                                    </label>
                                    <input v-model="form.temperatura" placeholder="36.5" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 rounded-2xl px-5 py-4 text-sm font-black text-slate-700 transition-all placeholder:text-slate-300" />
                                </div>

                                <!-- Pressão/Tensão -->
                                <div class="space-y-3">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 flex items-center gap-2">
                                        <Activity class="w-3 h-3" /> Pressão Art.
                                    </label>
                                    <input v-model="form.tensao" placeholder="12/8" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 rounded-2xl px-5 py-4 text-sm font-black text-slate-700 transition-all placeholder:text-slate-300" />
                                </div>

                                <!-- Pulso -->
                                <div class="space-y-3">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 flex items-center gap-2">
                                        <HeartPulse class="w-3 h-3" /> Pulso (bpm)
                                    </label>
                                    <input v-model="form.pulso" placeholder="80" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 rounded-2xl px-5 py-4 text-sm font-black text-slate-700 transition-all placeholder:text-slate-300" />
                                </div>

                                <!-- F. Respiratória -->
                                <div class="space-y-3">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 flex items-center gap-2">
                                        <Wind class="w-3 h-3" /> F. Resp.
                                    </label>
                                    <input v-model="form.f_respiratoria" placeholder="18" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 rounded-2xl px-5 py-4 text-sm font-black text-slate-700 transition-all placeholder:text-slate-300" />
                                </div>

                                <!-- Oximetria -->
                                <div class="space-y-3">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 flex items-center gap-2">
                                        <Beaker class="w-3 h-3" /> SPO2 (%)
                                    </label>
                                    <input v-model="form.oximetria" placeholder="98" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 rounded-2xl px-5 py-4 text-sm font-black text-slate-700 transition-all placeholder:text-slate-300" />
                                </div>
                            </div>

                            <div class="mt-8 space-y-3">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Observações Clínicas</label>
                                <textarea v-model="form.obs" rows="3" placeholder="Nota adicional sobre o estado do paciente..." class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 rounded-[2rem] px-6 py-5 text-sm font-medium text-slate-700 transition-all placeholder:text-slate-300 resize-none"></textarea>
                            </div>

                            <div class="mt-10 flex justify-end gap-4">
                                <button @click="selectedPaciente = null" class="px-8 py-4 bg-slate-100 text-slate-500 rounded-2xl font-black text-[11px] tracking-widest hover:bg-slate-200 transition-all uppercase">
                                    Cancelar
                                </button>
                                <button @click="enviarTriage" :disabled="form.processing" class="flex items-center px-12 py-4 bg-emerald-600 text-white rounded-2xl font-black text-[11px] tracking-widest hover:bg-emerald-700 transition-all shadow-xl shadow-emerald-200 disabled:opacity-50 uppercase">
                                    <Save class="w-4 h-4 mr-3" /> Enviar para Consultório
                                </button>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="h-full min-h-[500px] flex flex-col items-center justify-center bg-white rounded-[3rem] border-2 border-dashed border-slate-100 p-12 text-center">
                            <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mb-6">
                                <Activity class="w-10 h-10 text-slate-200" />
                            </div>
                            <h3 class="text-lg font-black text-slate-300 uppercase tracking-[0.2em]">Aguardando Seleção</h3>
                            <p class="text-sm font-bold text-slate-400 mt-2 max-w-xs">Selecione um paciente na fila à esquerda para iniciar os procedimentos de triagem.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Histórico de Hoje -->
                <div class="bg-white p-8 rounded-[3rem] shadow-xl shadow-slate-200/40 border border-slate-100">
                    <div class="flex items-center justify-between mb-8">
                        <h2 class="text-sm font-black text-slate-800 uppercase tracking-[0.2em] flex items-center gap-3">
                            <History class="w-5 h-5 text-slate-400" /> Histórico de Triagens (Hoje)
                        </h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full border-separate border-spacing-y-3">
                            <thead>
                                <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest text-left">
                                    <th class="px-6 pb-2">Paciente</th>
                                    <th class="px-6 pb-2">Hora</th>
                                    <th class="px-6 pb-2">Peso</th>
                                    <th class="px-6 pb-2">Tensão</th>
                                    <th class="px-6 pb-2">Temp.</th>
                                    <th class="px-6 pb-2">SPO2</th>
                                    <th class="px-6 pb-2 text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="h in historico" :key="h.Id" class="bg-slate-50/50 hover:bg-slate-50 transition-all rounded-2xl group">
                                    <td class="px-6 py-4 rounded-l-2xl">
                                        <p class="text-xs font-black text-slate-700 uppercase tracking-tight">{{ h.PacienteNome }}</p>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase">{{ h.IdAgenda }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-xs font-bold text-slate-500">
                                        {{ new Date(h.CREATED_AT).toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' }) }}
                                    </td>
                                    <td class="px-6 py-4 text-xs font-black text-slate-700">{{ h.Peso || '--' }} kg</td>
                                    <td class="px-6 py-4 text-xs font-black text-slate-700">{{ h.PressaoArterial || '--' }}</td>
                                    <td class="px-6 py-4 text-xs font-black text-slate-700">{{ h.Temperatura || '--' }}°C</td>
                                    <td class="px-6 py-4 text-xs font-black text-slate-700">{{ h.SituacaoOxigenio || '--' }}%</td>
                                    <td class="px-6 py-4 text-right rounded-r-2xl">
                                        <span class="px-3 py-1 bg-emerald-100 text-emerald-600 rounded-full text-[9px] font-black uppercase tracking-tighter shadow-sm">Processado</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-if="historico.length === 0" class="text-center py-12">
                            <p class="text-[10px] font-bold text-slate-300 uppercase tracking-widest">Nenhum registro para hoje</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Elegant Notification -->
        <Transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="notification.show" class="fixed bottom-8 right-8 z-[1000] flex items-center gap-4 px-6 py-4 bg-slate-900 text-white rounded-2xl shadow-2xl border border-white/10 backdrop-blur-xl">
                <div :class="notification.type === 'success' ? 'bg-emerald-500' : 'bg-red-500'" class="p-1.5 rounded-lg">
                    <Check v-if="notification.type === 'success'" class="w-4 h-4 text-white" />
                    <X v-else class="w-4 h-4 text-white" />
                </div>
                <p class="text-xs font-black uppercase tracking-widest">{{ notification.message }}</p>
            </div>
        </Transition>
    </DashboardLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
    animation: fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}
</style>

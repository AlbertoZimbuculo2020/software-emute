<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import axios from 'axios';
import { 
    Users, Search, Activity, History, ClipboardList, 
    Stethoscope, Beaker, Pill, Save, CheckCircle, 
    Printer, FileText, ArrowRight, User, Calendar,
    Thermometer, Weight, HeartPulse, Wind, Droplets,
    ChevronRight, Info, AlertCircle, Plus, Trash2
} from 'lucide-vue-next';

const props = defineProps({
    aguardando: Array
});

const searchTerm = ref('');
const selectedPaciente = ref(null);
const triageData = ref(null);
const patientHistory = ref([]);
const activeTab = ref('clinical'); // clinical, exams, prescription
const isLoading = ref(false);

const notification = ref({ show: false, message: '', type: 'success' });
const showNotification = (message, type = 'success') => {
    notification.value = { show: true, message, type };
    setTimeout(() => notification.value.show = false, 4000);
};

const form = useForm({
    Codigo: '',
    qp: '',
    hda: '',
    obj: '',
    complementares: '',
    recomendacoes: '',
    situacao: 'Finalizado',
});

const filteredAguardando = computed(() => {
    if (!searchTerm.value) return props.aguardando;
    const term = searchTerm.value.toLowerCase();
    return props.aguardando.filter(p => 
        p.PacienteNome.toLowerCase().includes(term) ||
        p.Codigo.toLowerCase().includes(term)
    );
});

const selecionarPaciente = async (paciente) => {
    selectedPaciente.value = paciente;
    isLoading.value = true;
    
    form.Codigo = paciente.Codigo;
    form.qp = paciente.QP || '';
    form.hda = paciente.HDA || '';
    form.obj = paciente.OBJ || '';
    form.complementares = paciente.COMPLEMENTARES || '';
    form.recomendacoes = paciente.RECOMENDACOES || '';
    form.situacao = 'Finalizado';

    try {
        const response = await axios.get(route('hospitalar.consultorio.paciente', paciente.Codigo));
        triageData.value = response.data.triagem;
        patientHistory.value = response.data.historico;
    } catch (error) {
        console.error('Erro ao carregar dados do paciente:', error);
        showNotification('Erro ao carregar dados complementares.', 'error');
    } finally {
        isLoading.value = false;
    }
};

const salvarConsulta = () => {
    if (!selectedPaciente.value) return;
    
    form.post(route('hospitalar.consultorio.store'), {
        onSuccess: () => {
            showNotification('Consulta finalizada com sucesso!');
            selectedPaciente.value = null;
            triageData.value = null;
            form.reset();
        }
    });
};

const calcularIdade = (dataNascimento) => {
    if (!dataNascimento) return 'N/D';
    const birthDate = new Date(dataNascimento);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) age--;
    return age + ' anos';
};
</script>

<template>
    <Head title="Consultório Médico" />

    <DashboardLayout>
        <div class="min-h-screen bg-[#f8fafc] p-4 lg:p-6">
            <div class="max-w-[1800px] mx-auto grid grid-cols-1 lg:grid-cols-12 gap-6 h-[calc(100vh-120px)]">
                
                <!-- Left Sidebar: Patient List & History -->
                <div class="lg:col-span-3 flex flex-col gap-6 overflow-hidden">
                    
                    <!-- Fila de Espera -->
                    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200/60 flex flex-col h-1/2 overflow-hidden">
                        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                            <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                                <Users class="w-4 h-4 text-blue-600" /> Fila de Espera
                            </h2>
                            <span class="bg-blue-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full">{{ aguardando.length }}</span>
                        </div>
                        
                        <div class="p-4 border-b border-slate-50">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                <input v-model="searchTerm" placeholder="Buscar na fila..." class="w-full bg-slate-50 border-transparent focus:ring-2 focus:ring-blue-500/20 rounded-xl pl-10 text-xs font-bold" />
                            </div>
                        </div>

                        <div class="flex-grow overflow-y-auto p-3 space-y-2 custom-scrollbar">
                            <div v-for="p in filteredAguardando" :key="p.Id" 
                                 @click="selecionarPaciente(p)"
                                 :class="selectedPaciente?.Codigo === p.Codigo ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'bg-slate-50 hover:bg-slate-100 text-slate-700'"
                                 class="p-4 rounded-2xl cursor-pointer transition-all group">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-[11px] font-black uppercase leading-tight">{{ p.PacienteNome }}</p>
                                        <p class="text-[9px] font-bold opacity-70 mt-1 uppercase">{{ p.Codigo }} • {{ p.Consulta }}</p>
                                    </div>
                                    <ChevronRight class="w-4 h-4 opacity-50 group-hover:translate-x-1 transition-transform" />
                                </div>
                            </div>
                            <div v-if="filteredAguardando.length === 0" class="p-10 text-center opacity-30">
                                <Users class="w-8 h-8 mx-auto mb-2" />
                                <p class="text-[10px] font-bold uppercase tracking-widest">Nenhum paciente</p>
                            </div>
                        </div>
                    </div>

                    <!-- Histórico Recente -->
                    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200/60 flex flex-col h-1/2 overflow-hidden">
                        <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                            <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                                <History class="w-4 h-4 text-amber-600" /> Histórico do Paciente
                            </h2>
                        </div>
                        
                        <div class="flex-grow overflow-y-auto p-4 space-y-3 custom-scrollbar">
                            <template v-if="selectedPaciente">
                                <div v-for="h in patientHistory" :key="h.Id" class="p-3 border-l-2 border-slate-200 hover:border-blue-500 bg-slate-50/50 rounded-r-xl transition-all">
                                    <div class="flex justify-between items-start mb-1">
                                        <p class="text-[10px] font-black text-slate-700 uppercase">{{ h.Consulta }}</p>
                                        <p class="text-[9px] font-bold text-slate-400">{{ h.DataAgendamento }}</p>
                                    </div>
                                    <p class="text-[10px] text-slate-500 line-clamp-2">{{ h.QP || 'Sem queixas registradas' }}</p>
                                </div>
                                <div v-if="patientHistory.length === 0" class="p-10 text-center opacity-30">
                                    <p class="text-[10px] font-bold uppercase tracking-widest">Sem histórico prévio</p>
                                </div>
                            </template>
                            <div v-else class="p-10 text-center opacity-30">
                                <Info class="w-8 h-8 mx-auto mb-2" />
                                <p class="text-[10px] font-bold uppercase tracking-widest">Selecione um paciente</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Area -->
                <div class="lg:col-span-9 flex flex-col gap-6 overflow-hidden">
                    
                    <template v-if="selectedPaciente">
                        <!-- Patient Header & Triage Stats -->
                        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 shrink-0">
                            <!-- Info Card -->
                            <div class="xl:col-span-7 bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-200/60 flex items-center gap-6">
                                <div class="w-20 h-20 bg-blue-600 rounded-3xl text-white flex items-center justify-center text-3xl font-black shadow-lg shadow-blue-200">
                                    {{ selectedPaciente.PacienteNome.substring(0, 2).toUpperCase() }}
                                </div>
                                <div class="flex-grow">
                                    <div class="flex items-center gap-3">
                                        <h1 class="text-xl font-black text-slate-900 uppercase tracking-tight">{{ selectedPaciente.PacienteNome }}</h1>
                                        <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-lg text-[9px] font-black uppercase tracking-widest">PARTICULAR</span>
                                    </div>
                                    <div class="flex items-center gap-4 mt-2">
                                        <div class="flex items-center gap-2 text-xs font-bold text-slate-400">
                                            <Calendar class="w-3.5 h-3.5" /> {{ calcularIdade(selectedPaciente.DataNascimento) }}
                                        </div>
                                        <div class="flex items-center gap-2 text-xs font-bold text-slate-400">
                                            <User class="w-3.5 h-3.5" /> {{ selectedPaciente.Genero }}
                                        </div>
                                        <div class="flex items-center gap-2 text-xs font-bold text-slate-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-200"></span>
                                            Processo: {{ selectedPaciente.Codigo }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Triage Stats Bento -->
                            <div class="xl:col-span-5 grid grid-cols-3 gap-3">
                                <div class="bg-emerald-50/50 p-4 rounded-2xl border border-emerald-100/50 flex flex-col justify-center">
                                    <div class="flex items-center gap-2 text-[9px] font-black text-emerald-600 uppercase tracking-widest mb-1">
                                        <Weight class="w-3 h-3" /> Peso
                                    </div>
                                    <p class="text-lg font-black text-slate-800">{{ triageData?.Peso || '--' }} <span class="text-[10px] text-slate-400">kg</span></p>
                                </div>
                                <div class="bg-amber-50/50 p-4 rounded-2xl border border-amber-100/50 flex flex-col justify-center">
                                    <div class="flex items-center gap-2 text-[9px] font-black text-amber-600 uppercase tracking-widest mb-1">
                                        <Thermometer class="w-3 h-3" /> Temp
                                    </div>
                                    <p class="text-lg font-black text-slate-800">{{ triageData?.Temperatura || '--' }} <span class="text-[10px] text-slate-400">°C</span></p>
                                </div>
                                <div class="bg-rose-50/50 p-4 rounded-2xl border border-rose-100/50 flex flex-col justify-center">
                                    <div class="flex items-center gap-2 text-[9px] font-black text-rose-600 uppercase tracking-widest mb-1">
                                        <HeartPulse class="w-3 h-3" /> Pressão
                                    </div>
                                    <p class="text-sm font-black text-slate-800">{{ triageData?.PressaoArterial || '--' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Clinical Tabs & Forms -->
                        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200/60 flex flex-col flex-grow overflow-hidden">
                            <!-- Tab Header -->
                            <div class="px-8 pt-6 flex items-center gap-8 border-b border-slate-100">
                                <button @click="activeTab = 'clinical'" :class="activeTab === 'clinical' ? 'text-blue-600 border-blue-600' : 'text-slate-400 border-transparent'" class="pb-4 px-2 text-[11px] font-black uppercase tracking-widest border-b-2 transition-all flex items-center gap-2">
                                    <ClipboardList class="w-4 h-4" /> Dados Clínicos
                                </button>
                                <button @click="activeTab = 'exams'" :class="activeTab === 'exams' ? 'text-blue-600 border-blue-600' : 'text-slate-400 border-transparent'" class="pb-4 px-2 text-[11px] font-black uppercase tracking-widest border-b-2 transition-all flex items-center gap-2">
                                    <Beaker class="w-4 h-4" /> Solicitar Exames
                                </button>
                                <button @click="activeTab = 'prescription'" :class="activeTab === 'prescription' ? 'text-blue-600 border-blue-600' : 'text-slate-400 border-transparent'" class="pb-4 px-2 text-[11px] font-black uppercase tracking-widest border-b-2 transition-all flex items-center gap-2">
                                    <Pill class="w-4 h-4" /> Receita Médica
                                </button>
                            </div>

                            <div class="flex-grow overflow-y-auto p-8 custom-scrollbar">
                                
                                <!-- Tab: Clinical Data -->
                                <div v-if="activeTab === 'clinical'" class="grid grid-cols-1 md:grid-cols-2 gap-8 animate-fadeIn">
                                    <div class="space-y-6">
                                        <div>
                                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 mb-2 block">Queixas Principais (QP)</label>
                                            <textarea v-model="form.qp" rows="3" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl px-5 py-4 text-sm font-medium transition-all" placeholder="O que o paciente sente?"></textarea>
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 mb-2 block">História da Doença Atual (HDA)</label>
                                            <textarea v-model="form.hda" rows="4" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl px-5 py-4 text-sm font-medium transition-all" placeholder="Evolução dos sintomas..."></textarea>
                                        </div>
                                    </div>
                                    <div class="space-y-6">
                                        <div>
                                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 mb-2 block">Exame Objectivo (OBJ)</label>
                                            <textarea v-model="form.obj" rows="3" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl px-5 py-4 text-sm font-medium transition-all" placeholder="Sinais físicos observados..."></textarea>
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 mb-2 block">Hipótese de Diagnóstico / Complementares</label>
                                            <textarea v-model="form.complementares" rows="4" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl px-5 py-4 text-sm font-medium transition-all" placeholder="Possíveis causas e diagnósticos..."></textarea>
                                        </div>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 mb-2 block">Recomendações e Observações</label>
                                        <textarea v-model="form.recomendacoes" rows="3" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl px-5 py-4 text-sm font-medium transition-all" placeholder="Conselhos médicos e orientações..."></textarea>
                                    </div>
                                </div>

                                <!-- Tab: Exams -->
                                <div v-if="activeTab === 'exams'" class="space-y-8 animate-fadeIn">
                                    <div class="flex items-center justify-between bg-slate-50 p-6 rounded-3xl border border-dashed border-slate-200">
                                        <div class="flex items-center gap-4">
                                            <div class="p-3 bg-white rounded-2xl shadow-sm text-blue-600">
                                                <Beaker class="w-6 h-6" />
                                            </div>
                                            <div>
                                                <h3 class="text-sm font-black text-slate-800 uppercase">Solicitação de Exames</h3>
                                                <p class="text-[11px] text-slate-500 font-bold">Laboratório, Imagem e Especialidades</p>
                                            </div>
                                        </div>
                                        <button class="bg-blue-600 text-white px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-700 transition-all flex items-center gap-2">
                                            <Plus class="w-4 h-4" /> Adicionar Exame
                                        </button>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div v-for="i in 3" :key="i" class="p-5 bg-white border border-slate-100 rounded-[2rem] shadow-sm flex items-center justify-between group hover:border-blue-200 transition-all">
                                            <div class="flex items-center gap-3">
                                                <div class="w-2 h-2 rounded-full bg-blue-400"></div>
                                                <p class="text-xs font-black text-slate-700 uppercase">Hemograma Completo</p>
                                            </div>
                                            <button class="opacity-0 group-hover:opacity-100 p-2 text-slate-300 hover:text-red-500 transition-all">
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab: Prescription -->
                                <div v-if="activeTab === 'prescription'" class="space-y-8 animate-fadeIn">
                                    <div class="flex items-center justify-between bg-slate-50 p-6 rounded-3xl border border-dashed border-slate-200">
                                        <div class="flex items-center gap-4">
                                            <div class="p-3 bg-white rounded-2xl shadow-sm text-amber-600">
                                                <Pill class="w-6 h-6" />
                                            </div>
                                            <div>
                                                <h3 class="text-sm font-black text-slate-800 uppercase">Receituário Médico</h3>
                                                <p class="text-[11px] text-slate-500 font-bold">Prescrição de fármacos e posologia</p>
                                            </div>
                                        </div>
                                        <button class="bg-amber-600 text-white px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-amber-700 transition-all flex items-center gap-2">
                                            <Plus class="w-4 h-4" /> Adicionar Fármaco
                                        </button>
                                    </div>

                                    <table class="w-full border-separate border-spacing-y-3">
                                        <thead>
                                            <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest text-left">
                                                <th class="px-6 pb-2">Fármaco</th>
                                                <th class="px-6 pb-2">Dosagem / Posologia</th>
                                                <th class="px-6 pb-2">Qtd</th>
                                                <th class="px-6 pb-2 text-right">Ação</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="i in 2" :key="i" class="bg-slate-50/50 hover:bg-slate-50 transition-all rounded-2xl group">
                                                <td class="px-6 py-4 rounded-l-2xl">
                                                    <p class="text-xs font-black text-slate-700 uppercase">Paracetamol 500mg</p>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <p class="text-[11px] font-bold text-slate-500 italic">Tomar 1 comprimido a cada 8 horas por 3 dias</p>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="text-xs font-black text-slate-700">1 CX</span>
                                                </td>
                                                <td class="px-6 py-4 text-right rounded-r-2xl">
                                                    <button class="p-2 text-slate-300 hover:text-red-500 transition-all">
                                                        <Trash2 class="w-4 h-4" />
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Footer Actions -->
                            <div class="px-8 py-6 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <button class="flex items-center gap-2 px-6 py-3 bg-white border border-slate-200 text-slate-600 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-100 transition-all">
                                        <Printer class="w-4 h-4" /> Imprimir Relatório
                                    </button>
                                    <button class="flex items-center gap-2 px-6 py-3 bg-white border border-slate-200 text-slate-600 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-100 transition-all">
                                        <FileText class="w-4 h-4" /> Imprimir Receita
                                    </button>
                                </div>
                                <div class="flex items-center gap-4">
                                    <select v-model="form.situacao" class="bg-white border-slate-200 focus:ring-blue-500 focus:border-blue-500 rounded-2xl text-[10px] font-black uppercase tracking-widest py-3 px-6 pr-10 appearance-none cursor-pointer">
                                        <option value="Finalizado">Finalizar Consulta</option>
                                        <option value="Laboratorio">Enviar para Lab</option>
                                        <option value="Reconsulta">Agendar Reconsulta</option>
                                        <option value="Internamento">Solicitar Internamento</option>
                                    </select>
                                    <button @click="salvarConsulta" :disabled="form.processing" class="flex items-center gap-3 px-10 py-3 bg-blue-600 text-white rounded-2xl text-[11px] font-black uppercase tracking-[0.2em] hover:bg-blue-700 transition-all shadow-xl shadow-blue-200 disabled:opacity-50">
                                        <Save class="w-5 h-5" /> GRAVAR DADOS
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Empty State -->
                    <div v-else class="flex-grow flex flex-col items-center justify-center bg-white rounded-[3rem] border-2 border-dashed border-slate-100 p-12 text-center">
                        <div class="w-32 h-32 bg-slate-50 rounded-full flex items-center justify-center mb-8">
                            <Stethoscope class="w-12 h-12 text-slate-200" />
                        </div>
                        <h2 class="text-xl font-black text-slate-300 uppercase tracking-[0.3em]">Aguardando Atendimento</h2>
                        <p class="text-sm font-bold text-slate-400 mt-4 max-w-sm">Selecione um paciente na fila de espera lateral para iniciar o atendimento clínico e registro de dados.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification -->
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
                    <CheckCircle v-if="notification.type === 'success'" class="w-4 h-4 text-white" />
                    <AlertCircle v-else class="w-4 h-4 text-white" />
                </div>
                <p class="text-[10px] font-black uppercase tracking-widest">{{ notification.message }}</p>
            </div>
        </Transition>
    </DashboardLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
    height: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.animate-fadeIn {
    animation: fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

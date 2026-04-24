<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import axios from 'axios';
import { 
    Users, Search, Activity, History, ClipboardList, 
    Stethoscope, Beaker, Pill, Save, CheckCircle, 
    Printer, FileText, ArrowRight, User, Calendar,
    Thermometer, Weight, HeartPulse, Wind, Droplets,
    ChevronRight, Info, AlertCircle, Plus, Trash2,
    BedDouble, UserPlus, LogOut, Clock, MessageSquare
} from 'lucide-vue-next';

const props = defineProps({
    internados: Array,
    historico: Array
});

const searchTerm = ref('');
const selectedPaciente = ref(null);
const details = ref({ prescricoes: [], atosMedicos: [], atosEnfermagem: [], sinaisVitais: [] });
const activeTab = ref('prescriptions'); // prescriptions, medical_acts, nursing_acts, vitals, discharge
const isLoading = ref(false);

const filteredInternados = computed(() => {
    if (!searchTerm.value) return props.internados;
    const term = searchTerm.value.toLowerCase();
    return props.internados.filter(p => 
        p.PacienteNome.toLowerCase().includes(term) ||
        p.Codigo.toLowerCase().includes(term)
    );
});

const selecionarPaciente = async (paciente) => {
    selectedPaciente.value = paciente;
    isLoading.value = true;
    try {
        const response = await axios.get(route('hospitalar.internamento.details', paciente.Codigo));
        details.value = response.data;
    } catch (error) {
        console.error('Erro ao carregar detalhes:', error);
    } finally {
        isLoading.value = false;
    }
};

const atoForm = useForm({
    IdAgenda: '',
    tipo: 'medico',
    descricao: '',
});

const registrarAto = (tipo) => {
    atoForm.IdAgenda = selectedPaciente.value.Codigo;
    atoForm.tipo = tipo;
    atoForm.post(route('hospitalar.internamento.ato'), {
        onSuccess: () => {
            atoForm.descricao = '';
            selecionarPaciente(selectedPaciente.value);
        }
    });
};

const darAlta = () => {
    if (confirm('Deseja confirmar a alta deste paciente?')) {
        router.post(route('hospitalar.internamento.alta', selectedPaciente.value.Codigo), {}, {
            onSuccess: () => {
                selectedPaciente.value = null;
            }
        });
    }
};

const calcularPermanencia = (dataInternamento) => {
    if (!dataInternamento) return 'N/D';
    const start = new Date(dataInternamento);
    const end = new Date();
    const diff = Math.floor((end - start) / (1000 * 60 * 60 * 24));
    return diff + ' dias';
};
</script>

<template>
    <Head title="Internamento Hospitalar" />

    <DashboardLayout>
        <div class="min-h-screen bg-[#f8fafc] p-4 lg:p-6">
            <div class="max-w-[1800px] mx-auto grid grid-cols-1 lg:grid-cols-12 gap-6 h-[calc(100vh-120px)]">
                
                <!-- Left Sidebar: Interned Patients -->
                <div class="lg:col-span-3 flex flex-col gap-6 overflow-hidden">
                    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200/60 flex flex-col h-full overflow-hidden">
                        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                            <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-2 mb-6">
                                <BedDouble class="w-4 h-4 text-blue-600" /> Pacientes Internados
                            </h2>
                            <div class="relative">
                                <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                <input v-model="searchTerm" placeholder="Buscar internado..." class="w-full bg-white border-slate-100 focus:ring-4 focus:ring-blue-500/10 rounded-2xl pl-12 text-xs font-bold py-4 transition-all" />
                            </div>
                        </div>

                        <div class="flex-grow overflow-y-auto p-4 space-y-3 custom-scrollbar">
                            <div v-for="p in filteredInternados" :key="p.Id" 
                                 @click="selecionarPaciente(p)"
                                 :class="selectedPaciente?.Codigo === p.Codigo ? 'bg-blue-600 text-white shadow-xl shadow-blue-200' : 'bg-slate-50 hover:bg-slate-100 text-slate-700'"
                                 class="p-5 rounded-3xl cursor-pointer transition-all group relative overflow-hidden">
                                <div class="relative z-10">
                                    <p class="text-[11px] font-black uppercase leading-tight">{{ p.PacienteNome }}</p>
                                    <div class="flex items-center gap-3 mt-2">
                                        <p class="text-[9px] font-bold opacity-70 flex items-center gap-1">
                                            <Clock class="w-3 h-3" /> {{ p.DataInternamento ? p.DataInternamento.substring(0, 10) : 'N/D' }}
                                        </p>
                                        <p class="text-[9px] font-black bg-white/20 px-2 py-0.5 rounded-full uppercase tracking-tighter">
                                            {{ p.Codigo }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-if="filteredInternados.length === 0" class="p-12 text-center opacity-30">
                                <BedDouble class="w-12 h-12 mx-auto mb-4" />
                                <p class="text-xs font-black uppercase tracking-widest">Nenhum internado</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content: Management Area -->
                <div class="lg:col-span-9 flex flex-col gap-6 overflow-hidden">
                    <template v-if="selectedPaciente">
                        <!-- Header Bento -->
                        <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-200/60 flex items-center justify-between shrink-0">
                            <div class="flex items-center gap-6">
                                <div class="w-16 h-16 bg-blue-600 rounded-3xl text-white flex items-center justify-center text-2xl font-black shadow-lg shadow-blue-200">
                                    {{ selectedPaciente.PacienteNome.substring(0, 2).toUpperCase() }}
                                </div>
                                <div>
                                    <h1 class="text-xl font-black text-slate-900 uppercase tracking-tight">{{ selectedPaciente.PacienteNome }}</h1>
                                    <div class="flex items-center gap-4 mt-1">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1">
                                            <Calendar class="w-3.5 h-3.5" /> Internado em: {{ selectedPaciente.DataInternamento || 'N/D' }}
                                        </span>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1">
                                            <Clock class="w-3.5 h-3.5" /> Permanência: {{ calcularPermanencia(selectedPaciente.DataInternamento) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <button class="flex items-center gap-2 px-6 py-3 bg-slate-50 text-slate-600 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-100 transition-all border border-slate-100">
                                    <Printer class="w-4 h-4" /> Processo Clínico
                                </button>
                                <button @click="darAlta" class="flex items-center gap-2 px-8 py-3 bg-emerald-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-200">
                                    <LogOut class="w-4 h-4" /> Registrar Alta
                                </button>
                            </div>
                        </div>

                        <!-- Tabs & Dynamic Content -->
                        <div class="bg-white rounded-[3rem] shadow-sm border border-slate-200/60 flex flex-col flex-grow overflow-hidden">
                            <!-- Tab Navigation -->
                            <div class="px-8 pt-6 flex items-center gap-8 border-b border-slate-50">
                                <button @click="activeTab = 'prescriptions'" :class="activeTab === 'prescriptions' ? 'text-blue-600 border-blue-600' : 'text-slate-400 border-transparent'" class="pb-5 px-2 text-[10px] font-black uppercase tracking-widest border-b-2 transition-all flex items-center gap-2">
                                    <Pill class="w-4 h-4" /> Prescrições
                                </button>
                                <button @click="activeTab = 'medical_acts'" :class="activeTab === 'medical_acts' ? 'text-blue-600 border-blue-600' : 'text-slate-400 border-transparent'" class="pb-5 px-2 text-[10px] font-black uppercase tracking-widest border-b-2 transition-all flex items-center gap-2">
                                    <Stethoscope class="w-4 h-4" /> Atos Médicos
                                </button>
                                <button @click="activeTab = 'nursing_acts'" :class="activeTab === 'nursing_acts' ? 'text-blue-600 border-blue-600' : 'text-slate-400 border-transparent'" class="pb-5 px-2 text-[10px] font-black uppercase tracking-widest border-b-2 transition-all flex items-center gap-2">
                                    <Activity class="w-4 h-4" /> Enfermagem
                                </button>
                                <button @click="activeTab = 'vitals'" :class="activeTab === 'vitals' ? 'text-blue-600 border-blue-600' : 'text-slate-400 border-transparent'" class="pb-5 px-2 text-[10px] font-black uppercase tracking-widest border-b-2 transition-all flex items-center gap-2">
                                    <HeartPulse class="w-4 h-4" /> Sinais Vitais
                                </button>
                            </div>

                            <div class="flex-grow overflow-y-auto p-8 custom-scrollbar">
                                <!-- Tab: Prescriptions -->
                                <div v-if="activeTab === 'prescriptions'" class="space-y-6 animate-fadeIn">
                                    <div v-for="p in details.prescricoes" :key="p.Id" class="p-6 bg-slate-50 rounded-3xl border border-slate-100 relative group">
                                        <div class="flex justify-between items-start mb-4">
                                            <div class="flex items-center gap-3">
                                                <span class="p-2 bg-blue-100 text-blue-600 rounded-xl">
                                                    <Pill class="w-4 h-4" />
                                                </span>
                                                <h4 class="text-xs font-black text-slate-800 uppercase">Prescrição #{{ p.Id }}</h4>
                                            </div>
                                            <span class="text-[9px] font-black text-slate-400 uppercase">{{ p.CREATED_AT }}</span>
                                        </div>
                                        <p class="text-sm text-slate-600 font-medium leading-relaxed">{{ p.Descricao }}</p>
                                        <div v-if="p.Observacao" class="mt-4 p-4 bg-white/60 rounded-2xl text-[11px] text-slate-500 italic">
                                            {{ p.Observacao }}
                                        </div>
                                    </div>
                                    <div v-if="details.prescricoes.length === 0" class="p-20 text-center opacity-30">
                                        <Pill class="w-12 h-12 mx-auto mb-4" />
                                        <p class="text-xs font-black uppercase tracking-widest">Nenhuma prescrição ativa</p>
                                    </div>
                                </div>

                                <!-- Tab: Medical/Nursing Acts -->
                                <div v-if="activeTab === 'medical_acts' || activeTab === 'nursing_acts'" class="flex flex-col h-full animate-fadeIn">
                                    <!-- History -->
                                    <div class="flex-grow space-y-4 mb-8">
                                        <div v-for="a in (activeTab === 'medical_acts' ? details.atosMedicos : details.atosEnfermagem)" :key="a.Id" class="p-5 bg-white border border-slate-100 rounded-3xl shadow-sm flex gap-4">
                                            <div class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center shrink-0">
                                                <User class="w-5 h-5 text-slate-400" />
                                            </div>
                                            <div class="flex-grow">
                                                <div class="flex justify-between items-start mb-1">
                                                    <p class="text-[10px] font-black text-slate-800 uppercase">{{ activeTab === 'medical_acts' ? a.Medico : a.Enfermeiro }}</p>
                                                    <p class="text-[9px] font-bold text-slate-400">{{ a.DataAto }}</p>
                                                </div>
                                                <p class="text-xs text-slate-600 leading-relaxed">{{ a.Descricao }}</p>
                                            </div>
                                        </div>
                                        <div v-if="(activeTab === 'medical_acts' ? details.atosMedicos : details.atosEnfermagem).length === 0" class="p-20 text-center opacity-30">
                                            <MessageSquare class="w-12 h-12 mx-auto mb-4" />
                                            <p class="text-xs font-black uppercase tracking-widest">Nenhum registro encontrado</p>
                                        </div>
                                    </div>

                                    <!-- Entry Form -->
                                    <div class="mt-auto p-6 bg-slate-50 rounded-[2.5rem] border border-slate-200/60">
                                        <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 ml-1">Novo Registro</h4>
                                        <div class="flex gap-4">
                                            <textarea v-model="atoForm.descricao" rows="2" class="flex-grow bg-white border-transparent focus:ring-4 focus:ring-blue-500/10 rounded-2xl px-6 py-4 text-sm font-medium transition-all" :placeholder="'Descreva o ato ' + (activeTab === 'medical_acts' ? 'médico...' : 'de enfermagem...')"></textarea>
                                            <button @click="registrarAto(activeTab === 'medical_acts' ? 'medico' : 'enfermagem')" class="bg-slate-900 text-white px-8 rounded-2xl hover:bg-slate-800 transition-all">
                                                <Save class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab: Vitals -->
                                <div v-if="activeTab === 'vitals'" class="space-y-8 animate-fadeIn">
                                    <div v-for="v in details.sinaisVitais" :key="v.Id" class="p-8 bg-slate-50 rounded-[2.5rem] border border-slate-100">
                                        <div class="flex justify-between items-center mb-6">
                                            <div class="flex items-center gap-3">
                                                <div class="p-2 bg-rose-100 text-rose-600 rounded-xl">
                                                    <HeartPulse class="w-4 h-4" />
                                                </div>
                                                <h4 class="text-[10px] font-black text-slate-800 uppercase tracking-widest">Medição em {{ v.DataAgendamento || v.CREATED_AT }}</h4>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                                            <div class="bg-white p-4 rounded-2xl shadow-sm flex flex-col items-center">
                                                <Thermometer class="w-4 h-4 text-amber-500 mb-2" />
                                                <p class="text-xs font-black text-slate-400 uppercase tracking-tighter">Temperatura</p>
                                                <p class="text-lg font-black text-slate-800">{{ v.Temperatura || '--' }} <span class="text-[10px]">°C</span></p>
                                            </div>
                                            <div class="bg-white p-4 rounded-2xl shadow-sm flex flex-col items-center">
                                                <Weight class="w-4 h-4 text-emerald-500 mb-2" />
                                                <p class="text-xs font-black text-slate-400 uppercase tracking-tighter">Peso</p>
                                                <p class="text-lg font-black text-slate-800">{{ v.Peso || '--' }} <span class="text-[10px]">kg</span></p>
                                            </div>
                                            <div class="bg-white p-4 rounded-2xl shadow-sm flex flex-col items-center">
                                                <HeartPulse class="w-4 h-4 text-rose-500 mb-2" />
                                                <p class="text-xs font-black text-slate-400 uppercase tracking-tighter">Pressão Art.</p>
                                                <p class="text-lg font-black text-slate-800">{{ v.PressaoArterial || '--' }}</p>
                                            </div>
                                            <div class="bg-white p-4 rounded-2xl shadow-sm flex flex-col items-center">
                                                <Droplets class="w-4 h-4 text-blue-500 mb-2" />
                                                <p class="text-xs font-black text-slate-400 uppercase tracking-tighter">Frequência</p>
                                                <p class="text-lg font-black text-slate-800">{{ v.FrequenciaCardiaca || '--' }} <span class="text-[10px]">bpm</span></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="details.sinaisVitais.length === 0" class="p-20 text-center opacity-30">
                                        <Activity class="w-12 h-12 mx-auto mb-4" />
                                        <p class="text-xs font-black uppercase tracking-widest">Sem registros de sinais vitais</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Empty State -->
                    <div v-else class="flex-grow flex flex-col items-center justify-center bg-white rounded-[3rem] border-2 border-dashed border-slate-100 p-12 text-center">
                        <div class="w-32 h-32 bg-slate-50 rounded-full flex items-center justify-center mb-8">
                            <BedDouble class="w-12 h-12 text-slate-200" />
                        </div>
                        <h2 class="text-xl font-black text-slate-300 uppercase tracking-[0.3em]">Gestão de Internamento</h2>
                        <p class="text-sm font-bold text-slate-400 mt-4 max-w-sm">Selecione um paciente internado na lista lateral para visualizar prescrições, registrar atos médicos e gerenciar a alta.</p>
                    </div>
                </div>
            </div>
        </div>
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

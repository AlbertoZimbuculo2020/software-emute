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
    ScanLine, Monitor, Bone, Radio, Camera,
    UserCircle, Phone, MapPin, Zap
} from 'lucide-vue-next';

const props = defineProps({
    aguardando: Array
});

const searchTerm = ref('');
const selectedPaciente = ref(null);
const details = ref({ exames: [], historico: [], paciente: {} });
const selectedExame = ref(null);
const resultadoText = ref('');
const isLoading = ref(false);

const filteredAguardando = computed(() => {
    if (!searchTerm.value) return props.aguardando;
    const term = searchTerm.value.toLowerCase();
    return props.aguardando.filter(a => 
        a.PacienteNome.toLowerCase().includes(term) ||
        a.Codigo.toLowerCase().includes(term)
    );
});

const selecionarPaciente = async (paciente) => {
    selectedPaciente.value = paciente;
    isLoading.value = true;
    selectedExame.value = null;
    resultadoText.value = '';

    try {
        const response = await axios.get(route('hospitalar.raiox.details', paciente.Codigo));
        details.value = response.data;
    } catch (error) {
        console.error('Erro ao carregar detalhes:', error);
    } finally {
        isLoading.value = false;
    }
};

const selecionarExame = (exame) => {
    selectedExame.value = exame;
    resultadoText.value = exame.Resultado || '';
};

const salvarResultado = () => {
    if (!selectedExame.value) return;

    router.post(route('hospitalar.raiox.resultado'), {
        idExame: selectedExame.value.Id,
        resultado: resultadoText.value
    }, {
        onSuccess: () => {
            selecionarPaciente(selectedPaciente.value);
        }
    });
};

const finalizarRaioX = () => {
    if (confirm('Deseja finalizar o atendimento de Raio-X para este paciente?')) {
        router.post(route('hospitalar.raiox.finalizar', selectedPaciente.value.Codigo), {}, {
            onSuccess: () => {
                selectedPaciente.value = null;
                details.value = { exames: [], historico: [], paciente: {} };
            }
        });
    }
};

const calcularIdade = (nascimento) => {
    if (!nascimento) return 'N/D';
    const birthDate = new Date(nascimento);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) age--;
    return age + ' anos';
};
</script>

<template>
    <Head title="Raio-X / Imagiologia" />

    <DashboardLayout>
        <div class="min-h-screen bg-[#0f172a] p-4 lg:p-6 text-slate-300">
            <div class="max-w-[1800px] mx-auto grid grid-cols-1 lg:grid-cols-12 gap-6 h-[calc(100vh-120px)]">
                
                <!-- Left Sidebar: Waiting List -->
                <div class="lg:col-span-3 flex flex-col gap-6 overflow-hidden">
                    <div class="bg-slate-900/50 rounded-[2rem] shadow-2xl border border-slate-800 flex flex-col h-full overflow-hidden backdrop-blur-xl">
                        <div class="p-6 border-b border-slate-800 bg-slate-800/30">
                            <h2 class="text-xs font-black text-blue-400 uppercase tracking-widest flex items-center gap-2 mb-6">
                                <Radio class="w-4 h-4" /> Lista de Espera
                            </h2>
                            <div class="relative">
                                <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
                                <input v-model="searchTerm" placeholder="Buscar paciente..." class="w-full bg-slate-950 border-slate-800 focus:ring-4 focus:ring-blue-500/20 rounded-2xl pl-12 text-xs font-bold py-4 transition-all text-white" />
                            </div>
                        </div>

                        <div class="flex-grow overflow-y-auto p-4 space-y-3 custom-scrollbar">
                            <div v-for="a in filteredAguardando" :key="a.Id" 
                                 @click="selecionarPaciente(a)"
                                 :class="selectedPaciente?.Codigo === a.Codigo ? 'bg-blue-600 text-white shadow-xl shadow-blue-500/20' : 'bg-slate-800/50 hover:bg-slate-800 text-slate-300'"
                                 class="p-5 rounded-3xl cursor-pointer transition-all group relative overflow-hidden border border-transparent hover:border-slate-700">
                                <div class="relative z-10">
                                    <p class="text-[11px] font-black uppercase leading-tight">{{ a.PacienteNome }}</p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span class="text-[9px] font-bold opacity-60">{{ a.DataAgendamento }}</span>
                                        <span class="text-[8px] font-black bg-white/10 px-2 py-0.5 rounded-full uppercase tracking-tighter">{{ a.Codigo }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-if="filteredAguardando.length === 0" class="p-12 text-center opacity-30">
                                <Monitor class="w-12 h-12 mx-auto mb-4" />
                                <p class="text-xs font-black uppercase tracking-widest leading-relaxed">Nenhum paciente aguardando</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Area -->
                <div class="lg:col-span-9 flex flex-col gap-6 overflow-hidden">
                    <template v-if="selectedPaciente">
                        <!-- Upper Detail Bento -->
                        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 shrink-0">
                            <!-- Patient Info -->
                            <div class="xl:col-span-12 bg-slate-900/80 p-8 rounded-[2.5rem] shadow-2xl border border-slate-800 grid grid-cols-1 md:grid-cols-4 gap-8 backdrop-blur-md">
                                <div class="flex items-center gap-6 md:col-span-2">
                                    <div class="w-20 h-20 bg-blue-600 rounded-[2rem] text-white flex items-center justify-center text-3xl font-black shadow-xl shadow-blue-500/20">
                                        {{ details.paciente.PacienteNome?.substring(0, 2).toUpperCase() }}
                                    </div>
                                    <div>
                                        <h1 class="text-xl font-black text-white uppercase tracking-tight">{{ details.paciente.PacienteNome }}</h1>
                                        <div class="flex flex-wrap items-center gap-4 mt-2">
                                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest flex items-center gap-1.5">
                                                <Calendar class="w-3.5 h-3.5 text-blue-400" /> {{ details.paciente.DataNascimento }} ({{ calcularIdade(details.paciente.DataNascimento) }})
                                            </span>
                                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest flex items-center gap-1.5">
                                                <UserCircle class="w-3.5 h-3.5 text-blue-400" /> {{ details.paciente.Genero }}
                                            </span>
                                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest flex items-center gap-1.5">
                                                <Phone class="w-3.5 h-3.5 text-blue-400" /> {{ details.paciente.Telefone || 'N/D' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-[10px] font-black text-slate-600 uppercase tracking-widest flex items-center gap-2">
                                        <MapPin class="w-3.5 h-3.5 text-blue-400" /> Morada
                                    </p>
                                    <p class="text-xs font-bold text-slate-400 truncate">{{ details.paciente.Morada || 'N/D' }}</p>
                                </div>
                                <div class="flex items-center justify-end">
                                    <div class="w-16 h-16 bg-slate-950 rounded-2xl flex items-center justify-center border border-slate-800 text-blue-500 shadow-inner">
                                        <Zap class="w-8 h-8 animate-pulse" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Main Workflow Bento -->
                        <div class="flex-grow grid grid-cols-1 xl:grid-cols-12 gap-6 overflow-hidden">
                            <!-- Left Section: Requested Exams -->
                            <div class="xl:col-span-4 bg-slate-900/50 rounded-[2.5rem] shadow-2xl border border-slate-800 flex flex-col overflow-hidden backdrop-blur-xl">
                                <div class="p-6 border-b border-slate-800 bg-slate-800/30 flex items-center justify-between">
                                    <h3 class="text-[10px] font-black text-blue-400 uppercase tracking-[0.2em] flex items-center gap-2">
                                        <Bone class="w-4 h-4" /> Solicitações de Imagem
                                    </h3>
                                </div>
                                <div class="flex-grow overflow-y-auto p-4 space-y-2 custom-scrollbar">
                                    <div v-for="e in details.exames" :key="e.Id" 
                                         @click="selecionarExame(e)"
                                         :class="[
                                             selectedExame?.Id === e.Id ? 'bg-blue-600 text-white shadow-xl' : 'bg-slate-800/50 hover:bg-slate-800 text-slate-400',
                                             e.Estado === 'Finalizado' ? 'border-l-4 border-emerald-500' : 'border-l-4 border-blue-500'
                                         ]"
                                         class="p-5 rounded-3xl cursor-pointer transition-all flex items-center justify-between group border border-transparent">
                                        <div>
                                            <p class="text-[11px] font-black uppercase">{{ e.Descricao }}</p>
                                            <p class="text-[9px] font-bold opacity-60 mt-1 uppercase">{{ e.Categoria }}</p>
                                        </div>
                                        <CheckCircle v-if="e.Estado === 'Finalizado'" class="w-4 h-4 text-emerald-400" />
                                        <Camera v-else class="w-4 h-4 text-blue-400" />
                                    </div>
                                    
                                    <div v-if="details.exames.length === 0" class="p-12 text-center opacity-30">
                                        <Monitor class="w-12 h-12 mx-auto mb-4" />
                                        <p class="text-[10px] font-bold uppercase tracking-widest leading-relaxed">Nenhuma solicitação</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Section: Results & Report -->
                            <div class="xl:col-span-8 flex flex-col gap-6">
                                <!-- Results Entry -->
                                <div class="bg-slate-900/80 rounded-[2.5rem] shadow-2xl border border-slate-800 p-8 flex flex-col min-h-[300px] backdrop-blur-md">
                                    <div class="flex items-center justify-between mb-6">
                                        <h3 class="text-[10px] font-black text-blue-400 uppercase tracking-[0.2em] flex items-center gap-2">
                                            <FileText class="w-4 h-4" /> Laudo Radiológico
                                        </h3>
                                        <div v-if="selectedExame" class="bg-blue-500/10 text-blue-400 px-4 py-1 rounded-full text-[10px] font-black uppercase border border-blue-500/20">
                                            {{ selectedExame.Descricao }}
                                        </div>
                                    </div>
                                    
                                    <div v-if="selectedExame" class="flex flex-col flex-grow">
                                        <textarea v-model="resultadoText" 
                                                  class="flex-grow w-full bg-slate-950 border-slate-800 focus:bg-slate-900 focus:ring-4 focus:ring-blue-500/10 rounded-[2rem] p-8 text-sm font-medium transition-all resize-none mb-6 text-slate-300 border shadow-inner"
                                                  placeholder="Digite o relatório detalhado do exame de imagem..."></textarea>
                                        
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <button class="p-3 bg-slate-800 text-slate-400 rounded-2xl hover:bg-slate-700 transition-all border border-slate-700">
                                                    <Printer class="w-5 h-5" />
                                                </button>
                                                <button class="p-3 bg-slate-800 text-slate-400 rounded-2xl hover:bg-slate-700 transition-all border border-slate-700">
                                                    <Camera class="w-5 h-5" />
                                                </button>
                                            </div>
                                            <button @click="salvarResultado" class="bg-blue-600 text-white px-10 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-700 transition-all shadow-xl shadow-blue-500/20 flex items-center gap-2">
                                                <Save class="w-4 h-4" /> Registrar Laudo
                                            </button>
                                        </div>
                                    </div>
                                    <div v-else class="flex-grow flex flex-col items-center justify-center text-center opacity-30">
                                        <Radio class="w-16 h-16 mb-4 text-blue-400" />
                                        <p class="text-xs font-black uppercase tracking-widest">Selecione uma imagem ao lado</p>
                                    </div>
                                </div>

                                <!-- Action Bar -->
                                <div class="bg-blue-900/20 rounded-[2.5rem] p-4 border border-blue-500/10 flex">
                                    <button @click="finalizarRaioX" class="w-full bg-blue-600 text-white rounded-2xl text-xs font-black uppercase tracking-[0.3em] hover:bg-blue-700 transition-all flex items-center justify-center gap-4 py-4 shadow-xl shadow-blue-500/20">
                                        <CheckCircle class="w-6 h-6" /> FINALIZAR ATENDIMENTO
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Empty State -->
                    <div v-else class="flex-grow flex flex-col items-center justify-center bg-slate-900/50 rounded-[3rem] border-2 border-dashed border-slate-800 p-12 text-center backdrop-blur-md">
                        <div class="w-32 h-32 bg-slate-800 rounded-full flex items-center justify-center mb-8 border border-slate-700">
                            <ScanLine class="w-12 h-12 text-blue-400" />
                        </div>
                        <h2 class="text-xl font-black text-slate-700 uppercase tracking-[0.3em]">Imagiologia & Raio-X</h2>
                        <p class="text-sm font-bold text-slate-600 mt-4 max-w-sm leading-relaxed">Aguardando a seleção de um paciente da fila para início do processamento de imagens.</p>
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
    background: #1e293b;
    border-radius: 10px;
}
</style>

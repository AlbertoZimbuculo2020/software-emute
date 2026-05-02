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
    FlaskConical, Check, Box
} from 'lucide-vue-next';

const props = defineProps({
    solicitacoes: Array,
    depositos: Array,
    farmacos: Array
});

const searchTerm = ref('');
const selectedSolicitacao = ref(null);
const details = ref({ exames: [], prescricoes: [] });
const selectedExame = ref(null);
const resultadoText = ref('');
const selectedDeposito = ref('');
const isLoading = ref(false);

// Fármaco Modal
const isFarmacoModalOpen = ref(false);
const farmacoSearch = ref('');
const farmacoForm = useForm({
    IdAgenda: '',
    IdProduto: '',
    Farmaco: '',
    Dosagem: '',
    Dias: '',
});

const filteredFarmacos = computed(() => {
    if (!farmacoSearch.value) return props.farmacos;
    const term = farmacoSearch.value.toLowerCase();
    return props.farmacos.filter(f => f.DESCRICAO.toLowerCase().includes(term));
});

const openFarmacoModal = () => {
    farmacoForm.reset();
    farmacoForm.IdAgenda = selectedSolicitacao.value.Codigo;
    isFarmacoModalOpen.value = true;
};

const selecionarFarmaco = (f) => {
    farmacoForm.IdProduto = f.ID_ARTIGO;
    farmacoForm.Farmaco = f.DESCRICAO;
};

const adicionarFarmaco = () => {
    farmacoForm.post(route('hospitalar.enfermaria.farmaco'), {
        onSuccess: () => {
            isFarmacoModalOpen.value = false;
            selecionarSolicitacao(selectedSolicitacao.value);
        }
    });
};

const filteredSolicitacoes = computed(() => {
    if (!searchTerm.value) return props.solicitacoes;
    const term = searchTerm.value.toLowerCase();
    return props.solicitacoes.filter(s => 
        s.PacienteNome.toLowerCase().includes(term) ||
        s.Codigo.toLowerCase().includes(term)
    );
});

const selecionarSolicitacao = async (solicitacao) => {
    selectedSolicitacao.value = solicitacao;
    isLoading.value = true;
    selectedExame.value = null;
    resultadoText.value = '';

    try {
        const response = await axios.get(route('hospitalar.enfermaria.details', solicitacao.Codigo));
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

    router.post(route('hospitalar.enfermaria.resultado'), {
        idExame: selectedExame.value.Id,
        resultado: resultadoText.value
    }, {
        onSuccess: () => {
            selecionarSolicitacao(selectedSolicitacao.value);
        }
    });
};

const finalizarAtendimento = () => {
    if (!selectedSolicitacao.value) return;

    if (confirm('Deseja finalizar o atendimento para este paciente?')) {
        router.post(route('hospitalar.enfermaria.finalizar', selectedSolicitacao.value.Codigo), {}, {
            onSuccess: () => {
                selectedSolicitacao.value = null;
                details.value = { exames: [], prescricoes: [] };
            }
        });
    }
};
</script>

<template>
    <Head title="Enfermaria - Serviços Solicitados" />

    <DashboardLayout>
        <div class="min-h-screen bg-[#f8fafc] p-3 lg:p-6">
            <div class="max-w-[1800px] mx-auto grid grid-cols-1 lg:grid-cols-12 gap-6 lg:h-[calc(100vh-120px)]">
                
                <!-- Left Sidebar: Requests List -->
                <div class="lg:col-span-4 flex flex-col gap-6 overflow-hidden h-[500px] lg:h-full">
                    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200/60 flex flex-col h-full overflow-hidden">
                        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                            <div class="flex items-center justify-between mb-6">
                                <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                                    <ClipboardList class="w-4 h-4 text-blue-600" /> Serviços Solicitados
                                </h2>
                                <span class="bg-blue-600 text-white text-[10px] font-black px-3 py-1 rounded-full shadow-lg shadow-blue-200">
                                    {{ solicitacoes.length }} Pendentes
                                </span>
                            </div>
                            
                            <div class="relative">
                                <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                <input v-model="searchTerm" placeholder="Buscar por paciente ou código..." class="w-full bg-white border-slate-100 focus:ring-4 focus:ring-blue-500/10 rounded-2xl pl-12 text-xs font-bold py-4 transition-all" />
                            </div>
                        </div>

                        <div class="flex-grow overflow-y-auto p-4 space-y-3 custom-scrollbar">
                            <div v-for="s in filteredSolicitacoes" :key="s.Id" 
                                 @click="selecionarSolicitacao(s)"
                                 :class="selectedSolicitacao?.Codigo === s.Codigo ? 'bg-blue-600 text-white shadow-xl shadow-blue-200 ring-4 ring-blue-500/10' : 'bg-slate-50 hover:bg-slate-100 text-slate-700'"
                                 class="p-5 rounded-3xl cursor-pointer transition-all group relative overflow-hidden">
                                
                                <div class="flex items-center justify-between relative z-10">
                                    <div class="flex-grow">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-[9px] font-black uppercase tracking-tighter opacity-60">{{ s.Codigo }}</span>
                                            <span v-if="s.Situacao === 'Internado'" class="bg-amber-100 text-amber-600 text-[8px] font-black px-1.5 py-0.5 rounded uppercase">INTERNADO</span>
                                        </div>
                                        <p class="text-xs font-black uppercase leading-tight">{{ s.PacienteNome }}</p>
                                        <div class="flex items-center gap-4 mt-2">
                                            <p class="text-[9px] font-bold opacity-70 flex items-center gap-1">
                                                <Stethoscope class="w-3 h-3" /> {{ s.MedicoNome || 'N/D' }}
                                            </p>
                                            <p class="text-[9px] font-black bg-blue-500/20 px-2 py-0.5 rounded-full">
                                                {{ s.QTD_SERVICOS }} SERVIÇOS
                                            </p>
                                        </div>
                                    </div>
                                    <ChevronRight class="w-5 h-5 opacity-40 group-hover:translate-x-1 transition-transform" />
                                </div>
                            </div>

                            <div v-if="filteredSolicitacoes.length === 0" class="p-12 text-center opacity-30">
                                <Users class="w-12 h-12 mx-auto mb-4" />
                                <p class="text-xs font-black uppercase tracking-widest">Nenhuma solicitação</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Content Area -->
                <div class="lg:col-span-8 flex flex-col gap-6 overflow-hidden">
                    <template v-if="selectedSolicitacao">
                        <!-- Upper Detail Bento -->
                        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 shrink-0">
                            <!-- Patient Info -->
                            <div class="xl:col-span-12 bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-200/60 flex items-center justify-between">
                                <div class="flex flex-col sm:flex-row sm:items-center gap-6">
                                    <div class="w-16 h-16 bg-slate-900 rounded-3xl text-white flex items-center justify-center text-2xl font-black shrink-0">
                                        {{ selectedSolicitacao.PacienteNome.substring(0, 2).toUpperCase() }}
                                    </div>
                                    <div class="flex-grow">
                                        <h1 class="text-lg font-black text-slate-900 uppercase tracking-tight">{{ selectedSolicitacao.PacienteNome }}</h1>
                                        <div class="flex flex-wrap items-center gap-4 mt-1">
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1">
                                                <Calendar class="w-3.5 h-3.5" /> {{ selectedSolicitacao.DataAgendamento }}
                                            </span>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1">
                                                <User class="w-3.5 h-3.5" /> {{ selectedSolicitacao.IdPaciente }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 mt-4 sm:mt-0 pt-4 sm:pt-0 border-t sm:border-t-0 border-slate-100">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Depósito:</label>
                                    <select v-model="selectedDeposito" class="w-full sm:w-auto bg-slate-50 border-transparent focus:ring-4 focus:ring-blue-500/10 rounded-2xl text-[10px] font-black uppercase tracking-widest py-3 px-6 pr-10 appearance-none cursor-pointer min-w-[180px]">
                                        <option v-for="d in depositos" :key="d.CODIGO" :value="d.CODIGO">{{ d.DEPOSITO }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Main Workflow Bento -->
                        <div class="flex-grow grid grid-cols-1 xl:grid-cols-12 gap-6 overflow-hidden h-auto lg:overflow-hidden">
                            <!-- Left Section: Services List -->
                            <div class="xl:col-span-5 bg-white rounded-[2.5rem] shadow-sm border border-slate-200/60 flex flex-col overflow-hidden min-h-[400px] lg:min-h-0">
                                <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                                    <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-[0.2em] flex items-center gap-2">
                                        <FlaskConical class="w-4 h-4 text-blue-600" /> Itens Solicitados
                                    </h3>
                                </div>
                                <div class="flex-grow overflow-y-auto p-4 space-y-2 custom-scrollbar">
                                    <div v-for="e in details.exames" :key="e.Id" 
                                         @click="selecionarExame(e)"
                                         :class="[
                                             selectedExame?.Id === e.Id ? 'bg-slate-900 text-white ring-4 ring-slate-200' : 'bg-slate-50 hover:bg-slate-100 text-slate-700',
                                             e.Estado === 'Finalizado' ? 'border-l-4 border-emerald-500' : 'border-l-4 border-amber-500'
                                         ]"
                                         class="p-4 rounded-2xl cursor-pointer transition-all flex items-center justify-between group">
                                        <div>
                                            <p class="text-[11px] font-black uppercase">{{ e.Descricao }}</p>
                                            <p class="text-[9px] font-bold opacity-60 mt-1 uppercase">{{ e.Categoria || 'Geral' }}</p>
                                        </div>
                                        <CheckCircle v-if="e.Estado === 'Finalizado'" class="w-4 h-4 text-emerald-500" />
                                        <Activity v-else class="w-4 h-4 text-amber-500 group-hover:animate-pulse" />
                                    </div>
                                    
                                    <div v-if="details.exames.length === 0" class="p-12 text-center opacity-30">
                                        <p class="text-[10px] font-bold uppercase tracking-widest">Nenhum exame</p>
                                    </div>
                                </div>
                                <div class="p-4 bg-slate-50 mt-auto">
                                    <button @click="openFarmacoModal" class="w-full bg-blue-600 text-white py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-700 transition-all shadow-lg shadow-blue-200 flex items-center justify-center gap-2">
                                        <Plus class="w-4 h-4" /> Adicionar Fármacos
                                    </button>
                                </div>
                            </div>

                            <!-- Right Section: Results & Finalization -->
                            <div class="xl:col-span-7 flex flex-col gap-6">
                                <!-- Prescriptions List (New Bento Section) -->
                                <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200/60 p-6 flex flex-col max-h-[300px]">
                                    <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-[0.2em] flex items-center gap-2 mb-4">
                                        <Pill class="w-4 h-4 text-amber-600" /> Fármacos Prescritos
                                    </h3>
                                    <div class="overflow-y-auto space-y-2 custom-scrollbar">
                                        <div v-for="p in details.prescricoes" :key="p.Id" class="p-3 bg-slate-50 rounded-xl flex items-center justify-between">
                                            <div>
                                                <p class="text-[10px] font-black text-slate-700 uppercase">{{ p.Farmaco }}</p>
                                                <p class="text-[9px] font-bold text-slate-400 uppercase mt-0.5">{{ p.Dosagem }} • {{ p.Dias }}</p>
                                            </div>
                                            <CheckCircle class="w-3.5 h-3.5 text-slate-300" />
                                        </div>
                                        <div v-if="details.prescricoes.length === 0" class="p-4 text-center opacity-30 italic text-[10px]">Nenhum fármaco registrado.</div>
                                    </div>
                                </div>

                                <!-- Results Entry -->
                                <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200/60 p-6 lg:p-8 flex flex-col flex-grow min-h-[300px]">
                                    <div class="flex items-center justify-between mb-6">
                                        <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-[0.2em] flex items-center gap-2">
                                            <History class="w-4 h-4 text-emerald-600" /> Registro de Resultado
                                        </h3>
                                        <span v-if="selectedExame" class="text-[10px] font-black text-blue-600 uppercase">{{ selectedExame.Descricao }}</span>
                                    </div>
                                    
                                    <div v-if="selectedExame" class="flex flex-col flex-grow">
                                        <textarea v-model="resultadoText" 
                                                  class="flex-grow w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-[2rem] p-8 text-sm font-medium transition-all resize-none"
                                                  placeholder="Digite aqui as observações ou resultado do procedimento..."></textarea>
                                        
                                        <div class="mt-6 flex items-center justify-end gap-4">
                                            <button @click="salvarResultado" class="bg-slate-900 text-white px-10 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-800 transition-all flex items-center gap-2">
                                                <Save class="w-4 h-4" /> Registrar Resultado
                                            </button>
                                        </div>
                                    </div>
                                    <div v-else class="flex-grow flex flex-col items-center justify-center text-center opacity-30">
                                        <Activity class="w-16 h-16 mb-4" />
                                        <p class="text-xs font-black uppercase tracking-widest">Selecione um item ao lado</p>
                                    </div>
                                </div>

                                <!-- Finalize Action -->
                                <div class="bg-emerald-600 rounded-[2rem] p-4 shadow-xl shadow-emerald-200">
                                    <button @click="finalizarAtendimento" class="w-full bg-white text-emerald-600 py-6 rounded-2xl text-xs font-black uppercase tracking-[0.3em] hover:bg-emerald-50 transition-all flex items-center justify-center gap-4">
                                        <CheckCircle class="w-6 h-6" /> FINALIZAR ATENDIMENTO
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Empty State -->
                    <div v-else class="flex-grow flex flex-col items-center justify-center bg-white rounded-[3rem] border-2 border-dashed border-slate-100 p-12 text-center">
                        <div class="w-32 h-32 bg-slate-50 rounded-full flex items-center justify-center mb-8">
                            <ClipboardList class="w-12 h-12 text-slate-200" />
                        </div>
                        <h2 class="text-xl font-black text-slate-300 uppercase tracking-[0.3em]">Enfermaria</h2>
                        <p class="text-sm font-bold text-slate-400 mt-4 max-w-sm">Selecione uma solicitação na lista lateral para processar exames, administrar fármacos e registrar resultados.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Adicionar Fármaco -->
        <div v-if="isFarmacoModalOpen" class="fixed inset-0 z-[2000] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fadeIn">
            <div class="bg-white w-full max-w-2xl rounded-[3rem] shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                <div class="p-8 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div>
                        <h2 class="text-lg font-black text-slate-800 uppercase tracking-tight">Administrar Fármaco</h2>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Selecione o produto e defina a posologia</p>
                    </div>
                    <button @click="isFarmacoModalOpen = false" class="p-3 hover:bg-white rounded-2xl transition-all">
                        <Plus class="w-6 h-6 text-slate-400 rotate-45" />
                    </button>
                </div>

                <div class="p-8 overflow-y-auto space-y-6 custom-scrollbar">
                    <div class="relative">
                        <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <input v-model="farmacoSearch" placeholder="Buscar fármaco no estoque..." class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl pl-12 py-4 text-xs font-bold transition-all" />
                    </div>

                    <div class="grid grid-cols-1 gap-2 max-h-[200px] overflow-y-auto custom-scrollbar pr-2">
                        <div v-for="f in filteredFarmacos" :key="f.ID_ARTIGO" 
                             @click="selecionarFarmaco(f)"
                             :class="farmacoForm.IdProduto === f.ID_ARTIGO ? 'bg-blue-600 text-white shadow-lg' : 'bg-slate-50 hover:bg-slate-100 text-slate-700'"
                             class="p-4 rounded-xl cursor-pointer transition-all flex items-center justify-between">
                            <span class="text-[11px] font-black uppercase">{{ f.DESCRICAO }}</span>
                            <span class="text-[9px] font-bold opacity-60 uppercase">{{ f.PV }} KZ</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6 pt-4 border-t border-slate-50">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Dosagem / Posologia</label>
                            <input v-model="farmacoForm.Dosagem" placeholder="Ex: 1 comp de 8/8h" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl px-6 py-4 text-sm font-black text-slate-700 transition-all uppercase" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Tempo / Dias</label>
                            <input v-model="farmacoForm.Dias" placeholder="Ex: 5 Dias" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl px-6 py-4 text-sm font-black text-slate-700 transition-all uppercase" />
                        </div>
                    </div>
                </div>

                <div class="p-8 bg-slate-50 flex items-center justify-end gap-4">
                    <button @click="isFarmacoModalOpen = false" class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-slate-600 transition-all">Cancelar</button>
                    <button @click="adicionarFarmaco" :disabled="!farmacoForm.IdProduto" class="bg-blue-600 text-white px-10 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-700 transition-all shadow-xl shadow-blue-200 disabled:opacity-50">Confirmar Administração</button>
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
</style>

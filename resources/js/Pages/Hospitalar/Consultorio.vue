<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import axios from 'axios';
import { 
    Users, Search, Activity, History, 
    Weight,
    Thermometer,
    HeartPulse,
    ClipboardList,
    Stethoscope,
    Pill,
    Printer,
    User,
    ChevronDown,
    Save,
    Info,
    ChevronRight,
    Plus,
    Trash2,
    X,
    CheckCircle,
    AlertCircle,
    FileText
} from 'lucide-vue-next';

const props = defineProps({
    aguardando: Array,
    catalogoExames: Array,
    empresa: Object
});

const searchTerm = ref('');
const selectedPaciente = ref(null);
const triageData = ref(null);
const patientHistory = ref([]);
const activeTab = ref('exams'); // clinical, exams, prescription
const isLoading = ref(false);

// Refs novos para Solicitar Exames
const activeExamFilter = ref('SOLICITADOS'); // SOLICITADOS, LABORATORIO, RAIOX, FORA
const showLancarResultadosModal = ref(false);
const lancarResultadoMode = ref('manual');
const searchExameTerm = ref('');
const selectedExameToLancar = ref(null);

const examesSolicitados = ref([]); 
const selectedExams = ref([]); 

const examesList = computed(() => {
    let result = [];
    if (activeExamFilter.value === 'SOLICITADOS') {
        result = examesSolicitados.value.map(e => ({
            id: 'sol_' + e.Id,
            codigo: e.CodExame,
            nome: e.Descricao,
            resultado: e.Resultado || 'Pendente',
            selected: false,
            isRequested: true
        }));
    } else if (activeExamFilter.value === 'LABORATORIO') {
        result = (props.catalogoExames || []).filter(e => e.Exame_Fora !== 'True' && e.Categoria !== 'IMAGEM' && e.Categoria !== 'RAIO X').map(e => ({
            id: 'cat_' + e.Id,
            codigo: e.Codigo,
            nome: e.Descricao,
            resultado: '',
            selected: false,
            isRequested: false
        }));
    } else if (activeExamFilter.value === 'RAIOX') {
        result = (props.catalogoExames || []).filter(e => e.Categoria === 'IMAGEM' || e.Categoria === 'RAIO X').map(e => ({
            id: 'cat_' + e.Id,
            codigo: e.Codigo,
            nome: e.Descricao,
            resultado: '',
            selected: false,
            isRequested: false
        }));
    } else if (activeExamFilter.value === 'FORA') {
        result = (props.catalogoExames || []).filter(e => e.Exame_Fora === 'True').map(e => ({
            id: 'cat_' + e.Id,
            codigo: e.Codigo,
            nome: e.Descricao,
            resultado: '',
            selected: false,
            isRequested: false
        }));
    }
    
    if (searchExameTerm.value) {
        result = result.filter(e => e.nome.toLowerCase().includes(searchExameTerm.value.toLowerCase()));
    }
    return result;
});

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

const openLancarModal = (exame) => {
    selectedExameToLancar.value = exame;
    showLancarResultadosModal.value = true;
};

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
        examesSolicitados.value = response.data.exames_solicitados || [];
        selectedExams.value = []; // Limpar seleções ao trocar de paciente
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

const calcularIdadeFormatoDesktop = (dataNascimento) => {
    if (!dataNascimento) return 'N/D';
    const birthDate = new Date(dataNascimento);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) age--;
    
    if (age === 0) {
        const months = (today.getFullYear() - birthDate.getFullYear()) * 12 + today.getMonth() - birthDate.getMonth();
        return months + ' Meses';
    }
    return age + ' Anos';
};

const examesParaImprimir = computed(() => {
    let selected = examesList.value.filter(e => selectedExams.value.includes(e.id));
    
    if (selected.length === 0) {
        if (!examesSolicitados.value || examesSolicitados.value.length === 0) return {};
        let grouped = {};
        examesSolicitados.value.forEach(e => {
            const cat = e.Categoria || 'GERAL';
            if(!grouped[cat]) grouped[cat] = [];
            grouped[cat].push({ Descricao: e.Descricao || e.ExameDescricao, Resultado: e.Resultado });
        });
        return grouped;
    }
    
    let grouped = {};
    selected.forEach(e => {
        let original = (props.catalogoExames || []).find(cat => cat.Codigo === e.codigo) || {};
        const catName = original.Categoria || 'GERAL';
        if(!grouped[catName]) grouped[catName] = [];
        grouped[catName].push({
            Descricao: e.nome,
            Resultado: e.resultado
        });
    });
    return grouped;
});

const imprimirRequisicao = () => {
    if(!selectedPaciente.value) {
        showNotification('Selecione um paciente primeiro!', 'error');
        return;
    }
    if(Object.keys(examesParaImprimir.value).length === 0) {
        showNotification('Nenhum exame selecionado para imprimir.', 'error');
        return;
    }
    window.print();
};

const toggleSelectAll = (event) => {
    const currentIds = examesList.value.map(e => e.id);
    if (event.target.checked) {
        const newSelection = new Set([...selectedExams.value, ...currentIds]);
        selectedExams.value = Array.from(newSelection);
    } else {
        selectedExams.value = selectedExams.value.filter(id => !currentIds.includes(id));
    }
};

const isAllSelected = computed(() => {
    if (examesList.value.length === 0) return false;
    return examesList.value.every(e => selectedExams.value.includes(e.id));
});

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
                <div class="lg:col-span-9 flex flex-col gap-6 overflow-y-auto custom-scrollbar pb-8 pr-2">
                    
                    <template v-if="selectedPaciente">
                        <!-- Patient Header & Triage Stats -->
                        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 shrink-0">
                            <div class="xl:col-span-7 bg-white shadow-sm border border-slate-200/80 rounded-lg flex flex-col shrink-0">
                                <div class="bg-slate-100 text-center py-2.5 border-b border-slate-200/80 font-bold text-slate-700 text-[13px]">
                                    Dados do Paciente
                                </div>
                                <div class="p-5 flex flex-col gap-4">
                                    <div class="flex items-center gap-4">
                                        <span class="w-24 text-sm font-medium text-slate-600 shrink-0">Código</span>
                                        <input type="text" :value="selectedPaciente.IdPaciente" readonly class="w-32 bg-white border border-slate-200 rounded text-sm text-slate-700 px-3 py-1.5 pointer-events-none focus:outline-none" />
                                        <span class="text-sm font-medium text-slate-600 shrink-0 ml-2">Nome</span>
                                        <input type="text" :value="selectedPaciente.PacienteNome" readonly class="flex-grow bg-white border border-slate-200 rounded text-sm text-slate-700 px-3 py-1.5 pointer-events-none focus:outline-none" />
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="w-24 text-sm font-medium text-slate-600 shrink-0 leading-tight">Data de<br>Nascimento</span>
                                        <input type="text" :value="selectedPaciente.DataNascimento ? new Date(selectedPaciente.DataNascimento).toLocaleString('pt-PT') : ''" readonly class="flex-grow bg-white border border-slate-200 rounded text-sm text-slate-700 px-3 py-1.5 pointer-events-none focus:outline-none" />
                                        <span class="w-12 text-sm font-medium text-slate-600 shrink-0 ml-2 text-right">Idade</span>
                                        <input type="text" :value="calcularIdadeFormatoDesktop(selectedPaciente.DataNascimento)" readonly class="w-32 bg-white border border-slate-200 rounded text-sm text-slate-700 px-3 py-1.5 pointer-events-none focus:outline-none" />
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="w-24 text-sm font-medium text-slate-600 shrink-0">Telefone</span>
                                        <input type="text" :value="selectedPaciente.Telefone" readonly class="flex-grow bg-white border border-slate-200 rounded text-sm text-slate-700 px-3 py-1.5 pointer-events-none focus:outline-none" />
                                        <span class="w-12 text-sm font-medium text-slate-600 shrink-0 ml-2 text-right">Sexo</span>
                                        <input type="text" :value="selectedPaciente.Genero" readonly class="w-32 bg-white border border-slate-200 rounded text-sm text-slate-700 px-3 py-1.5 pointer-events-none focus:outline-none" />
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="w-24 text-sm font-medium text-slate-600 shrink-0">Morada</span>
                                        <input type="text" :value="selectedPaciente.Rua || selectedPaciente.Cidade || ''" readonly class="flex-grow bg-white border border-slate-200 rounded text-sm text-slate-700 px-3 py-1.5 pointer-events-none focus:outline-none" />
                                    </div>
                                    <hr class="border-slate-100 my-1" />
                                    <div class="flex items-center gap-6 pl-1">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="radio" readonly :checked="!selectedPaciente.Seguradora" class="w-4 h-4 text-blue-600 border-slate-300 pointer-events-none focus:ring-0" />
                                            <span class="text-sm text-slate-700">Particular</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="radio" readonly :checked="!!selectedPaciente.Seguradora" class="w-4 h-4 text-blue-600 border-slate-300 pointer-events-none focus:ring-0" />
                                            <span class="text-sm text-slate-700">Assegurado</span>
                                        </label>
                                    </div>
                                    <div class="flex items-center gap-4 mt-1">
                                        <span class="w-24 text-sm font-medium text-slate-600 shrink-0">Asseguradora</span>
                                        <input type="text" :value="selectedPaciente.Seguradora || ''" readonly class="flex-grow bg-white border border-slate-200 rounded text-sm text-slate-700 px-3 py-1.5 pointer-events-none focus:outline-none" />
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="w-24 text-sm font-medium text-slate-600 shrink-0">Consulta</span>
                                        <input type="text" :value="selectedPaciente.Consulta" readonly class="flex-grow bg-white border border-slate-200 rounded text-sm text-slate-700 px-3 py-1.5 pointer-events-none focus:outline-none" />
                                        <span class="text-sm font-medium text-slate-600 shrink-0 ml-2">Médico</span>
                                        <input type="text" :value="selectedPaciente.MedicoNome" readonly class="flex-grow bg-white border border-slate-200 rounded text-sm text-slate-700 px-3 py-1.5 pointer-events-none focus:outline-none min-w-[200px]" />
                                    </div>
                                </div>
                            </div>

                            <div class="xl:col-span-5 grid grid-cols-3 gap-3">
                                <div class="bg-emerald-50/50 p-3 rounded-2xl border border-emerald-100/50 flex flex-col justify-center">
                                    <div class="flex items-center gap-2 text-[9px] font-black text-emerald-600 uppercase tracking-widest mb-1">Peso</div>
                                    <p class="text-sm font-black text-slate-800">{{ triageData?.Peso || '--' }} <span class="text-[9px] text-slate-400">kg</span></p>
                                </div>
                                <div class="bg-amber-50/50 p-3 rounded-2xl border border-amber-100/50 flex flex-col justify-center">
                                    <div class="flex items-center gap-2 text-[9px] font-black text-amber-600 uppercase tracking-widest mb-1">Temp. (ºC)</div>
                                    <p class="text-sm font-black text-slate-800">{{ triageData?.Temperatura || '--' }} <span class="text-[9px] text-slate-400">°C</span></p>
                                </div>
                                <div class="bg-rose-50/50 p-3 rounded-2xl border border-rose-100/50 flex flex-col justify-center">
                                    <div class="flex items-center gap-2 text-[9px] font-black text-rose-600 uppercase tracking-widest mb-1">Pressão</div>
                                    <p class="text-sm font-black text-slate-800">{{ triageData?.PressaoArterial || '--' }}</p>
                                </div>
                                <div class="bg-blue-50/50 p-3 rounded-2xl border border-blue-100/50 flex flex-col justify-center">
                                    <div class="flex items-center gap-2 text-[9px] font-black text-blue-600 uppercase tracking-widest mb-1">Freq. Cardíaca</div>
                                    <p class="text-sm font-black text-slate-800">{{ triageData?.FrequenciaCardiaca || '--' }}</p>
                                </div>
                                <div class="bg-purple-50/50 p-3 rounded-2xl border border-purple-100/50 flex flex-col justify-center">
                                    <div class="flex items-center gap-2 text-[9px] font-black text-purple-600 uppercase tracking-widest mb-1">Freq. Resp.</div>
                                    <p class="text-sm font-black text-slate-800">{{ triageData?.FrequenciaRespiratoria || '--' }}</p>
                                </div>
                                <div class="bg-cyan-50/50 p-3 rounded-2xl border border-cyan-100/50 flex flex-col justify-center">
                                    <div class="flex items-center gap-2 text-[9px] font-black text-cyan-600 uppercase tracking-widest mb-1">Sat. Oxigénio</div>
                                    <p class="text-sm font-black text-slate-800">{{ triageData?.SaturacaoOxigenio || '--' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Detailed Content Area -->
                        <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200/60 flex flex-col flex-grow mt-6 min-h-[700px]">
                            <!-- Tabs (Match screenshot) -->
                            <div class="px-8 pt-6 flex items-center gap-8 border-b border-slate-100">
                                <button @click="activeTab = 'clinical'" :class="activeTab === 'clinical' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-slate-400 hover:text-slate-600 border-transparent'" class="pb-4 px-2 text-[11px] font-black uppercase tracking-widest border-b-2 transition-all flex items-center gap-2">
                                    <ClipboardList class="w-4 h-4" /> Dados Clínicos
                                </button>
                                <button @click="activeTab = 'exams'" :class="activeTab === 'exams' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-slate-400 hover:text-slate-600 border-transparent'" class="pb-4 px-2 text-[11px] font-black uppercase tracking-widest border-b-2 transition-all flex items-center gap-2">
                                    <Activity class="w-4 h-4" /> Solicitar Exames
                                </button>
                                <button @click="activeTab = 'prescription'" :class="activeTab === 'prescription' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-slate-400 hover:text-slate-600 border-transparent'" class="pb-4 px-2 text-[11px] font-black uppercase tracking-widest border-b-2 transition-all flex items-center gap-2">
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
                                <div v-if="activeTab === 'exams'" class="flex-grow flex flex-col animate-fadeIn px-2 md:px-12 lg:px-16 pt-6 pb-2">
                                    
                                    <!-- Top Buttons (Pills like screenshot) -->
                                    <div class="flex gap-4 shrink-0 mb-4">
                                        <button @click="activeExamFilter = 'SOLICITADOS'" :class="activeExamFilter === 'SOLICITADOS' ? 'bg-[#3b82f6] text-white shadow-lg shadow-blue-500/20' : 'bg-[#f8fafc] text-[#64748b] hover:bg-[#f1f5f9]'" class="flex-grow py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all">Exames Solicitados</button>
                                        <button @click="activeExamFilter = 'LABORATORIO'" :class="activeExamFilter === 'LABORATORIO' ? 'bg-[#3b82f6] text-white shadow-lg shadow-blue-500/20' : 'bg-[#f8fafc] text-[#64748b] hover:bg-[#f1f5f9]'" class="flex-grow py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all">Exames do Laboratório</button>
                                        <button @click="activeExamFilter = 'RAIOX'" :class="activeExamFilter === 'RAIOX' ? 'bg-[#3b82f6] text-white shadow-lg shadow-blue-500/20' : 'bg-[#f8fafc] text-[#64748b] hover:bg-[#f1f5f9]'" class="flex-grow py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all">Raio X</button>
                                        <button @click="activeExamFilter = 'FORA'" :class="activeExamFilter === 'FORA' ? 'bg-[#3b82f6] text-white shadow-lg shadow-blue-500/20' : 'bg-[#f8fafc] text-[#64748b] hover:bg-[#f1f5f9]'" class="flex-grow py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all">Exames Fora</button>
                                    </div>
                                    
                                    <!-- Dark Space Bar (Screenshot separator) -->
                                    <div class="bg-[#1e3a8a] text-white text-center py-1.5 rounded-2xl text-xs font-black tracking-widest shadow-inner shrink-0 mb-4">
                                        ...
                                    </div>

                                    <!-- Middle Action Buttons -->
                                    <div class="flex gap-4 shrink-0 mb-6">
                                        <button class="bg-[#3b82f6] text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center justify-center gap-3 hover:bg-blue-500 transition-all shadow-md w-[280px] shrink-0">
                                            <Activity class="w-4 h-4" /> Enviar no Laboratório
                                        </button>
                                        <button @click="imprimirRequisicao" class="bg-[#f59e0b] text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center justify-center gap-3 hover:bg-amber-500 transition-all shadow-md flex-grow">
                                            <Printer class="w-4 h-4" /> Imprimir Requisição
                                        </button>
                                        <button class="bg-[#f59e0b] text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center justify-center gap-3 hover:bg-amber-500 transition-all shadow-md flex-grow">
                                            <Printer class="w-4 h-4" /> Imprimir Resultados
                                        </button>
                                        <button @click="showLancarResultadosModal = true" class="bg-[#0f172a] text-[#94a3b8] w-20 flex items-center justify-center rounded-2xl hover:text-white transition-all shadow-md shrink-0">
                                            <User class="w-5 h-5" />
                                        </button>
                                    </div>
                                    
                                    <!-- Data Grid (The 'Cruds') -->
                                    <div class="flex-grow border border-slate-200 rounded-3xl overflow-hidden shadow-sm flex flex-col mb-8 bg-white min-h-[300px]">
                                        <!-- Search Box -->
                                        <div class="p-4 border-b border-slate-100 flex gap-4 bg-white">
                                            <input v-model="searchExameTerm" placeholder="Digite o texto a procurar..." class="flex-grow text-xs px-6 py-3.5 bg-white border border-slate-200 focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10 rounded-2xl transition-all font-medium text-slate-700 shadow-sm outline-none" />
                                            <button class="px-10 py-3.5 bg-white border border-slate-200 rounded-2xl text-[10px] font-black uppercase text-slate-600 hover:bg-slate-50 transition-colors shadow-sm tracking-widest">Buscar</button>
                                        </div>
                                        
                                        <!-- Table Body -->
                                        <div class="flex-grow overflow-auto bg-white custom-scrollbar">
                                            <table class="w-full text-left border-collapse">
                                                <thead class="sticky top-0 bg-slate-50 border-b border-slate-200 shadow-sm z-10">
                                                    <tr class="text-[10px] uppercase font-black text-slate-500 tracking-widest">
                                                        <th class="p-5 w-16 text-center border-r border-slate-100">
                                                            <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                                                        </th>
                                                        <th class="p-5 border-r border-slate-100">Exame</th>
                                                        <th class="p-5 w-48 text-center">Resultado</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="text-xs font-medium text-slate-600">
                                                    <!-- Special Input for 'FORA' -->
                                                    <tr v-if="activeExamFilter === 'FORA'" class="border-b border-slate-100 bg-amber-50 hover:bg-amber-100/50 transition-colors group">
                                                        <td class="p-5 text-center border-r border-slate-100/50">
                                                            <input type="checkbox" checked class="w-4 h-4 rounded border-slate-300 text-blue-600" />
                                                        </td>
                                                        <td class="p-0 border-r border-slate-100/50">
                                                            <input type="text" placeholder="Escreva o nome do exame de fora aqui..." class="w-full bg-transparent border-none focus:ring-0 px-5 py-5 text-slate-700 outline-none font-bold" />
                                                        </td>
                                                        <td class="p-5 text-center text-slate-400 italic">Pendente...</td>
                                                    </tr>
                                                    
                                                    <tr v-for="exame in examesList" :key="exame.id" class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                                        <td class="p-5 text-center border-r border-slate-100">
                                                            <input type="checkbox" :value="exame.id" v-model="selectedExams" class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                                                        </td>
                                                        <td class="p-5 font-bold text-slate-700 border-r border-slate-100">{{ exame.nome }}</td>
                                                        <td class="p-5 text-center">
                                                            <span v-if="!exame.isRequested" class="text-slate-400">---</span>
                                                            <button v-else @click="openLancarModal(exame)" class="px-4 py-2 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded-xl text-[9px] font-black uppercase tracking-wider transition-colors shadow-sm w-full">Lançar Res.</button>
                                                        </td>
                                                    </tr>
                                                    <tr v-if="examesList.length === 0 && activeExamFilter !== 'FORA'">
                                                        <td colspan="3" class="p-16 text-center opacity-40">
                                                            <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Nenhum exame encontrado</p>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
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
                            <div class="px-8 py-6 bg-transparent border-t border-slate-100 flex items-center justify-between mt-auto shrink-0">
                                <div class="flex gap-4 items-center">
                                    <button class="px-6 py-4 bg-white border border-slate-200 text-slate-500 rounded-[12px] text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all flex items-center gap-2 shadow-sm">
                                        <Printer class="w-4 h-4 text-slate-400" /> Imprimir Relatório
                                    </button>
                                    <button class="px-6 py-4 bg-white border border-slate-200 text-slate-500 rounded-[12px] text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all flex items-center gap-2 shadow-sm">
                                        <FileText class="w-4 h-4 text-slate-400" /> Imprimir Receita
                                    </button>
                                </div>
                                <div class="flex gap-4 items-center">
                                    <div class="relative">
                                        <select v-model="form.situacao" class="bg-white border border-slate-200 text-slate-700 rounded-[12px] text-xs font-bold py-4 pl-6 pr-12 appearance-none cursor-pointer shadow-sm hover:bg-slate-50 transition-all">
                                            <option value="Finalizado">Finalizar Consulta</option>
                                            <option value="Laboratorio">Enviar para Lab</option>
                                            <option value="Reconsulta">Agendar Reconsulta</option>
                                            <option value="Internamento">Solicitar Internamento</option>
                                        </select>
                                        <ChevronDown class="w-4 h-4 text-slate-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none" />
                                    </div>
                                    <button @click="salvarConsulta" :disabled="form.processing" class="px-10 py-4 bg-blue-600 text-white rounded-[12px] text-[11px] font-black uppercase tracking-widest hover:bg-blue-700 transition-all shadow-lg shadow-blue-500/30 flex items-center gap-2 disabled:opacity-50">
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

        <!-- Modal: Lançar Resultados de Exame -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="showLancarResultadosModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showLancarResultadosModal = false"></div>
                
                <div class="relative bg-white w-full max-w-[1200px] border border-slate-300 shadow-2xl overflow-hidden h-[85vh] flex flex-col">
                    <!-- Top dark blue header -->
                    <div class="bg-[#000080] text-white text-center py-2 font-bold uppercase text-[10px] flex justify-between items-center px-4 tracking-widest">
                        <span>Lançar Resultados de Exames</span>
                        <span class="text-xs tracking-normal">LANÇAR RESULTADO DE EXAME APARTIR DO CONSULTÓRIO</span>
                        <button @click="showLancarResultadosModal = false" class="hover:text-red-300 flex items-center gap-1"><X class="w-4 h-4" /> Fechar</button>
                    </div>
                    
                    <div class="bg-indigo-50/50 text-center py-2 text-[11px] font-black text-[#000080] uppercase tracking-widest border-b border-slate-200">
                        Selecione o Exame e Lança o Resultado
                    </div>

                    <div class="flex-grow flex flex-col md:flex-row overflow-hidden bg-slate-100">
                        <!-- Left Panel: Exames List -->
                        <div class="w-full md:w-1/3 border-r border-slate-300 bg-white flex flex-col">
                            <div class="p-2 border-b border-slate-200 flex justify-center bg-slate-50">
                                <button class="text-[10px] uppercase font-black text-slate-500 flex items-center gap-2 hover:text-blue-600 transition-colors">
                                    <Printer class="w-4 h-4" /> IMPRIMIR RESULTADOS
                                </button>
                            </div>
                            <div class="p-1 px-3 border-b border-slate-200 bg-slate-50/50 flex gap-2 items-center">
                                <span class="text-[9px] font-bold text-slate-400 italic flex-grow">Arraste o cabeçalho de uma coluna para agrupar...</span>
                                <Search class="w-3.5 h-3.5 text-slate-400" />
                            </div>
                            <div class="flex-grow overflow-auto custom-scrollbar">
                                <table class="w-full text-left font-bold text-xs border-collapse">
                                    <thead class="bg-slate-50 border-b border-slate-200 shadow-sm sticky top-0">
                                        <tr>
                                            <th class="p-3 text-slate-500 w-10 text-center border-r border-slate-200">
                                                <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" class="w-3.5 h-3.5 rounded border-slate-300 text-blue-600" />
                                            </th>
                                            <th class="p-3 text-slate-500 border-r border-slate-200">Exame</th>
                                            <th class="p-3 text-slate-500">Resultado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="ex in examesList" :key="ex.id" @click="selectedExameToLancar = ex" :class="selectedExameToLancar?.id === ex.id ? 'bg-blue-50/50 text-blue-800' : 'hover:bg-slate-50 text-slate-700'" class="border-b border-slate-100 cursor-pointer transition-colors group">
                                            <td class="p-3 text-center border-r border-slate-100 text-[#000080] font-black pt-2">
                                                <input type="checkbox" :value="ex.id" v-model="selectedExams" @click.stop class="w-3.5 h-3.5 rounded border-slate-300 text-blue-600" />
                                            </td>
                                            <td class="p-3 border-r border-slate-100 truncate max-w-[150px] group-hover:text-blue-600 transition-colors">{{ ex.nome }}</td>
                                            <td class="p-3 text-slate-500 font-medium truncate max-w-[100px]">{{ ex.resultado }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Right Panel: Lançamento -->
                        <div class="w-full md:w-2/3 flex flex-col bg-[#e6e6e6]">
                            <div class="bg-[#000080] text-white text-center py-4 font-black shadow-inner">
                                {{ selectedExameToLancar?.nome || 'SELECIONE UM EXAME À ESQUERDA' }}
                            </div>
                            
                            <div class="p-6 overflow-y-auto" v-if="selectedExameToLancar">
                                <div class="flex flex-wrap items-center gap-8 mb-6 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <div class="relative flex items-center justify-center border border-slate-300 rounded-full w-4 h-4 p-2 group-hover:border-blue-500 transition-colors">
                                            <input type="radio" v-model="lancarResultadoMode" value="manual" class="absolute opacity-0 w-full h-full cursor-pointer" />
                                            <div v-show="lancarResultadoMode === 'manual'" class="w-2.5 h-2.5 bg-blue-600 rounded-full"></div>
                                        </div>
                                        <span class="text-[11px] font-black uppercase text-slate-700">Preencher Resultado Manualmente</span>
                                    </label>
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <div class="relative flex items-center justify-center border border-slate-300 rounded-full w-4 h-4 p-2 group-hover:border-blue-500 transition-colors">
                                            <input type="radio" v-model="lancarResultadoMode" value="anexar" class="absolute opacity-0 w-full h-full cursor-pointer" />
                                            <div v-show="lancarResultadoMode === 'anexar'" class="w-2.5 h-2.5 bg-blue-600 rounded-full"></div>
                                        </div>
                                        <span class="text-[11px] font-black uppercase text-slate-700">Anexar Resultado (PDF, Imagem)</span>
                                    </label>
                                </div>

                                <div v-if="lancarResultadoMode === 'manual'" class="space-y-6">
                                    <div class="bg-white border border-slate-300 shadow-sm flex flex-col">
                                        <div class="bg-slate-200/80 px-4 py-2 font-black text-[10px] uppercase text-slate-700 border-b border-slate-300 tracking-widest">Resultado</div>
                                        <textarea v-model="selectedExameToLancar.resultado" rows="5" class="w-full border-none focus:ring-0 text-sm p-4 font-bold text-slate-800 resize-y" placeholder="Digite o resultado do exame..."></textarea>
                                    </div>
                                    
                                    <div class="bg-white border border-slate-300 shadow-sm flex flex-col">
                                        <div class="bg-slate-200/80 px-4 py-2 font-black text-[10px] uppercase text-slate-700 border-b border-slate-300 tracking-widest">Observação</div>
                                        <textarea rows="3" class="w-full border-none focus:ring-0 text-sm p-4 font-bold text-slate-800 resize-y" placeholder="Anotações adicionais do médico..."></textarea>
                                    </div>
                                    
                                    <div class="flex justify-start">
                                        <button class="bg-white border border-slate-300 px-8 py-3.5 text-[10px] uppercase tracking-widest font-black text-slate-700 hover:bg-slate-50 hover:text-blue-600 shadow-sm transition-colors">Gravar Resultado</button>
                                    </div>
                                </div>

                                <div v-else class="space-y-6">
                                    <div class="flex flex-wrap gap-4 bg-white p-4 border border-slate-300 shadow-sm">
                                        <button class="bg-white border border-slate-300 px-8 py-2.5 text-[10px] uppercase tracking-widest font-black text-slate-700 hover:bg-slate-50 hover:text-blue-600 shadow-sm transition-colors">Anexar PDF</button>
                                        <button class="bg-white border border-slate-300 px-8 py-2.5 text-[10px] uppercase tracking-widest font-black text-slate-700 hover:bg-slate-50 hover:text-blue-600 shadow-sm transition-colors">Anexar Imagem</button>
                                        <button class="bg-white border border-slate-300 px-8 py-2.5 text-[10px] uppercase tracking-widest font-black text-slate-700 hover:bg-slate-50 hover:text-blue-600 shadow-sm transition-colors md:ml-auto">Gravar Anexos</button>
                                    </div>
                                    
                                    <div class="border border-slate-300 bg-slate-200 min-h-[300px] p-4 shadow-inner flex flex-col gap-2">
                                        <!-- Sample Attachment -->
                                        <div class="bg-white p-3 flex items-center justify-between border border-slate-300 shadow-sm">
                                            <div class="flex items-center gap-4">
                                                <div class="w-12 h-14 bg-red-50 border border-red-200 flex flex-col items-center justify-center text-red-600 font-black shadow-sm">
                                                    <FileText class="w-6 h-6 mb-1" />
                                                    <span class="text-[8px] uppercase tracking-widest">PDF</span>
                                                </div>
                                                <div>
                                                    <p class="text-[11px] font-black text-slate-700 uppercase">Arquivo PDF: WhatsApp_Image_2026_03_14.jpeg</p>
                                                    <p class="text-[10px] font-bold text-slate-400 mt-0.5 uppercase tracking-widest">Tamanho PDF: 520Kb</p>
                                                </div>
                                            </div>
                                            <button class="bg-[#f0f0f0] border border-slate-300 px-4 py-2.5 text-[9px] font-black uppercase text-slate-600 hover:bg-white hover:text-red-500 transition-colors shadow-sm tracking-widest">Remover Anexo</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Empty state panel -->
                            <div v-else class="p-12 flex flex-col items-center justify-center flex-grow text-center opacity-40">
                                <FileText class="w-16 h-16 text-slate-500 mb-4" />
                                <p class="text-sm font-black text-slate-700 uppercase tracking-widest">Aguardando Seleção</p>
                                <p class="text-xs font-bold text-slate-500 mt-2">Escolha um exame na lista ao lado para lançar os resultados correspondentes.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- PRINT VIEW WRAPPER (HIDDEN ON SCREEN, VISIBLE ON PRINT) -->
        <div class="hidden print-layout printable-area">
            <div v-if="selectedPaciente" class="w-full bg-white text-black font-sans print-page">
                <!-- Header -->
                <div style="display: flex; border-bottom: 1px dashed black; padding-bottom: 20px; margin-bottom: 20px; align-items: center;">
                    <div v-if="empresa?.IMAGEM" style="width: 140px; height: 100px; display: flex; align-items: center; justify-content: center; margin-right: 20px;">
                        <img :src="empresa.IMAGEM" style="max-width: 100%; max-height: 100%; object-fit: contain;" />
                    </div>
                    <div v-else style="width: 120px; height: 120px; background-color: #333; color: white; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 10px; margin-right: 20px;">
                        <span style="font-weight: 800; font-size: 22px;">EMUTE</span>
                        <span style="font-size: 10px; letter-spacing: 1px;">SOFTWARE</span>
                    </div>
                    <div style="flex-grow: 1;">
                        <h1 style="font-weight: 900; font-size: 28px; text-transform: uppercase; margin: 0;">{{ empresa?.DESCRICAO || 'INEVITAVEL' }}</h1>
                        <p style="font-size: 14px; margin: 5px 0;">Contribuinte n° {{ empresa?.NIF || '---' }}</p>
                        <p style="font-size: 14px; margin: 0;">Contacto: {{ empresa?.TELEFONE || '---' }} | {{ empresa?.EMAIL || '---' }}</p>
                        <p style="font-size: 14px; margin: 5px 0;">Endereço: {{ empresa?.RUA || '---' }}, {{ empresa?.CIDADE || '' }}</p>
                    </div>
                </div>

                <h2 style="text-align: center; font-weight: 900; font-size: 24px; margin-bottom: 20px; border-bottom: 3px solid black; padding-bottom: 10px; text-transform: uppercase;">
                    Requisições de Exames
                </h2>

                <!-- Patient Details -->
                <table style="width: 100%; font-size: 14px; margin-bottom: 30px; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 4px 0;"><strong>Nome:</strong> {{ selectedPaciente.PacienteNome }}</td>
                        <td style="padding: 4px 0;"><strong>Idade:</strong> {{ calcularIdadeFormatoDesktop(selectedPaciente.DataNascimento) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0;"><strong>Sexo:</strong> {{ selectedPaciente.Genero?.toUpperCase() }}</td>
                        <td style="padding: 4px 0;"><strong>N° Processo:</strong> {{ selectedPaciente.Codigo }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0;"><strong>Data do Exame:</strong> {{ new Date().toLocaleString('pt-PT') }}</td>
                        <td style="padding: 4px 0;"><strong>Empresa:</strong> {{ selectedPaciente.Entidade || '' }}</td>
                    </tr>
                </table>

                <!-- Exams Table Grouped -->
                <div v-for="(exames, categoria) in examesParaImprimir" :key="categoria" style="margin-bottom: 30px;">
                    <div style="background-color: #e2e8f0; padding: 8px; border: 1px solid #ccc; font-weight: bold; font-size: 14px;">
                        Categoria do Exame: {{ categoria }}
                    </div>
                    
                    <div v-for="exame in exames" :key="exame.Descricao" style="margin-bottom: 15px;">
                        <div style="background-color: #94a3b8; padding: 6px 8px; border: 1px solid #666; font-weight: 900; font-size: 13px;">
                            Exame: {{ exame.Descricao }}
                        </div>
                        <table style="width: 100%; font-size: 12px; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 1px solid black;">
                                    <th style="padding: 4px; text-align: left;">Dados</th>
                                    <th style="padding: 4px; text-align: center;">Resultados</th>
                                    <th style="padding: 4px; text-align: center;">Referencias</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr style="border-bottom: 1px solid #ccc;">
                                    <td style="padding: 6px 4px;">{{ exame.Descricao }}</td>
                                    <td style="padding: 6px 4px; text-align: center;">{{ exame.Resultado || '' }}</td>
                                    <td style="padding: 6px 4px; text-align: center;"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Signatures -->
                <div style="margin-top: 80px; text-align: center;">
                    <p style="font-style: italic; font-size: 14px; margin-bottom: 40px;">Assinatura do Técnico do Laboratório</p>
                    <div style="width: 300px; border-bottom: 1px solid black; margin: 0 auto 5px;"></div>
                    <p style="font-size: 14px; font-weight: bold;">Dr(a). : {{ selectedPaciente.MedicoNome || 'Médico Responsável' }}</p>
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

<style>
@media print {
    /* Ocultar elementos UI nativos do Vue na página original */
    body * {
        visibility: hidden;
    }
    
    @page { 
        size: A4 portrait;
        margin: 1cm;
    }

    body, html {
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
    }

    /* Mostrar apenas a área de impressão */
    .printable-area, .printable-area * {
        visibility: visible;
    }

    /* Ajuste de posição para cobrir tudo e não aparecer cortado pelo dashboard */
    .printable-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        display: block !important;
        background: white !important;
        color: black !important;
    }
    
    .print-page {
        width: 100%;
        margin: 0;
        padding: 0;
    }
}
</style>

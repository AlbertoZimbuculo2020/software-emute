<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import { 
    Search, UserPlus, Plus, ClipboardList, Stethoscope, 
    Calendar, MousePointer2, RotateCcw, FileText, Activity, 
    CreditCard, Users, User, ChevronDown, Check, X
} from 'lucide-vue-next';
import { watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    medicos: Array,
    consultas: Array,
    seguradoras: Array,
    agendamentos: Array,
    filters: Object
});

const form = useForm({
    IdPaciente: '',
    nome: '',
    filiacao_pai: '',
    filiacao_mae: '',
    data_nascimento: new Date().toISOString().split('T')[0],
    idade: 0,
    telefone: '',
    sexo: 'MASCULINO',
    endereco: '',
    tipo_paciente: 'Particular',
    IdSeguradora: '',
    IdConsulta: '',
    IdMedico: '',
    DataAgendamento: new Date().toISOString().split('T')[0],
    situacao: 'Agendada',
});

const searchLoading = ref(false);
const searchTerm = ref('');
const searchResults = ref([]);
const showResults = ref(false);
const patientNameInput = ref(null);
const notification = ref({ show: false, message: '', type: 'success' });

// Filtros de Data
const startDate = ref(props.filters?.startDate || new Date().toISOString().split('T')[0]);
const endDate = ref(props.filters?.endDate || new Date().toISOString().split('T')[0]);

const filtrarPorData = () => {
    router.get(route('hospitalar.recepcao'), {
        startDate: startDate.value,
        endDate: endDate.value
    }, {
        preserveState: true,
        replace: true
    });
};

const showNotification = (message, type = 'success') => {
    notification.value = { show: true, message, type };
    setTimeout(() => {
        notification.value.show = false;
    }, 4000);
};

const buscarPaciente = debounce(async () => {
    if (!searchTerm.value || searchTerm.value.length < 2) {
        searchResults.value = [];
        showResults.value = false;
        return;
    }
    
    searchLoading.value = true;
    try {
        const response = await axios.post(route('hospitalar.recepcao.search'), { term: searchTerm.value });
        searchResults.value = response.data;
        showResults.value = response.data.length > 0;
    } catch (error) {
        console.error(error);
    } finally {
        searchLoading.value = false;
    }
}, 300);

watch(searchTerm, () => {
    buscarPaciente();
});

const selecionarPaciente = (p) => {
    form.IdPaciente = p.Codigo;
    form.nome = p.Nome;
    form.telefone = p.Telefone || '';
    form.endereco = p.Endereco || '';
    form.filiacao_pai = p.Pai || '';
    form.filiacao_mae = p.Mae || '';
    form.sexo = p.Genero || 'MASCULINO';
    
    if (p.DataNascimento) {
        const birth = new Date(p.DataNascimento);
        const today = new Date();
        let age = today.getFullYear() - birth.getFullYear();
        const m = today.getMonth() - birth.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
            age--;
        }
        form.idade = age;
        form.data_nascimento = p.DataNascimento;
    }
    
    showResults.value = false;
    searchTerm.value = p.Nome;
};

const agendarConsulta = () => {
    if (!form.nome) {
        showNotification('Por favor, preencha pelo menos o nome do paciente.', 'error');
        return;
    }

    form.post(route('hospitalar.recepcao.store'), {
        onSuccess: () => {
            showNotification('Paciente admitido com sucesso!');
            form.reset();
            form.DataAgendamento = new Date().toISOString().split('T')[0];
            searchTerm.value = '';
        },
        onError: (err) => {
            console.error(err);
            showNotification('Erro ao realizar agendamento.', 'error');
        }
    });
};

const enviarParaTriagem = (agendamento) => {
    if (!agendamento) {
        showNotification('Selecione um agendamento para enviar à triagem.', 'error');
        return;
    }
    
    if (agendamento.Situacao === 'Triagem') {
        showNotification('Este paciente já está na triagem.', 'error');
        return;
    }
    
    router.post(route('hospitalar.recepcao.enviar-triagem'), { codigo: agendamento.Codigo }, {
        onSuccess: () => {
            showNotification('Paciente enviado para a triagem com sucesso!');
        }
    });
};

const limparForm = () => {
    form.reset();
    searchTerm.value = '';
    searchResults.value = [];
    showResults.value = false;
    form.DataAgendamento = new Date().toISOString().split('T')[0];
    
    // Focus the name input for new registration
    setTimeout(() => {
        if (patientNameInput.value) {
            patientNameInput.value.focus();
        }
    }, 100);
};
</script>

<template>
    <Head title="Recepção - Marcação de Consulta" />

    <DashboardLayout>
        <div class="min-h-screen bg-[#f8fafc] p-4 lg:p-8">
            <div class="max-w-[1700px] mx-auto space-y-8">
                
                <!-- Header Moderno -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl shadow-sm border border-slate-200/60">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                            <div class="p-2 bg-blue-600 rounded-xl text-white shadow-lg shadow-blue-200">
                                <Activity class="w-6 h-6" />
                            </div>
                            RECEPÇÃO HOSPITALAR
                        </h1>
                        <p class="text-slate-500 text-sm font-medium mt-1 ml-11">Gestão de Admissão e Fluxo de Pacientes</p>
                    </div>
                    
                    <div class="flex items-center gap-4 bg-slate-50 p-2 rounded-2xl border border-slate-100">
                        <div class="text-right px-4 border-r border-slate-200">
                            <p class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Data do Sistema</p>
                            <p class="text-sm font-black text-slate-700">{{ new Date().toLocaleDateString('pt-PT', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
                        </div>
                        <div class="p-2 bg-white rounded-xl shadow-sm">
                            <Calendar class="w-5 h-5 text-blue-600" />
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    
                    <!-- Left Column: Dados do Paciente (Modern Card) -->
                    <div class="lg:col-span-6 xl:col-span-5 flex flex-col gap-6">
                        <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-200/60 overflow-hidden flex flex-col">
                            <!-- Card Header -->
                            <div class="bg-slate-50/50 px-8 py-5 border-b border-slate-100 flex items-center justify-between">
                                <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                                    <Users class="w-4 h-4 text-blue-600" /> Dados do Paciente
                                </h2>
                                <div class="flex gap-2">
                                    <button @click="limparForm" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-all">
                                        <RotateCcw class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>

                            <div class="p-8 space-y-6">
                                <!-- Modern Search Bar -->
                                <div class="flex flex-wrap items-center gap-3">
                                    <button @click="buscarPaciente" :disabled="searchLoading" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-xs font-black flex items-center shadow-lg shadow-blue-200 transition-all active:scale-95 uppercase tracking-wide">
                                        <Search v-if="!searchLoading" class="w-4 h-4 mr-2" />
                                        <span v-else class="w-4 h-4 mr-2 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                        BUSCAR PACIENTE
                                    </button>
                                    <button @click="limparForm" class="bg-slate-800 hover:bg-slate-900 text-white px-5 py-2.5 rounded-xl text-xs font-black flex items-center shadow-lg shadow-slate-200 transition-all active:scale-95 uppercase tracking-wide">
                                        <UserPlus class="w-4 h-4 mr-2" /> NOVO PACIENTE
                                    </button>
                                    <div class="flex-grow min-w-[150px] relative">
                                        <input v-model="searchTerm" @focus="showResults = searchResults.length > 0" placeholder="Código ou Nome..." class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 rounded-xl px-4 py-2.5 text-sm transition-all placeholder:text-slate-400" />
                                        
                                        <!-- Dropdown de Resultados -->
                                        <div v-if="showResults" class="absolute top-full left-0 right-0 mt-2 bg-white border border-slate-200 rounded-2xl shadow-2xl z-[100] max-h-64 overflow-auto custom-scrollbar p-2 space-y-1">
                                            <div v-for="p in searchResults" :key="p.Codigo" @click="selecionarPaciente(p)" class="flex items-center justify-between p-3 hover:bg-blue-50 rounded-xl cursor-pointer transition-colors group">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-black text-[10px]">
                                                        {{ p.Nome.substring(0, 2) }}
                                                    </div>
                                                    <div>
                                                        <p class="text-xs font-black text-slate-800">{{ p.Nome }}</p>
                                                        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-tighter">{{ p.Codigo }} | {{ p.Telefone || 'Sem Telefone' }}</p>
                                                    </div>
                                                </div>
                                                <Check class="w-4 h-4 text-blue-500 opacity-0 group-hover:opacity-100 transition-opacity" />
                                            </div>
                                        </div>
                                        <!-- Overlay para fechar dropdown -->
                                        <div v-if="showResults" @click="showResults = false" class="fixed inset-0 z-[-1]"></div>
                                    </div>
                                </div>

                                <!-- Form Section -->
                                <div class="space-y-5">
                                    <!-- Field Group: Nome -->
                                    <div class="space-y-1.5">
                                        <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">Nome Completo</label>
                                        <div class="relative group">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <User class="h-4 h-4 text-slate-300 group-focus-within:text-blue-500 transition-colors" />
                                            </div>
                                            <input ref="patientNameInput" v-model="form.nome" class="w-full pl-10 bg-slate-50 border-slate-100 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 rounded-xl px-4 py-2.5 text-sm font-semibold transition-all shadow-sm" />
                                        </div>
                                    </div>

                                    <!-- Field Group: Filiação -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div class="space-y-1.5">
                                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">Filiação (Pai)</label>
                                            <input v-model="form.filiacao_pai" class="w-full bg-slate-50 border-slate-100 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 rounded-xl px-4 py-2.5 text-sm font-medium transition-all shadow-sm" />
                                        </div>
                                        <div class="space-y-1.5">
                                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">Filiação (Mãe)</label>
                                            <input v-model="form.filiacao_mae" class="w-full bg-slate-50 border-slate-100 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 rounded-xl px-4 py-2.5 text-sm font-medium transition-all shadow-sm" />
                                        </div>
                                    </div>

                                    <!-- Field Group: Nascimento / Idade / Sexo -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                        <div class="space-y-1.5">
                                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">Data Nasc.</label>
                                            <input type="date" v-model="form.data_nascimento" class="w-full bg-slate-50 border-slate-100 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 rounded-xl px-4 py-2.5 text-sm font-medium transition-all shadow-sm" />
                                        </div>
                                        <div class="space-y-1.5">
                                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">Idade</label>
                                            <input v-model="form.idade" class="w-full bg-slate-50 border-slate-100 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 rounded-xl px-4 py-2.5 text-sm font-black text-center transition-all shadow-sm" />
                                        </div>
                                        <div class="space-y-1.5">
                                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">Sexo</label>
                                            <select v-model="form.sexo" class="w-full bg-slate-50 border-slate-100 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 rounded-xl px-4 py-2.5 text-sm font-bold transition-all shadow-sm">
                                                <option value="MASCULINO">MASCULINO</option>
                                                <option value="FEMININO">FEMININO</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Field Group: Contato / Endereço -->
                                    <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
                                        <div class="md:col-span-4 space-y-1.5">
                                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">Telefone</label>
                                            <input v-model="form.telefone" class="w-full bg-slate-50 border-slate-100 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 rounded-xl px-4 py-2.5 text-sm font-bold transition-all shadow-sm" />
                                        </div>
                                        <div class="md:col-span-8 space-y-1.5">
                                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">Endereço</label>
                                            <div class="flex gap-2">
                                                <input v-model="form.endereco" class="flex-grow bg-slate-50 border-slate-100 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 rounded-xl px-4 py-2.5 text-sm font-medium transition-all shadow-sm" />
                                                <button class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 rounded-xl text-[10px] font-black transition-colors uppercase">SEM</button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Field Group: Tipo de Paciente -->
                                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 flex items-center justify-between">
                                        <label class="text-[11px] font-black text-slate-500 uppercase tracking-widest">Tipo de Atendimento</label>
                                        <div class="flex bg-white p-1 rounded-xl shadow-inner border border-slate-100">
                                            <button @click="form.tipo_paciente = 'Particular'" :class="form.tipo_paciente === 'Particular' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50'" class="px-4 py-1.5 rounded-lg text-[10px] font-black uppercase transition-all">Particular</button>
                                            <button @click="form.tipo_paciente = 'Assegurado'" :class="form.tipo_paciente === 'Assegurado' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50'" class="px-4 py-1.5 rounded-lg text-[10px] font-black uppercase transition-all">Assegurado</button>
                                        </div>
                                    </div>

                                    <!-- Selects Group -->
                                    <div class="space-y-4">
                                        <!-- Seguradora -->
                                        <div class="space-y-1.5">
                                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">Seguradora / Convênio</label>
                                            <div class="flex gap-2">
                                                <select v-model="form.IdSeguradora" :disabled="form.tipo_paciente !== 'Assegurado'" class="flex-grow bg-slate-50 border-slate-100 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 rounded-xl px-4 py-2.5 text-sm font-medium transition-all shadow-sm disabled:opacity-50">
                                                    <option value="">Selecione uma seguradora...</option>
                                                    <option v-for="s in props.seguradoras" :key="s.Id" :value="s.Id">{{ s.Nome }}</option>
                                                </select>
                                                <button class="bg-blue-50 text-blue-600 p-2.5 rounded-xl hover:bg-blue-100 transition-colors border border-blue-100"><Plus class="w-5 h-5" /></button>
                                            </div>
                                        </div>

                                        <!-- Data Agendamento -->
                                        <div class="space-y-1.5">
                                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">Data do Agendamento</label>
                                            <div class="relative group">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <Calendar class="h-4 w-4 text-slate-300 group-focus-within:text-blue-500 transition-colors" />
                                                </div>
                                                <input type="date" v-model="form.DataAgendamento" class="w-full pl-10 bg-white border-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 rounded-xl px-4 py-2.5 text-sm font-black transition-all shadow-sm" />
                                            </div>
                                        </div>

                                        <!-- Consulta -->
                                        <div class="space-y-1.5">
                                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">Especialidade / Consulta</label>
                                            <div class="flex gap-2">
                                                <select v-model="form.IdConsulta" class="flex-grow bg-slate-50 border-slate-100 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 rounded-xl px-4 py-2.5 text-sm font-bold transition-all shadow-sm">
                                                    <option value="">Selecione o tipo de consulta...</option>
                                                    <option v-for="c in props.consultas" :key="c.Id" :value="c.Id">{{ c.Descricao }} - {{ c.Valor }} KZ</option>
                                                </select>
                                                <button class="bg-blue-50 text-blue-600 p-2.5 rounded-xl hover:bg-blue-100 transition-colors border border-blue-100"><Plus class="w-5 h-5" /></button>
                                            </div>
                                        </div>

                                        <!-- Médico -->
                                        <div class="space-y-1.5">
                                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">Médico Responsável</label>
                                            <div class="flex gap-2">
                                                <select v-model="form.IdMedico" class="flex-grow bg-slate-50 border-slate-100 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 rounded-xl px-4 py-2.5 text-sm font-bold transition-all shadow-sm">
                                                    <option value="">Selecione o médico...</option>
                                                    <option v-for="m in props.medicos" :key="m.Id" :value="m.Id">{{ m.Nome }}</option>
                                                </select>
                                                <button class="bg-blue-50 text-blue-600 p-2.5 rounded-xl hover:bg-blue-100 transition-colors border border-blue-100"><Plus class="w-5 h-5" /></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modern Tile Options -->
                        <div class="bg-white p-8 rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-200/60">
                            <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-6 flex items-center gap-2">
                                <MousePointer2 class="w-4 h-4 text-blue-600" /> Confirmar Atendimento
                            </h3>
                            <div class="grid grid-cols-2 gap-5">
                                <button @click="() => { form.situacao = 'Agendada'; agendarConsulta(); }" class="group bg-blue-50 hover:bg-blue-600 text-blue-600 hover:text-white p-6 rounded-3xl transition-all duration-300 flex flex-col items-center gap-3 border border-blue-100 hover:border-blue-600 hover:shadow-xl hover:shadow-blue-200">
                                    <div class="p-3 bg-white group-hover:bg-blue-500 rounded-2xl shadow-sm transition-colors">
                                        <Calendar class="w-6 h-6" />
                                    </div>
                                    <span class="text-[11px] font-black uppercase tracking-wider">Agendar Consulta</span>
                                </button>
                                <button @click="() => { form.DataAgendamento = new Date().toISOString().split('T')[0]; form.situacao = 'Triagem'; agendarConsulta(); }" class="group bg-blue-600 hover:bg-blue-700 text-white p-6 rounded-3xl transition-all duration-300 flex flex-col items-center gap-3 shadow-lg shadow-blue-200 hover:shadow-blue-300 active:scale-95">
                                    <div class="p-3 bg-white/20 rounded-2xl backdrop-blur-md">
                                        <Activity class="w-6 h-6" />
                                    </div>
                                    <span class="text-[11px] font-black uppercase tracking-wider">Enviar para Triagem</span>
                                </button>
                                <!-- Tile: Triagem -->
                                <div @click="enviarParaTriagem(agendamentos.length > 0 ? agendamentos[agendamentos.length - 1] : null)" class="group bg-white p-6 rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/40 hover:shadow-emerald-200/40 hover:border-emerald-200 transition-all cursor-pointer overflow-hidden relative active:scale-95">
                                    <div class="flex items-center gap-4 relative z-10">
                                        <div class="p-3 bg-emerald-50 rounded-2xl text-emerald-600 group-hover:scale-110 transition-transform">
                                            <Activity class="w-6 h-6" />
                                        </div>
                                        <div class="text-left">
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Procedimento</p>
                                            <h3 class="text-sm font-black text-slate-800 tracking-tight">ENVIAR TRIAGEM</h3>
                                        </div>
                                    </div>
                                    <div class="absolute -right-4 -bottom-4 opacity-[0.03] group-hover:opacity-[0.08] transition-opacity">
                                        <Activity class="w-24 h-24" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Dashboard (Modern Dashboard style) -->
                    <div class="lg:col-span-6 xl:col-span-7 flex flex-col gap-8">
                        
                        <!-- Main Table Section -->
                        <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-200/60 overflow-hidden flex flex-col flex-grow min-h-[600px]">
                            <!-- Header with Filters -->
                            <div class="px-8 py-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4 bg-slate-50/30">
                                <div>
                                    <h2 class="text-lg font-black text-slate-900 tracking-tight">Agendamentos</h2>
                                    <p class="text-slate-400 text-xs font-medium">Lista de consultas marcadas e estados</p>
                                </div>
                                <div class="flex items-center gap-3 bg-white p-2 rounded-2xl shadow-sm border border-slate-200/60">
                                    <div class="flex items-center gap-2 px-3 border-r border-slate-100">
                                        <span class="text-[10px] font-black text-slate-400 uppercase">Período:</span>
                                        <input type="date" v-model="startDate" class="border-none focus:ring-0 text-xs font-bold text-slate-600 p-0" />
                                        <span class="text-slate-300 font-bold">-</span>
                                        <input type="date" v-model="endDate" class="border-none focus:ring-0 text-xs font-bold text-slate-600 p-0" />
                                    </div>
                                    <button @click="filtrarPorData" class="bg-blue-600 hover:bg-blue-700 text-white p-2 rounded-xl shadow-md shadow-blue-100 transition-all active:scale-95">
                                        <RotateCcw class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>

                            <!-- Modern Table Wrapper -->
                            <div class="flex-grow overflow-auto p-6">
                                <table class="w-full border-separate border-spacing-y-3">
                                    <thead>
                                        <tr class="text-[11px] font-black text-slate-400 uppercase tracking-widest">
                                            <th class="px-4 py-2 text-left">Paciente</th>
                                            <th class="px-4 py-2 text-left">Consulta / Especialidade</th>
                                            <th class="px-4 py-2 text-left">Médico</th>
                                            <th class="px-4 py-2 text-center">Estado</th>
                                            <th class="px-4 py-2 text-right">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="agenda in props.agendamentos" :key="agenda.Id" class="group bg-white hover:bg-blue-50/30 transition-all duration-300 shadow-sm border border-slate-100 rounded-2xl">
                                            <td class="px-4 py-5 first:rounded-l-2xl border-y border-l border-slate-50 group-hover:border-blue-100">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center text-slate-500 font-black text-xs uppercase">
                                                        {{ agenda.PacienteNome?.substring(0, 2) }}
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-black text-slate-800 leading-none">{{ agenda.PacienteNome }}</p>
                                                        <p class="text-[10px] text-slate-400 font-bold mt-1.5 uppercase">Cód: {{ agenda.IdPaciente || 'N/A' }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-5 border-y border-slate-50 group-hover:border-blue-100">
                                                <p class="text-xs font-bold text-slate-700">{{ agenda.Consulta }}</p>
                                                <p class="text-[10px] text-slate-400 font-medium mt-1 flex items-center gap-1">
                                                    <Calendar class="w-3 h-3" /> {{ agenda.DataAgendamento }}
                                                </p>
                                            </td>
                                            <td class="px-4 py-5 border-y border-slate-50 group-hover:border-blue-100">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-6 h-6 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center">
                                                        <Stethoscope class="w-3.5 h-3.5" />
                                                    </div>
                                                    <span class="text-xs font-semibold text-slate-600">{{ agenda.MedicoNome || 'Pendente' }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-5 border-y border-slate-50 group-hover:border-blue-100 text-center">
                                                <span :class="{
                                                    'bg-amber-50 text-amber-600 border-amber-100': agenda.Situacao === 'Agendada',
                                                    'bg-blue-50 text-blue-600 border-blue-100': agenda.Situacao === 'Triagem',
                                                    'bg-emerald-50 text-emerald-600 border-emerald-100': agenda.Situacao === 'Finalizado',
                                                }" class="px-3 py-1.5 rounded-xl border text-[10px] font-black uppercase tracking-wider">
                                                    {{ agenda.Situacao }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-5 last:rounded-r-2xl border-y border-r border-slate-50 group-hover:border-blue-100 text-right">
                                                <div class="flex justify-end gap-2">
                                                    <button v-if="agenda.Situacao === 'Agendada'" @click="enviarParaTriagem(agenda)" class="p-2 bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white rounded-xl transition-all shadow-sm" title="Enviar para Triagem">
                                                        <Activity class="w-4 h-4" />
                                                    </button>
                                                    <button class="p-2 bg-slate-50 hover:bg-blue-600 hover:text-white rounded-xl transition-all shadow-sm">
                                                        <FileText class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="props.agendamentos.length === 0">
                                            <td colspan="5" class="p-20 text-center">
                                                <div class="flex flex-col items-center gap-4 opacity-30">
                                                    <Calendar class="w-16 h-16" />
                                                    <p class="text-sm font-black uppercase tracking-widest">Sem agendamentos hoje</p>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Secondary Info Split -->
                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                            <!-- Table 1 -->
                            <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-200/60 overflow-hidden">
                                <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between">
                                    <h3 class="text-[11px] font-black text-slate-700 uppercase tracking-widest flex items-center gap-2">
                                        <CreditCard class="w-4 h-4 text-blue-600" /> Exames por Pagar
                                    </h3>
                                    <span class="bg-blue-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full">0</span>
                                </div>
                                <div class="p-10 text-center opacity-40">
                                    <p class="text-xs font-bold italic text-slate-400">Nenhum registro pendente</p>
                                </div>
                            </div>
                            <!-- Table 2 -->
                            <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-200/60 overflow-hidden">
                                <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between">
                                    <h3 class="text-[11px] font-black text-slate-700 uppercase tracking-widest flex items-center gap-2">
                                        <Activity class="w-4 h-4 text-emerald-600" /> Área de Internamento
                                    </h3>
                                    <span class="bg-slate-200 text-slate-600 text-[10px] font-black px-2 py-0.5 rounded-full">0</span>
                                </div>
                                <div class="p-10 text-center opacity-40">
                                    <p class="text-xs font-bold italic text-slate-400">Nenhum paciente internado</p>
                                </div>
                            </div>
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
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f8fafc;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}

input[type="date"]::-webkit-calendar-picker-indicator {
    cursor: pointer;
    opacity: 0.5;
    transition: opacity 0.2s;
}
input[type="date"]::-webkit-calendar-picker-indicator:hover {
    opacity: 1;
}
</style>



<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import axios from 'axios';
import { Search, CheckCircle2, AlertCircle, Printer, PlusCircle, ClipboardCheck, Plus, LayoutGrid, Users, Stethoscope, Syringe, Activity, X, Package2, Minus } from 'lucide-vue-next';

const props = defineProps({
    internados: { type: Array, default: () => [] },
    historico: { type: Array, default: () => [] }
});

const page = usePage();

const isMedico = computed(() => {
    return page.props.auth.user.ID_PERFIL == 3;
});

const isEnfermeiro = computed(() => {
    return page.props.auth.user.ID_PERFIL == 2 || page.props.auth.user.ID_PERFIL == 3 || page.props.auth.user.ACESSO === 'SIM';
});

const requireMedico = (action) => {
    if (isMedico.value) {
        action();
    } else {
        showToast('Área restrita a médicos', 'error');
    }
};

const searchTerm = ref('');
const histSearchTerm = ref('');
const prescricoesSearchTerm = ref('');
const selectedPaciente = ref(null);
const showPrescricaoModal = ref(false);
const showAtoModal = ref(false);
const showSinaisModal = ref(false);
const showAltaModal = ref(false);
const showCumprimentoModal = ref(false);

// ─── Cumprimento (Enfermagem) state ───────────────────────────────────────
const depositos = ref([]);
const depositoSelecionado = ref('');
const searchArtigo = ref('');
const artigos = ref([]);
const cartFarmacos = ref([]);
const motivoSaida = ref('');
const cumprimentoTab = ref('prescricoes'); // 'prescricoes' | 'ficha'
const prescricoesCumprimento = ref([]);  // cópia das prescrições com estado local

const atoType = ref('medico'); // 'medico' or 'enfermagem'

const prescricaoForm = ref({
    Descricao: '',
    Observacao: '',
    Tipo: 'Internamento'
});

const atoForm = ref({
    descricao: ''
});

const sinaisForm = ref({
    Peso: '0',
    Temperatura: '0',
    PressaoArterial: '0',
    PressaoArterialBE: '0',
    FrequenciaCardioca: '0',
    PulsoBE: '0',
    FrequenciaRespiratoria: '0',
    SituacaoOxigenio: '0',
    Obs: '',
    Enfermeiro: ''
});

const altaForm = ref({
    Operado: '',
    Complicacoes: '',
    Repouso: '',
    Obs: ''
});

const details = ref({ prescricoes: [], atosMedicos: [], atosEnfermagem: [], sinaisVitais: [] });
const isLoading = ref(false);

const filteredInternados = computed(() => {
    if (!searchTerm.value) return props.internados;
    const term = searchTerm.value.toLowerCase();
    return props.internados.filter(p => 
        p.PacienteNome?.toLowerCase().includes(term) ||
        p.Codigo?.toLowerCase().includes(term)
    );
});

const filteredHistorico = computed(() => {
    if (!histSearchTerm.value) return props.historico;
    const term = histSearchTerm.value.toLowerCase();
    return props.historico.filter(h => 
        h.PacienteNome?.toLowerCase().includes(term) ||
        h.Codigo?.toLowerCase().includes(term)
    );
});

const filteredPrescricoes = computed(() => {
    if (!prescricoesSearchTerm.value) return details.value.prescricoes;
    const term = prescricoesSearchTerm.value.toLowerCase();
    return details.value.prescricoes.filter(p => 
        p.Descricao?.toLowerCase().includes(term) ||
        p.Medico?.toLowerCase().includes(term)
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

const showConfirmModal = ref(false);
const confirmAction = ref(null);
const confirmTitle = ref('');
const confirmText = ref('');

const toast = ref({ show: false, message: '', type: 'success' });

const showToast = (message, type = 'success') => {
    toast.value = { show: true, message, type };
    setTimeout(() => { toast.value.show = false; }, 4000);
};

const openConfirm = (title, text, action) => {
    confirmTitle.value = title;
    confirmText.value = text;
    confirmAction.value = action;
    showConfirmModal.value = true;
};

const executeConfirm = () => {
    if (confirmAction.value) confirmAction.value();
    showConfirmModal.value = false;
};

const submitPrescricao = async () => {
    if (!selectedPaciente.value) return;
    try {
        await axios.post(route('hospitalar.internamento.prescricao.store'), {
            ...prescricaoForm.value,
            IdAgenda: selectedPaciente.value.Codigo
        });
        showPrescricaoModal.value = false;
        prescricaoForm.value = { Descricao: '', Observacao: '', Tipo: 'Internamento' };
        showToast('Prescrição gravada com sucesso');
        selecionarPaciente(selectedPaciente.value);
    } catch (error) {
        showToast('Erro ao salvar prescrição', 'error');
    }
};

const togglePrescricaoStatus = async (prescricao, campo) => {
    try {
        const novoValor = prescricao[campo] !== 'True';
        await axios.post(route('hospitalar.internamento.prescricao.toggle', prescricao.Id), {
            campo: campo,
            valor: novoValor
        });
        prescricao[campo] = novoValor ? 'True' : 'False';
        showToast('Status atualizado');
        const response = await axios.get(route('hospitalar.internamento.details', selectedPaciente.value.Codigo));
        details.value = response.data;
    } catch (error) {
        showToast('Erro ao atualizar status', 'error');
    }
};

const submitAto = async () => {
    if (!selectedPaciente.value) return;
    try {
        await axios.post(route('hospitalar.internamento.ato'), {
            ...atoForm.value,
            tipo: atoType.value,
            IdAgenda: selectedPaciente.value.Codigo
        });
        showAtoModal.value = false;
        atoForm.value = { descricao: '' };
        showToast('Ato registrado com sucesso');
        selecionarPaciente(selectedPaciente.value);
    } catch (error) {
        showToast('Erro ao salvar ato', 'error');
    }
};

const submitSinais = async () => {
    if (!selectedPaciente.value) return;
    try {
        await axios.post(route('hospitalar.internamento.sinais.store'), {
            ...sinaisForm.value,
            IdAgenda: selectedPaciente.value.Codigo
        });
        showSinaisModal.value = false;
        sinaisForm.value = { Peso: '', Temperatura: '', PressaoArterial: '', FrequenciaCardioca: '', FrequenciaRespiratoria: '', SituacaoOxigenio: '', Obs: '' };
        showToast('Sinais vitais registrados');
        selecionarPaciente(selectedPaciente.value);
    } catch (error) {
        showToast('Erro ao salvar sinais vitais', 'error');
    }
};

const submitAlta = async () => {
    if (!selectedPaciente.value) return;
    try {
        await axios.post(route('hospitalar.internamento.alta', selectedPaciente.value.Codigo), altaForm.value);
        showAltaModal.value = false;
        selectedPaciente.value = null;
        showToast('Alta processada com sucesso');
        recarregar();
    } catch (error) {
        showToast('Erro ao processar alta', 'error');
    }
};

const handleAltaConfirm = () => {
    openConfirm(
        'Confirmar Alta Médica', 
        'Tem certeza que deseja dar alta a este paciente? Esta ação atualizará o status do agendamento.',
        submitAlta
    );
};

const recarregar = async () => {
    router.reload({ 
        only: ['internados', 'historico'],
        onSuccess: () => {
            if (selectedPaciente.value) {
                selecionarPaciente(selectedPaciente.value);
            }
        }
    });
};

const openAtoModal = (type) => {
    atoType.value = type;
    showAtoModal.value = true;
};

const openSinaisModal = () => {
    sinaisForm.value = {
        Peso: '0',
        Temperatura: '0',
        PressaoArterial: '0',
        PressaoArterialBE: '0',
        FrequenciaCardioca: '0',
        PulsoBE: '0',
        FrequenciaRespiratoria: '0',
        SituacaoOxigenio: '0',
        Obs: '',
        Enfermeiro: page.props.auth.user.name || 'Enfermeiro'
    };
    showSinaisModal.value = true;
};

const imprimirProcesso = () => {
    if (!selectedPaciente.value) return;
    window.open(route('hospitalar.internamento.imprimir.processo', selectedPaciente.value.Codigo), '_blank');
};

const imprimirAtosEnfermagem = () => {
    if (!selectedPaciente.value) return;
    window.open(route('hospitalar.internamento.imprimir.atos-enfermagem', selectedPaciente.value.Codigo), '_blank');
};

const imprimirRelatorioVitais = () => {
    if (!selectedPaciente.value) return;
    window.open(route('hospitalar.internamento.imprimir.vitais', selectedPaciente.value.Codigo), '_blank');
};

// ─── Cumprimento (Enfermagem) ─────────────────────────────────────────────

const openCumprimentoModal = async () => {
    if (!selectedPaciente.value) return;
    showCumprimentoModal.value = true;
    cumprimentoTab.value = 'prescricoes';
    cartFarmacos.value = [];
    motivoSaida.value = '';
    searchArtigo.value = '';
    artigos.value = [];

    // Carrega prescrições com estado local de cumprimento
    prescricoesCumprimento.value = details.value.prescricoes.map(p => ({
        id: p.Id,
        DataInternamento: p.DataInternamento || p.CREATED_AT,
        Descricao: p.Descricao,
        c0: p.Cumprimento === 'True',
        c1: p.Cumprimento1 === 'True',
        c2: p.Cumprimento2 === 'True',
        c3: p.Cumprimento3 === 'True',
        Observacao: p.Observacao || ''
    }));

    // Carrega depósitos
    try {
        const res = await axios.get(route('hospitalar.internamento.depositos'));
        depositos.value = res.data;
        if (res.data.length > 0) {
            depositoSelecionado.value = res.data[0].CODIGO;
            await buscarArtigos();
        }
    } catch (e) {
        console.error('Erro ao carregar depósitos:', e);
    }
};

const buscarArtigos = async () => {
    try {
        const res = await axios.get(route('hospitalar.internamento.artigos'), {
            params: { deposito: depositoSelecionado.value, search: searchArtigo.value }
        });
        artigos.value = res.data;
    } catch (e) {
        console.error('Erro ao buscar artigos:', e);
    }
};

const adicionarAoCarrinho = (artigo) => {
    const existe = cartFarmacos.value.find(i => i.CODIGO === artigo.CODIGO);
    if (existe) {
        existe.quantidade++;
    } else {
        cartFarmacos.value.push({ ...artigo, quantidade: 1 });
    }
};

const removerDoCarrinho = (codigo) => {
    cartFarmacos.value = cartFarmacos.value.filter(i => i.CODIGO !== codigo);
};

const gravarCumprimento = async () => {
    try {
        await axios.post(route('hospitalar.internamento.cumprimento'), {
            cumprimentos: prescricoesCumprimento.value
        });
        showToast('Cumprimento gravado com sucesso!');
        // Abrir impresso
        window.open(route('hospitalar.internamento.imprimir.cumprimento', selectedPaciente.value.Codigo), '_blank');
    } catch (e) {
        showToast('Erro ao gravar cumprimento', 'error');
    }
};

const finalizarSaidaFarmaco = async () => {
    if (cartFarmacos.value.length === 0) {
        showToast('Adicione fármacos ao carrinho primeiro!', 'error');
        return;
    }
    try {
        await axios.post(route('hospitalar.internamento.saida-farmaco'), {
            itens: cartFarmacos.value,
            deposito: depositoSelecionado.value,
            paciente: selectedPaciente.value?.PacienteNome,
            motivo: motivoSaida.value || 'Saída de Fármacos - Internamento',
            idAgenda: selectedPaciente.value?.Codigo
        });
        showToast('Saída de fármacos registada com sucesso!');
        cartFarmacos.value = [];
        motivoSaida.value = '';
        await buscarArtigos(); // refresh stock
    } catch (e) {
        showToast('Erro ao registar saída de fármacos', 'error');
    }
};
</script>

<template>
    <Head title="Internamento" />

    <DashboardLayout>
        <!-- Toast Notification -->
        <Transition name="toast">
            <div v-if="toast.show" 
                class="fixed bottom-10 right-10 z-[600] p-4 rounded bg-white shadow-2xl border-l-4"
                :class="toast.type === 'success' ? 'border-green-500' : 'border-red-500'">
                <div class="flex items-center gap-3">
                    <CheckCircle2 v-if="toast.type === 'success'" class="w-5 h-5 text-green-500" />
                    <AlertCircle v-else class="w-5 h-5 text-red-500" />
                    <span class="text-xs font-bold">{{ toast.message }}</span>
                </div>
            </div>
        </Transition>

        <div class="h-[calc(100vh-64px)] flex flex-col bg-[#f4f4f4] text-[11px] text-slate-800 overflow-hidden font-sans">

            <!-- Segmented Top Action Bar (Matches Screenshot) -->
            <div class="flex items-center gap-1 p-1 bg-white border-b border-slate-300 shrink-0 h-14">
                <!-- Group 1: General -->
                <div class="flex gap-1 pr-2 border-r border-slate-200">
                    <button @click="recarregar" class="bg-[#2196F3] text-white px-3 py-2 font-bold hover:bg-[#1976D2] text-[10px] min-w-[120px]">
                        Atualizar Registos
                    </button>
                    <button @click="imprimirProcesso" :disabled="!selectedPaciente" class="bg-[#2196F3] text-white px-3 py-2 font-bold hover:bg-[#1976D2] text-[10px] min-w-[120px] disabled:opacity-50">
                        Imprimir Processo Clínico
                    </button>
                </div>

                <!-- Group 2: Área dos Médicos -->
                <div class="flex flex-col flex-1 mx-1 border border-blue-200 rounded-sm">
                    <div class="bg-[#f0f7ff] text-[#2196F3] text-center font-black text-[9px] uppercase py-0.5 border-b border-blue-100">Área dos Médicos</div>
                    <div class="flex gap-0.5 p-0.5">
                        <button @click="requireMedico(() => showPrescricaoModal = true)" class="flex-1 bg-[#2196F3] text-white py-1.5 font-bold hover:bg-[#1976D2] text-[9px]">Prescrições Médicas</button>
                        <button @click="requireMedico(() => openAtoModal('medico'))" class="flex-1 bg-[#2196F3] text-white py-1.5 font-bold hover:bg-[#1976D2] text-[9px]">Registo de Actos Médicos</button>
                        <button @click="requireMedico(() => showAltaModal = true)" class="flex-1 bg-[#2196F3] text-white py-1.5 font-bold hover:bg-[#1976D2] text-[9px]">Título de Alta</button>
                    </div>
                </div>

                <!-- Group 3: Área dos Enfermeiros -->
                <div v-if="isEnfermeiro" class="flex flex-col flex-1 mx-1 border border-blue-200 rounded-sm">
                    <div class="bg-[#f0f7ff] text-[#2196F3] text-center font-black text-[9px] uppercase py-0.5 border-b border-blue-100">Área dos Enfermeiros</div>
                    <div class="flex gap-0.5 p-0.5">
                        <button @click="openCumprimentoModal" class="flex-1 bg-[#2196F3] text-white py-1.5 font-bold hover:bg-[#1976D2] text-[9px]">Cumprimento (Enfermagem)</button>
                        <button @click="openSinaisModal" class="flex-1 bg-[#2196F3] text-white py-1.5 font-bold hover:bg-[#1976D2] text-[9px]">Controlo de Sinais Vitais</button>
                        <button @click="openAtoModal('enfermagem')" class="flex-1 bg-[#2196F3] text-white py-1.5 font-bold hover:bg-[#1976D2] text-[9px]">Registo de Actos da Enfermaria</button>
                    </div>
                </div>
            </div>

            <!-- Dashboard Sub-Header (Blue Bar) -->
            <div class="bg-[#000080] h-6 flex items-center px-4 shrink-0 shadow-md">
                <div class="text-white font-bold text-[10px] tracking-widest flex items-center gap-2">
                    <Users class="w-3.5 h-3.5" />
                    GESTÃO DE INTERNAMENTO HOSPITALAR
                    <span v-if="selectedPaciente" class="ml-4 bg-white/20 px-2 py-0.5 rounded">PACIENTE ATUAL: {{ selectedPaciente.PacienteNome }}</span>
                </div>
            </div>

            <!-- Main Layout (Matches Screenshot) -->
            <div class="flex-1 flex overflow-hidden p-1 gap-1">
                
                <!-- Left Column: Patient Lists (Large Area) -->
                <div class="w-[65%] flex flex-col gap-1 overflow-hidden">
                    
                    <!-- Top: Pacientes Internados -->
                    <div class="flex-[3] bg-white border border-slate-300 flex flex-col overflow-hidden">
                        <div class="bg-[#f8f9fa] border-b border-slate-200 p-1 flex items-center">
                             <div class="relative flex-1 max-w-sm">
                                <Search class="w-3 h-3 absolute left-2 top-1.5 text-slate-400" />
                                <input v-model="searchTerm" type="text" placeholder="Drag a column header here to group by that column" class="w-full pl-7 pr-2 py-1 text-[10px] bg-white border border-slate-200 outline-none focus:border-blue-400" />
                            </div>
                        </div>
                        <div class="flex-1 overflow-y-auto custom-scrollbar">
                            <table class="w-full text-left border-collapse border-slate-200">
                                <thead class="sticky top-0 bg-[#eef2f7] border-b border-slate-300 z-10 text-[10px] text-slate-600">
                                    <tr>
                                        <th class="p-2 border-r border-slate-200 font-bold">Codigo</th>
                                        <th class="p-2 border-r border-slate-200 font-bold">Paciente</th>
                                        <th class="p-2 border-r border-slate-200 font-bold">Consulta</th>
                                        <th class="p-2 border-r border-slate-200 font-bold">Data Internamento</th>
                                        <th class="p-2 border-r border-slate-200 font-bold">Tipo</th>
                                        <th class="p-2 font-bold">Medico</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in filteredInternados" :key="p.Codigo" 
                                        @click="selecionarPaciente(p)"
                                        class="border-b border-slate-100 hover:bg-blue-50 cursor-pointer text-[10px] transition-colors"
                                        :class="{'bg-[#e3f2fd] border-l-4 border-l-[#2196F3]': selectedPaciente?.Codigo === p.Codigo}">
                                        <td class="p-2 border-r border-slate-100/50">{{ p.Codigo }}</td>
                                        <td class="p-2 border-r border-slate-100/50 font-bold">{{ p.PacienteNome }}</td>
                                        <td class="p-2 border-r border-slate-100/50">{{ p.DescricaoConsulta || 'N/D' }}</td>
                                        <td class="p-2 border-r border-slate-100/50">{{ p.DataInternamento }}</td>
                                        <td class="p-2 border-r border-slate-100/50">{{ p.Tipo || 'Internamento' }}</td>
                                        <td class="p-2">{{ p.MedicoNome }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Bottom: Histórico -->
                    <div class="flex-1 bg-white border border-slate-300 flex flex-col overflow-hidden min-h-[150px]">
                        <div class="bg-[#f0f0f0] px-2 py-1 font-bold text-slate-600 border-b border-slate-300 text-[10px]">Histórico de Pacientes Internados</div>
                        <div class="p-1 bg-white border-b border-slate-200">
                            <input v-model="histSearchTerm" type="text" placeholder="Enter text to search..." class="w-64 border border-slate-200 px-2 py-1 text-[10px]" />
                        </div>
                        <div class="flex-1 overflow-y-auto custom-scrollbar">
                            <table class="w-full text-left border-collapse text-[9px]">
                                <thead class="sticky top-0 bg-[#f8f8f8] border-b border-slate-200 z-10 text-slate-500">
                                    <tr>
                                        <th class="p-1.5 border-r border-slate-200">Codigo</th>
                                        <th class="p-1.5 border-r border-slate-200">Tipo</th>
                                        <th class="p-1.5 border-r border-slate-200">Paciente</th>
                                        <th class="p-1.5 border-r border-slate-200">Data Entrada</th>
                                        <th class="p-1.5">Relatorio Processo...</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="h in filteredHistorico" :key="h.Codigo" class="border-b border-slate-50 hover:bg-slate-50">
                                        <td class="p-1.5 border-r border-slate-50">{{ h.Codigo }}</td>
                                        <td class="p-1.5 border-r border-slate-50">Histórico</td>
                                        <td class="p-1.5 border-r border-slate-50">{{ h.PacienteNome }}</td>
                                        <td class="p-1.5 border-r border-slate-50">{{ h.DataEntrada }}</td>
                                        <td class="p-1.5 text-blue-600 underline cursor-pointer">Visualizar</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Vertical Clinical Records (Matches Screenshot Stack) -->
                <div class="w-[35%] flex flex-col gap-1 overflow-hidden">
                    
                    <!-- 1. Prescrições Médicas -->
                    <div class="flex-[1.5] bg-white border border-slate-300 flex flex-col overflow-hidden">
                        <div class="bg-[#f0f0f0] px-2 py-1 font-bold text-slate-700 border-b border-slate-300 flex justify-between items-center text-[10px]">
                            <span>Prescrições Médicas</span>
                        </div>
                        <div class="p-1.5 bg-white border-b border-slate-200 flex gap-1">
                            <input v-model="prescricoesSearchTerm" type="text" placeholder="Enter text to search..." class="flex-1 border border-slate-200 px-2 py-1 text-[10px]" />
                            <button class="bg-[#f8f8f8] border border-slate-300 px-3 py-1 hover:bg-slate-100 text-[10px]">Find</button>
                        </div>
                        <div class="flex-1 overflow-y-auto custom-scrollbar">
                            <table class="w-full text-left border-collapse text-[10px]">
                                <thead class="sticky top-0 bg-[#f4f4f4] border-b border-slate-200 z-10 text-slate-500">
                                    <tr>
                                        <th class="p-1 border-r border-slate-200 text-center w-6">F</th>
                                        <th class="p-1.5 border-r border-slate-200">Medico</th>
                                        <th class="p-1.5 border-r border-slate-200">Descricao</th>
                                        <th class="p-1.5 border-r border-slate-200">Enfermeiro</th>
                                        <th class="p-1 border-r border-slate-200 text-center w-6">M</th>
                                        <th class="p-1 border-r border-slate-200 text-center w-6">T</th>
                                        <th class="p-1 border-r border-slate-200 text-center w-6">N</th>
                                        <th class="p-1.5">Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in filteredPrescricoes" :key="p.Id" class="border-b border-slate-50">
                                        <td class="p-1 border-r border-slate-50 text-center">
                                            <input type="checkbox" :checked="p.Cumprimento === 'True'" @change="togglePrescricaoStatus(p, 'Cumprimento')" />
                                        </td>
                                        <td class="p-1.5 border-r border-slate-50 truncate max-w-[80px]" :title="p.Medico">{{ p.Medico }}</td>
                                        <td class="p-1.5 border-r border-slate-50 truncate max-w-[120px]" :title="p.Descricao">{{ p.Descricao }}</td>
                                        <td class="p-1.5 border-r border-slate-50 italic text-slate-400">{{ p.Infermeiro || '---' }}</td>
                                        <td class="p-1 border-r border-slate-50 text-center">
                                            <input type="checkbox" :checked="p.Cumprimento1 === 'True'" @change="togglePrescricaoStatus(p, 'Cumprimento1')" />
                                        </td>
                                        <td class="p-1 border-r border-slate-50 text-center">
                                            <input type="checkbox" :checked="p.Cumprimento2 === 'True'" @change="togglePrescricaoStatus(p, 'Cumprimento2')" />
                                        </td>
                                        <td class="p-1 border-r border-slate-50 text-center">
                                            <input type="checkbox" :checked="p.Cumprimento3 === 'True'" @change="togglePrescricaoStatus(p, 'Cumprimento3')" />
                                        </td>
                                        <td class="p-1.5 whitespace-nowrap">{{ p.DataInternamento || p.CREATED_AT }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 2. Registo de Visitas e Actos Médicos -->
                    <div class="flex-1 bg-white border border-slate-300 flex flex-col overflow-hidden">
                        <div class="bg-[#f0f0f0] px-2 py-1 font-bold text-slate-700 border-b border-slate-300 text-[10px]">Registo de Visitas e Actos Médicos</div>
                        <div class="p-1 bg-white border-b border-slate-200">
                             <div class="relative">
                                <Search class="w-3 h-3 absolute left-2 top-1.5 text-slate-400" />
                                <input type="text" placeholder="Drag a column header here to group by that column" class="w-full pl-7 pr-2 py-1 text-[9px] bg-white border border-slate-200 outline-none" />
                            </div>
                        </div>
                        <div class="flex-1 overflow-y-auto custom-scrollbar">
                            <table class="w-full text-left border-collapse text-[9px]">
                                <thead class="sticky top-0 bg-[#f8f8f8] border-b border-slate-200 z-10 text-slate-500">
                                    <tr>
                                        <th class="p-1.5 border-r border-slate-200">Data</th>
                                        <th class="p-1.5 border-r border-slate-200">Medico</th>
                                        <th class="p-1.5">Descrição</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="a in details.atosMedicos" :key="a.Id" class="border-b border-slate-50">
                                        <td class="p-1.5 border-r border-slate-50 whitespace-nowrap">{{ a.DataAto || a.CREATED_AT }}</td>
                                        <td class="p-1.5 border-r border-slate-50">{{ a.Medico }}</td>
                                        <td class="p-1.5 italic text-slate-500">{{ a.Descricao }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 3. Controlo de Sinais Vitais -->
                    <div class="flex-1 bg-white border border-slate-300 flex flex-col overflow-hidden">
                        <div class="bg-[#f0f0f0] px-2 py-1 font-bold text-slate-700 border-b border-slate-300 text-[10px]">Controlo de Sinais Vitais</div>
                        <div class="p-1 bg-white border-b border-slate-200">
                             <div class="relative">
                                <Search class="w-3 h-3 absolute left-2 top-1.5 text-slate-400" />
                                <input type="text" placeholder="Drag a column header here to group by that column" class="w-full pl-7 pr-2 py-1 text-[9px] bg-white border border-slate-200 outline-none" />
                            </div>
                        </div>
                        <div class="flex-1 overflow-y-auto custom-scrollbar">
                            <table class="w-full text-left border-collapse text-[9px]">
                                <thead class="sticky top-0 bg-[#f8f8f8] border-b border-slate-200 z-10 text-slate-500">
                                    <tr>
                                        <th class="p-1.5 border-r border-slate-200">Cod. Paciente</th>
                                        <th class="p-1.5 border-r border-slate-200">Data</th>
                                        <th class="p-1.5 border-r border-slate-200">Medico</th>
                                        <th class="p-1.5 border-r border-slate-200 text-center w-8">Temp.</th>
                                        <th class="p-1.5 border-r border-slate-200 text-center w-8">Peso</th>
                                        <th class="p-1.5 text-center w-12">Pressao</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="v in details.sinaisVitais" :key="v.Id" class="border-b border-slate-50">
                                        <td class="p-1.5 border-r border-slate-50">{{ selectedPaciente?.Codigo }}</td>
                                        <td class="p-1.5 border-r border-slate-50 whitespace-nowrap">{{ v.DataAgendamento || v.CREATED_AT }}</td>
                                        <td class="p-1.5 border-r border-slate-50">{{ selectedPaciente?.PacienteNome }}</td>
                                        <td class="p-1.5 border-r border-slate-50 text-center font-bold text-red-600">{{ v.Temperatura }}°</td>
                                        <td class="p-1.5 border-r border-slate-50 text-center">{{ v.Peso }}kg</td>
                                        <td class="p-1.5 text-center">{{ v.PressaoArterial }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        
        <!-- Nursing Modal (Refined to be unified but opened via button) -->
        <div v-if="showAtoModal && atoType === 'enfermagem'" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[500] p-4">
            <div class="bg-white w-full max-w-5xl shadow-2xl border-4 border-[#000080] flex flex-col h-[700px] rounded-sm overflow-hidden animate-in zoom-in duration-200">
                <div class="bg-[#000080] text-white p-2.5 font-bold text-xs text-center uppercase tracking-widest relative">
                    REGISTO DE VISITAS E ATOS DA ENFERMARIA
                    <button @click="showAtoModal = false" class="absolute right-3 top-2 hover:bg-red-600 rounded px-2 transition-colors">&times;</button>
                </div>
                
                <div class="flex-1 flex flex-col bg-[#e9e9e9] p-6 overflow-hidden">
                    <div class="text-center text-slate-800 font-bold text-2xl mb-6">Registo de Visitas e Atos da Enfermagem</div>
                    
                    <div class="flex gap-4 mb-6 shrink-0">
                        <button @click="submitAto" class="bg-white border-2 border-slate-400 p-3 flex flex-col items-center min-w-[130px] hover:bg-green-50 hover:border-green-500 transition-all rounded shadow-md group">
                            <Plus class="w-12 h-12 text-green-600 mb-1" />
                            <span class="text-[11px] font-black uppercase text-slate-700">Adicionar</span>
                        </button>
                        <button @click="imprimirAtosEnfermagem" class="bg-white border-2 border-slate-400 p-3 flex flex-col items-center min-w-[130px] hover:bg-blue-50 hover:border-blue-500 transition-all rounded shadow-md group">
                            <Printer class="w-12 h-12 text-slate-600 mb-1" />
                            <span class="text-[11px] font-black uppercase text-slate-700">Imprimir</span>
                        </button>
                    </div>

                    <div class="flex gap-8 mb-6 shrink-0 bg-white/50 p-4 border border-slate-300 rounded">
                        <div class="w-1/3 space-y-4">
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase mb-1">Data e Hora</label>
                                <div class="w-full border-2 border-slate-300 p-2 text-xs bg-white font-bold">{{ new Date().toLocaleString() }}</div>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase mb-1">Paciente</label>
                                <div class="w-full border-2 border-slate-300 p-2 text-xs bg-[#f0f0f0] font-black">{{ selectedPaciente?.PacienteNome }}</div>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase mb-1">Enfermeiro</label>
                                <div class="w-full border-2 border-slate-300 p-2 text-xs bg-white font-bold">{{ $page.props.auth.user.name }}</div>
                            </div>
                        </div>
                        <div class="flex-1 flex flex-col">
                            <label class="block text-xs font-black text-slate-500 uppercase mb-1">Atos Realizados</label>
                            <textarea v-model="atoForm.descricao" class="flex-1 border-2 border-slate-300 p-3 text-sm focus:border-blue-500 outline-none resize-none bg-white rounded shadow-inner" placeholder="Descreva as intervenções..."></textarea>
                        </div>
                    </div>

                    <div class="flex-1 bg-white border-2 border-slate-400 flex flex-col overflow-hidden rounded shadow-xl">
                        <div class="bg-white text-slate-400 text-[10px] p-2 border-b border-slate-200 flex justify-between items-center italic font-bold">
                            <span>Drag a column header here to group by that column</span>
                            <Search class="w-4 h-4" />
                        </div>
                        <div class="flex-1 overflow-y-auto custom-scrollbar">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead class="sticky top-0 bg-[#f4f4f4] border-b-2 border-slate-300 z-10 font-black uppercase text-slate-600">
                                    <tr>
                                        <th class="p-3 border-r border-slate-300 w-24">AGENDA</th>
                                        <th class="p-3 border-r border-slate-300 w-44">DATA_HORA</th>
                                        <th class="p-3 border-r border-slate-300">DESCRICAO</th>
                                        <th class="p-3 border-r border-slate-300 w-56">PACIENTE</th>
                                        <th class="p-3 w-56">ENFERMEIRO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="a in details.atosEnfermagem" :key="a.Id" class="border-b border-slate-100 hover:bg-blue-50 transition-colors">
                                        <td class="p-3 border-r border-slate-100 text-slate-400 font-mono">{{ selectedPaciente?.Codigo }}</td>
                                        <td class="p-3 border-r border-slate-100">{{ a.DataAto || a.CREATED_AT }}</td>
                                        <td class="p-3 border-r border-slate-100 text-blue-800 italic">{{ a.Descricao }}</td>
                                        <td class="p-3 border-r border-slate-100">{{ selectedPaciente?.PacienteNome }}</td>
                                        <td class="p-3 font-bold text-slate-500 uppercase text-[10px]">{{ a.Enfermeiro }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Medical Ato Modal -->
        <div v-if="showAtoModal && atoType === 'medico'" class="fixed inset-0 bg-black/70 flex items-center justify-center z-[500] p-4">
            <div class="bg-white w-full max-w-lg shadow-2xl rounded-sm overflow-hidden border-2 border-blue-900 animate-in zoom-in duration-200">
                <div class="bg-[#000080] text-white p-4 font-bold text-center uppercase tracking-widest relative">
                    Registo de Visita Médica
                    <button @click="showAtoModal = false" class="absolute right-4 top-3 hover:bg-red-600 rounded px-2 transition-colors">&times;</button>
                </div>
                <div class="p-6">
                    <label class="block text-[11px] font-black uppercase text-slate-500 mb-2">Evolução Clínica / Ato Médico</label>
                    <textarea v-model="atoForm.descricao" rows="10" class="w-full border-2 border-slate-300 p-4 text-sm font-bold focus:border-blue-600 outline-none resize-none rounded bg-slate-50 shadow-inner" placeholder="Registe a evolução do paciente..."></textarea>
                </div>
                <div class="p-4 bg-slate-100 border-t border-slate-200 flex justify-end gap-2">
                    <button @click="showAtoModal = false" class="px-6 py-2 border-2 border-slate-300 hover:bg-slate-200 text-xs font-black uppercase rounded transition-all">Cancelar</button>
                    <button @click="submitAto" class="px-6 py-2 bg-blue-600 text-white hover:bg-blue-800 text-xs font-black uppercase rounded shadow-lg transition-all">Gravar Registo</button>
                </div>
            </div>
        </div>

        <!-- Sinais Vitais Modal (Modernized Legacy) -->
        <div v-if="showSinaisModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-[500] p-4">
            <div class="bg-slate-50 w-full max-w-6xl shadow-2xl rounded-xl overflow-hidden flex flex-col h-[750px] border border-white animate-in zoom-in duration-300">
                <!-- Premium Header -->
                <div class="bg-gradient-to-r from-[#000080] to-blue-800 text-white p-4 flex justify-between items-center shadow-lg relative">
                    <div class="flex items-center gap-3">
                        <Activity class="w-5 h-5 text-blue-300" />
                        <span class="font-black uppercase text-sm tracking-widest">Controlo de Sinais Vitais</span>
                    </div>
                    <div class="absolute left-1/2 -translate-x-1/2 font-black text-blue-200 text-xs tracking-tighter opacity-50">
                        SESSÃO: {{ selectedPaciente?.Codigo }}
                    </div>
                    <button @click="showSinaisModal = false" class="hover:bg-red-500/20 text-white/80 hover:text-white rounded-full p-1.5 transition-all">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="flex-1 flex overflow-hidden p-6 gap-6">
                    <!-- Left Column: Patient & Form -->
                    <div class="w-[45%] flex flex-col gap-6 overflow-y-auto custom-scrollbar pr-2">
                        <!-- Patient Mini-Card -->
                        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm relative overflow-hidden group">
                            <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50 rounded-bl-full -mr-12 -mt-12 transition-all group-hover:scale-110"></div>
                            <div class="relative">
                                <div class="text-blue-600 font-black text-xs mb-1 uppercase tracking-tighter">Paciente em Internamento</div>
                                <div class="text-2xl font-black text-slate-800 leading-tight">{{ selectedPaciente?.PacienteNome }}</div>
                                <div class="flex items-center gap-4 mt-3 text-xs font-bold text-slate-500">
                                    <span class="flex items-center gap-1.5"><LayoutGrid class="w-3.5 h-3.5" /> ID: {{ selectedPaciente?.Codigo }}</span>
                                    <span class="flex items-center gap-1.5"><Users class="w-3.5 h-3.5" /> {{ selectedPaciente?.Tipo || 'Normal' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Vital Signs Form -->
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-5">
                            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                                <div class="p-2 bg-blue-50 text-blue-600 rounded-lg"><Syringe class="w-4 h-4" /></div>
                                <span class="font-black text-xs uppercase text-slate-500 tracking-wider">Registo de Parâmetros</span>
                            </div>

                            <div class="space-y-4">
                                <!-- Nurse Field -->
                                <div class="group">
                                    <label class="flex items-center gap-2 text-[10px] font-black uppercase text-slate-400 mb-1.5 group-focus-within:text-blue-500 transition-colors">
                                        <Users class="w-3 h-3" /> Enfermeiro Responsável
                                    </label>
                                    <div class="w-full bg-slate-50 border border-slate-200 p-2.5 rounded-lg text-xs font-black text-slate-700 shadow-inner">{{ $page.props.auth.user.name }}</div>
                                </div>

                                <!-- Form Grid -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div v-for="f in [
                                        {label: 'Peso', unit: 'kg', model: 'Peso', icon: LayoutGrid},
                                        {label: 'Temperatura', unit: '°C', model: 'Temperatura', icon: AlertCircle},
                                        {label: 'Tensão Art. (BD)', unit: 'mmHg', model: 'PressaoArterial', icon: Activity},
                                        {label: 'Tensão Art. (BE)', unit: 'mmHg', model: 'PressaoArterialBE', icon: Activity},
                                        {label: 'Pulsação (BD)', unit: 'bpm', model: 'FrequenciaCardioca', icon: CheckCircle2},
                                        {label: 'Pulsação (BE)', unit: 'bpm', model: 'PulsoBE', icon: CheckCircle2},
                                        {label: 'Freq. Resp.', unit: 'rpm', model: 'FrequenciaRespiratoria', icon: Stethoscope},
                                        {label: 'Oximetria', unit: '%', model: 'SituacaoOxigenio', icon: ClipboardCheck},
                                    ]" :key="f.model" class="group">
                                        <label class="flex items-center gap-2 text-[10px] font-black uppercase text-slate-400 mb-1.5 group-focus-within:text-blue-500 transition-colors">
                                            <component :is="f.icon" class="w-3 h-3" /> {{ f.label }}
                                        </label>
                                        <div class="relative">
                                            <input v-model="sinaisForm[f.model]" type="text" class="w-full border border-slate-200 p-2.5 pr-8 rounded-lg text-xs font-black outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all text-right shadow-sm bg-slate-50/50" />
                                            <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[9px] font-bold text-slate-300">{{ f.unit }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="group">
                                    <label class="flex items-center gap-2 text-[10px] font-black uppercase text-slate-400 mb-1.5 group-focus-within:text-blue-500">
                                        <ClipboardCheck class="w-3 h-3" /> Observações Clínicas
                                    </label>
                                    <textarea v-model="sinaisForm.Obs" class="w-full border border-slate-200 p-3 rounded-xl text-xs font-bold outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all h-24 shadow-sm bg-slate-50/50 resize-none" placeholder="Indique qualquer anomalia ou observação relevante..."></textarea>
                                </div>
                            </div>

                            <button @click="submitSinais" class="w-full py-3.5 bg-gradient-to-r from-blue-600 to-blue-800 text-white font-black uppercase text-xs rounded-xl shadow-xl hover:shadow-blue-500/20 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2 mt-4">
                                <PlusCircle class="w-4 h-4" /> Gravar Dados da Triagem
                            </button>
                        </div>
                    </div>

                    <!-- Right Column: History Tracking -->
                    <div class="flex-1 flex flex-col gap-6">
                        <div class="bg-white flex-1 rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                            <div class="p-5 border-b border-slate-100 bg-white flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-slate-50 text-slate-600 rounded-lg"><ClipboardCheck class="w-4 h-4" /></div>
                                    <span class="font-black text-xs uppercase text-slate-500 tracking-wider">Histórico de Triagens</span>
                                </div>
                                <div class="flex gap-2">
                                    <div class="flex flex-col">
                                        <span class="text-[8px] font-black text-slate-400 uppercase ml-1 mb-0.5">Início</span>
                                        <input type="date" class="border border-slate-200 p-1.5 rounded-lg text-[10px] font-bold outline-none bg-slate-50" />
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[8px] font-black text-slate-400 uppercase ml-1 mb-0.5">Fim</span>
                                        <input type="date" class="border border-slate-200 p-1.5 rounded-lg text-[10px] font-bold outline-none bg-slate-50" />
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex-1 overflow-auto custom-scrollbar">
                                <table class="w-full text-left border-collapse">
                                    <thead class="bg-slate-50 sticky top-0 font-black uppercase text-[10px] text-slate-500 border-b border-slate-200 z-10">
                                        <tr>
                                            <th class="p-4">Data/Hora</th>
                                            <th class="p-4">Paciente</th>
                                            <th class="p-4 text-center">Temp/Peso</th>
                                            <th class="p-4 text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-[11px]">
                                        <tr v-for="sv in details.sinaisVitais" :key="sv.Id" class="border-b border-slate-50 hover:bg-blue-50/50 transition-colors group">
                                            <td class="p-4 font-bold text-slate-600">{{ new Date(sv.CREATED_AT).toLocaleString() }}</td>
                                            <td class="p-4">
                                                <div class="font-black text-slate-800">{{ selectedPaciente?.PacienteNome }}</div>
                                                <div class="text-[10px] text-slate-400 font-bold uppercase">{{ sv.IdAgenda }}</div>
                                            </td>
                                            <td class="p-4 text-center">
                                                <span class="inline-flex gap-2 bg-slate-100 px-2 py-1 rounded-full font-black text-slate-600">
                                                    <span>{{ sv.Temperatura }}°C</span>
                                                    <span class="text-slate-300">|</span>
                                                    <span>{{ sv.Peso }}kg</span>
                                                </span>
                                            </td>
                                            <td class="p-4 text-center">
                                                <button @click="sinaisForm = {...sv}" class="bg-white border border-slate-200 px-3 py-1.5 rounded-lg font-black text-blue-600 hover:bg-blue-600 hover:text-white hover:border-blue-600 transition-all shadow-sm">
                                                    Selecionar
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="!details.sinaisVitais.length">
                                            <td colspan="4" class="p-12 text-center text-slate-400">
                                                <AlertCircle class="w-12 h-12 mx-auto mb-3 opacity-20" />
                                                <div class="font-bold">Nenhum registo encontrado para este paciente.</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Footer Actions -->
                            <div class="p-6 bg-slate-50 border-t border-slate-200">
                                <button @click="imprimirRelatorioVitais" class="w-full py-3.5 bg-white border-2 border-blue-600 text-blue-600 font-black uppercase text-xs rounded-xl shadow-lg hover:bg-blue-600 hover:text-white transition-all flex items-center justify-center gap-2">
                                    <Printer class="w-4 h-4" /> Gerar Relatório de Controlo de Sinais
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alta Modal -->
        <!-- Cumprimento (Enfermagem) Modal -->
        <div v-if="showCumprimentoModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-[500] p-4">
            <div class="bg-slate-50 w-full max-w-7xl shadow-2xl rounded-xl overflow-hidden flex flex-col h-[850px] border border-white animate-in zoom-in duration-300">
                <!-- Header -->
                <div class="bg-gradient-to-r from-[#000080] to-blue-800 text-white p-4 flex justify-between items-center shadow-lg relative">
                    <div class="flex items-center gap-3">
                        <ClipboardCheck class="w-5 h-5 text-blue-300" />
                        <span class="font-black uppercase text-sm tracking-widest">PRESCRIÇÕES MÉDICAS - Cumprimento da Enfermagem</span>
                    </div>
                    <button @click="showCumprimentoModal = false" class="hover:bg-red-500/20 text-white/80 hover:text-white rounded-full p-1.5 transition-all">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Main Content Area -->
                <div class="flex-1 flex flex-col overflow-hidden">
                    <!-- Top Section: Patient Info & Pharmacy (Stock Exit) -->
                    <div class="grid grid-cols-12 gap-6 p-6 border-b border-slate-200 bg-white">
                        <!-- Patient & Nurse Info -->
                        <div class="col-span-3 space-y-4">
                            <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                                <label class="block text-[10px] font-black uppercase text-blue-400 mb-1">Paciente</label>
                                <div class="text-sm font-black text-slate-800">{{ selectedPaciente?.PacienteNome }}</div>
                            </div>
                            <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                                <label class="block text-[10px] font-black uppercase text-slate-400 mb-1">Enfermeiro</label>
                                <div class="text-sm font-black text-slate-800">{{ $page.props.auth.user.name }}</div>
                            </div>
                            <button @click="gravarCumprimento" class="w-full py-3 bg-blue-600 text-white font-black uppercase text-xs rounded-xl shadow-lg hover:bg-blue-700 transition-all flex items-center justify-center gap-2">
                                <Printer class="w-4 h-4" /> Gravar e Imprimir
                            </button>
                        </div>

                        <!-- Fármacos Usados Section -->
                        <div class="col-span-9 bg-slate-50 rounded-xl border border-slate-200 overflow-hidden flex flex-col border-dashed">
                            <div class="bg-slate-200/50 px-4 py-2 flex justify-between items-center">
                                <div class="flex items-center gap-2 text-[10px] font-black uppercase text-slate-600">
                                    <Package2 class="w-3.5 h-3.5" /> Fármacos Usados (Saída de Stock)
                                </div>
                                <div class="flex items-center gap-4">
                                    <select v-model="depositoSelecionado" @change="buscarArtigos" class="text-[10px] font-bold border-none bg-white rounded px-2 py-1 outline-none shadow-sm">
                                        <option value="">Selecione o Depósito</option>
                                        <option v-for="d in depositos" :key="d.CODIGO" :value="d.CODIGO">{{ d.DEPOSITO }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="flex flex-1 overflow-hidden">
                                <!-- Search & Selection -->
                                <div class="w-1/2 p-4 border-r border-slate-200 flex flex-col gap-3">
                                    <div class="relative">
                                        <Search class="w-3.5 h-3.5 absolute left-3 top-2.5 text-slate-400" />
                                        <input v-model="searchArtigo" @input="buscarArtigos" type="text" placeholder="Pesquisar produto..." class="w-full pl-9 pr-4 py-2 text-xs border border-slate-200 rounded-lg outline-none focus:border-blue-400 bg-white" />
                                    </div>
                                    <div class="flex-1 overflow-y-auto custom-scrollbar border border-slate-200 rounded-lg bg-white">
                                        <table class="w-full text-[10px]">
                                            <thead class="bg-slate-50 sticky top-0 border-b border-slate-100 z-10">
                                                <tr>
                                                    <th class="p-2 text-left">Produto</th>
                                                    <th class="p-2 text-right">Stock</th>
                                                    <th class="p-2 text-center">Add</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="a in artigos" :key="a.CODIGO" class="border-b border-slate-50 hover:bg-blue-50">
                                                    <td class="p-2 font-bold text-slate-700">{{ a.PRODUTO }}</td>
                                                    <td class="p-2 text-right font-black text-blue-600">{{ a.Stock }}</td>
                                                    <td class="p-2 text-center">
                                                        <button @click="adicionarAoCarrinho(a)" class="p-1 hover:bg-blue-600 hover:text-white rounded transition-colors text-blue-600">
                                                            <Plus class="w-4 h-4" />
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Cart & Finalize -->
                                <div class="w-1/2 p-4 flex flex-col gap-3">
                                    <div class="flex-1 overflow-y-auto custom-scrollbar border border-slate-200 rounded-lg bg-white">
                                        <table class="w-full text-[10px]">
                                            <thead class="bg-slate-50 sticky top-0 border-b border-slate-100 z-10">
                                                <tr>
                                                    <th class="p-2 text-left">Produto</th>
                                                    <th class="p-2 text-center">Qtd</th>
                                                    <th class="p-2 text-right">Preço</th>
                                                    <th class="p-2 text-center"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="item in cartFarmacos" :key="item.CODIGO" class="border-b border-slate-50">
                                                    <td class="p-2 font-bold">{{ item.PRODUTO }}</td>
                                                    <td class="p-2 text-center">
                                                        <input v-model="item.quantidade" type="number" class="w-12 text-center border-none bg-slate-50 rounded font-black" min="1" />
                                                    </td>
                                                    <td class="p-2 text-right font-black">{{ (item.PRECO * item.quantidade).toLocaleString() }}</td>
                                                    <td class="p-2 text-center">
                                                        <button @click="removerDoCarrinho(item.CODIGO)" class="text-red-500 hover:scale-110 transition-transform">
                                                            <Minus class="w-4 h-4" />
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="flex gap-2">
                                        <input v-model="motivoSaida" type="text" placeholder="Motivo da saída..." class="flex-1 border border-slate-200 p-2 text-xs rounded-lg outline-none focus:border-blue-400" />
                                        <button @click="finalizarSaidaFarmaco" class="bg-green-600 text-white px-4 py-2 rounded-lg font-black text-xs hover:bg-green-700 shadow-md transition-all">
                                            Finalizar Saída
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Section: Tabs & Prescriptions -->
                    <div class="flex-1 flex flex-col overflow-hidden">
                        <!-- Tabs -->
                        <div class="flex border-b border-slate-200 bg-slate-100 px-6">
                            <button @click="cumprimentoTab = 'prescricoes'" 
                                :class="cumprimentoTab === 'prescricoes' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-slate-400'"
                                class="px-6 py-3 font-black text-[10px] uppercase tracking-wider transition-all">
                                Prescrições Médicas
                            </button>
                            <button @click="cumprimentoTab = 'ficha'" 
                                :class="cumprimentoTab === 'ficha' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-slate-400'"
                                class="px-6 py-3 font-black text-[10px] uppercase tracking-wider transition-all">
                                Ficha Médica
                            </button>
                        </div>

                        <!-- Tab Content -->
                        <div class="flex-1 p-6 overflow-hidden">
                            <!-- Prescriptions Tab -->
                            <div v-if="cumprimentoTab === 'prescricoes'" class="h-full flex flex-col border border-slate-200 rounded-xl bg-white shadow-sm overflow-hidden">
                                <div class="flex-1 overflow-y-auto custom-scrollbar">
                                    <table class="w-full text-left border-collapse">
                                        <thead class="bg-slate-50 sticky top-0 border-b border-slate-200 z-10 text-[10px] font-black uppercase text-slate-500">
                                            <tr>
                                                <th class="p-4 w-40">Data e Hora</th>
                                                <th class="p-4">Prescrição Médica</th>
                                                <th class="p-4 w-60 text-center">Cumprimento (M/T/N/...)</th>
                                                <th class="p-4">Notas de Enfermagem</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="p in prescricoesCumprimento" :key="p.id" class="border-b border-slate-50 hover:bg-blue-50/30 transition-colors">
                                                <td class="p-4 font-bold text-slate-500">{{ p.DataInternamento }}</td>
                                                <td class="p-4 font-black text-slate-800">{{ p.Descricao }}</td>
                                                <td class="p-4">
                                                    <div class="flex justify-center gap-4">
                                                        <label class="flex items-center gap-1 cursor-pointer">
                                                            <input type="checkbox" v-model="p.c0" class="rounded text-blue-600 focus:ring-blue-500" />
                                                            <span class="text-[9px] font-black text-slate-400">M</span>
                                                        </label>
                                                        <label class="flex items-center gap-1 cursor-pointer">
                                                            <input type="checkbox" v-model="p.c1" class="rounded text-blue-600 focus:ring-blue-500" />
                                                            <span class="text-[9px] font-black text-slate-400">T</span>
                                                        </label>
                                                        <label class="flex items-center gap-1 cursor-pointer">
                                                            <input type="checkbox" v-model="p.c2" class="rounded text-blue-600 focus:ring-blue-500" />
                                                            <span class="text-[9px] font-black text-slate-400">N</span>
                                                        </label>
                                                        <label class="flex items-center gap-1 cursor-pointer">
                                                            <input type="checkbox" v-model="p.c3" class="rounded text-blue-600 focus:ring-blue-500" />
                                                            <span class="text-[9px] font-black text-slate-400">...</span>
                                                        </label>
                                                    </div>
                                                </td>
                                                <td class="p-4">
                                                    <input v-model="p.Observacao" type="text" class="w-full border border-slate-200 p-2 text-[10px] rounded focus:border-blue-400 outline-none" placeholder="Observações..." />
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Ficha Médica Tab (Placeholder for now) -->
                            <div v-else class="h-full flex items-center justify-center border border-slate-200 rounded-xl bg-slate-50 border-dashed">
                                <div class="text-center">
                                    <ClipboardCheck class="w-12 h-12 text-slate-200 mx-auto mb-3" />
                                    <div class="text-slate-400 font-bold uppercase text-xs tracking-widest">A visualizar Ficha Médica do Paciente</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showAltaModal" class="fixed inset-0 bg-black/70 flex items-center justify-center z-[500] p-4">
            <div class="bg-white w-full max-w-md shadow-2xl rounded-sm overflow-hidden border border-slate-200 scale-in-center">
                <div class="bg-green-700 text-white p-5 font-black text-center uppercase tracking-widest relative">
                    Título de Alta Hospitalar
                    <button @click="showAltaModal = false" class="absolute right-4 top-4 hover:bg-green-900 rounded-full w-8 h-8 flex items-center justify-center">&times;</button>
                </div>
                <div class="p-8 space-y-6 bg-white">
                    <div>
                        <label class="block text-[11px] font-black uppercase text-slate-500 mb-1.5">Procedimento Operado</label>
                        <input v-model="altaForm.Operado" type="text" class="w-full border-2 border-slate-200 p-3 text-sm rounded-lg outline-none focus:border-green-600 bg-slate-50" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-black uppercase text-slate-500 mb-1.5">Complicações</label>
                        <textarea v-model="altaForm.Complicacoes" rows="2" class="w-full border-2 border-slate-200 p-3 text-sm rounded-lg outline-none focus:border-green-600 bg-slate-50 resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-[11px] font-black uppercase text-slate-500 mb-1.5">Repouso e Recomendações</label>
                        <input v-model="altaForm.Repouso" type="text" class="w-full border-2 border-slate-200 p-3 text-sm rounded-lg outline-none focus:border-green-600 bg-slate-50" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-black uppercase text-slate-500 mb-1.5">Notas de Alta Finais</label>
                        <textarea v-model="altaForm.Obs" rows="3" class="w-full border-2 border-slate-200 p-3 text-sm rounded-lg outline-none focus:border-green-600 bg-slate-50 resize-none"></textarea>
                    </div>
                </div>
                <div class="p-4 bg-slate-50 flex gap-3 border-t border-slate-100">
                    <button @click="showAltaModal = false" class="flex-1 py-4 border-2 border-slate-200 text-xs font-black uppercase rounded-lg hover:bg-white transition-all">Cancelar</button>
                    <button @click="handleAltaConfirm" class="flex-[2] py-4 bg-green-600 text-white text-xs font-black uppercase rounded-lg hover:bg-green-700 shadow-xl transition-all">Confirmar Alta</button>
                </div>
            </div>
        </div>

        <!-- Prescrição Modal -->
        <div v-if="showPrescricaoModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-[500] p-4">
            <div class="bg-white w-full max-w-md shadow-2xl rounded-sm overflow-hidden border border-slate-200 scale-in-center">
                <div class="bg-[#000080] text-white p-5 font-black text-center uppercase tracking-widest relative">
                    Nova Prescrição Médica
                    <button @click="showPrescricaoModal = false" class="absolute right-5 top-4 hover:bg-blue-900 rounded-full w-8 h-8 flex items-center justify-center">&times;</button>
                </div>
                <div class="p-8 space-y-6">
                    <div>
                        <label class="block text-[11px] font-black uppercase text-slate-500 mb-1.5">Tipo</label>
                        <select v-model="prescricaoForm.Tipo" class="w-full border-2 border-slate-200 p-3 text-sm rounded-lg outline-none focus:border-blue-600 font-black bg-slate-50">
                            <option value="Internamento">Internamento</option>
                            <option value="Observacao">Observação</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-black uppercase text-slate-500 mb-1.5">Prescrição / Medicamento</label>
                        <textarea v-model="prescricaoForm.Descricao" rows="6" class="w-full border-2 border-slate-200 p-3 text-sm font-bold rounded-lg outline-none focus:border-blue-600 resize-none bg-slate-50 shadow-inner" placeholder="Ex: Ciprofloxacina 400mg..."></textarea>
                    </div>
                    <div>
                        <label class="block text-[11px] font-black uppercase text-slate-500 mb-1.5">Observações para Enfermagem</label>
                        <input v-model="prescricaoForm.Observacao" type="text" class="w-full border-2 border-slate-200 p-3 text-sm rounded-lg outline-none focus:border-blue-600 bg-slate-50" />
                    </div>
                </div>
                <div class="p-4 bg-slate-50 border-t border-slate-100 flex gap-3">
                    <button @click="showPrescricaoModal = false" class="flex-1 py-4 border-2 border-slate-200 text-xs font-black uppercase rounded-lg hover:bg-white transition-all text-slate-400">Descartar</button>
                    <button @click="submitPrescricao" class="flex-[2] py-4 bg-[#2196F3] text-white text-xs font-black uppercase rounded-lg hover:bg-blue-700 shadow-xl transition-all">Gravar Plano</button>
                </div>
            </div>
        </div>

        <!-- Elegant Confirmation Modal -->
        <div v-if="showConfirmModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-[4px] flex items-center justify-center z-[1000] p-4 animate-in fade-in duration-200">
            <div class="bg-white w-full max-w-sm rounded-xl shadow-2xl overflow-hidden border border-slate-100 scale-in-center">
                <div class="p-10 text-center">
                    <div class="w-24 h-24 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-8">
                        <AlertCircle class="w-12 h-12" />
                    </div>
                    <h3 class="text-2xl font-black text-slate-800 mb-4 tracking-tighter">{{ confirmTitle }}</h3>
                    <p class="text-sm text-slate-400 leading-relaxed font-bold px-4">{{ confirmText }}</p>
                </div>
                <div class="flex border-t border-slate-50 p-4 gap-3 bg-slate-50/50">
                    <button @click="showConfirmModal = false" class="flex-1 py-4 text-xs font-black text-slate-400 hover:bg-white transition-all uppercase tracking-widest rounded-lg border border-slate-100">Cancelar</button>
                    <button @click="executeConfirm" class="flex-1 py-4 text-xs font-black text-blue-600 hover:bg-blue-600 hover:text-white transition-all uppercase tracking-widest rounded-lg border border-blue-100 shadow-lg shadow-blue-50">Confirmar</button>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active {
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.toast-enter-from {
    transform: translateY(100px) scale(0.9);
    opacity: 0;
}
.toast-leave-to {
    transform: translateX(100px);
    opacity: 0;
}

@keyframes scale-in {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
.scale-in-center {
    animation: scale-in 0.2s ease-out forwards;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f5f9;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
button:active {
    transform: scale(0.98);
}
</style>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import axios from 'axios';
import { 
    Users, Search, Activity, History, 
    Weight, Thermometer, HeartPulse, ClipboardList, Stethoscope, Pill, Printer, User, Camera,
    ChevronDown, Save, Info, ChevronRight, Plus, Trash2, X, CheckCircle, AlertCircle, FileText, 
    SendHorizontal, BedDouble, UserRoundCog, ArrowRightLeft, Database
} from 'lucide-vue-next';

const props = defineProps({
    aguardando: Array,
    catalogoExames: Array,
    catalogoFarmacos: { type: Array, default: () => [] },
    catalogoCid:      { type: Array, default: () => [] },
    listaMedicos:     { type: Array, default: () => [] },
    empresa: Object
});

const searchTerm = ref('');
const selectedPaciente = ref(null);
const triageData = ref(null);
const patientHistory = ref([]);
const isLoading = ref(false);

const activeExamFilter = ref('SOLICITADOS'); 
const showLancarResultadosModal = ref(false);
const showDocumentosModal = ref(false);
const searchExameTerm = ref('');
const selectedExameToLancar = ref(null);
const lancarModo = ref('manual');
const lancarSubDadosList = ref([]);

watch(selectedExameToLancar, (newVal) => {
    if (newVal) {
        lancarModo.value = 'manual';
        lancarSubDadosList.value = [];
        if (newVal.filhos) {
            const parts = newVal.filhos.split('|');
            lancarSubDadosList.value = parts.map(p => {
                const sp = p.split('=');
                return { 
                    dado: sp[0], 
                    unidade: sp[1] || '', 
                    resultado: '' 
                };
            });
        }
    }
});

const examesSolicitados = ref([]); 
const selectedExams = ref([]); 

// CID-10 Logic
const selectedCids = ref([]);
const hdaNotes = ref('');
const searchCidTerm = ref('');

const filteredCidCatalog = computed(() => {
    if (!searchCidTerm.value) return [];
    const term = searchCidTerm.value.toLowerCase();
    return props.catalogoCid.filter(c => 
        c.Descricao.toLowerCase().includes(term) || 
        c.Indicador?.toString().includes(term)
    ).slice(0, 10);
});

const addCid = (cid) => {
    if (!selectedCids.value.includes(cid.Descricao)) {
        selectedCids.value.push(cid.Descricao);
    }
    searchCidTerm.value = '';
};

const removeCid = (index) => {
    selectedCids.value.splice(index, 1);
};

const parsingHDA = (hda) => {
    if (!hda) return { notes: '', cids: [] };
    if (!hda.includes('|')) return { notes: hda, cids: [] };
    const parts = hda.split('|');
    const notes = parts[0] || '';
    const cidPart = parts[1] || '';
    const cids = cidPart.split('\n').map(c => c.trim()).filter(c => c !== '');
    return { notes, cids };
};

const examesList = computed(() => {
    let result = [];
    if (activeExamFilter.value === 'SOLICITADOS') {
        result = examesSolicitados.value.map(e => ({
            id: 'sol_' + e.Id, dbId: e.Id, codigo: e.CodExame, nome: e.Descricao,
            resultado: e.Resultado || '', obs: e.Obs || '', selected: false, isRequested: true,
            categoria: e.Categoria || '', filhos: e.Filhos || ''
        }));
    } else if (activeExamFilter.value === 'LABORATORIO') {
        result = (props.catalogoExames || []).filter(e => e.Exame_Fora !== 'True' && e.Categoria !== 'IMAGEM' && e.Categoria !== 'RAIO X').map(e => ({
            id: 'cat_' + e.Id, dbId: null, codigo: e.Codigo, nome: e.Descricao, resultado: '', selected: false, isRequested: false
        }));
    } else if (activeExamFilter.value === 'RAIOX') {
        result = (props.catalogoExames || []).filter(e => e.Categoria === 'IMAGEM' || e.Categoria === 'RAIO X').map(e => ({
            id: 'cat_' + e.Id, dbId: null, codigo: e.Codigo, nome: e.Descricao, resultado: '', selected: false, isRequested: false
        }));
    } else if (activeExamFilter.value === 'FORA') {
        result = (props.catalogoExames || []).filter(e => e.Exame_Fora === 'True').map(e => ({
            id: 'cat_' + e.Id, dbId: null, codigo: e.Codigo, nome: e.Descricao, resultado: '', selected: false, isRequested: false
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

// RECEITA MÉDICA 
const receitaItens = ref([]); 
const novaReceita = ref([]);  
const novoFarmaco = ref({ farmaco: '', dosagem: '', dias: '' });
const savingReceita = ref(false);

const adicionarFarmacoLocal = () => {
    if (!novoFarmaco.value.farmaco.trim()) return;
    novaReceita.value.push({ ...novoFarmaco.value });
    novoFarmaco.value = { farmaco: '', dosagem: '', dias: '' };
};

const removerFarmacoLocal = (idx) => {
    novaReceita.value.splice(idx, 1);
};

const gravarReceita = async () => {
    if (!selectedPaciente.value) return;
    if (novaReceita.value.length === 0) { showNotification('Adicione novos fármacos para gravar.', 'error'); return; }
    savingReceita.value = true;
    try {
        await axios.post(route('hospitalar.consultorio.receita.store'), {
            IdAgenda: selectedPaciente.value.Codigo,
            itens: novaReceita.value
        });
        novaReceita.value = [];
        showNotification('Receita gravada com sucesso!');
        await selecionarPaciente(selectedPaciente.value);
    } catch (e) {
        showNotification('Erro ao gravar receita.', 'error');
    } finally {
        savingReceita.value = false;
    }
};

const removerItemReceita = async (id) => {
    try {
        await axios.post(route('hospitalar.consultorio.receita.destroy'), { id });
        receitaItens.value = receitaItens.value.filter(r => r.Id !== id);
        showNotification('Item removido!');
    } catch (e) {
        showNotification('Erro ao remover item.', 'error');
    }
};

// IMPRESSÃO 
const imprimirDadosClinico = () => {
    if (!selectedPaciente.value) return;
    window.open(route('hospitalar.consultorio.imprimir.ficha', selectedPaciente.value.Codigo), '_blank');
};

const gerarJustificativo = () => {
    if (!selectedPaciente.value) return;
    window.open(route('hospitalar.consultorio.imprimir.justificativo', selectedPaciente.value.Codigo), '_blank');
};

const gerarGuiaTransferencia = () => {
    if (!selectedPaciente.value) return;
    window.open(route('hospitalar.consultorio.imprimir.guia', selectedPaciente.value.Codigo), '_blank');
};
const imprimirReceita = () => {
    if (!selectedPaciente.value) return;
    window.open(route('hospitalar.consultorio.imprimir.receita', selectedPaciente.value.Codigo), '_blank');
};
const imprimirRequisicao = () => {
    if (!selectedPaciente.value) return;
    const ids = selectedExams.value.join(',');
    let url = route('hospitalar.consultorio.imprimir.requisicao', selectedPaciente.value.Codigo);
    if (ids) {
        url += '?exames=' + ids;
    }
    window.open(url, '_blank');
};
const imprimirResultadosLab = () => {
    if (!selectedPaciente.value) return;
    window.open(route('hospitalar.laboratorio.imprimir', selectedPaciente.value.Codigo), '_blank');
};

const visualizarRelatorio = (codigoAgenda) => {
    if (!codigoAgenda) return;
    window.open(route('hospitalar.consultorio.imprimir.ficha', codigoAgenda), '_blank');
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

const todosItensReceita = computed(() => {
    const fromDB  = receitaItens.value.map(r => ({ id: r.Id, farmaco: r.Farmaco, dosagem: r.Dosagem, dias: r.Dias, fromDB: true }));
    const fromNew = novaReceita.value.map((n, i) => ({ id: 'new_' + i, ...n, fromDB: false }));
    return [...fromDB, ...fromNew];
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
    
    const { notes, cids } = parsingHDA(paciente.HDA);
    hdaNotes.value = notes;
    selectedCids.value = cids;
    
    form.Codigo = paciente.Codigo;
    form.qp = paciente.QP || '';
    form.obj = paciente.OBJ || '';
    form.complementares = paciente.COMPLEMENTARES || '';
    form.recomendacoes = paciente.RECOMENDACOES || '';
    form.situacao = 'Finalizado';
    novaReceita.value = [];

    try {
        const response = await axios.get(route('hospitalar.consultorio.paciente', paciente.Codigo));
        triageData.value = response.data.triagem;
        patientHistory.value = response.data.historico;
        examesSolicitados.value = response.data.exames_solicitados || [];
        receitaItens.value     = response.data.receita || [];
        selectedExams.value = [];
    } catch (error) {
        showNotification('Erro ao carregar dados do paciente.', 'error');
    } finally {
        isLoading.value = false;
    }
};

const salvarConsulta = () => {
    if (!selectedPaciente.value) return;
    form.hda = hdaNotes.value + '\n|' + selectedCids.value.join('\n');
    form.post(route('hospitalar.consultorio.store'), {
        onSuccess: () => {
            showNotification('Dados gravados com sucesso!');
        }
    });
};

const adicionarCidDaPesquisa = () => {
    const termo = searchCidTerm.value.trim();
    if (termo && !selectedCids.value.includes(termo)) {
        selectedCids.value.push(termo);
    }
    searchCidTerm.value = '';
};

const enviarExamesAoLaboratorio = () => {
    if(!selectedPaciente.value) return;
    const catalogExams = selectedExams.value.filter(id => id.startsWith('cat_'));
    
    if (catalogExams.length === 0) {
        showNotification('Selecione pelo menos um novo exame para enviar.', 'error');
        return;
    }

    isLoading.value = true;
    axios.post(route('hospitalar.consultorio.solicitar-exames'), {
        IdAgenda: selectedPaciente.value.Codigo,
        exames: catalogExams
    }).then(() => {
        showNotification('Exames enviados com sucesso!');
        selectedExams.value = [];
        selecionarPaciente(selectedPaciente.value);
    }).catch(err => {
        console.error(err);
        showNotification('Erro ao enviar exames ao laboratório.', 'error');
    }).finally(() => isLoading.value = false);
};

const toggleSelectAll = (event) => {
    const currentIds = examesList.value.map(e => e.id);
    if (event.target.checked) selectedExams.value = Array.from(new Set([...selectedExams.value, ...currentIds]));
    else selectedExams.value = selectedExams.value.filter(id => !currentIds.includes(id));
};

const isAllSelected = computed(() => examesList.value.length > 0 && examesList.value.every(e => selectedExams.value.includes(e.id)));

const showEncaminharModal = ref(false);
const encaminharMedico = ref('');
const encaminharMotivo = ref('');
const encaminhando = ref(false);

const encaminharPaciente = async () => {
    if (!encaminharMedico.value) return;
    encaminhando.value = true;
    try {
        await axios.post(route('hospitalar.consultorio.encaminhar'), {
            IdAgenda: selectedPaciente.value.Codigo,
            IdMedico: encaminharMedico.value,
            motivo:   encaminharMotivo.value
        });
        showNotification('Paciente encaminhado!');
        showEncaminharModal.value = false;
        selectedPaciente.value = null;
        router.reload({ only: ['aguardando'] });
    } finally { encaminhando.value = false; }
};

</script>

<template>
    <Head title="Consultório Médico" />

    <DashboardLayout>
        <div class="p-1 lg:p-2 bg-slate-100 min-h-screen text-[10px] font-sans">
            <!-- Main Grid: Exact order as Photo 1 -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-1 lg:h-[calc(100vh-100px)] lg:overflow-hidden h-auto overflow-visible">
                
                <!-- LEFT COLUMN: Waitlist & History -->
                <div class="col-span-1 lg:col-span-3 flex flex-col gap-1 overflow-hidden h-[500px] lg:h-full">
                    <!-- Waitlist -->
                    <div class="flex-grow bg-white border border-slate-300 flex flex-col shadow-sm">
                        <div class="bg-blue-600 text-white text-center py-1 font-black uppercase tracking-widest text-[9px]">Lista de Espera</div>
                        <div class="p-1 border-b border-slate-100 italic text-[8px] text-slate-400 bg-slate-50">Drag a column header here to group by that column</div>
                        <div class="flex-grow overflow-auto custom-scrollbar">
                            <table class="w-full border-collapse">
                                <thead class="bg-slate-50 sticky top-0 border-b border-slate-200">
                                    <tr class="text-left font-bold text-slate-500">
                                        <th class="p-1.5 border-r border-slate-100">Codigo</th>
                                        <th class="p-1.5 border-r border-slate-100">Data</th>
                                        <th class="p-1.5">Paciente</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in filteredAguardando" :key="p.Codigo" 
                                        @click="selecionarPaciente(p)"
                                        :class="selectedPaciente?.Codigo === p.Codigo ? 'bg-blue-600 text-white shadow-inner' : 'hover:bg-blue-50 text-slate-600'"
                                        class="cursor-pointer border-b border-slate-100 transition-colors">
                                        <td class="p-1.5 border-r border-slate-100/30">{{ p.Codigo }}</td>
                                        <td class="p-1.5 border-r border-slate-100/30">{{ p.DataAgendamento?.substring(0,10) }}</td>
                                        <td class="p-1.5 truncate font-bold uppercase">{{ p.PacienteNome }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- History -->
                    <div class="h-1/3 bg-white border border-slate-300 flex flex-col shadow-sm">
                        <div class="bg-blue-600 text-white text-center py-1 font-black uppercase tracking-widest text-[9px]">Histórico do Paciente</div>
                        <div class="p-1.5 flex gap-1 bg-slate-50 border-b border-slate-200">
                            <input type="text" :value="selectedPaciente?.PacienteNome" class="flex-grow border border-slate-300 px-2 py-1 bg-white rounded uppercase font-bold text-slate-600" readonly />
                            <button class="bg-blue-600 text-white px-3 py-1 font-bold flex items-center gap-1 rounded hover:bg-blue-700 transition-all text-[8px]">
                                <Search class="w-3 h-3" /> BUSCAR
                            </button>
                        </div>
                        <div class="flex-grow overflow-auto custom-scrollbar">
                            <table class="w-full border-collapse">
                                <thead class="bg-slate-50 sticky top-0 border-b border-slate-200">
                                    <tr class="text-left font-bold text-slate-400 uppercase text-[8px]">
                                        <th class="p-1.5 border-r border-slate-100">Data</th>
                                        <th class="p-1.5">Relatorio</th>
                                    </tr>
                                </thead>
                                <tbody class="text-slate-600">
                                    <tr v-for="h in patientHistory" :key="h.Id" class="border-b border-slate-100 hover:bg-slate-50">
                                        <td class="p-1.5">{{ h.DataAgendamento?.substring(0,10) }}</td>
                                        <td @click="visualizarRelatorio(h.Codigo)" class="p-1.5 text-blue-600 font-bold underline cursor-pointer uppercase">Visualizar</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- CENTER COLUMN: Patient Data & Triage & Exams -->
                <div class="col-span-1 lg:col-span-5 flex flex-col gap-1 overflow-hidden h-auto lg:h-full">
                    <!-- Patient Data -->
                    <div class="bg-white border border-slate-300 flex flex-col shadow-sm shrink-0">
                        <div class="bg-blue-900 text-white text-center py-1 font-black uppercase tracking-[0.2em] text-[10px]">{{ selectedPaciente?.PacienteNome || 'Selecione um Paciente' }}</div>
                        <div class="bg-blue-600 text-white text-center py-1 font-black uppercase tracking-widest text-[9px]">Dados do Paciente</div>
                        <div class="p-2 grid grid-cols-12 gap-y-1 gap-x-2 bg-slate-50">
                            <div class="col-span-4 flex flex-col">
                                <label class="font-black text-slate-400 text-[8px] uppercase">Código</label>
                                <input :value="selectedPaciente?.Codigo" class="border border-slate-200 px-1.5 py-0.5 bg-white font-bold rounded" readonly />
                            </div>
                            <div class="col-span-8 flex flex-col">
                                <label class="font-black text-slate-400 text-[8px] uppercase">Nome</label>
                                <input :value="selectedPaciente?.PacienteNome" class="border border-slate-200 px-1.5 py-0.5 bg-white font-bold rounded" readonly />
                            </div>
                            <div class="col-span-8 flex flex-col">
                                <label class="font-black text-slate-400 text-[8px] uppercase">Data Nascimento</label>
                                <input :value="selectedPaciente?.DataNascimento" class="border border-slate-200 px-1.5 py-0.5 bg-white font-bold rounded" readonly />
                            </div>
                            <div class="col-span-4 flex flex-col">
                                <label class="font-black text-slate-400 text-[8px] uppercase">Idade</label>
                                <input :value="calcularIdadeFormatoDesktop(selectedPaciente?.DataNascimento)" class="border border-slate-200 px-1.5 py-0.5 bg-white font-bold rounded" readonly />
                            </div>
                            <div class="col-span-8 flex flex-col">
                                <label class="font-black text-slate-400 text-[8px] uppercase">Telefone</label>
                                <input :value="selectedPaciente?.Telefone" class="border border-slate-200 px-1.5 py-0.5 bg-white font-bold rounded" readonly />
                            </div>
                            <div class="col-span-4 flex flex-col">
                                <label class="font-black text-slate-400 text-[8px] uppercase">Sexo</label>
                                <input :value="selectedPaciente?.Genero?.toUpperCase()" class="border border-slate-200 px-1.5 py-0.5 bg-white font-bold rounded" readonly />
                            </div>
                            <div class="col-span-12 flex flex-col">
                                <label class="font-black text-slate-400 text-[8px] uppercase">Morada</label>
                                <input :value="selectedPaciente?.Rua" class="border border-slate-200 px-1.5 py-0.5 bg-white font-bold rounded" readonly />
                            </div>
                            <div class="col-span-12 flex gap-4 mt-0.5 py-1">
                                <label class="flex items-center gap-1 font-bold"><input type="radio" :checked="!selectedPaciente?.Seguradora" disabled /> Particular</label>
                                <label class="flex items-center gap-1 font-bold"><input type="radio" :checked="selectedPaciente?.Seguradora" disabled /> Assegurado</label>
                            </div>
                            <div class="col-span-12 flex flex-col">
                                <label class="font-black text-slate-400 text-[8px] uppercase">Asseguradora</label>
                                <input :value="selectedPaciente?.Seguradora?.toUpperCase()" class="border border-slate-200 px-1.5 py-0.5 bg-white font-black text-purple-700 rounded" readonly />
                            </div>
                        </div>
                    </div>

                    <!-- Triage (Vitals) -->
                    <div class="bg-white border border-slate-300 flex flex-col shadow-sm shrink-0">
                        <div class="bg-blue-600 text-white text-center py-1 font-black uppercase tracking-widest text-[9px]">Dados da Triagem</div>
                        <div class="p-1 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                            <button class="bg-white border border-slate-300 p-0.5 rounded shadow-sm hover:bg-slate-100 transition-colors flex items-center gap-1 px-2">
                                <Save class="w-3 h-3 text-blue-600" /> <span class="font-black text-[8px] uppercase">Gravar Dados da Triagem</span>
                            </button>
                        </div>
                        <div class="bg-white">
                            <table class="w-full border-collapse">
                                <tbody class="text-slate-700">
                                    <tr class="border-b border-slate-50 group hover:bg-slate-50 font-bold">
                                        <td class="p-1.5 border-r border-slate-100 w-1/2 bg-slate-50/50 text-[9px]">Peso corporal (kg):</td>
                                        <td class="p-1.5 text-right">{{ triageData?.Peso || '--' }} kg</td>
                                    </tr>
                                    <tr class="border-b border-slate-50 group hover:bg-slate-50 font-bold">
                                        <td class="p-1.5 border-r border-slate-100 bg-slate-50/50 text-[9px]">Temperatura (°C):</td>
                                        <td class="p-1.5 text-right">{{ triageData?.Temperatura || '--' }} °C</td>
                                    </tr>
                                    <tr class="border-b border-slate-50 group hover:bg-slate-50 font-bold">
                                        <td class="p-1.5 border-r border-slate-100 bg-slate-50/50 text-[9px]">Pressão Arterial:</td>
                                        <td class="p-1.5 text-right">{{ triageData?.PressaoArterial || '--' }}</td>
                                    </tr>
                                    <tr class="border-b border-slate-50 group hover:bg-slate-50 font-bold">
                                        <td class="p-1.5 border-r border-slate-100 bg-slate-50/50 text-[9px]">Frequência Cardíaca:</td>
                                        <td class="p-1.5 text-right">{{ triageData?.FrequenciaCardiaca || '--' }}</td>
                                    </tr>
                                    <tr class="border-b border-slate-50 group hover:bg-slate-50 font-bold">
                                        <td class="p-1.5 border-r border-slate-100 bg-slate-50/50 text-[9px]">Frequência Respiratória:</td>
                                        <td class="p-1.5 text-right">{{ triageData?.FrequenciaRespiratoria || '--' }}</td>
                                    </tr>
                                    <tr class="border-b border-slate-50 group hover:bg-slate-50 font-bold">
                                        <td class="p-1.5 border-r border-slate-100 bg-slate-50/50 text-[9px]">Saturação de O2 (%):</td>
                                        <td class="p-1.5 text-right">{{ triageData?.SaturacaoOxigenio || '--' }}%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Exams solicitation -->
                    <div class="flex-grow bg-white border border-slate-300 flex flex-col shadow-sm overflow-hidden min-h-[250px]">
                        <div class="bg-blue-600 text-white text-center py-1 font-black uppercase tracking-widest text-[9px]">Solicitar Exames/Serviços</div>
                        <div class="flex p-0.5 gap-0.5 bg-slate-200 shrink-0 border-b border-slate-300">
                            <button v-for="f in ['SOLICITADOS', 'LABORATORIO', 'RAIOX', 'FORA']" :key="f"
                                @click="activeExamFilter = f"
                                :class="activeExamFilter === f ? 'bg-blue-600 text-white' : 'bg-blue-400 text-white hover:bg-blue-500'"
                                class="flex-1 py-1.5 font-black uppercase text-[8px] transition-all">
                                {{ f.replace('SOLICITADOS', 'Exames Solicitados').replace('LABORATORIO', 'Exames do Lab').replace('RAIOX', 'Raio X').replace('FORA', 'Exames Fora') }}
                            </button>
                        </div>
                        <div class="bg-blue-900 text-white text-center py-0.5 text-[8px] font-bold shrink-0 tracking-[0.3em]">---</div>
                        <div class="p-1.5 flex gap-1 bg-slate-50 shrink-0 border-b border-slate-200">
                            <input v-model="searchExameTerm" placeholder="Enter text to search..." class="flex-grow border border-slate-300 px-2 py-1 bg-white rounded text-[10px]" />
                            <button class="bg-white border border-slate-300 px-4 py-1 font-bold uppercase text-[9px] hover:bg-slate-100">Find</button>
                        </div>
                        <div class="flex-grow overflow-auto custom-scrollbar">
                            <table class="w-full border-collapse">
                                <thead class="bg-slate-50 sticky top-0 border-b border-slate-200 z-10 font-bold text-slate-500 text-[8px]">
                                    <tr class="text-left uppercase">
                                        <th class="p-1.5 w-8 border-r border-slate-100 text-center"><input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" class="rounded" /></th>
                                        <th class="p-1.5 border-r border-slate-100">Exame</th>
                                        <th class="p-1.5 text-right">Resultado</th>
                                    </tr>
                                </thead>
                                <tbody class="text-slate-600 font-medium">
                                    <tr v-for="ex in examesList" :key="ex.id" class="border-b border-slate-50 hover:bg-blue-50 transition-colors">
                                        <td class="p-1.5 border-r border-slate-100/50 text-center"><input type="checkbox" :value="ex.id" v-model="selectedExams" class="rounded" /></td>
                                        <td class="p-1.5 border-r border-slate-100/50 font-bold uppercase">{{ ex.nome }}</td>
                                        <td class="p-1.5 text-right text-blue-500 italic">{{ ex.resultado || '---' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-1 grid grid-cols-2 gap-1 bg-slate-100 shrink-0 border-t border-slate-300">
                            <button @click="enviarExamesAoLaboratorio" class="bg-blue-600 text-white py-2 font-black uppercase flex items-center justify-center gap-2 hover:bg-blue-700 text-[9px] shadow-sm">
                                <SendHorizontal class="w-3.5 h-3.5" /> ENVIAR NO LABORATÓRIO
                            </button>
                            <button @click="showLancarResultadosModal = true" class="bg-slate-800 text-white py-2 font-bold flex items-center justify-center hover:bg-slate-900 shadow-sm">
                                <User class="w-3.5 h-3.5" />
                            </button>
                            <button @click="imprimirRequisicao" class="bg-orange-500 text-white py-2 font-black uppercase flex items-center justify-center gap-2 hover:bg-orange-600 text-[9px] shadow-sm">
                                <Printer class="w-3.5 h-3.5" /> IMPRIMIR REQUISIÇÃO
                            </button>
                            <button @click="imprimirResultadosLab" class="bg-orange-500 text-white py-2 font-black uppercase flex items-center justify-center gap-2 hover:bg-orange-600 text-[9px] shadow-sm">
                                <Printer class="w-3.5 h-3.5" /> IMPRIMIR RESULTADOS
                            </button>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Clinical Data & Prescription & Final Actions -->
                <div class="col-span-1 lg:col-span-4 flex flex-col gap-1 overflow-hidden h-auto lg:h-full">
                    <!-- Clinical Data -->
                    <div class="bg-white border border-slate-300 flex flex-col flex-grow shadow-sm overflow-hidden">
                        <div class="bg-blue-600 text-white text-center py-1 font-black uppercase tracking-widest text-[9px]">Dados Clínicos</div>
                        <div class="p-2 flex flex-col gap-2 overflow-y-auto custom-scrollbar flex-grow bg-slate-50">
                            <!-- QP -->
                            <div class="flex flex-col">
                                <label class="font-black text-slate-400 text-[8px] uppercase tracking-widest ml-1">Queixas Principais</label>
                                <textarea v-model="form.qp" class="w-full border border-slate-200 p-1.5 h-12 resize-none bg-white font-bold text-slate-700 outline-none focus:border-blue-500" placeholder="..."></textarea>
                            </div>
                            <!-- HDA -->
                            <div class="flex flex-col">
                                <label class="font-black text-slate-400 text-[8px] uppercase tracking-widest ml-1">Histórico da doença atual</label>
                                <textarea v-model="hdaNotes" class="w-full border border-slate-200 p-1.5 h-16 resize-none bg-white font-bold text-slate-700 outline-none focus:border-blue-500" placeholder="..."></textarea>
                            </div>
                            <!-- OBJ -->
                            <div class="flex flex-col">
                                <label class="font-black text-slate-400 text-[8px] uppercase tracking-widest ml-1">Exames Objectivos</label>
                                <textarea v-model="form.obj" class="w-full border border-slate-200 p-1.5 h-12 resize-none bg-white font-bold text-slate-700 outline-none focus:border-blue-500" placeholder="..."></textarea>
                            </div>
                            
                            <!-- Diagnostic Row: CID + Add CID UI -->
                            <div class="grid grid-cols-2 gap-2">
                                <div class="flex flex-col">
                                    <label class="font-black text-slate-400 text-[8px] uppercase tracking-widest ml-1">Hipótese de Diagnóstico</label>
                                    <div class="border border-slate-200 bg-white h-24 overflow-y-auto p-1 font-black text-slate-600 text-[9px] shadow-inner">
                                        <div v-for="(cid, idx) in selectedCids" :key="idx" class="flex justify-between items-center hover:bg-red-50 p-0.5 group">
                                            <span class="truncate">{{ cid }}</span>
                                            <button @click="removeCid(idx)" class="text-red-500 opacity-0 group-hover:opacity-100"><X class="w-3 h-3"/></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="font-black text-slate-400 text-[8px] uppercase tracking-widest ml-1">Adicionar CidDez</label>
                                    <div class="relative">
                                        <input v-model="searchCidTerm" class="w-full border border-slate-200 px-1 py-1 bg-white font-bold text-[10px]" placeholder="Pesquisar..." />
                                        <div v-if="filteredCidCatalog.length > 0" class="absolute top-full left-0 right-0 z-50 bg-white border border-slate-200 shadow-xl max-h-32 overflow-auto">
                                            <div v-for="c in filteredCidCatalog" :key="c.codigo" @click="addCid(c)" class="p-1 hover:bg-blue-600 hover:text-white cursor-pointer border-b border-slate-50 text-[8px]">
                                                <span class="font-black">{{ c.Indicador }}</span> - {{ c.Descricao }}
                                            </div>
                                        </div>
                                    </div>
                                    <button @click="adicionarCidDaPesquisa" class="bg-blue-600 text-white py-1 font-black flex items-center justify-center gap-1 text-[8px] uppercase rounded shadow-sm hover:bg-blue-700">
                                        <Plus class="w-3 h-3" /> Adicionar
                                    </button>
                                    <button class="bg-blue-500 text-white py-1 font-black text-[8px] uppercase rounded shadow-sm">Cadastrar Novo</button>
                                </div>
                            </div>

                            <!-- Observations -->
                            <div class="flex flex-col">
                                <label class="font-black text-slate-400 text-[8px] uppercase tracking-widest ml-1">Observações</label>
                                <textarea v-model="form.recomendacoes" class="w-full border border-slate-200 p-1.5 h-12 resize-none bg-white font-bold text-slate-700 outline-none focus:border-blue-500" placeholder="..."></textarea>
                            </div>

                            <div class="flex gap-1 pt-1">
                                <button @click="salvarConsulta" class="flex-grow bg-blue-600 text-white py-2 font-black uppercase text-[9px] tracking-widest shadow-md hover:bg-blue-700 transition-all flex items-center justify-center gap-2 rounded">
                                    <Save class="w-3.5 h-3.5" /> GRAVAR DADOS
                                </button>
                                <button @click="imprimirDadosClinico" class="bg-orange-500 text-white px-4 rounded font-black hover:bg-orange-600 shadow-md flex items-center justify-center">
                                    <Printer class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Prescription -->
                    <div class="h-[25%] bg-white border border-slate-300 flex flex-col shadow-sm shrink-0 overflow-hidden">
                        <div class="bg-blue-600 text-white text-center py-1 font-black uppercase tracking-widest text-[9px]">Receita Médica</div>
                        <div class="p-1.5 flex gap-2 bg-slate-50 border-b border-slate-200 shrink-0">
                            <button @click="gravarReceita" class="bg-blue-600 text-white px-4 py-1.5 rounded font-black flex items-center gap-1 hover:bg-blue-700 text-[8px] uppercase shadow-sm">
                                <Save class="w-3 h-3" /> Gravar
                            </button>
                            <button @click="imprimirReceita" class="bg-orange-500 text-white px-4 py-1.5 rounded font-black flex items-center gap-1 hover:bg-orange-600 text-[8px] uppercase shadow-sm">
                                <Printer class="w-3 h-3" /> Receita
                            </button>
                        </div>
                        <div class="flex-grow overflow-auto bg-white custom-scrollbar">
                            <table class="w-full border-collapse">
                                <thead class="bg-slate-50 border-b border-slate-200 sticky top-0 z-10 font-black text-slate-400 text-[8px] uppercase tracking-widest">
                                    <tr>
                                        <th class="p-1.5 border-r border-slate-100 text-left">Farmaco</th>
                                        <th class="p-1.5 border-r border-slate-100 text-left">Dosagem</th>
                                        <th class="p-1.5 text-left">Qtd</th>
                                    </tr>
                                </thead>
                                <tbody class="text-[9px] font-bold text-slate-600">
                                    <tr v-for="item in todosItensReceita" :key="item.id" class="border-b border-slate-50 hover:bg-amber-50/50 transition-colors uppercase">
                                        <td class="p-1.5 border-r border-slate-100 flex items-center gap-1">
                                            <ArrowRightLeft class="w-2.5 h-2.5 text-blue-400" /> {{ item.farmaco }}
                                        </td>
                                        <td class="p-1.5 border-r border-slate-100">{{ item.dosagem }}</td>
                                        <td class="p-1.5">{{ item.dias }}</td>
                                    </tr>
                                    <tr class="bg-slate-50/50">
                                        <td class="p-1 border-r border-slate-100">
                                            <div class="flex items-center gap-1">
                                                <Plus @click="adicionarFarmacoLocal" class="w-3.5 h-3.5 text-emerald-500 cursor-pointer" />
                                                <input v-model="novoFarmaco.farmaco" list="farmacos-list" class="w-full bg-transparent border-none outline-none p-0 font-black text-blue-900 placeholder-slate-300" placeholder="+" />
                                                <datalist id="farmacos-list">
                                                    <option v-for="f in props.catalogoFarmacos" :key="f.Id" :value="f.Descricao" />
                                                </datalist>
                                            </div>
                                        </td>
                                        <td class="p-1 border-r border-slate-100">
                                            <input v-model="novoFarmaco.dosagem" class="w-full bg-transparent border-none outline-none p-0" placeholder="Posologia" />
                                        </td>
                                        <td class="p-1">
                                            <input v-model="novoFarmaco.dias" type="number" class="w-full bg-transparent border-none outline-none p-0" placeholder="Qtd" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Final Action Buttons (2x2 Grid at bottom right) -->
                    <div class="grid grid-cols-2 gap-1 mt-auto pt-1 pb-1 shrink-0">
                        <button @click="form.situacao = 'Internado'; salvarConsulta()" class="bg-blue-600 text-white py-3 rounded font-black uppercase text-[9px] flex items-center justify-center gap-2 hover:bg-blue-700 shadow-sm">
                            <Activity class="w-4 h-4" /> INTERNAMENTO
                        </button>
                        <button @click="showDocumentosModal = true" class="bg-slate-800 text-white py-3 rounded font-black uppercase text-[9px] flex items-center justify-center gap-2 hover:bg-slate-900 shadow-sm">
                            <FileText class="w-4 h-4" /> DOCUMENTOS
                        </button>
                        <button @click="salvarConsulta" class="bg-emerald-500 text-white py-3 rounded font-black uppercase text-[9px] flex items-center justify-center gap-2 hover:bg-emerald-600 shadow-sm">
                            <CheckCircle class="w-4 h-4" /> FINALIZAR
                        </button>
                        <button @click="showEncaminharModal = true" class="bg-blue-600 text-white py-3 rounded font-black uppercase text-[9px] flex items-center justify-center gap-2 hover:bg-blue-700 shadow-sm">
                            <ArrowRightLeft class="w-4 h-4" /> ENCAMINHAR
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: Lançar Resultados -->
        <div v-if="showLancarResultadosModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
             <div class="bg-white w-full max-w-[900px] rounded-lg shadow-2xl border border-slate-300 flex flex-col h-[80vh] overflow-hidden">
                <div class="bg-blue-900 text-white text-center py-1.5 font-black uppercase text-[10px] flex justify-between px-6">
                    <span>Lançar Resultados</span>
                    <button @click="showLancarResultadosModal = false">×</button>
                </div>
                <div class="flex-grow flex overflow-hidden">
                    <div class="w-1/3 border-r border-slate-200 flex flex-col bg-slate-50">
                        <div class="flex-grow overflow-y-auto">
                        <table class="w-full border-collapse">
                            <thead class="bg-slate-100 border-b border-slate-200">
                                <tr class="text-left font-black text-[8px] text-slate-400 uppercase">
                                    <th class="p-3 border-r border-slate-200">Exame</th>
                                    <th class="p-3">Res</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="ex in examesList" :key="ex.id" @click="selectedExameToLancar = ex" :class="selectedExameToLancar?.id === ex.id ? 'bg-blue-600 text-white' : 'hover:bg-blue-50'" class="cursor-pointer border-b border-slate-100 font-bold text-[9px]">
                                    <td class="p-3 border-r border-slate-200/30 uppercase">{{ ex.nome }}</td>
                                    <td class="p-3 italic">{{ ex.resultado || '---' }}</td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                        <div class="shrink-0 p-2 border-t border-slate-200 bg-white">
                            <button @click="imprimirDadosClinico" class="w-full bg-orange-500 text-white py-2 rounded font-black uppercase text-[9px] hover:bg-orange-600 shadow flex items-center justify-center gap-2">
                                <Printer class="w-3.5 h-3.5" /> Imprimir Resultados
                            </button>
                        </div>
                    </div>
                    <div class="flex-grow p-6 bg-white flex flex-col overflow-hidden">
                        <template v-if="selectedExameToLancar">
                            <h4 class="text-lg font-black text-blue-900 mb-4 uppercase flex justify-between items-center">
                                <span>{{ selectedExameToLancar.nome }}</span>
                                <span v-if="selectedExameToLancar.categoria === 'RAIO X'" class="text-[10px] bg-red-100 text-red-700 px-2 py-1 rounded">RAIO X</span>
                                <span v-else class="text-[10px] bg-blue-100 text-blue-700 px-2 py-1 rounded">LABORATÓRIO</span>
                            </h4>
                            
                            <!-- Panel for RAIO X -->
                            <div v-if="selectedExameToLancar.categoria === 'RAIO X' || selectedExameToLancar.categoria === 'IMAGEM'" class="flex-grow flex flex-col justify-center items-center gap-6 border-2 border-dashed border-slate-200 rounded-lg p-6 bg-slate-50">
                                <div class="text-center">
                                    <Camera class="w-16 h-16 text-slate-300 mx-auto mb-2" />
                                    <p class="font-bold text-slate-500 text-[10px] uppercase">Este exame requer anexo de imagem</p>
                                </div>
                                <div class="flex gap-4">
                                    <button class="bg-blue-600 text-white px-6 py-3 rounded font-black uppercase text-[10px] hover:bg-blue-700 shadow-md">Anexar Imagem</button>
                                    <button class="bg-emerald-600 text-white px-6 py-3 rounded font-black uppercase text-[10px] hover:bg-emerald-700 shadow-md">Gravar Anexos</button>
                                </div>
                            </div>

                            <!-- Panel for NORMAL -->
                            <div v-else class="flex-grow flex flex-col overflow-hidden">
                                <div class="flex gap-4 mb-4 border-b border-slate-200 pb-4 shrink-0">
                                    <label class="flex items-center gap-2 font-bold text-[10px] text-slate-700 cursor-pointer">
                                        <input type="radio" v-model="lancarModo" value="manual" class="w-4 h-4 text-blue-600 focus:ring-blue-500" />
                                        PREENCHER RESULTADO MANUALMENTE
                                    </label>
                                    <label class="flex items-center gap-2 font-bold text-[10px] text-slate-700 cursor-pointer">
                                        <input type="radio" v-model="lancarModo" value="anexo" class="w-4 h-4 text-blue-600 focus:ring-blue-500" />
                                        ANEXAR RESULTADO (PDF, IMAGEM)
                                    </label>
                                </div>

                                <!-- MANUAL MODE -->
                                <div v-if="lancarModo === 'manual'" class="flex-grow flex flex-col overflow-hidden gap-4">
                                    <!-- Exame COM sub-dados (Hemograma, etc.) -->
                                    <div v-if="lancarSubDadosList.length > 0" class="flex-grow overflow-y-auto border border-slate-200 rounded">
                                        <table class="w-full text-left border-collapse">
                                            <thead class="bg-slate-100 sticky top-0">
                                                <tr class="font-black text-[9px] text-slate-500 uppercase">
                                                    <th class="p-2 border-b border-slate-200 w-1/3">Dado</th>
                                                    <th class="p-2 border-b border-slate-200 w-1/3 border-l border-slate-200">Resultado</th>
                                                    <th class="p-2 border-b border-slate-200 w-1/3 border-l border-slate-200">Unidade/Referência</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(sub, i) in lancarSubDadosList" :key="i" class="border-b border-slate-100 hover:bg-slate-50">
                                                    <td class="p-2 text-[10px] font-bold border-r border-slate-100">{{ sub.dado }}</td>
                                                    <td class="p-1 border-r border-slate-100"><input v-model="sub.resultado" class="w-full border border-slate-300 rounded px-2 py-1 text-[10px] font-bold outline-none focus:border-blue-500" /></td>
                                                    <td class="p-2 text-[10px] text-slate-500">{{ sub.unidade }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- Exame SEM sub-dados (Simples) -->
                                    <div v-else class="flex flex-col gap-4 h-full">
                                        <div class="flex flex-col gap-1">
                                            <label class="font-black text-[9px] text-slate-500 uppercase">Resultado</label>
                                            <select v-model="selectedExameToLancar.resultado" class="border border-slate-300 rounded p-2 text-xs font-bold focus:border-blue-500 outline-none">
                                                <option value="">Selecione...</option>
                                                <option value="Positivo">Positivo</option>
                                                <option value="Negativo">Negativo</option>
                                                <option value="Outro">Outro (Descrever na observação)</option>
                                            </select>
                                        </div>
                                        <div class="flex flex-col gap-1 flex-grow">
                                            <label class="font-black text-[9px] text-slate-500 uppercase">Observação</label>
                                            <textarea v-model="selectedExameToLancar.obs" class="w-full h-full border border-slate-300 rounded p-2 text-xs font-bold focus:border-blue-500 outline-none resize-none" placeholder="Detalhes adicionais..."></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-end mt-auto pt-4 border-t border-slate-100">
                                        <button class="bg-blue-600 text-white px-6 py-2 rounded font-black uppercase text-[10px] hover:bg-blue-700 shadow-md">Gravar Resultado</button>
                                    </div>
                                </div>

                                <!-- ANEXO MODE -->
                                <div v-if="lancarModo === 'anexo'" class="flex-grow flex flex-col gap-4 h-full">
                                    <div class="flex gap-2">
                                        <button class="bg-slate-800 text-white px-4 py-2 rounded font-black uppercase text-[9px] hover:bg-slate-900 shadow flex items-center gap-2"><FileText class="w-3 h-3"/> Anexar PDF</button>
                                        <button class="bg-slate-800 text-white px-4 py-2 rounded font-black uppercase text-[9px] hover:bg-slate-900 shadow flex items-center gap-2"><Camera class="w-3 h-3"/> Anexar Imagem</button>
                                    </div>
                                    <div class="flex-grow border-2 border-dashed border-slate-200 rounded flex flex-col items-center justify-center bg-slate-50">
                                        <FileText class="w-12 h-12 text-slate-300 mb-2" />
                                        <span class="text-[10px] font-bold text-slate-400 uppercase">Nenhum anexo encontrado</span>
                                    </div>
                                    <div class="flex justify-end mt-auto pt-4 border-t border-slate-100">
                                        <button class="bg-emerald-600 text-white px-6 py-2 rounded font-black uppercase text-[10px] hover:bg-emerald-700 shadow-md">Gravar Anexos</button>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div v-else class="flex-grow flex flex-col items-center justify-center opacity-10">
                            <ClipboardList class="w-20 h-20" />
                            <p class="font-black uppercase tracking-widest text-lg">Selecione um Exame</p>
                        </div>
                    </div>
                </div>
             </div>
        </div>

        <!-- MODAL: Encaminhar -->
        <div v-if="showEncaminharModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
            <div class="bg-white rounded-lg shadow-2xl w-full max-w-md border border-slate-300 overflow-hidden">
                <div class="bg-blue-600 text-white px-6 py-3 font-black uppercase text-[10px]">Encaminhar Paciente</div>
                <div class="p-6 space-y-4">
                    <div class="flex flex-col gap-1">
                        <label class="font-black text-slate-400 text-[8px] uppercase">Médico de Destino</label>
                        <select v-model="encaminharMedico" class="border border-slate-300 rounded p-2 text-xs font-bold bg-white">
                            <option value="">Selecione...</option>
                            <option v-for="m in props.listaMedicos" :key="m.Codigo" :value="m.Codigo">{{ m.Nome }}</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="font-black text-slate-400 text-[8px] uppercase">Motivo</label>
                        <textarea v-model="encaminharMotivo" class="border border-slate-300 rounded p-2 h-24 text-xs font-bold bg-white resize-none"></textarea>
                    </div>
                    <div class="flex gap-2 pt-2">
                        <button @click="showEncaminharModal = false" class="flex-1 py-2 font-black uppercase text-[9px] text-slate-400">Cancelar</button>
                        <button @click="encaminharPaciente" class="flex-1 bg-blue-600 text-white py-2 rounded font-black uppercase text-[9px]">Confirmar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- NOTIFICATION -->
        <Transition enter-active-class="duration-300 ease-out" enter-from-class="translate-x-full opacity-0" leave-active-class="duration-200 ease-in" leave-to-class="translate-x-full opacity-0">
            <div v-if="notification.show" class="fixed bottom-6 right-6 z-[1000] bg-slate-900 text-white px-6 py-4 rounded shadow-2xl flex items-center gap-4">
                <CheckCircle v-if="notification.type === 'success'" class="w-4 h-4 text-emerald-500" />
                <AlertCircle v-else class="w-4 h-4 text-red-500" />
                <span class="text-[9px] font-black uppercase tracking-widest">{{ notification.message }}</span>
            </div>
        </Transition>
        <!-- MODAL: Documentos -->
        <div v-if="showDocumentosModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
             <div class="bg-white w-[400px] rounded shadow-2xl border border-slate-300 flex flex-col overflow-hidden">
                <div class="bg-blue-900 text-white text-center py-2 font-black uppercase text-[10px] flex justify-between px-4">
                    <span>Documentos Auxiliares</span>
                    <button @click="showDocumentosModal = false">×</button>
                </div>
                <div class="p-6 flex flex-col gap-4 bg-slate-50">
                    <button @click="gerarJustificativo" class="bg-blue-600 text-white py-3 rounded font-black uppercase text-[10px] shadow-sm hover:bg-blue-700 transition-all flex items-center justify-center gap-2">
                        <FileText class="w-4 h-4" /> Justificativo Médico
                    </button>
                    <button @click="gerarGuiaTransferencia" class="bg-emerald-600 text-white py-3 rounded font-black uppercase text-[10px] shadow-sm hover:bg-emerald-700 transition-all flex items-center justify-center gap-2">
                        <FileText class="w-4 h-4" /> Guia de Transferência
                    </button>
                </div>
             </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

input, textarea, select {
    outline: none;
}

button:active { transform: scale(0.98); }

@media print {
    body * { visibility: hidden; }
    .printable-area, .printable-area * { visibility: visible; }
}
</style>

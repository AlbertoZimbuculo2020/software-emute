<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import axios from 'axios';
import { 
    Users, Search, Activity, History, Clock,
    Weight, Thermometer, HeartPulse, ClipboardList, Stethoscope, Pill, Printer, User, Camera, Download,
    ChevronDown, Save, Info, ChevronRight, ChevronLeft, Plus, Trash2, X, CheckCircle, AlertCircle, FileText, 
    SendHorizontal, BedDouble, UserRoundCog, ArrowRightLeft, Database, Building2, Settings2, Heart
} from 'lucide-vue-next';

const props = defineProps({
    aguardando: Array,
    catalogoExames: Array,
    catalogoFarmacos: { type: Array, default: () => [] },
    catalogoCid:      { type: Array, default: () => [] },
    listaMedicos:     { type: Array, default: () => [] },
    config: { type: Object, default: () => ({ triageEnabled: true, fontSize: '10px' }) }
});

const waitlist = ref([...props.aguardando]);
const searchTerm = ref('');
const selectedPaciente = ref(null);
const triageData = ref(null);
const patientHistory = ref([]);
const isLoading = ref(false);
const confirmModal = ref({ isOpen: false, title: '', message: '', onConfirm: null });
const activeTab = ref(1);
const showSidebar = ref(true);

const activeExamFilter = ref('LABORATORIO'); 
const showLancarResultadosModal = ref(false);
const showDocumentosModal = ref(false);
const searchExameQuery = ref('');
const selectedExameToLancar = ref(null);
const lancarModo = ref('manual');
const lancarSubDadosList = ref([]);
const tipoPaciente = ref('Particular');

// Real-time Queue Logic
const audioNotification = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');

const refreshWaitlist = async () => {
    try {
        const response = await axios.get(route('hospitalar.consultorio.waitlist'));
        const newData = response.data;
        
        // Check for new patients
        if (newData.length > waitlist.value.length) {
            audioNotification.play().catch(() => {});
            showNotification('Um novo paciente entrou na fila!');
        }

        // Check for patients returning from Lab
        newData.forEach(p => {
            const old = waitlist.value.find(o => o.Codigo === p.Codigo);
            if (old && old.Situacao !== 'Consultorio' && p.Situacao === 'Consultorio') {
                audioNotification.play().catch(() => {});
                showNotification(`O paciente ${p.PacienteNome} retornou do laboratório!`);
            }
        });

        waitlist.value = newData;
    } catch (e) {
        console.error("Erro ao atualizar fila:", e);
    }
};

onMounted(() => {
    const timer = setInterval(refreshWaitlist, 15000); // Refresh every 15s
    onUnmounted(() => clearInterval(timer));
});

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
const isEconomicMode = ref(false); 
const isDuplicate = ref(true); 

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
    const term = searchExameQuery.value ? searchExameQuery.value.toLowerCase() : '';
    
    // If searching, we search everything in the catalog + requested
    if (term) {
        const cleanResult = (res) => {
            if (!res) return '';
            return res.replace(/\|/g, ' / ').replace(/\s+/g, ' ').trim();
        };

        const requested = examesSolicitados.value.map(e => ({
            id: 'sol_' + e.Id, dbId: e.Id, codigo: e.CodExame, nome: e.Descricao,
            resultado: cleanResult(e.Resultado), obs: e.Obs || '', selected: false, isRequested: true,
            categoria: e.Categoria || '', filhos: e.Filhos || ''
        }));

        const catalog = (props.catalogoExames || []).map(e => ({
            id: 'cat_' + e.Id, dbId: null, codigo: e.Codigo, nome: e.Descricao, resultado: '', selected: false, isRequested: false
        }));

        // Deduplicate: if it's in requested, don't show the catalog version
        const requestedCodigos = new Set(requested.map(r => r.codigo));
        const filteredCatalog = catalog.filter(c => !requestedCodigos.has(c.codigo));

        const all = [...requested, ...filteredCatalog];
        return all.filter(e => e.nome.toLowerCase().includes(term));
    }

    // Normal tab-based view
    let result = [];
    if (activeExamFilter.value === 'SOLICITADOS') {
        result = examesSolicitados.value.map(e => ({
            id: 'sol_' + e.Id, dbId: e.Id, codigo: e.CodExame, nome: e.Descricao,
            resultado: e.Resultado ? e.Resultado.replace(/\|/g, ' / ').replace(/\s+/g, ' ').trim() : '', 
            obs: e.Obs || '', selected: false, isRequested: true,
            categoria: e.Categoria || '', filhos: e.Filhos || ''
        }));
    } else if (activeExamFilter.value === 'LABORATORIO') {
        result = (props.catalogoExames || []).filter(e => e.Tipo === 'NORMAL' && e.Exame_Fora !== 'True').map(e => ({
            id: 'cat_' + e.Id, dbId: null, codigo: e.Codigo, nome: e.Descricao, resultado: '', selected: false, isRequested: false
        }));
    } else if (activeExamFilter.value === 'RAIOX') {
        result = (props.catalogoExames || []).filter(e => e.Tipo === 'RAIO X' || e.Tipo === 'IMAGEM').map(e => ({
            id: 'cat_' + e.Id, dbId: null, codigo: e.Codigo, nome: e.Descricao, resultado: '', selected: false, isRequested: false
        }));
    } else if (activeExamFilter.value === 'FORA') {
        result = (props.catalogoExames || []).filter(e => e.Tipo === 'FORA' || e.Exame_Fora === 'True').map(e => ({
            id: 'cat_' + e.Id, dbId: null, codigo: e.Codigo, nome: e.Descricao, resultado: '', selected: false, isRequested: false
        }));
    }
    return result;
});

// Pagination for Exams
const exameCurrentPage = ref(1);
const exameItemsPerPage = 5;
const totalPagesExames = computed(() => Math.ceil(examesList.value.length / exameItemsPerPage));
const paginatedExamesList = computed(() => {
    const start = (exameCurrentPage.value - 1) * exameItemsPerPage;
    return examesList.value.slice(start, start + exameItemsPerPage);
});

watch([activeExamFilter, searchExameQuery], () => {
    exameCurrentPage.value = 1;
});


const speak = (text) => {
    if (!('speechSynthesis' in window)) return;
    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'pt-PT';
    utterance.rate = 1.0;
    const voices = window.speechSynthesis.getVoices();
    const ptVoice = voices.find(v => v.lang.startsWith('pt'));
    if (ptVoice) utterance.voice = ptVoice;
    window.speechSynthesis.speak(utterance);
};

const notification = ref({ show: false, message: '', type: 'success' });
const showNotification = (message, type = 'success') => {
    notification.value = { show: true, message, type };
    speak(message);
    setTimeout(() => notification.value.show = false, 4000);
};

const previousWaitlist = ref([]);
watch(() => props.aguardando, (newList) => {
    if (previousWaitlist.value.length > 0) {
        newList.forEach(p => {
            const prev = previousWaitlist.value.find(old => old.Codigo === p.Codigo);
            if (!prev) {
                speak(`Novo paciente na fila: ${p.PacienteNome}`);
            } else if (prev.Situacao === 'Laboratorio' && p.Situacao !== 'Laboratorio') {
                speak(`O paciente ${p.PacienteNome} retornou do laboratório.`);
            }
        });
    }
    previousWaitlist.value = JSON.parse(JSON.stringify(newList));
}, { deep: true });

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

const removerItemLocalOuDB = (item, idx) => {
    if (item.fromDB) {
        removerItemReceita(item.id);
    } else {
        removerFarmacoLocal(idx - receitaItens.value.length);
    }
};

const MED_FAMILIAR = ['Diabetes', 'Hipertensão', 'Doença Cardíaca', 'Câncer'];
const MED_RECOMENDACOES = ['Reduzir Peso', 'Praticar Esporte', 'Usar EPIs', 'Reduzir consumo de tabaco', 'Controlar tensão arterial', 'Consumir alimentos saudáveis', 'Reduzir consumo de bebida alcoólica'];
const MED_ENCAMINHAMENTOS = ['Oftalmologista', 'Otorrinolaringologista', 'Cardiologista'];
const MED_EXAM_TYPES = ['Admissão', 'Periódico', 'Demissão', 'Ocasional', 'Retorno ao Trabalho', 'Mudança de Função'];
const MED_RISKS = ['Ruído', 'Produtos Químicos', 'Stress', 'Radiação', 'Postura Viciosa', 'Chumbo', 'Calor', 'Frio', 'Poeira', 'Vibração', 'Ergonomia inadequada'];
const MED_SYSTEMS = [
    { key: 'pele', label: 'Pele e Faneras', alts: ['Micoses', 'Dermatoses'] },
    { key: 'respiratorio', label: 'Respiratório', alts: ['Asma', 'Bronquite', 'D. Rinite', 'Sinusite'] },
    { key: 'cardiovascular', label: 'CardioVascular', alts: ['D. Coronário', 'Ins. Venosa', 'HTA'] },
    { key: 'digestivo', label: 'Digestivo', alts: ['Doença Crônica', 'Doença Biliar'] },
    { key: 'genitoUrinario', label: 'Génito Urinário', alts: ['Litíase', 'Inf. Urinário'] },
    { key: 'endocrino', label: 'Endócrino', alts: ['Diabetes', 'Disf. Tireoideia'] },
    { key: 'nervoso', label: 'Nervoso', alts: ['Tremor', 'Neuropatia', 'Depressão', 'Irritabilidade'] },
    { key: 'osteoarticular', label: 'Osteoarticular e Conjuntivo', alts: ['Tendinite', 'Hérnia', 'Raquialgia', 'Lombalgia', 'Ciatalgia'] },
];

// MEDICINA OCUPACIONAL
const showMedOcupacionalModal = ref(false);
const isReadOnly = ref(false);
const activeMedTab = ref(1);
const catalogoVacinas = ref([]);
const medOcupacionalForm = useForm({
    IdAgenda: '',
    empresa: '',
    funcao: '',
    tipoExame: [],
    outroTipoExame: '',
    factoresRisco: [],
    outroFactorRisco: '',
    historiaPregressa: {
        infecto: { checked: false, detail: '' },
        cronicas: { checked: false, detail: '' },
        alergias: { checked: false, detail: '' },
        cirurgias: { checked: false, detail: '' },
    },
    historiaFamiliar: [],
    outroHistoriaFamiliar: '',
    historicoOcupacional: [{ funcao: '', tempo: '' }],
    idadeInicioTrabalho: '',
    vacinas: [],
    apresentouCarteiraVacina: false,
    habitosVida: {
        medicacao: { checked: false, detail: '' },
        alimentacao: { checked: false, detail: '' },
        tabaco: { checked: false, qtd: '', tempo: '' },
        alcool: { checked: false, qtd: '', tempo: '' },
        drogas: { checked: false, qual: '', tempo: '' },
        lazer: { checked: false, detail: '' },
    },
    avaliacaoDentaria: {
        estadoBoca: '',
        riscoInfeccao: 'Baixo',
        encaminhadoTratamento: false,
    },
    exameFisico: {
        pele: { estado: 'Normal', alteracoes: [], obs: '' },
        respiratorio: { estado: 'Normal', alteracoes: [], obs: '' },
        cardiovascular: { estado: 'Normal', alteracoes: [], obs: '' },
        digestivo: { estado: 'Normal', alteracoes: [], obs: '' },
        genitoUrinario: { estado: 'Normal', alteracoes: [], obs: '' },
        endocrino: { estado: 'Normal', alteracoes: [], obs: '' },
        nervoso: { estado: 'Normal', alteracoes: [], obs: '' },
        osteoarticular: { estado: 'Normal', alteracoes: [], obs: '' },
    },
    recomendacoes: [],
    outraRecomendacao: '',
    encaminhamentos: [],
    outroEncaminhamento: '',
    resultadoFinal: 'Apto',
});

const loadVacinas = async () => {
    try {
        const res = await axios.get(route('hospitalar.vacinas'));
        catalogoVacinas.value = res.data;
    } catch (e) { console.error("Erro vacinas:", e); }
};

const addHistoricoOcupacional = () => medOcupacionalForm.historicoOcupacional.push({ funcao: '', tempo: '' });
const removeHistoricoOcupacional = (idx) => medOcupacionalForm.historicoOcupacional.splice(idx, 1);

const salvarMedOcupacional = async () => {
    medOcupacionalForm.IdAgenda = selectedPaciente.value.Codigo;
    try {
        await axios.post(route('hospitalar.consultorio.medicina-ocupacional.store'), medOcupacionalForm.data());
        showNotification('Dados de Medicina Ocupacional gravados!');
    } catch (e) {
        showNotification(e.response?.data?.message || 'Erro ao gravar dados.', 'error');
    }
};

const finalizarMedOcupacional = async () => {
    if (!confirm("Deseja finalizar esta consulta de Medicina Ocupacional? O relatório será impresso e o atendimento concluído.")) return;
    
    await salvarMedOcupacional();
    imprimirDadosClinico();
    
    try {
        await axios.post(route('hospitalar.consultorio.store'), {
            Codigo: selectedPaciente.value.Codigo,
            situacao: 'Finalizado'
        });
        showMedOcupacionalModal.value = false;
        selectedPaciente.value = null;
        refreshWaitlist();
    } catch (e) {
        showNotification('Erro ao finalizar consulta.', 'error');
    }
};


const showJustificativoModal = ref(false);
const justificativoData = ref({ familiar: '', data_internado: '', data_inicio: '', data_fim: '' });

const gerarJustificativo = () => {
    if (!selectedPaciente.value) return;
    showJustificativoModal.value = true;
};

const confirmarJustificativo = () => {
    let url = route('hospitalar.consultorio.imprimir.justificativo', selectedPaciente.value.Codigo);
    const params = new URLSearchParams({
        familiar: justificativoData.value.familiar,
        data_internado: justificativoData.value.data_internado,
        data_inicio: justificativoData.value.data_inicio,
        data_fim: justificativoData.value.data_fim
    });
    url += '?' + params.toString();
    
    if (isEconomicMode.value) {
        url += '&modo=economico';
        if (!isDuplicate.value) url += '&duplicado=0';
    }
    window.open(url, '_blank');
    showJustificativoModal.value = false;
};

const showGuiaModal = ref(false);
const guiaData = ref({ correspondente: '', motivo: '', exames_realizados: '', analises: '', diagnostico: '', tratamento: '', historia_clinica: '', hora_admissao: '', hora_saida: '', obs_final: '', tratamento_itens: [] });

const addTratamentoItem = () => {
    guiaData.value.tratamento_itens.push({ medicamento: '', quantidade: '', dosagem: '', horario: '', via: '' });
};

const removeTratamentoItem = (index) => {
    guiaData.value.tratamento_itens.splice(index, 1);
};

const formatTratamentoItens = () => {
    if (guiaData.value.tratamento_itens.length === 0) return guiaData.value.tratamento;
    return guiaData.value.tratamento_itens
        .filter(item => item.medicamento)
        .map(item => `${item.medicamento} ${item.dosagem || ''} - ${item.quantidade || ''} - ${item.horario || ''} - ${item.via || ''}`)
        .join('\n');
};

const gerarGuiaTransferencia = () => {
    if (!selectedPaciente.value) return;
    showGuiaModal.value = true;
};

const confirmarGuia = () => {
    if (!selectedPaciente.value) return;
    
    let dataToSend = { ...guiaData.value };
    let trattamentoText = formatTratamentoItens();
    if (trattamentoText) {
        dataToSend.tratamento = trattamentoText;
    }
    if (dataToSend.tratamento_itens && dataToSend.tratamento_itens.length > 0) {
        dataToSend.tratamento_itens = JSON.stringify(dataToSend.tratamento_itens.filter(item => item.medicamento));
    } else {
        dataToSend.tratamento_itens = '[]';
    }
    
    const url = route('hospitalar.consultorio.imprimir.guia', selectedPaciente.value.Codigo);
    const params = new URLSearchParams(dataToSend);
    
    if (isEconomicMode.value) {
        params.append('modo', 'economico');
        if (!isDuplicate.value) params.append('duplicado', '0');
    }
    
    window.open(url + '?' + params.toString(), '_blank');
    showGuiaModal.value = false;
};
const imprimirReceita = () => {
    if (!selectedPaciente.value) return;
    let url = route('hospitalar.consultorio.imprimir.receita', selectedPaciente.value.Codigo);
    if (isEconomicMode.value) {
        url += '?modo=economico';
        if (!isDuplicate.value) url += '&duplicado=0';
    }
    window.open(url, '_blank');
};
const imprimirRequisicao = () => {
    if (!selectedPaciente.value) return;
    const ids = selectedExams.value.join(',');
    let url = route('hospitalar.consultorio.imprimir.requisicao', selectedPaciente.value.Codigo);
    let sep = '?';
    if (ids) { url += sep + 'exames=' + ids; sep = '&'; }
    if (isEconomicMode.value) { 
        url += sep + 'modo=economico';
        if (!isDuplicate.value) url += '&duplicado=0';
    }
    window.open(url, '_blank');
};
const imprimirResultadosLab = () => {
    if (!selectedPaciente.value) return;
    let url = route('hospitalar.laboratorio.imprimir', selectedPaciente.value.Codigo);
    if (isEconomicMode.value) {
        url += '?modo=economico';
        if (!isDuplicate.value) url += '&duplicado=0';
    }
    window.open(url, '_blank');
};

const visualizarRelatorio = (codigoAgenda) => {
    if (!codigoAgenda) return;
    window.open(route('hospitalar.consultorio.imprimir.ficha', codigoAgenda), '_blank');
};

const baixarRelatorio = (codigoAgenda) => {
    if (!codigoAgenda) return;
    window.open(route('hospitalar.consultorio.imprimir.ficha', { id: codigoAgenda, download: 1 }), '_blank');
};

const calcularIdadeFormatoDesktop = (dataNascimento) => {
    if (!dataNascimento) return 'N/D';
    const birthDate = new Date(dataNascimento);
    const today = new Date();
    
    let years = today.getFullYear() - birthDate.getFullYear();
    let months = today.getMonth() - birthDate.getMonth();
    let days = today.getDate() - birthDate.getDate();

    if (days < 0) {
        months--;
        const lastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
        days += lastMonth.getDate();
    }
    if (months < 0) {
        years--;
        months += 12;
    }

    if (years > 0) {
        return years + (years === 1 ? ' Ano' : ' Anos');
    } else if (months > 0) {
        return months + (months === 1 ? ' Mês' : ' Meses');
    } else {
        return days + (days === 1 ? ' Dia' : ' Dias');
    }
};

const todosItensReceita = computed(() => {
    const fromDB  = receitaItens.value.map(r => ({ id: r.Id, farmaco: r.Farmaco, dosagem: r.Dosagem, dias: r.Dias, fromDB: true }));
    const fromNew = novaReceita.value.map((n, i) => ({ id: 'new_' + i, ...n, fromDB: false }));
    return [...fromDB, ...fromNew];
});

const filteredAguardando = computed(() => {
    if (!searchTerm.value) return waitlist.value;
    const term = searchTerm.value.toLowerCase();
    return waitlist.value.filter(p => 
        p.PacienteNome.toLowerCase().includes(term) ||
        p.Codigo.toLowerCase().includes(term)
    );
});

const selecionarPaciente = async (paciente, readOnly = false, force = false) => {
    // If it's already selected, don't reload unless force
    if (selectedPaciente.value?.Codigo === paciente.Codigo && !readOnly && !force) return;

    selectedPaciente.value = paciente;
    isLoading.value = true;
    isReadOnly.value = readOnly;
    activeTab.value = 1; // Reset to first tab
    if (force) exameCurrentPage.value = 1; // Reset exam pagination on force reload
    
    const { notes, cids } = parsingHDA(paciente.HDA);
    hdaNotes.value = notes;
    selectedCids.value = cids;
    
    form.Codigo = paciente.Codigo;
    form.qp = paciente.QP || '';
    form.obj = paciente.OBJ || '';
    form.hda = paciente.HDA || ''; // Initialize HDA correctly
    form.complementares = paciente.COMPLEMENTARES || '';
    form.recomendacoes = paciente.RECOMENDACOES || '';
    form.situacao = paciente.Situacao; // Keep original situation
    
    // Parse HDA if it contains CID pipe
    const parsed = parsingHDA(paciente.HDA);
    hdaNotes.value = parsed.notes;
    selectedCids.value = parsed.cids;
    novaReceita.value = [];
    tipoPaciente.value = (paciente.Seguradora || paciente.Convenio) ? 'Assegurado' : 'Particular';

    // Initialize/Reset medOcupacionalForm
    medOcupacionalForm.value = {
        IdAgenda: paciente.Codigo,
        empresa: paciente.Empresa || '',
        funcao: paciente.Funcao || '',
        tipoExame: [],
        outroTipoExame: '',
        factoresRisco: [],
        outroFactorRisco: '',
        exameFisico: Object.fromEntries(MED_SYSTEMS.map(s => [s.key, { estado: 'Normal', alteracoes: [], obs: '' }])),
        conclusao: 'Apto',
        vacinas: [],
    };

    // Medicina Ocupacional Check
    if (paciente.Consulta === 'MEDICINA OCUPACIONAL') {
        confirmModal.value = {
            isOpen: true,
            title: 'Medicina Ocupacional',
            message: 'Esta é uma consulta de Medicina Ocupacional. Deseja abrir a ficha específica?',
            onConfirm: () => {
                confirmModal.value.isOpen = false;
                loadVacinas();
                showMedOcupacionalModal.value = true;
            }
        };
    }

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

onMounted(() => {
    const interval = setInterval(async () => {
        // Silently reload the waiting list
        router.reload({ only: ['aguardando'], preserveState: true });
        
        // If a patient is being attended, silently refresh their exams to catch lab results
        if (selectedPaciente.value && !isLoading.value && !isReadOnly.value) {
            try {
                const response = await axios.get(route('hospitalar.consultorio.paciente', selectedPaciente.value.Codigo));
                if (response.data.exames_solicitados) {
                    examesSolicitados.value = response.data.exames_solicitados;
                }
            } catch (e) {
                console.error("Auto-refresh failed", e);
            }
        }
    }, 15000); // 15 seconds

    onUnmounted(() => clearInterval(interval));
});

// Silent auto-save logic
let saveTimeout = null;
watch([() => form.qp, () => hdaNotes, () => form.obj, () => form.complementares, () => form.recomendacoes, selectedCids], () => {
    if (!selectedPaciente.value || isLoading.value) return;
    
    if (saveTimeout) clearTimeout(saveTimeout);
    saveTimeout = setTimeout(() => {
        salvarConsultaSilenciosa();
    }, 5000); // Auto-save after 5s of inactivity
});

const salvarConsultaSilenciosa = async () => {
    if (!selectedPaciente.value) return;
    form.hda = hdaNotes.value + '\n|' + selectedCids.value.join('\n');
    // form.situacao stays as the current situation (Consultorio/Reconsulta)
    
    try {
        await axios.post(route('hospitalar.consultorio.store'), form.data());
    } catch (e) {
    }
};

const imprimirDadosClinico = () => {
    if (!selectedPaciente.value) return;
    
    let routeName = 'hospitalar.consultorio.imprimir.ficha';
    if (selectedPaciente.value.Consulta === 'MEDICINA OCUPACIONAL') {
        routeName = 'hospitalar.consultorio.imprimir.medicina-ocupacional';
    }

    let url = route(routeName, selectedPaciente.value.Codigo);
    const params = new URLSearchParams();
    if (isEconomicMode.value) {
        params.append('modo', 'economico');
        if (!isDuplicate.value) params.append('duplicado', '0');
    }
    if (params.toString()) url += '?' + params.toString();
    window.open(url, '_blank');
};

const solicitarExame = async (exame) => {
    if (!selectedPaciente.value) return;
    try {
        await axios.post(route('hospitalar.consultorio.solicitar-exames'), {
            IdAgenda: selectedPaciente.value.Codigo,
            exames: ['cat_' + (exame.dbId || exame.id.toString().replace('cat_', ''))]
        });
        showNotification('Exame solicitado!');
        await selecionarPaciente(selectedPaciente.value, false, true);
        router.reload({ only: ['aguardando'] });
    } catch (e) {
        showNotification('Erro ao solicitar exame.', 'error');
    }
};

const removerExameSolicitado = async (exame) => {
    confirmModal.value = {
        isOpen: true,
        title: 'Remover Exame',
        message: `Deseja remover o exame "${exame.nome}"?`,
        onConfirm: async () => {
            confirmModal.value.isOpen = false;
            try {
                await axios.post(route('hospitalar.consultorio.remover-exame'), { Id: exame.dbId });
                showNotification('Exame removido com sucesso!');
                await selecionarPaciente(selectedPaciente.value, false, true);
                router.reload({ only: ['aguardando'] });
            } catch (e) {
                showNotification(e.response?.data?.error || 'Erro ao remover exame.', 'error');
            }
        }
    };
};

const salvarConsulta = async () => {
    if (!selectedPaciente.value) return;
    if (saveTimeout) clearTimeout(saveTimeout);
    
    isLoading.value = true;
    form.hda = hdaNotes.value + '\n|' + selectedCids.value.join('\n');
    
    try {
        // Save normal clinical data
        await axios.post(route('hospitalar.consultorio.store'), form.data());
        
        // If occupational, save occupational data too
        if (selectedPaciente.value.Consulta === 'MEDICINA OCUPACIONAL' || medOcupacionalForm.value.empresa) {
            await axios.post(route('hospitalar.consultorio.medicina-ocupacional.store'), medOcupacionalForm.value);
        }
        
        showNotification('Dados gravados com sucesso!');
        router.reload({ only: ['aguardando'] });
    } catch (e) {
        showNotification(e.response?.data?.message || 'Erro ao gravar dados.', 'error');
    } finally {
        isLoading.value = false;
    }
};

const confirmarFinalizar = () => {
    confirmModal.value = {
        isOpen: true,
        title: 'Finalizar Atendimento',
        message: 'Deseja concluir o atendimento clínico e liberar o paciente? Esta ação irá gerar o relatório final.',
        onConfirm: async () => {
            confirmModal.value.isOpen = false;
            form.situacao = 'Finalizado';
            await salvarConsulta();
            selectedPaciente.value = null;
        }
    };
};

const confirmarInternamento = () => {
    confirmModal.value = {
        isOpen: true,
        title: 'Solicitar Internamento',
        message: 'O paciente será encaminhado para a unidade de internamento. Confirmar?',
        onConfirm: () => {
            confirmModal.value.isOpen = false;
            form.situacao = 'Internado';
            salvarConsulta();
            selectedPaciente.value = null;
        }
    };
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
    }).then(async () => {
        showNotification('Exames enviados com sucesso!');
        selectedExams.value = [];
        await selecionarPaciente(selectedPaciente.value, false, true);
        router.reload({ only: ['aguardando'] });
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

const currentFontSize = ref(props.config.fontSize || '10px');
const changeFontSize = (type) => {
    let size = parseInt(currentFontSize.value);
    if (type === 'increment') size++;
    else size--;
    if (size < 8) size = 8;
    if (size > 14) size = 14;
    currentFontSize.value = size + 'px';
};

</script>

<template>
    <Head title="Consultório Médico" />

    <DashboardLayout>
        <div :style="{ fontSize: currentFontSize }" class="p-0 bg-slate-100 min-h-[calc(100vh-64px)] flex font-sans relative">
            
            <!-- Sidebar: Lista de Espera -->
            <Transition 
                enter-active-class="transition-all duration-300 ease-in-out"
                enter-from-class="-ml-72 opacity-0"
                enter-to-class="ml-0 opacity-100"
                leave-active-class="transition-all duration-300 ease-in-out"
                leave-from-class="ml-0 opacity-100"
                leave-to-class="-ml-72 opacity-0"
            >
                <div v-if="showSidebar" class="w-72 bg-white border-r border-slate-200 flex flex-col shadow-xl z-30 shrink-0">
                    <div class="bg-blue-900 text-white p-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Users class="w-4 h-4" />
                            <span class="font-black uppercase tracking-widest text-[10px]">Fila de Espera</span>
                        </div>
                        <span class="bg-blue-700 px-2 py-0.5 rounded-full text-[9px] font-black">{{ filteredAguardando.length }}</span>
                    </div>
                    
                    <div class="p-3">
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
                            <input v-model="searchTerm" placeholder="Buscar paciente..." class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-9 pr-4 py-2 text-[10px] font-bold focus:bg-white transition-all outline-none" />
                        </div>
                    </div>

                    <div class="flex-grow overflow-y-auto custom-scrollbar">
                        <div v-for="p in filteredAguardando" :key="p.Codigo" 
                            @click="selecionarPaciente(p)"
                            :class="selectedPaciente?.Codigo === p.Codigo ? 'bg-blue-50 border-r-4 border-blue-600' : 'hover:bg-slate-50 border-r-4 border-transparent'"
                            class="p-3 cursor-pointer border-b border-slate-100 transition-all">
                            <div class="flex justify-between items-start mb-1">
                                <span class="text-[9px] font-black text-blue-600">#{{ p.Codigo }}</span>
                                <span v-if="p.Situacao === 'Laboratorio'" class="bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded text-[8px] font-black animate-pulse">LAB</span>
                            </div>
                            <div class="font-black text-slate-700 uppercase text-[10px] truncate">{{ p.PacienteNome }}</div>
                            <div class="flex items-center gap-2 mt-1 text-[8px] text-slate-400 font-bold">
                                <clock class="w-3 h-3" /> {{ p.DataAgendamento?.substring(11,16) }}
                                <span class="mx-1">•</span>
                                {{ p.Convenio || 'Particular' }}
                            </div>
                        </div>
                        <div v-if="filteredAguardando.length === 0" class="p-10 text-center flex flex-col items-center opacity-20">
                            <Users class="w-12 h-12 mb-2" />
                            <span class="text-[10px] font-black uppercase">Nenhum paciente</span>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- Main Content Area -->
            <div class="flex-grow flex flex-col min-w-0 bg-white">
                
                <!-- Page Header / Top Navigation -->
                <div class="h-14 bg-white border-b border-slate-200 flex items-center justify-between px-4 shrink-0 z-20">
                    <div class="flex items-center gap-4">
                        <button @click="showSidebar = !showSidebar" class="p-2 hover:bg-slate-100 rounded-lg transition-colors text-slate-500">
                            <ChevronRight :class="showSidebar ? 'rotate-180' : ''" class="w-5 h-5 transition-transform" />
                        </button>
                        
                        <!-- Nome da Tela -->
                        <div class="text-blue-600 font-black text-[9px] uppercase tracking-widest flex items-center gap-1.5 border-r border-slate-200 pr-4 mr-2">
                            <Stethoscope class="w-4 h-4" /> Consultório
                        </div>

                        <div v-if="selectedPaciente" class="flex items-center gap-3 animate-fadeIn">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                <User class="w-5 h-5 text-blue-600" />
                            </div>
                            <div class="flex flex-col">
                                <h2 class="font-black text-slate-800 uppercase text-xs leading-none">{{ selectedPaciente.PacienteNome }}</h2>
                                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">
                                    {{ selectedPaciente.Genero }} • {{ calcularIdadeFormatoDesktop(selectedPaciente.DataNascimento) }} • #{{ selectedPaciente.Codigo }}
                                </span>
                            </div>
                        </div>
                        <div v-else class="text-slate-300 font-black uppercase text-[10px] tracking-[0.2em]">
                            Selecione um paciente na fila para iniciar o atendimento
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                         <!-- Global Font Size Control -->
                        <div class="bg-slate-50 border border-slate-200 rounded-full px-2 py-1 flex items-center gap-2 mr-4">
                            <button @click="changeFontSize('decrement')" class="w-5 h-5 flex items-center justify-center bg-white border border-slate-200 hover:bg-slate-50 rounded-full font-bold text-[10px] shadow-sm">-</button>
                            <span class="text-[9px] font-black w-6 text-center text-slate-500">{{ currentFontSize }}</span>
                            <button @click="changeFontSize('increment')" class="w-5 h-5 flex items-center justify-center bg-white border border-slate-200 hover:bg-slate-50 rounded-full font-bold text-[10px] shadow-sm">+</button>
                        </div>

                        <button @click="imprimirDadosClinico" class="p-2 hover:bg-slate-100 rounded-lg text-slate-500 transition-all tooltip" title="Imprimir Ficha">
                            <Printer class="w-5 h-5" />
                        </button>
                        <button v-if="selectedPaciente" @click="baixarRelatorio(selectedPaciente.Codigo)" class="p-2 hover:bg-slate-100 rounded-lg text-emerald-600 transition-all tooltip" title="Baixar PDF">
                            <Download class="w-5 h-5" />
                        </button>
                        <button class="p-2 hover:bg-slate-100 rounded-lg text-slate-500 transition-all">
                            <settings-2 class="w-5 h-5" />
                        </button>
                    </div>
                </div>

<!-- Main Grid Content Area (Original Layout Restored) -->
<div class="flex-grow overflow-x-auto overflow-y-hidden relative bg-slate-200 p-2 custom-scrollbar">
    <template v-if="selectedPaciente">
        <div class="grid grid-cols-12 gap-2 h-full overflow-y-auto custom-scrollbar pr-1 min-w-[900px]">
            
            <!-- COLUNA 1: Histórico -->
            <div class="col-span-12 lg:col-span-3 flex flex-col h-full bg-white rounded shadow-sm border border-slate-300 overflow-hidden">
                <div class="bg-slate-100 px-3 py-2 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <History class="w-4 h-4 text-slate-600" />
                        <span class="font-black text-slate-700 uppercase text-[10px] tracking-widest">Histórico</span>
                    </div>
                    <span class="bg-slate-200 px-2 py-0.5 rounded text-[9px] font-bold">{{ patientHistory.length }}</span>
                </div>
                <div class="flex-grow overflow-y-auto bg-slate-50 p-2 space-y-2">
                    <div v-for="h in patientHistory" :key="h.Codigo" class="bg-white border border-slate-200 p-2 rounded hover:shadow-md transition-all cursor-pointer group">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-[9px] font-black text-blue-600">{{ h.DataEntrada?.substring(0, 10) }}</span>
                            <span class="text-[8px] font-bold text-slate-400 bg-slate-100 px-1 rounded">{{ h.HoraEntrada }}</span>
                        </div>
                        <div class="text-[10px] font-bold text-slate-700 truncate">{{ h.MedicoNome || 'N/D' }}</div>
                        <div class="flex justify-between items-center mt-1">
                            <span class="text-[8px] uppercase tracking-tighter bg-emerald-50 text-emerald-600 px-1 rounded font-bold">{{ h.Situacao }}</span>
                            <button @click="visualizarRelatorio(h.Codigo)" class="text-[8px] uppercase font-black text-blue-500 opacity-0 group-hover:opacity-100 transition-opacity">Ver Ficha</button>
                        </div>
                    </div>
                    <div v-if="patientHistory.length === 0" class="text-center p-4 text-slate-400 text-[10px] uppercase font-bold italic">Nenhum histórico</div>
                </div>
            </div>

            <!-- COLUNA 2: Dados Paciente, Triagem, Exames -->
            <div class="col-span-12 lg:col-span-4 flex flex-col gap-2 h-full overflow-y-auto">
                <!-- Patient Data -->
                <div class="bg-white rounded shadow-sm border border-slate-300 overflow-hidden shrink-0">
                    <div class="bg-slate-100 px-3 py-2 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <User class="w-4 h-4 text-slate-600" />
                            <span class="font-black text-slate-700 uppercase text-[10px] tracking-widest">Dados do Paciente</span>
                        </div>
                        <button v-if="selectedPaciente.Consulta === 'MEDICINA OCUPACIONAL'" @click="showMedOcupacionalModal = true" class="bg-orange-500 text-white px-2 py-0.5 rounded text-[9px] font-black uppercase shadow animate-pulse">
                            Med. Ocupacional
                        </button>
                    </div>
                    <div class="p-3 grid grid-cols-2 gap-2 bg-slate-50">
                        <div class="space-y-0.5 col-span-2">
                            <label class="text-[8px] font-black text-slate-400 uppercase">Paciente</label>
                            <div class="text-[10px] font-bold text-slate-700 bg-white p-1.5 border border-slate-200 rounded truncate">{{ selectedPaciente.PacienteNome }}</div>
                        </div>
                        <div class="space-y-0.5">
                            <label class="text-[8px] font-black text-slate-400 uppercase">Convênio</label>
                            <div class="text-[10px] font-bold text-slate-700 bg-white p-1.5 border border-slate-200 rounded truncate">{{ selectedPaciente.Convenio || 'Particular' }}</div>
                        </div>
                        <div class="space-y-0.5">
                            <label class="text-[8px] font-black text-slate-400 uppercase">Seguradora</label>
                            <div class="text-[10px] font-bold text-slate-700 bg-white p-1.5 border border-slate-200 rounded truncate">{{ selectedPaciente.Seguradora || '---' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Triage -->
                <div class="bg-white rounded shadow-sm border border-slate-300 overflow-hidden shrink-0">
                    <div class="bg-slate-100 px-3 py-2 border-b border-slate-200 flex items-center gap-2">
                        <Activity class="w-4 h-4 text-emerald-600" />
                        <span class="font-black text-slate-700 uppercase text-[10px] tracking-widest">Triagem</span>
                    </div>
                    <div class="p-2 grid grid-cols-3 gap-2 bg-slate-50">
                        <div v-for="v in [
                            { l: 'Peso', val: triageData?.Peso, u: 'kg' },
                            { l: 'Temp', val: triageData?.Temperatura, u: '°C' },
                            { l: 'PA', val: triageData?.PressaoArterial, u: '' },
                            { l: 'FC', val: triageData?.FrequenciaCardiaca, u: 'bpm' },
                            { l: 'FR', val: triageData?.FrequenciaRespiratoria, u: 'mpm' },
                            { l: 'SpO2', val: triageData?.SaturacaoOxigenio, u: '%' }
                        ]" :key="v.l" class="bg-white border border-slate-200 rounded p-1.5 text-center">
                            <div class="text-[8px] text-slate-400 font-black uppercase">{{ v.l }}</div>
                            <div class="text-[11px] font-black text-slate-700">{{ v.val || '--' }} <span class="text-[7px] font-normal">{{ v.u }}</span></div>
                        </div>
                    </div>
                </div>

                <!-- Exams (Rest of col) -->
                <div class="bg-white rounded shadow-sm border border-slate-300 flex flex-col flex-grow min-h-0">
                    <div class="bg-slate-100 px-2 py-1.5 border-b border-slate-200 flex items-center justify-between">
                        <span class="font-black text-slate-700 uppercase text-[10px] tracking-widest pl-1">Exames / Serviços</span>
                        <div class="flex gap-1">
                            <button @click="enviarExamesAoLaboratorio" class="bg-blue-600 text-white px-2 py-1 rounded text-[8px] font-black uppercase shadow">Solicitar</button>
                            <button @click="imprimirRequisicao" class="bg-orange-500 text-white px-2 py-1 rounded text-[8px] font-black uppercase shadow">Imp.</button>
                        </div>
                    </div>
                    <div class="flex flex-col flex-grow min-h-0 bg-slate-50">
                        <div class="flex gap-1 p-1 bg-slate-200 overflow-x-auto custom-scrollbar">
                            <button v-for="f in ['SOLICITADOS', 'LABORATORIO', 'RAIOX', 'FORA']" :key="f"
                                @click="activeExamFilter = f"
                                :class="activeExamFilter === f ? 'bg-white shadow text-blue-600' : 'text-slate-500'"
                                class="px-2 py-1 rounded text-[8px] font-black uppercase flex-shrink-0">
                                {{ f.replace('SOLICITADOS', 'Solicitados').replace('LABORATORIO', 'Laboratório').replace('RAIOX', 'Raio-X').replace('FORA', 'Exames Fora') }}
                            </button>
                        </div>
                        <div class="p-1 shrink-0">
                            <input v-model="searchExameQuery" placeholder="Buscar exame..." class="w-full text-[9px] p-1 border border-slate-300 rounded focus:border-blue-500 outline-none font-bold" />
                        </div>
                        <div class="flex-grow overflow-y-auto custom-scrollbar p-1">
                            <div v-for="ex in paginatedExamesList" :key="ex.id" class="flex items-center justify-between p-1.5 border-b border-slate-200 hover:bg-slate-100 group">
                                <div class="flex items-center gap-2 overflow-hidden">
                                    <input v-if="!ex.isRequested" type="checkbox" :value="ex.id" v-model="selectedExams" class="w-3 h-3 text-blue-600 rounded" />
                                    <CheckCircle v-else class="w-3 h-3 text-emerald-500 shrink-0" />
                                    <span class="text-[9px] font-bold text-slate-700 uppercase truncate" :title="ex.nome">{{ ex.nome }}</span>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <span v-if="ex.resultado" class="text-[8px] text-blue-600 font-black italic">{{ ex.resultado }}</span>
                                    <button v-if="ex.isRequested" @click="selectedExameToLancar = ex; showLancarResultadosModal = true" class="text-blue-600 font-black text-[8px] uppercase hover:underline">Lançar</button>
                                    <button v-if="ex.isRequested" @click="removerExameSolicitado(ex)" class="text-red-500 opacity-0 group-hover:opacity-100"><Trash2 class="w-3 h-3" /></button>
                                </div>
                            </div>
                            
                            <!-- Pagination Controls -->
                            <div v-if="totalPagesExames > 1" class="flex items-center justify-between p-2 mt-auto border-t border-slate-200 bg-white shadow-inner">
                                <button @click="exameCurrentPage--" :disabled="exameCurrentPage === 1" class="p-1 rounded hover:bg-slate-100 disabled:opacity-30 transition-colors">
                                    <ChevronLeft class="w-3 h-3 text-slate-600" />
                                </button>
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-tighter">
                                    Página {{ exameCurrentPage }} de {{ totalPagesExames }}
                                </span>
                                <button @click="exameCurrentPage++" :disabled="exameCurrentPage >= totalPagesExames" class="p-1 rounded hover:bg-slate-100 disabled:opacity-30 transition-colors">
                                    <ChevronRight class="w-3 h-3 text-slate-600" />
                                </button>
                            </div>
                        </div>
                        <div class="p-1 grid grid-cols-2 gap-1 bg-slate-100 shrink-0 border-t border-slate-300">
                            <button @click="enviarExamesAoLaboratorio" :disabled="isReadOnly" class="bg-blue-600 text-white py-1.5 font-black uppercase flex items-center justify-center gap-1 hover:bg-blue-700 text-[8px] shadow-sm disabled:opacity-50 rounded">
                                <SendHorizontal class="w-3 h-3" /> LABORATÓRIO
                            </button>
                            <button @click="showLancarResultadosModal = true" class="bg-slate-800 text-white py-1.5 font-black uppercase flex items-center justify-center gap-1 hover:bg-slate-900 shadow-sm rounded text-[8px]">
                                <Activity class="w-3 h-3" /> LANÇAR RESULTADOS
                            </button>
                            <button @click="imprimirRequisicao" class="bg-orange-500 text-white py-1.5 font-black uppercase flex items-center justify-center gap-1 hover:bg-orange-600 text-[8px] shadow-sm rounded">
                                <Printer class="w-3 h-3" /> REQUISIÇÃO
                            </button>
                            <button @click="imprimirResultadosLab" class="bg-orange-500 text-white py-1.5 font-black uppercase flex items-center justify-center gap-1 hover:bg-orange-600 text-[8px] shadow-sm rounded">
                                <Printer class="w-3 h-3" /> RESULTADOS
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLUNA 3: Dados Clínicos, Receita -->
            <div class="col-span-12 lg:col-span-5 flex flex-col gap-2 h-full overflow-y-auto">
                <!-- Clinical Notes -->
                <div class="bg-white rounded shadow-sm border border-slate-300 flex flex-col shrink-0">
                    <div class="bg-slate-100 px-3 py-2 border-b border-slate-200 flex items-center gap-2">
                        <Stethoscope class="w-4 h-4 text-blue-600" />
                        <span class="font-black text-slate-700 uppercase text-[10px] tracking-widest">Dados Clínicos</span>
                    </div>
                    <div class="p-2 grid grid-cols-1 gap-2 bg-slate-50">
                        <textarea v-model="form.qp" placeholder="Queixa Principal (QP)..." class="w-full text-[10px] p-2 border border-slate-300 rounded h-16 font-medium focus:border-blue-500 outline-none resize-none custom-scrollbar"></textarea>
                        
                        <!-- HDA & CID combined visually -->
                        <div class="border border-slate-300 rounded overflow-hidden bg-white">
                            <textarea v-model="hdaNotes" placeholder="História da Doença Atual (HDA)..." class="w-full text-[10px] p-2 border-b border-slate-200 h-16 font-medium outline-none resize-none custom-scrollbar"></textarea>
                            <div class="p-1 bg-slate-100 flex flex-col gap-1">
                                <label class="text-[8px] font-black text-slate-400 uppercase ml-1">Hipótese Diagnóstica (CID-10)</label>
                                <div class="relative">
                                    <input v-model="searchCidTerm" placeholder="Pesquisar CID ou Código..." class="w-full text-[9px] p-1.5 border border-slate-300 rounded focus:border-blue-500 outline-none font-bold" />
                                    <div v-if="filteredCidCatalog.length > 0" class="absolute z-50 bg-white border border-slate-300 w-full mt-1 max-h-32 overflow-y-auto shadow-xl rounded custom-scrollbar">
                                        <div v-for="c in filteredCidCatalog" :key="c.codigo" @click="addCid(c)" class="p-1.5 text-[9px] hover:bg-blue-600 hover:text-white cursor-pointer font-bold border-b border-slate-100 transition-colors">
                                            <span class="font-black">{{ c.Indicador }}</span> - {{ c.Descricao }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-1">
                                    <button @click="adicionarCidDaPesquisa" :disabled="isReadOnly" class="flex-1 bg-blue-600 text-white py-1 font-black flex items-center justify-center gap-1 text-[8px] uppercase rounded shadow-sm hover:bg-blue-700 disabled:opacity-50 transition-all">
                                        <Plus class="w-3 h-3" /> Adicionar
                                    </button>
                                    <button :disabled="isReadOnly" class="flex-1 bg-blue-500 text-white py-1 font-black text-[8px] uppercase rounded shadow-sm disabled:opacity-50 hover:bg-blue-600 transition-all">
                                        Cadastrar
                                    </button>
                                </div>
                                <div class="flex flex-col gap-0.5 mt-1 bg-white border border-slate-200 rounded p-1 min-h-[40px] max-h-24 overflow-y-auto custom-scrollbar">
                                    <div v-for="(cid, idx) in selectedCids" :key="idx" class="flex justify-between items-center bg-blue-50 hover:bg-red-50 p-1 rounded group transition-colors">
                                        <span class="text-[9px] font-bold text-slate-700 truncate pr-2">{{ cid }}</span>
                                        <button @click="removeCid(idx)" class="text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">×</button>
                                    </div>
                                    <div v-if="selectedCids.length === 0" class="text-[8px] text-slate-300 italic text-center py-2 uppercase">Nenhum diagnóstico selecionado</div>
                                </div>
                            </div>
                        </div>

                        <textarea v-model="form.obj" placeholder="Exame Físico / Objectivo..." class="w-full text-[10px] p-2 border border-slate-300 rounded h-16 font-medium focus:border-blue-500 outline-none resize-none custom-scrollbar"></textarea>
                        <textarea v-model="form.complementares" placeholder="Exames Complementares..." class="w-full text-[10px] p-2 border border-slate-300 rounded h-12 font-medium focus:border-blue-500 outline-none resize-none custom-scrollbar"></textarea>
                    </div>
                </div>

                <!-- Prescription (Rest of col) -->
                <div class="bg-white rounded shadow-sm border border-slate-300 flex flex-col flex-grow min-h-0">
                    <div class="bg-slate-100 px-2 py-1.5 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Pill class="w-4 h-4 text-orange-500" />
                            <span class="font-black text-slate-700 uppercase text-[10px] tracking-widest">Receita Médica</span>
                        </div>
                        <div class="flex gap-1">
                            <button @click="gravarReceita" class="bg-blue-600 text-white px-2 py-1 rounded text-[8px] font-black uppercase shadow">Gravar</button>
                            <button @click="imprimirReceita" class="bg-orange-500 text-white px-2 py-1 rounded text-[8px] font-black uppercase shadow">Imp.</button>
                        </div>
                    </div>
                    <div class="flex flex-col flex-grow min-h-0 bg-slate-50">
                        <div class="flex-grow overflow-y-auto custom-scrollbar">
                            <table class="w-full text-left text-[9px]">
                                <thead class="bg-slate-200 text-slate-500 font-black uppercase sticky top-0">
                                    <tr>
                                        <th class="p-1.5">Fármaco</th>
                                        <th class="p-1.5">Dosagem</th>
                                        <th class="p-1.5 w-10">Qtd</th>
                                        <th class="p-1.5 w-6"></th>
                                    </tr>
                                </thead>
                                <tbody class="font-bold text-slate-700 bg-white">
                                    <tr v-for="(item, idx) in todosItensReceita" :key="item.id" class="border-b border-slate-100 hover:bg-slate-50 group">
                                        <td class="p-1.5 uppercase truncate max-w-[100px]">{{ item.farmaco }}</td>
                                        <td class="p-1.5 truncate max-w-[80px]">{{ item.dosagem }}</td>
                                        <td class="p-1.5 text-center">{{ item.dias }}</td>
                                        <td class="p-1.5 text-center">
                                            <button @click="removerItemLocalOuDB(item, idx)" class="text-red-400 opacity-0 group-hover:opacity-100 hover:text-red-600"><Trash2 class="w-3 h-3"/></button>
                                        </td>
                                    </tr>
                                    <!-- Add row -->
                                    <tr class="bg-blue-50/50">
                                        <td class="p-1 border-r border-white">
                                            <input v-model="novoFarmaco.farmaco" list="farmacos-list" placeholder="Fármaco..." class="w-full bg-white border border-slate-300 rounded p-1 text-[9px]" @keydown.enter="adicionarFarmacoLocal" />
                                        </td>
                                        <td class="p-1 border-r border-white">
                                            <input v-model="novoFarmaco.dosagem" placeholder="Dosagem..." class="w-full bg-white border border-slate-300 rounded p-1 text-[9px]" @keydown.enter="adicionarFarmacoLocal" />
                                        </td>
                                        <td class="p-1">
                                            <input v-model="novoFarmaco.dias" placeholder="Qtd" type="number" class="w-full bg-white border border-slate-300 rounded p-1 text-[9px] text-center" @keydown.enter="adicionarFarmacoLocal" />
                                        </td>
                                        <td class="p-1 text-center">
                                            <button @click="adicionarFarmacoLocal" class="bg-blue-500 text-white p-1 rounded hover:bg-blue-600"><Plus class="w-3 h-3"/></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </template>

                    <!-- Empty State: No patient selected -->
                    <div v-else class="h-full flex flex-col items-center justify-center p-12 text-center animate-fadeIn">
                        <div class="w-32 h-32 bg-slate-50 rounded-full flex items-center justify-center mb-6 border border-slate-100 shadow-inner">
                            <Stethoscope class="w-16 h-16 text-slate-200" />
                        </div>
                        <h3 class="text-lg font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Pronto para o Próximo Atendimento?</h3>
                        <p class="text-slate-400 text-xs font-medium max-w-sm mb-8">Selecione um paciente na fila à esquerda para visualizar sua ficha, histórico e iniciar o atendimento clínico.</p>
                        <div class="flex gap-4">
                            <button @click="showSidebar = true" class="bg-blue-600 text-white px-8 py-3 rounded-xl font-black uppercase text-[10px] tracking-widest hover:bg-blue-700 transition-all shadow-lg shadow-blue-100 flex items-center gap-2">
                                <Users class="w-4 h-4" /> Abrir Fila de Espera
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sticky Footer Actions -->
                <div v-if="selectedPaciente" class="h-14 bg-white border-t border-slate-200 px-6 flex items-center justify-between shrink-0 z-20 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                    <div class="flex items-center gap-2">

                        <button @click="confirmarInternamento" class="text-blue-600 hover:bg-blue-50 px-3 py-2 rounded font-black uppercase text-[10px] tracking-widest transition-all flex items-center gap-2">
                            <BedDouble class="w-4 h-4" /> Solicitar Internamento
                        </button>
                        <button @click="showEncaminharModal = true" class="text-blue-600 hover:bg-blue-50 px-3 py-2 rounded font-black uppercase text-[10px] tracking-widest transition-all flex items-center gap-2">
                            <ArrowRightLeft class="w-4 h-4" /> Encaminhar
                        </button>
                        <button @click="showDocumentosModal = true" class="text-slate-600 hover:bg-slate-100 px-3 py-2 rounded font-black uppercase text-[10px] tracking-widest transition-all flex items-center gap-2">
                            <FileText class="w-4 h-4" /> Documentos
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <button @click="showDocumentosModal = true" class="bg-orange-500 text-white h-10 min-w-[140px] px-6 rounded font-black uppercase text-[10px] tracking-widest hover:bg-orange-600 transition-all shadow shadow-orange-100 flex items-center justify-center gap-2 whitespace-nowrap">
                            <Printer class="w-4 h-4" /> Imprimir
                        </button>
                        <button @click="salvarConsulta" :disabled="isLoading" class="bg-blue-600 text-white h-10 min-w-[140px] px-6 rounded font-black uppercase text-[10px] tracking-widest hover:bg-blue-700 transition-all shadow shadow-blue-100 flex items-center justify-center gap-2 active:scale-95 disabled:opacity-50 whitespace-nowrap">
                            <Save v-if="!isLoading" class="w-4 h-4" />
                            <div v-else class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                            Gravar Dados
                        </button>
                        <button @click="confirmarFinalizar" class="bg-emerald-500 text-white h-10 min-w-[140px] px-6 rounded font-black uppercase text-[10px] tracking-widest hover:bg-emerald-600 transition-all shadow shadow-emerald-100 flex items-center justify-center gap-2 active:scale-95 whitespace-nowrap">
                            <CheckCircle class="w-4 h-4" /> Finalizar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: Escolher Documento -->
        <div v-if="showDocumentosModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
            <div class="bg-white w-full max-w-[500px] rounded shadow-2xl border border-slate-300 overflow-hidden">
                <div class="bg-slate-800 text-white p-3 font-black uppercase text-[10px] flex justify-between">
                    <span>Escolher Documento para Impressão</span>
                    <button @click="showDocumentosModal = false">×</button>
                </div>
                <div class="p-4 flex flex-col gap-4 bg-slate-50">
                    <!-- TOGGLE ECONOMICO NO MODAL -->
                    <div class="flex flex-col gap-2 bg-white p-3 rounded border border-slate-200 shadow-sm">
                        <label class="flex items-center justify-between cursor-pointer">
                            <span class="text-[10px] font-black text-slate-600 uppercase">Economia de Papel (2x A5)</span>
                            <div class="relative">
                                <input type="checkbox" v-model="isEconomicMode" class="sr-only">
                                <div class="w-10 h-5 bg-slate-300 rounded-full shadow-inner"></div>
                                <div :class="['absolute w-5 h-5 bg-white rounded-full shadow inset-y-0 left-0 transition-all', isEconomicMode ? 'translate-x-5 bg-emerald-500' : '']"></div>
                            </div>
                        </label>
                        <label v-if="isEconomicMode" class="flex items-center gap-2 mt-1 border-t border-slate-100 pt-2 cursor-pointer">
                            <input type="checkbox" v-model="isDuplicate" class="w-4 h-4 text-blue-600 rounded" />
                            <span class="text-[9px] font-bold text-slate-500 uppercase">Imprimir 2 cópias (Duplicado)</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 gap-2">
                        <button @click="imprimirDadosClinico(); showDocumentosModal = false" class="w-full text-left p-3 border border-slate-200 hover:bg-blue-50 rounded flex items-center gap-3 transition-all group bg-white">
                            <FileText class="w-5 h-5 text-blue-600" />
                            <div class="flex-grow flex flex-col">
                                <span class="font-black text-slate-700 text-[11px] uppercase">Ficha Médica Geral</span>
                                <span class="text-[9px] text-slate-400">Visualizar/Imprimir relatório completo</span>
                            </div>
                        </button>
                        <button @click="baixarRelatorio(selectedPaciente.Codigo); showDocumentosModal = false" class="w-full text-left p-3 border border-slate-200 hover:bg-emerald-50 rounded flex items-center gap-3 transition-all group bg-white">
                            <Download class="w-5 h-5 text-emerald-600" />
                            <div class="flex-grow flex flex-col">
                                <span class="font-black text-slate-700 text-[11px] uppercase">Baixar em PDF</span>
                                <span class="text-[9px] text-slate-400">Download directo do arquivo PDF</span>
                            </div>
                        </button>
                        <button @click="gerarJustificativo(); showDocumentosModal = false" class="w-full text-left p-3 border border-slate-200 hover:bg-blue-50 rounded flex items-center gap-3 transition-all group bg-white">
                            <FileText class="w-5 h-5 text-orange-600" />
                            <div class="flex flex-col">
                                <span class="font-black text-slate-700 text-[11px] uppercase">Justificativo Médico</span>
                                <span class="text-[9px] text-slate-400">Declaração de presença e incapacidade</span>
                            </div>
                        </button>
                        <button @click="gerarGuiaTransferencia(); showDocumentosModal = false" class="w-full text-left p-3 border border-slate-200 hover:bg-blue-50 rounded flex items-center gap-3 transition-all group bg-white">
                            <ArrowRightLeft class="w-5 h-5 text-emerald-600" />
                            <div class="flex flex-col">
                                <span class="font-black text-slate-700 text-[11px] uppercase">Guia de Transferência</span>
                                <span class="text-[9px] text-slate-400">Transferência para outra unidade de saúde</span>
                            </div>
                        </button>
                    </div>
                </div>
                <div class="p-3 bg-slate-100 border-t border-slate-200 text-right">
                    <button @click="showDocumentosModal = false" class="bg-slate-300 text-slate-700 px-4 py-1.5 rounded font-bold uppercase text-[9px]">Fechar</button>
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


        <!-- MODAL: Justificativo Médico -->
        <div v-if="showJustificativoModal" class="fixed inset-0 z-[120] flex items-center justify-center p-4 bg-black/50 backdrop-blur-md">
            <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl border border-slate-300 overflow-hidden animate-modalIn">
                <div class="bg-gradient-to-r from-orange-600 to-orange-500 text-white p-4 font-black uppercase text-[10px] flex justify-between items-center">
                    <span>Preencher Justificativo Médico</span>
                    <button @click="showJustificativoModal = false"><X class="w-4 h-4"/></button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-[9px] font-black text-slate-400 uppercase">Acompanhante de (Opcional)</label>
                        <input v-model="justificativoData.familiar" class="w-full border border-slate-300 rounded p-2 text-xs font-bold" placeholder="Nome do paciente acompanhado" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[9px] font-black text-slate-400 uppercase">Data de Internamento (Se aplicável)</label>
                        <input type="date" v-model="justificativoData.data_internado" class="w-full border border-slate-300 rounded p-2 text-xs font-bold" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1">
                            <label class="text-[9px] font-black text-slate-400 uppercase">Início Repouso</label>
                            <input type="date" v-model="justificativoData.data_inicio" class="w-full border border-slate-300 rounded p-2 text-xs font-bold" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[9px] font-black text-slate-400 uppercase">Fim Repouso</label>
                            <input type="date" v-model="justificativoData.data_fim" class="w-full border border-slate-300 rounded p-2 text-xs font-bold" />
                        </div>
                    </div>
                    <div class="flex gap-2 pt-4">
                        <button @click="showJustificativoModal = false" class="flex-1 py-3 font-black uppercase text-[10px] text-slate-400 hover:text-slate-600">Cancelar</button>
                        <button @click="confirmarJustificativo" class="flex-1 bg-orange-600 text-white py-3 rounded-xl font-black uppercase text-[10px] shadow-lg shadow-orange-200">Gerar Documento</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: Guia de Transferência -->
        <div v-if="showGuiaModal" class="fixed inset-0 z-[120] flex items-center justify-center p-4 bg-black/50 backdrop-blur-md">
            <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl border border-slate-300 overflow-hidden">
                <div class="bg-gradient-to-r from-emerald-600 to-emerald-500 text-white p-4 font-black uppercase text-[10px] flex justify-between items-center">
                    <span>Preencher Guia de Transferência</span>
                    <button @click="showGuiaModal = false"><X class="w-4 h-4"/></button>
                </div>
                <div class="p-6 space-y-4 max-h-[85vh] overflow-y-auto custom-scrollbar">
                    
                    <!-- Dados do Destino -->
                    <div class="flex flex-col gap-1">
                        <label class="text-[9px] font-black text-slate-400 uppercase">Unidade de Destino *</label>
                        <input v-model="guiaData.correspondente" class="w-full border border-slate-300 rounded p-2 text-xs font-bold" placeholder="Nome do Hospital/Clínica de Destino" />
                    </div>

                    <!-- Tempos -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1">
                            <label class="text-[9px] font-black text-slate-400 uppercase">Hora de Admissão</label>
                            <input type="time" v-model="guiaData.hora_admissao" class="w-full border border-slate-300 rounded p-2 text-xs font-bold" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[9px] font-black text-slate-400 uppercase">Hora de Saída</label>
                            <input type="time" v-model="guiaData.hora_saida" class="w-full border border-slate-300 rounded p-2 text-xs font-bold" />
                        </div>
                    </div>

                    <!-- História Clínica -->
                    <div class="flex flex-col gap-1">
                        <label class="text-[9px] font-black text-slate-400 uppercase">História Clínica</label>
                        <textarea v-model="guiaData.historia_clinica" class="w-full border border-slate-300 rounded p-2 text-xs font-bold h-24 resize-none" placeholder="Descreva a história clínica do paciente..."></textarea>
                    </div>

                    <!-- Motivo da Transferência -->
                    <div class="flex flex-col gap-1">
                        <label class="text-[9px] font-black text-slate-400 uppercase">Motivo da Transferência *</label>
                        <textarea v-model="guiaData.motivo" class="w-full border border-slate-300 rounded p-2 text-xs font-bold h-20 resize-none" placeholder="Motivo claro da transferência..."></textarea>
                    </div>

                    <!-- Diagnóstico -->
                    <div class="flex flex-col gap-1">
                        <label class="text-[9px] font-black text-slate-400 uppercase">Diagnóstico *</label>
                        <textarea v-model="guiaData.diagnostico" class="w-full border border-slate-300 rounded p-2 text-xs font-bold h-16 resize-none" placeholder="Diagnóstico / Hipótese Diagnóstica"></textarea>
                    </div>

                    <!-- Exames e Análises -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1">
                            <label class="text-[9px] font-black text-slate-400 uppercase">Exames Realizados</label>
                            <textarea v-model="guiaData.exames_realizados" class="w-full border border-slate-300 rounded p-2 text-xs font-bold h-20 resize-none"></textarea>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[9px] font-black text-slate-400 uppercase">Análises Laboratoriais</label>
                            <textarea v-model="guiaData.analises" class="w-full border border-slate-300 rounded p-2 text-xs font-bold h-20 resize-none"></textarea>
                        </div>
                    </div>

                    <!-- Tratamento Estruturado -->
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center justify-between">
                            <label class="text-[9px] font-black text-slate-400 uppercase">Tratamento Efetuado</label>
                            <button @click="addTratamentoItem" type="button" class="text-[9px] font-black text-emerald-600 hover:text-emerald-700 uppercase flex items-center gap-1">
                                + Adicionar Medicamento
                            </button>
                        </div>
                        
                        <div v-if="guiaData.tratamento_itens.length > 0" class="space-y-2">
                            <div v-for="(item, index) in guiaData.tratamento_itens" :key="index" class="grid grid-cols-12 gap-1 items-end bg-slate-50 p-2 rounded border border-slate-200">
                                <div class="col-span-3">
                                    <label class="text-[8px] font-black text-slate-400 uppercase">Medicamento</label>
                                    <input v-model="item.medicamento" class="w-full border border-slate-300 rounded p-1 text-[10px]" placeholder="Ex: Amoxicilina" />
                                </div>
                                <div class="col-span-2">
                                    <label class="text-[8px] font-black text-slate-400 uppercase">Dosagem</label>
                                    <input v-model="item.dosagem" class="w-full border border-slate-300 rounded p-1 text-[10px]" placeholder="Ex: 500mg" />
                                </div>
                                <div class="col-span-2">
                                    <label class="text-[8px] font-black text-slate-400 uppercase">Quantidade</label>
                                    <input v-model="item.quantidade" class="w-full border border-slate-300 rounded p-1 text-[10px]" placeholder="Ex: 10cp" />
                                </div>
                                <div class="col-span-2">
                                    <label class="text-[8px] font-black text-slate-400 uppercase">Horário</label>
                                    <input v-model="item.horario" class="w-full border border-slate-300 rounded p-1 text-[10px]" placeholder="Ex: 8/8h" />
                                </div>
                                <div class="col-span-2">
                                    <label class="text-[8px] font-black text-slate-400 uppercase">Via</label>
                                    <select v-model="item.via" class="w-full border border-slate-300 rounded p-1 text-[10px]">
                                        <option value="">Selecione</option>
                                        <option value="VO">VO (Oral)</option>
                                        <option value="IV">IV (Intravenosa)</option>
                                        <option value="IM">IM (Intramuscular)</option>
                                        <option value="SC">SC (Subcutânea)</option>
                                        <option value="Topica">Tópica</option>
                                        <option value="Retal">Retal</option>
                                    </select>
                                </div>
                                <div class="col-span-1 flex justify-center">
                                    <button @click="removeTratamentoItem(index)" type="button" class="text-red-400 hover:text-red-600 p-1">
                                        <X class="w-3 h-3" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-[9px] font-black text-slate-400 uppercase">Tratamento Adicional (texto livre)</label>
                            <textarea v-model="guiaData.tratamento" class="w-full border border-slate-300 rounded p-2 text-xs font-bold h-16 resize-none" placeholder="Outros tratamentos não listados acima..."></textarea>
                        </div>
                    </div>

                    <!-- Observações Finais -->
                    <div class="flex flex-col gap-1">
                        <label class="text-[9px] font-black text-slate-400 uppercase">Observações Finais</label>
                        <textarea v-model="guiaData.obs_final" class="w-full border border-slate-300 rounded p-2 text-xs font-bold h-16 resize-none" placeholder="Observações adicionais..."></textarea>
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button @click="showGuiaModal = false" class="flex-1 py-3 font-black uppercase text-[10px] text-slate-400 hover:text-slate-600">Cancelar</button>
                        <button @click="confirmarGuia" class="flex-1 bg-emerald-600 text-white py-3 rounded-xl font-black uppercase text-[10px] shadow-lg shadow-emerald-200">Gerar Documento</button>
                    </div>
                </div>
            </div>
        </div>

    </DashboardLayout>

    <!-- PREMIUM CONFIRM MODAL -->
    <Transition enter-active-class="duration-300 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="duration-200 ease-in" leave-to-class="opacity-0 scale-95">
        <div v-if="confirmModal.isOpen" class="fixed inset-0 z-[2000] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-md" @click="confirmModal.isOpen = false"></div>
            <div class="relative bg-white rounded-[2.5rem] shadow-2xl p-10 max-w-md w-full border border-white/20 animate-fadeIn text-center">
                <div class="flex flex-col items-center">
                    <div class="w-20 h-20 bg-blue-50 rounded-[2rem] flex items-center justify-center mb-6 shadow-inner">
                        <Stethoscope class="w-10 h-10 text-blue-600" />
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

    <!-- MODAL: Medicina Ocupacional (FULL) -->
    <div v-if="showMedOcupacionalModal" class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
        <div class="bg-white w-full max-w-[1000px] h-[95vh] rounded shadow-2xl border border-slate-300 flex flex-col overflow-hidden">
            <div class="bg-blue-900 text-white p-3 font-black uppercase text-[10px] flex justify-between items-center shrink-0">
                <div class="flex items-center gap-2">
                    <UserRoundCog class="w-5 h-5" />
                    <span>Ficha de Medicina Ocupacional - {{ selectedPaciente?.PacienteNome }}</span>
                </div>
                <button @click="showMedOcupacionalModal = false" class="hover:bg-red-500 p-1 rounded transition-colors"><X class="w-5 h-5" /></button>
            </div>

            <!-- Tabs Header -->
            <div class="flex bg-slate-100 border-b border-slate-200 shrink-0">
                <button v-for="t in [1,2,3,4,5]" :key="t" @click="activeMedTab = t" 
                    :class="activeMedTab === t ? 'bg-white border-b-2 border-blue-600 text-blue-600' : 'text-slate-400 hover:bg-slate-50'"
                    class="px-6 py-2.5 font-black uppercase text-[9px] transition-all">
                    {{ t === 1 ? 'Empresa / Exame' : t === 2 ? 'Histórico' : t === 3 ? 'Exame Físico' : t === 4 ? 'Conclusão' : 'Exames e Serviços' }}
                </button>
            </div>

            <div class="flex-grow overflow-y-auto p-4 custom-scrollbar bg-slate-50/50">
                <!-- TAB 1: Empresa e Tipo de Exame -->
                <div v-if="activeMedTab === 1" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1">
                            <label class="font-black text-slate-500 text-[8px] uppercase">Empresa *</label>
                            <input v-model="medOcupacionalForm.empresa" class="w-full border border-slate-300 p-2 rounded bg-white text-[10px] font-bold" placeholder="Nome da Empresa" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="font-black text-slate-500 text-[8px] uppercase">Função *</label>
                            <input v-model="medOcupacionalForm.funcao" class="w-full border border-slate-300 p-2 rounded bg-white text-[10px] font-bold" placeholder="Função na Empresa" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div class="bg-white p-3 border border-slate-200 rounded">
                            <h4 class="font-black text-blue-900 text-[9px] uppercase mb-3 border-b pb-1">Tipo de Exame</h4>
                            <div class="grid grid-cols-2 gap-2">
                                <label v-for="type in MED_EXAM_TYPES" :key="type" class="flex items-center gap-2 text-[9px] font-bold text-slate-600 cursor-pointer">
                                    <input type="checkbox" :value="type" v-model="medOcupacionalForm.tipoExame" class="rounded text-blue-600" /> {{ type }}
                                </label>
                            </div>
                        </div>
                        <div class="bg-white p-3 border border-slate-200 rounded">
                            <h4 class="font-black text-red-900 text-[9px] uppercase mb-3 border-b pb-1">Factores de Risco</h4>
                            <div class="grid grid-cols-2 gap-2">
                                <label v-for="risk in MED_RISKS" :key="risk" class="flex items-center gap-2 text-[9px] font-bold text-slate-600 cursor-pointer">
                                    <input type="checkbox" :value="risk" v-model="medOcupacionalForm.factoresRisco" class="rounded text-red-600" /> {{ risk }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: Histórico -->
                <div v-if="activeMedTab === 2" class="space-y-6">
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-6 bg-white p-3 border border-slate-200 rounded">
                            <h4 class="font-black text-slate-700 text-[9px] uppercase mb-3 border-b pb-1">História Pregressa</h4>
                            <div v-for="(v, k) in medOcupacionalForm.historiaPregressa" :key="k" class="flex items-center gap-3 mb-1">
                                <label class="flex items-center gap-2 w-32 shrink-0 text-[9px] font-bold text-slate-600 cursor-pointer">
                                    <input type="checkbox" v-model="v.checked" /> {{ k.toUpperCase() }}
                                </label>
                                <input v-if="v.checked" v-model="v.detail" class="flex-grow border-b border-slate-200 text-[9px] outline-none" placeholder="..." />
                            </div>
                        </div>
                        <div class="col-span-6 bg-white p-3 border border-slate-200 rounded">
                            <h4 class="font-black text-slate-700 text-[9px] uppercase mb-3 border-b pb-1">Histórico Familiar</h4>
                            <div class="grid grid-cols-2 gap-2">
                                <label v-for="f in MED_FAMILIAR" :key="f" class="flex items-center gap-2 text-[9px] font-bold text-slate-600 cursor-pointer">
                                    <input type="checkbox" :value="f" v-model="medOcupacionalForm.historiaFamiliar" /> {{ f }}
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border border-slate-200 rounded overflow-hidden">
                        <div class="bg-slate-800 text-white p-2 font-black uppercase text-[8px] flex justify-between">
                            <span>Histórico Ocupacional Anterior</span>
                            <button @click="addHistoricoOcupacional" class="bg-emerald-600 px-2 py-0.5 rounded text-[8px] font-black uppercase">+ Adicionar</button>
                        </div>
                        <table class="w-full">
                            <tbody>
                                <tr v-for="(h, i) in medOcupacionalForm.historicoOcupacional" :key="i" class="border-b border-slate-50">
                                    <td class="p-1"><input v-model="h.funcao" class="w-full bg-transparent border-none text-[9px] font-bold" placeholder="Função..." /></td>
                                    <td class="p-1"><input v-model="h.tempo" class="w-full bg-transparent border-none text-[9px] font-bold" placeholder="Tempo..." /></td>
                                    <td class="p-1 text-right"><button @click="removeHistoricoOcupacional(i)" class="text-red-500 p-1"><Trash2 class="w-3.5 h-3.5" /></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 3: Exame Físico por Sistemas -->
                <div v-if="activeMedTab === 3" class="space-y-1">
                    <div class="bg-slate-800 text-white p-2 font-black uppercase text-[8px] grid grid-cols-12 gap-4 sticky top-0 z-10">
                        <div class="col-span-3">Sistema / Aparelho</div>
                        <div class="col-span-2 text-center">Estado</div>
                        <div class="col-span-4">Alterações Específicas</div>
                        <div class="col-span-3">Observações</div>
                    </div>
                    <div v-for="s in MED_SYSTEMS" :key="s.key" class="bg-white border border-slate-200 grid grid-cols-12 gap-4 p-2 items-center group hover:bg-blue-50/50 transition-colors">
                        <div class="col-span-3 font-black text-slate-700 text-[9px] uppercase">{{ s.label }}</div>
                        <div class="col-span-2 flex justify-center gap-3">
                            <label class="flex items-center gap-1 text-[9px] font-black text-emerald-600 cursor-pointer">
                                <input type="radio" value="Normal" v-model="medOcupacionalForm.exameFisico[s.key].estado" /> N
                            </label>
                            <label class="flex items-center gap-1 text-[9px] font-black text-red-600 cursor-pointer">
                                <input type="radio" value="Com Alteração" v-model="medOcupacionalForm.exameFisico[s.key].estado" /> A
                            </label>
                        </div>
                        <div class="col-span-4">
                            <div v-if="medOcupacionalForm.exameFisico[s.key].estado === 'Com Alteração'" class="flex flex-wrap gap-2">
                                <label v-for="alt in s.alts" :key="alt" class="flex items-center gap-1 text-[8px] font-bold text-slate-500 cursor-pointer hover:text-slate-900">
                                    <input type="checkbox" :value="alt" v-model="medOcupacionalForm.exameFisico[s.key].alteracoes" class="rounded w-3 h-3" /> {{ alt }}
                                </label>
                            </div>
                            <div v-else class="text-[8px] text-slate-300 italic uppercase">Sem alterações</div>
                        </div>
                        <div class="col-span-3">
                            <input v-model="medOcupacionalForm.exameFisico[s.key].obs" class="w-full border-b border-transparent focus:border-blue-300 bg-transparent text-[9px] p-1 font-medium outline-none" placeholder="..." />
                        </div>
                    </div>
                </div>

                <!-- TAB 4: Conclusão -->
                <div v-if="activeMedTab === 4" class="space-y-6">
                    <div class="bg-white p-6 border-2 border-blue-100 rounded-xl shadow-inner text-center">
                        <h4 class="font-black text-slate-700 text-xs uppercase mb-6 tracking-[0.3em]">Resultado Final da Consulta</h4>
                        <div class="flex justify-center gap-4">
                            <label v-for="res in ['Apto', 'Não Apto Temporariamente', 'Apto Condicionalmente', 'Não Apto Definitivamente']" :key="res" 
                                :class="medOcupacionalForm.resultadoFinal === res ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-400'"
                                class="p-4 rounded-lg border cursor-pointer w-48 text-[9px] font-black uppercase text-center">
                                <input type="radio" :value="res" v-model="medOcupacionalForm.resultadoFinal" class="sr-only" />
                                {{ res }}
                            </label>
                        </div>
                    </div>
                </div>

                <!-- TAB 5: Exames -->
                <div v-if="activeMedTab === 5" class="h-full">
                    <div class="bg-white border border-slate-200 rounded h-[60vh] overflow-y-auto">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 sticky top-0">
                                <tr class="text-[8px] font-black text-slate-400 uppercase">
                                    <th class="p-2">Exame / Serviço</th>
                                    <th class="p-2">Estado</th>
                                    <th class="p-2 w-24">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="ex in examesList" :key="ex.id" class="border-b border-slate-50">
                                    <td class="p-2 text-[10px] font-bold">{{ ex.nome }}</td>
                                    <td class="p-2 text-[10px] italic">{{ ex.isRequested ? 'Solicitado' : '---' }}</td>
                                    <td class="p-1">
                                        <button v-if="!ex.isRequested" @click="solicitarExame(ex)" class="bg-blue-600 text-white px-3 py-1 rounded font-black text-[8px] uppercase w-full">Solicitar</button>
                                        <button v-else @click="removerExameSolicitado(ex)" class="text-red-500 font-black text-[8px] uppercase w-full">Remover</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-white border-t border-slate-200 flex justify-between items-center shrink-0 shadow-2xl">
                <button @click="showMedOcupacionalModal = false" class="bg-slate-200 text-slate-600 px-8 py-2.5 rounded font-black uppercase text-[10px]">Sair</button>
                <div class="flex gap-2">
                    <button @click="salvarMedOcupacional" :disabled="medOcupacionalForm.processing" class="bg-blue-600 text-white px-8 py-2.5 rounded font-black uppercase text-[10px] flex items-center gap-2">
                        <Save class="w-4 h-4" /> {{ medOcupacionalForm.processing ? 'Gravando...' : 'Gravar' }}
                    </button>
                    <button @click="finalizarMedOcupacional" class="bg-emerald-600 text-white px-8 py-2.5 rounded font-black uppercase text-[10px] flex items-center gap-2">
                        <CheckCircle class="w-4 h-4" /> Finalizar
                    </button>
                    <button @click="imprimirDadosClinico" class="bg-orange-500 text-white px-8 py-2.5 rounded font-black uppercase text-[10px] flex items-center gap-2">
                        <Printer class="w-4 h-4" /> Imprimir
                    </button>
                </div>
            </div>
        </div>
    </div>
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
@keyframes modalIn {
    from { opacity: 0; transform: translateY(20px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
.animate-modalIn { animation: modalIn 0.3s ease-out forwards; }
.animate-fadeIn { animation: fadeIn 0.4s ease-out forwards; }
</style>

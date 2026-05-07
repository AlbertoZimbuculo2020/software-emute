<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
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
    empresa: Object,
    config: { type: Object, default: () => ({ triageEnabled: true, fontSize: '10px' }) }
});

const waitlist = ref([...props.aguardando]);
const searchTerm = ref('');
const selectedPaciente = ref(null);
const triageData = ref(null);
const patientHistory = ref([]);
const isLoading = ref(false);
const confirmModal = ref({ isOpen: false, title: '', message: '', onConfirm: null });

const activeExamFilter = ref('LABORATORIO'); 
const showLancarResultadosModal = ref(false);
const showDocumentosModal = ref(false);
const searchExameTerm = ref('');
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
    const term = searchExameTerm.value ? searchExameTerm.value.toLowerCase() : '';
    
    // If searching, we search everything in the catalog + requested
    if (term) {
        const requested = examesSolicitados.value.map(e => ({
            id: 'sol_' + e.Id, dbId: e.Id, codigo: e.CodExame, nome: e.Descricao,
            resultado: e.Resultado || '', obs: e.Obs || '', selected: false, isRequested: true,
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
            resultado: e.Resultado || '', obs: e.Obs || '', selected: false, isRequested: true,
            categoria: e.Categoria || '', filhos: e.Filhos || ''
        }));
    } else if (activeExamFilter.value === 'LABORATORIO') {
        result = (props.catalogoExames || []).filter(e => e.Tipo === 'NORMAL' && e.Exame_Fora !== 'True').map(e => ({
            id: 'cat_' + e.Id, dbId: null, codigo: e.Codigo, nome: e.Descricao, resultado: '', selected: false, isRequested: false
        }));
    } else if (activeExamFilter.value === 'RAIOX') {
        result = (props.catalogoExames || []).filter(e => e.Tipo === 'RAIO X').map(e => ({
            id: 'cat_' + e.Id, dbId: null, codigo: e.Codigo, nome: e.Descricao, resultado: '', selected: false, isRequested: false
        }));
    } else if (activeExamFilter.value === 'FORA') {
        result = (props.catalogoExames || []).filter(e => e.Tipo === 'FORA' || e.Exame_Fora === 'True').map(e => ({
            id: 'cat_' + e.Id, dbId: null, codigo: e.Codigo, nome: e.Descricao, resultado: '', selected: false, isRequested: false
        }));
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

const removerItemLocalOuDB = (item, idx) => {
    if (item.fromDB) {
        removerItemReceita(item.id);
    } else {
        removerFarmacoLocal(idx - receitaItens.value.length);
    }
};

const MED_EXAM_TYPES = ['Admissão', 'Periódico', 'Demissão', 'Ocasional', 'Retorno ao Trabalho', 'Mudança de Função'];
const MED_RISKS = ['Ruído', 'Produtos Químicos', 'Stress', 'Radiação', 'Postura Viciosa', 'Chumbo', 'Calor', 'Frio', 'Poeira', 'Vibração', 'Ergonomia inadequada'];
const MED_FAMILIAR = ['Diabetes', 'Hipertensão', 'Doença Cardíaca', 'Câncer'];
const MED_RECOMENDACOES = ['Reduzir Peso', 'Praticar Esporte', 'Usar EPIs', 'Reduzir consumo de tabaco', 'Controlar tensão arterial', 'Consumir alimentos saudáveis', 'Reduzir consumo de bebida alcoólica'];
const MED_ENCAMINHAMENTOS = ['Oftalmologista', 'Otorrinolaringologista', 'Cardiologista'];
const MED_SYSTEMS = [
    { key: 'pele', label: 'Pele e Faneras', alts: ['Micoses', 'Dermatoses'] },
    { key: 'respiratorio', label: 'Respiratório', alts: ['Asma', 'Bronquite', 'D. Rinite', 'Sinusite'] },
    { key: 'cardiovascular', label: 'CardioVascular', alts: ['D. Coronário', 'Ins. Venosa', 'HTA'] },
    { key: 'digestivo', label: 'Digestivo', alts: ['Doença Crónica', 'Doença Biliar'] },
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

// IMPRESSÃO 
const imprimirDadosClinico = () => {
    if (!selectedPaciente.value) return;
    
    let routeName = 'hospitalar.consultorio.imprimir.ficha';
    if (selectedPaciente.value.Consulta === 'MEDICINA OCUPACIONAL') {
        routeName = 'hospitalar.consultorio.imprimir.medicina-ocupacional';
    }

    let url = route(routeName, selectedPaciente.value.Codigo);
    if (isEconomicMode.value) {
        url += '?modo=economico';
        if (!isDuplicate.value) url += '&duplicado=0';
    }
    window.open(url, '_blank');
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
const guiaData = ref({ correspondente: '', motivo: '', exames_realizados: '', analises: '', diagnostico: '', tratamento: '' });

const gerarGuiaTransferencia = () => {
    if (!selectedPaciente.value) return;
    showGuiaModal.value = true;
};

const confirmarGuia = () => {
    let url = route('hospitalar.consultorio.imprimir.guia', selectedPaciente.value.Codigo);
    const params = new URLSearchParams(guiaData.value);
    url += '?' + params.toString();
    
    if (isEconomicMode.value) {
        url += '&modo=economico';
        if (!isDuplicate.value) url += '&duplicado=0';
    }
    window.open(url, '_blank');
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

const selecionarPaciente = async (paciente, readOnly = false) => {
    console.log("Paciente selecionado:", paciente);
    // If it's already selected, don't reload unless force
    if (selectedPaciente.value?.Codigo === paciente.Codigo && !readOnly) return;

    selectedPaciente.value = paciente;
    isLoading.value = true;
    isReadOnly.value = readOnly;
    
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
    tipoPaciente.value = paciente.Seguradora ? 'Assegurado' : 'Particular';

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
    form.situacao = 'Pendente'; // Don't finalize on silent save
    
    try {
        await axios.post(route('hospitalar.consultorio.store'), form.data());
        console.log("Consulta salva silenciosamente...");
    } catch (e) {
        console.warn("Falha no salvamento automático");
    }
};

const salvarConsulta = () => {
    if (!selectedPaciente.value) return;
    if (saveTimeout) clearTimeout(saveTimeout);
    form.hda = hdaNotes.value + '\n|' + selectedCids.value.join('\n');
    form.post(route('hospitalar.consultorio.store'), {
        onSuccess: () => {
            showNotification('Dados gravados com sucesso!');
        }
    });
};

const removerExameSolicitado = async (exame) => {
    if (!confirm(`Deseja remover o exame "${exame.nome}"?`)) return;
    try {
        await axios.post(route('hospitalar.consultorio.remover-exame'), { Id: exame.dbId });
        showNotification('Exame removido com sucesso!');
        selecionarPaciente(selectedPaciente.value);
    } catch (e) {
        showNotification(e.response?.data?.error || 'Erro ao remover exame.', 'error');
    }
};

const confirmarFinalizar = () => {
    confirmModal.value = {
        isOpen: true,
        title: 'Finalizar Consulta',
        message: 'Deseja concluir o atendimento clínico e liberar o paciente?',
        onConfirm: () => {
            confirmModal.value.isOpen = false;
            form.situacao = 'Finalizado';
            salvarConsulta();
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
        <div :style="{ fontSize: currentFontSize }" class="p-1 lg:p-2 bg-slate-100 min-h-screen font-sans">
            <!-- Global Font Size Control (Floating) -->
            <div class="fixed top-20 right-4 z-[60] no-print">
                <div class="bg-white border border-slate-300 shadow-xl rounded-full p-1 flex items-center gap-1">
                    <button @click="changeFontSize('decrement')" class="w-6 h-6 flex items-center justify-center bg-slate-100 hover:bg-slate-200 rounded-full font-bold">-</button>
                    <span class="text-[9px] font-black w-8 text-center">{{ currentFontSize }}</span>
                    <button @click="changeFontSize('increment')" class="w-6 h-6 flex items-center justify-center bg-slate-100 hover:bg-slate-200 rounded-full font-bold">+</button>
                </div>
            </div>
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
                                <tbody class="text-[9px] font-medium">
                                    <tr v-for="p in filteredAguardando" :key="p.Codigo" 
                                        @click="selectedPaciente?.Codigo === p.Codigo ? null : null"
                                        @dblclick="selecionarPaciente(p)"
                                        :class="selectedPaciente?.Codigo === p.Codigo ? 'bg-blue-600 text-white shadow-inner' : 'hover:bg-blue-50 text-slate-600'"
                                        class="cursor-pointer border-b border-slate-100 transition-colors">
                                        <td class="p-1.5 border-r border-slate-100/30">{{ p.Codigo }}</td>
                                        <td class="p-1.5 border-r border-slate-100/30">{{ p.DataAgendamento?.substring(0,10) }}</td>
                                        <td class="p-1.5 truncate font-bold uppercase flex items-center justify-between">
                                            {{ p.PacienteNome }}
                                            <span v-if="p.Situacao === 'Laboratorio'" class="bg-amber-400 text-white px-1 rounded text-[7px] animate-pulse">LAB</span>
                                        </td>
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
                                    <tr v-for="h in patientHistory" :key="h.Id" 
                                        @dblclick="selecionarPaciente(h, true)"
                                        class="border-b border-slate-100 hover:bg-slate-50 cursor-pointer">
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
                        <div class="bg-blue-900 text-white text-center py-1 font-black uppercase tracking-[0.2em] text-[9px]">{{ selectedPaciente?.PacienteNome || 'Selecione um Paciente' }}</div>
                        <div class="p-1.5 grid grid-cols-12 gap-y-1 gap-x-2 bg-slate-50">
                            <!-- Row 1: Code, Name, Sex -->
                            <div class="col-span-2 flex flex-col">
                                <label class="font-black text-slate-400 text-[7px] uppercase">Código</label>
                                <input :value="selectedPaciente?.Codigo" class="border border-slate-200 px-1 py-0.5 bg-white font-bold rounded text-[9px]" readonly />
                            </div>
                            <div class="col-span-7 flex flex-col">
                                <label class="font-black text-slate-400 text-[7px] uppercase">Nome</label>
                                <input :value="selectedPaciente?.PacienteNome" class="border border-slate-200 px-1 py-0.5 bg-white font-bold rounded text-[9px]" readonly />
                            </div>
                            <div class="col-span-3 flex flex-col">
                                <label class="font-black text-slate-400 text-[7px] uppercase">Sexo</label>
                                <input :value="selectedPaciente?.Genero?.toUpperCase()" class="border border-slate-200 px-1 py-0.5 bg-white font-bold rounded text-[9px]" readonly />
                            </div>
                            
                            <!-- Row 2: Birth, Age, Phone -->
                            <div class="col-span-4 flex flex-col">
                                <label class="font-black text-slate-400 text-[7px] uppercase">Nascimento</label>
                                <input :value="selectedPaciente?.DataNascimento" class="border border-slate-200 px-1 py-0.5 bg-white font-bold rounded text-[9px]" readonly />
                            </div>
                            <div class="col-span-3 flex flex-col">
                                <label class="font-black text-slate-400 text-[7px] uppercase">Idade</label>
                                <input :value="calcularIdadeFormatoDesktop(selectedPaciente?.DataNascimento)" class="border border-slate-200 px-1 py-0.5 bg-white font-bold rounded text-[9px]" readonly />
                            </div>
                            <div class="col-span-5 flex flex-col">
                                <label class="font-black text-slate-400 text-[7px] uppercase">Telefone</label>
                                <input :value="selectedPaciente?.Telefone" class="border border-slate-200 px-1 py-0.5 bg-white font-bold rounded text-[9px]" readonly />
                            </div>
                            
                            <!-- Row 3: Address & Type -->
                            <div class="col-span-7 flex flex-col">
                                <label class="font-black text-slate-400 text-[7px] uppercase">Morada</label>
                                <input :value="selectedPaciente?.Rua" class="border border-slate-200 px-1 py-0.5 bg-white font-bold rounded text-[9px]" readonly />
                            </div>
                            <div class="col-span-5 flex flex-col justify-end">
                                <div class="flex gap-2 py-1">
                                    <label class="flex items-center gap-1 font-black text-[8px] cursor-pointer"><input type="radio" v-model="tipoPaciente" value="Particular" :disabled="isReadOnly" /> Particular</label>
                                    <label class="flex items-center gap-1 font-black text-[8px] cursor-pointer"><input type="radio" v-model="tipoPaciente" value="Assegurado" :disabled="isReadOnly" /> Segurado</label>
                                </div>
                            </div>

                            <div v-if="tipoPaciente === 'Assegurado'" class="col-span-12 flex flex-col -mt-1">
                                <label class="font-black text-slate-400 text-[7px] uppercase">Seguradora</label>
                                <input :value="selectedPaciente?.Seguradora?.toUpperCase()" class="border border-slate-200 px-1.5 py-0.5 bg-purple-50 font-black text-purple-700 rounded text-[9px]" readonly />
                            </div>
                        </div>
                    </div>

                    <!-- Triage (Vitals) -->
                    <div class="bg-white border border-slate-300 flex flex-col shadow-sm shrink-0">
                        <div class="bg-blue-600 text-white text-center py-1 font-black uppercase tracking-widest text-[9px]">Dados da Triagem</div>
                        <div v-if="!isReadOnly" class="p-1 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                            <button class="bg-white border border-slate-300 p-0.5 rounded shadow-sm hover:bg-slate-100 transition-colors flex items-center gap-1 px-2">
                                <Save class="w-3 h-3 text-blue-600" /> <span class="font-black text-[8px] uppercase">Gravar Dados da Triagem</span>
                            </button>
                        </div>
                        <div class="bg-white p-1.5 grid grid-cols-3 gap-1.5">
                            <div class="flex flex-col border border-slate-100 rounded p-1 bg-slate-50/30">
                                <label class="font-black text-slate-400 text-[7px] uppercase">Peso</label>
                                <span class="font-black text-blue-900 text-[10px]">{{ triageData?.Peso || '--' }} kg</span>
                            </div>
                            <div class="flex flex-col border border-slate-100 rounded p-1 bg-slate-50/30">
                                <label class="font-black text-slate-400 text-[7px] uppercase">Temp.</label>
                                <span class="font-black text-blue-900 text-[10px]">{{ triageData?.Temperatura || '--' }} °C</span>
                            </div>
                            <div class="flex flex-col border border-slate-100 rounded p-1 bg-slate-50/30">
                                <label class="font-black text-slate-400 text-[7px] uppercase">P. Arterial</label>
                                <span class="font-black text-blue-900 text-[10px]">{{ triageData?.PressaoArterial || '--' }}</span>
                            </div>
                            <div class="flex flex-col border border-slate-100 rounded p-1 bg-slate-50/30">
                                <label class="font-black text-slate-400 text-[7px] uppercase">F. Cardíaca</label>
                                <span class="font-black text-blue-900 text-[10px]">{{ triageData?.FrequenciaCardiaca || '--' }}</span>
                            </div>
                            <div class="flex flex-col border border-slate-100 rounded p-1 bg-slate-50/30">
                                <label class="font-black text-slate-400 text-[7px] uppercase">F. Resp.</label>
                                <span class="font-black text-blue-900 text-[10px]">{{ triageData?.FrequenciaRespiratoria || '--' }}</span>
                            </div>
                            <div class="flex flex-col border border-slate-100 rounded p-1 bg-slate-50/30">
                                <label class="font-black text-slate-400 text-[7px] uppercase">Sat. O2</label>
                                <span class="font-black text-blue-900 text-[10px]">{{ triageData?.SaturacaoOxigenio || '--' }} %</span>
                            </div>
                        </div>
                    </div>

                    <!-- Exams solicitation -->
                    <div class="flex-grow bg-white border border-slate-300 flex flex-col shadow-sm overflow-hidden min-h-[250px]">
                        <div class="bg-blue-600 text-white text-center py-1 font-black uppercase tracking-widest text-[9px]">Solicitar Exames/Serviços</div>
                        <div class="flex p-0.5 gap-0.5 bg-slate-200 shrink-0 border-b border-slate-300">
                            <button v-for="f in ['SOLICITADOS', 'LABORATORIO', 'RAIOX', 'FORA']" :key="f"
                                @click="activeExamFilter = f"
                                :class="activeExamFilter === f ? 'bg-blue-600 text-white shadow-inner' : 'bg-blue-400/50 text-slate-600 hover:bg-blue-500 hover:text-white'"
                                class="flex-1 py-1 font-black uppercase text-[7px] transition-all rounded-sm">
                                {{ f.replace('SOLICITADOS', 'Solicitados').replace('LABORATORIO', 'Laboratório').replace('RAIOX', 'Raio X').replace('FORA', 'Fora') }}
                            </button>
                        </div>
                        <div class="p-1.5 flex gap-1 bg-slate-50 shrink-0 border-b border-slate-200">
                            <input v-model="searchExameTerm" :disabled="isReadOnly" placeholder="Enter text to search..." class="flex-grow border border-slate-300 px-2 py-1 bg-white rounded text-[10px]" />
                            <button :disabled="isReadOnly" class="bg-white border border-slate-300 px-4 py-1 font-bold uppercase text-[9px] hover:bg-slate-100">Find</button>
                        </div>
                        <div class="flex-grow overflow-auto custom-scrollbar">
                            <table class="w-full border-collapse">
                                <thead class="bg-slate-50 sticky top-0 border-b border-slate-200 z-10 font-bold text-slate-500 text-[8px]">
                                    <tr class="text-left uppercase">
                                        <th class="p-1.5 w-8 border-r border-slate-100 text-center"><input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" :disabled="isReadOnly" class="rounded" /></th>
                                        <th class="p-1.5 border-r border-slate-100">Exame</th>
                                        <th class="p-1.5 text-right">Resultado</th>
                                    </tr>
                                </thead>
                                <tbody class="text-slate-600 font-medium">
                                    <tr v-for="ex in examesList" :key="ex.id" :class="ex.isRequested ? 'bg-emerald-50/50' : ''" class="border-b border-slate-50 hover:bg-blue-50 transition-colors group">
                                        <td class="p-1.5 border-r border-slate-100/50 text-center">
                                            <input v-if="!ex.isRequested" type="checkbox" :value="ex.id" v-model="selectedExams" :disabled="isReadOnly" class="rounded" />
                                            <CheckCircle v-else class="w-3.5 h-3.5 text-emerald-500 mx-auto" />
                                        </td>
                                        <td class="p-1.5 border-r border-slate-100/50 font-bold uppercase flex justify-between items-center">
                                            <div class="flex flex-col">
                                                <span>{{ ex.nome }}</span>
                                                <span v-if="ex.isRequested" class="text-[7px] text-emerald-600 font-black">SOLICITADO</span>
                                            </div>
                                            <button v-if="ex.isRequested && !isReadOnly" @click="removerExameSolicitado(ex)" class="text-red-500 opacity-0 group-hover:opacity-100"><Trash2 class="w-3 h-3"/></button>
                                        </td>
                                        <td class="p-1.5 text-right text-blue-500 italic">{{ ex.resultado || '---' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-1 grid grid-cols-2 gap-1 bg-slate-100 shrink-0 border-t border-slate-300">
                            <button @click="enviarExamesAoLaboratorio" :disabled="isReadOnly" class="bg-blue-600 text-white py-2 font-black uppercase flex items-center justify-center gap-2 hover:bg-blue-700 text-[9px] shadow-sm disabled:opacity-50">
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
                                <textarea v-model="form.qp" :disabled="isReadOnly" class="w-full border border-slate-200 p-1.5 h-12 resize-none bg-white font-bold text-slate-700 outline-none focus:border-blue-500 disabled:bg-slate-100" placeholder="..."></textarea>
                            </div>
                            <!-- HDA -->
                            <div class="flex flex-col">
                                <label class="font-black text-slate-400 text-[8px] uppercase tracking-widest ml-1">Histórico da doença atual</label>
                                <textarea v-model="hdaNotes" :disabled="isReadOnly" class="w-full border border-slate-200 p-1.5 h-16 resize-none bg-white font-bold text-slate-700 outline-none focus:border-blue-500 disabled:bg-slate-100" placeholder="..."></textarea>
                            </div>
                            <!-- OBJ -->
                            <div class="flex flex-col">
                                <label class="font-black text-slate-400 text-[8px] uppercase tracking-widest ml-1">Exames Objectivos</label>
                                <textarea v-model="form.obj" :disabled="isReadOnly" class="w-full border border-slate-200 p-1.5 h-12 resize-none bg-white font-bold text-slate-700 outline-none focus:border-blue-500 disabled:bg-slate-100" placeholder="..."></textarea>
                            </div>
                            
                            <!-- Diagnostic Row: CID + Add CID UI -->
                            <div class="grid grid-cols-2 gap-2">
                                <div class="flex flex-col">
                                    <label class="font-black text-slate-400 text-[8px] uppercase tracking-widest ml-1">Hipótese de Diagnóstico</label>
                                    <div class="border border-slate-200 bg-white h-24 overflow-y-auto p-1 font-black text-slate-600 text-[9px] shadow-inner disabled:bg-slate-100">
                                        <div v-for="(cid, idx) in selectedCids" :key="idx" class="flex justify-between items-center hover:bg-red-50 p-0.5 group">
                                            <span class="truncate">{{ cid }}</span>
                                            <button v-if="!isReadOnly" @click="removeCid(idx)" class="text-red-500 opacity-0 group-hover:opacity-100"><X class="w-3 h-3"/></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="font-black text-slate-400 text-[8px] uppercase tracking-widest ml-1">Adicionar CidDez</label>
                                    <div class="relative">
                                        <input v-model="searchCidTerm" :disabled="isReadOnly" class="w-full border border-slate-200 px-1 py-1 bg-white font-bold text-[10px] disabled:bg-slate-100" placeholder="Pesquisar..." />
                                        <div v-if="filteredCidCatalog.length > 0 && !isReadOnly" class="absolute top-full left-0 right-0 z-50 bg-white border border-slate-200 shadow-xl max-h-32 overflow-auto">
                                            <div v-for="c in filteredCidCatalog" :key="c.codigo" @click="addCid(c)" class="p-1 hover:bg-blue-600 hover:text-white cursor-pointer border-b border-slate-50 text-[8px]">
                                                <span class="font-black">{{ c.Indicador }}</span> - {{ c.Descricao }}
                                            </div>
                                        </div>
                                    </div>
                                    <button @click="adicionarCidDaPesquisa" :disabled="isReadOnly" class="bg-blue-600 text-white py-1 font-black flex items-center justify-center gap-1 text-[8px] uppercase rounded shadow-sm hover:bg-blue-700 disabled:opacity-50">
                                        <Plus class="w-3 h-3" /> Adicionar
                                    </button>
                                    <button :disabled="isReadOnly" class="bg-blue-500 text-white py-1 font-black text-[8px] uppercase rounded shadow-sm disabled:opacity-50">Cadastrar Novo</button>
                                </div>
                            </div>

                            <!-- Observations -->
                            <div class="flex flex-col">
                                <label class="font-black text-slate-400 text-[8px] uppercase tracking-widest ml-1">Observações</label>
                                <textarea v-model="form.recomendacoes" :disabled="isReadOnly" class="w-full border border-slate-200 p-1.5 h-12 resize-none bg-white font-bold text-slate-700 outline-none focus:border-blue-500 disabled:bg-slate-100" placeholder="..."></textarea>
                            </div>

                            <div class="flex gap-1 pt-1">
                                <button @click="salvarConsulta" :disabled="isReadOnly" class="flex-grow bg-blue-600 text-white py-2 font-black uppercase text-[9px] tracking-widest shadow-md hover:bg-blue-700 transition-all flex items-center justify-center gap-2 rounded disabled:opacity-50">
                                    <Save class="w-3.5 h-3.5" /> GRAVAR DADOS
                                </button>
                                <button @click="imprimirDadosClinico" class="bg-orange-500 text-white px-4 rounded font-black hover:bg-orange-600 shadow-md flex items-center justify-center">
                                    <Printer class="w-3.5 h-3.5" />
                                </button>
                                <!-- MINI TOGGLE ECONOMICO -->
                                <div class="flex flex-col gap-0.5">
                                    <button @click="isEconomicMode = !isEconomicMode" :class="isEconomicMode ? 'bg-emerald-500' : 'bg-slate-400'" class="px-2 py-0.5 rounded text-[7px] font-black text-white uppercase transition-all shadow-md">
                                        {{ isEconomicMode ? 'Eco ON' : 'Eco OFF' }}
                                    </button>
                                    <label v-if="isEconomicMode" class="flex items-center gap-1 cursor-pointer">
                                        <input type="checkbox" v-model="isDuplicate" class="w-2.5 h-2.5" />
                                        <span class="text-[7px] font-bold text-slate-500 uppercase">2x</span>
                                    </label>
                                </div>
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
                                    <tr v-for="(item, idx) in todosItensReceita" :key="item.id" class="border-b border-slate-50 hover:bg-amber-50/50 transition-colors uppercase group">
                                        <td class="p-1.5 border-r border-slate-100 flex items-center gap-1">
                                            <ArrowRightLeft class="w-2.5 h-2.5 text-blue-400" /> {{ item.farmaco }}
                                        </td>
                                        <td class="p-1.5 border-r border-slate-100">{{ item.dosagem }}</td>
                                        <td class="p-1.5 flex justify-between items-center">
                                            {{ item.dias }}
                                            <button @click="removerItemLocalOuDB(item, idx)" class="text-red-500 opacity-0 group-hover:opacity-100"><Trash2 class="w-2.5 h-2.5"/></button>
                                        </td>
                                    </tr>
                                    <tr class="bg-slate-50/50">
                                        <td class="p-1 border-r border-slate-100">
                                            <div class="flex items-center gap-1">
                                                <Plus @click="adicionarFarmacoLocal" class="w-3.5 h-3.5 text-emerald-500 cursor-pointer" />
                                                <input v-model="novoFarmaco.farmaco" list="farmacos-list" 
                                                    @keydown.enter="adicionarFarmacoLocal"
                                                    class="w-full bg-transparent border-none outline-none p-0 font-black text-blue-900 placeholder-slate-300" placeholder="+" />
                                                <datalist id="farmacos-list">
                                                    <option v-for="f in props.catalogoFarmacos" :key="f.Id" :value="f.Descricao" />
                                                </datalist>
                                            </div>
                                        </td>
                                        <td class="p-1 border-r border-slate-100">
                                            <input v-model="novoFarmaco.dosagem" @keydown.enter="adicionarFarmacoLocal" class="w-full bg-transparent border-none outline-none p-0" placeholder="Posologia" />
                                        </td>
                                        <td class="p-1">
                                            <input v-model="novoFarmaco.dias" type="number" @keydown.enter="adicionarFarmacoLocal" class="w-full bg-transparent border-none outline-none p-0" placeholder="Qtd" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Final Action Buttons (2x2 Grid at bottom right) -->
                    <div class="grid grid-cols-2 gap-1 mt-auto pt-1 pb-1 shrink-0">
                        <button @click="confirmarInternamento" class="bg-blue-600 text-white py-3 rounded font-black uppercase text-[9px] flex items-center justify-center gap-2 hover:bg-blue-700 shadow-sm">
                            <Activity class="w-4 h-4" /> INTERNAMENTO
                        </button>
                        <button @click="showDocumentosModal = true" class="bg-slate-800 text-white py-3 rounded font-black uppercase text-[9px] flex items-center justify-center gap-2 hover:bg-slate-900 shadow-sm">
                            <FileText class="w-4 h-4" /> DOCUMENTOS
                        </button>
                        <button @click="confirmarFinalizar" class="bg-emerald-500 text-white py-3 rounded font-black uppercase text-[9px] flex items-center justify-center gap-2 hover:bg-emerald-600 shadow-sm">
                            <CheckCircle class="w-4 h-4" /> FINALIZAR
                        </button>
                        <button @click="showEncaminharModal = true" class="bg-blue-600 text-white py-3 rounded font-black uppercase text-[9px] flex items-center justify-center gap-2 hover:bg-blue-700 shadow-sm">
                            <ArrowRightLeft class="w-4 h-4" /> ENCAMINHAR
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
                <div class="p-4 grid grid-cols-1 gap-2">
                    <button @click="imprimirDadosClinico(); showDocumentosModal = false" class="w-full text-left p-3 border border-slate-200 hover:bg-blue-50 rounded flex items-center gap-3 transition-all group">
                        <FileText class="w-5 h-5 text-blue-600" />
                        <div class="flex flex-col">
                            <span class="font-black text-slate-700 text-[11px] uppercase">Ficha Médica Geral</span>
                            <span class="text-[9px] text-slate-400">Relatório completo da consulta atual</span>
                        </div>
                    </button>
                    <button @click="imprimirDadosClinico(); showDocumentosModal = false" class="w-full text-left p-3 border border-slate-200 hover:bg-blue-50 rounded flex items-center gap-3 transition-all group">
                        <FileText class="w-5 h-5 text-slate-400" />
                        <div class="flex flex-col">
                            <span class="font-black text-slate-700 text-[11px] uppercase">Ficha Médica (Rascunho)</span>
                            <span class="text-[9px] text-slate-400">Visualizar dados sem finalizar a consulta</span>
                        </div>
                    </button>
                    <button @click="gerarJustificativo(); showDocumentosModal = false" class="w-full text-left p-3 border border-slate-200 hover:bg-blue-50 rounded flex items-center gap-3 transition-all group">
                        <FileText class="w-5 h-5 text-orange-600" />
                        <div class="flex flex-col">
                            <span class="font-black text-slate-700 text-[11px] uppercase">Justificativo Médico</span>
                            <span class="text-[9px] text-slate-400">Declaração de presença e incapacidade</span>
                        </div>
                    </button>
                    <button @click="gerarGuiaTransferencia(); showDocumentosModal = false" class="w-full text-left p-3 border border-slate-200 hover:bg-blue-50 rounded flex items-center gap-3 transition-all group">
                        <ArrowRightLeft class="w-5 h-5 text-emerald-600" />
                        <div class="flex flex-col">
                            <span class="font-black text-slate-700 text-[11px] uppercase">Guia de Transferência</span>
                            <span class="text-[9px] text-slate-400">Transferência para outra unidade de saúde</span>
                        </div>
                    </button>
                </div>
                <div class="p-3 bg-slate-50 border-t border-slate-200 text-right">
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
                            <div class="flex flex-col">
                                <span class="font-black text-slate-700 text-[11px] uppercase">Ficha Médica Geral</span>
                                <span class="text-[9px] text-slate-400">Relatório completo da consulta atual</span>
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
            <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl border border-slate-300 overflow-hidden">
                <div class="bg-gradient-to-r from-emerald-600 to-emerald-500 text-white p-4 font-black uppercase text-[10px] flex justify-between items-center">
                    <span>Preencher Guia de Transferência</span>
                    <button @click="showGuiaModal = false"><X class="w-4 h-4"/></button>
                </div>
                <div class="p-6 space-y-3 max-h-[80vh] overflow-y-auto custom-scrollbar">
                    <div class="flex flex-col gap-1">
                        <label class="text-[9px] font-black text-slate-400 uppercase">Unidade de Destino</label>
                        <input v-model="guiaData.correspondente" class="w-full border border-slate-300 rounded p-2 text-xs font-bold" placeholder="Nome do Hospital/Clínica" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[9px] font-black text-slate-400 uppercase">Motivo da Transferência</label>
                        <textarea v-model="guiaData.motivo" class="w-full border border-slate-300 rounded p-2 text-xs font-bold h-20 resize-none"></textarea>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[9px] font-black text-slate-400 uppercase">Diagnóstico / Hipótese</label>
                        <textarea v-model="guiaData.diagnostico" class="w-full border border-slate-300 rounded p-2 text-xs font-bold h-16 resize-none"></textarea>
                    </div>
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
                    <div class="flex flex-col gap-1">
                        <label class="text-[9px] font-black text-slate-400 uppercase">Tratamento Efetuado</label>
                        <textarea v-model="guiaData.tratamento" class="w-full border border-slate-300 rounded p-2 text-xs font-bold h-20 resize-none"></textarea>
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

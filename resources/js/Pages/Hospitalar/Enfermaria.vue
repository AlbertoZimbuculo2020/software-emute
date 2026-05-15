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
    <Head title="Enfermaria Hospitalar" />

    <DashboardLayout>
        <!-- Toast Notification -->
        <Transition enter-active-class="duration-300 ease-out" enter-from-class="translate-y-4 opacity-0" enter-to-class="translate-y-0 opacity-100" leave-active-class="duration-200 ease-in" leave-to-class="translate-y-4 opacity-0">
            <div v-if="resultadoText === 'SUCCESS_DUMMY'" class="fixed bottom-10 right-10 z-[1000] bg-white px-6 py-4 rounded shadow-2xl border-l-4 border-emerald-500 flex items-center gap-4 min-w-[300px]">
                <Check class="w-5 h-5 text-emerald-500" />
                <span class="text-[11px] font-bold text-slate-800 uppercase">Operação realizada com sucesso</span>
            </div>
        </Transition>

        <div class="h-[calc(100vh-64px)] flex flex-col bg-slate-100 text-[11px] text-slate-800 overflow-hidden font-sans relative">
            
            <!-- Segmented Top Action Bar -->
            <div class="flex items-center gap-2 p-2 bg-white border-b border-slate-200 shrink-0 h-[72px] shadow-sm z-10 justify-between">
                <div class="flex flex-col mx-2 justify-center h-full">
                    <div class="text-blue-600 font-black text-[9px] uppercase mb-1.5 tracking-widest flex items-center gap-1.5"><Activity class="w-3 h-3" /> Paciente em Atendimento</div>
                    <div class="flex items-center gap-4">
                        <span class="font-black text-xs uppercase tracking-tighter text-slate-800">{{ selectedSolicitacao?.PacienteNome || 'Nenhum paciente selecionado' }}</span>
                        <span v-if="selectedSolicitacao" class="bg-blue-100 text-blue-600 px-2 py-0.5 rounded text-[9px] font-black tracking-widest uppercase">{{ selectedSolicitacao.Codigo }}</span>
                    </div>
                </div>

                <div class="flex gap-4 pr-4 h-full items-center">
                    <div v-if="selectedSolicitacao" class="flex flex-col border-r border-slate-200 pr-4">
                        <label class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Depósito / Armazém</label>
                        <select v-model="selectedDeposito" class="bg-slate-50 border border-slate-200 rounded px-2 py-1 text-[9px] font-black uppercase text-slate-600 focus:border-blue-500 outline-none w-48">
                            <option v-for="d in depositos" :key="d.CODIGO" :value="d.CODIGO">{{ d.DEPOSITO }}</option>
                        </select>
                    </div>

                    <button @click="openFarmacoModal" :disabled="!selectedSolicitacao" class="bg-slate-100 text-slate-700 px-4 py-2 font-black uppercase text-[10px] tracking-widest hover:bg-slate-200 transition-all rounded shadow-sm flex items-center gap-2 disabled:opacity-50">
                        <Plus class="w-3.5 h-3.5" /> Adicionar Fármacos
                    </button>

                    <button @click="finalizarAtendimento" :disabled="!selectedSolicitacao" class="bg-emerald-600 text-white px-6 py-2 font-black uppercase text-[10px] tracking-widest hover:bg-emerald-700 transition-all rounded shadow-sm flex items-center gap-2 disabled:opacity-50">
                        <CheckCircle class="w-3.5 h-3.5" /> Finalizar Atendimento
                    </button>
                </div>
            </div>

            <!-- Dashboard Sub-Header (Blue Bar) -->
            <div class="bg-slate-800 text-slate-300 h-8 flex items-center px-4 justify-between shrink-0 shadow-sm z-10 text-[10px]">
                <div class="flex items-center gap-4">
                    <span class="font-bold flex items-center gap-2"><Clock class="w-3 h-3 text-blue-400" /> Data: <span class="text-white">{{ new Date().toLocaleDateString('pt-PT') }}</span></span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="font-bold">Aguardando: <span class="text-emerald-400 font-black">{{ solicitacoes.length }} Serviços Pendentes</span></span>
                    <div class="w-px h-3 bg-slate-600"></div>
                    <span class="font-bold">Módulo: <span class="text-blue-400">Enfermaria</span></span>
                </div>
            </div>

            <!-- Main Layout -->
            <div class="flex-1 flex overflow-hidden p-2 gap-2 relative z-0">
                
                <!-- LEFT COLUMN: SOLICITAÇÕES -->
                <div class="w-1/3 flex flex-col bg-white rounded shadow-sm border border-slate-300 overflow-hidden shrink-0">
                    <div class="bg-slate-100/50 p-2 border-b border-slate-200 flex items-center justify-between shrink-0">
                        <span class="font-black text-[10px] uppercase text-slate-500 tracking-widest">Serviços Solicitados</span>
                        <input v-model="searchTerm" placeholder="Pesquisar paciente..." class="border border-slate-200 rounded px-2 py-1 text-[9px] uppercase focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none w-40" />
                    </div>
                    
                    <div class="flex-1 overflow-auto bg-slate-50 relative">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-slate-200 sticky top-0 z-10 shadow-sm">
                                <tr class="text-[9px] font-black text-slate-500 uppercase tracking-widest">
                                    <th class="p-2 border-r border-slate-300">Agenda</th>
                                    <th class="p-2 border-r border-slate-300">Paciente</th>
                                    <th class="p-2 text-center w-10">...</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="s in filteredSolicitacoes" :key="s.Id" @click="selecionarSolicitacao(s)" 
                                    class="border-b border-slate-200 cursor-pointer hover:bg-blue-50 transition-colors"
                                    :class="selectedSolicitacao?.Codigo === s.Codigo ? 'bg-blue-100/80 border-l-4 border-l-blue-500' : 'bg-white border-l-4 border-l-transparent'">
                                    <td class="p-2 border-r border-slate-50 font-bold text-slate-700">
                                        {{ s.Codigo }}
                                        <div v-if="s.Situacao === 'Internado'" class="mt-1"><span class="bg-amber-100 text-amber-600 px-1 py-0.5 rounded text-[7px] font-black uppercase">Internado</span></div>
                                    </td>
                                    <td class="p-2 border-r border-slate-50">
                                        <div class="font-bold uppercase text-[10px] text-slate-800">{{ s.PacienteNome }}</div>
                                        <div class="text-[8px] text-slate-500 font-bold uppercase mt-0.5">Médico: {{ s.MedicoNome || 'N/D' }}</div>
                                        <div class="text-[8px] text-blue-500 font-black mt-0.5">{{ s.QTD_SERVICOS }} Serviços</div>
                                    </td>
                                    <td class="p-2 text-center text-blue-600"><ChevronRight class="w-4 h-4 mx-auto" /></td>
                                </tr>
                                <tr v-if="filteredSolicitacoes.length === 0">
                                    <td colspan="3" class="p-8 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">Nenhuma solicitação pendente</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- RIGHT COLUMN: DETALHES E WORKFLOW -->
                <div v-if="selectedSolicitacao" class="flex-1 flex flex-col gap-2 overflow-hidden">
                    
                    <!-- Exames & Fármacos Grid -->
                    <div class="h-1/2 flex gap-2">
                        <!-- Exames Solicitados -->
                        <div class="flex-1 flex flex-col bg-white rounded shadow-sm border border-slate-300 overflow-hidden">
                            <div class="bg-slate-100/50 p-2 border-b border-slate-200 flex items-center justify-between shrink-0">
                                <span class="font-black text-[10px] uppercase text-slate-500 tracking-widest flex items-center gap-1.5"><FlaskConical class="w-3 h-3" /> Itens Solicitados</span>
                            </div>
                            <div class="flex-1 overflow-auto bg-slate-50">
                                <table class="w-full text-left border-collapse">
                                    <tbody>
                                        <tr v-for="e in details.exames" :key="e.Id" @click="selecionarExame(e)"
                                            class="border-b border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors"
                                            :class="selectedExame?.Id === e.Id ? 'bg-slate-800 text-white border-l-4 border-l-slate-400' : 'bg-white border-l-4 border-l-transparent'">
                                            <td class="p-2 border-r border-slate-50 w-8 text-center">
                                                <CheckCircle v-if="e.Estado === 'Finalizado'" class="w-3.5 h-3.5 text-emerald-500 mx-auto" />
                                                <Activity v-else class="w-3.5 h-3.5 text-amber-500 mx-auto" :class="selectedExame?.Id !== e.Id ? 'opacity-50' : ''" />
                                            </td>
                                            <td class="p-2">
                                                <div class="font-black uppercase text-[10px]">{{ e.Descricao }}</div>
                                                <div class="text-[8px] font-bold uppercase mt-0.5" :class="selectedExame?.Id === e.Id ? 'text-slate-300' : 'text-slate-400'">{{ e.Categoria || 'Geral' }}</div>
                                            </td>
                                        </tr>
                                        <tr v-if="details.exames?.length === 0">
                                            <td colspan="2" class="p-8 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">Nenhum exame solicitado</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Fármacos Prescritos -->
                        <div class="flex-1 flex flex-col bg-white rounded shadow-sm border border-slate-300 overflow-hidden">
                            <div class="bg-slate-100/50 p-2 border-b border-slate-200 flex items-center justify-between shrink-0">
                                <span class="font-black text-[10px] uppercase text-slate-500 tracking-widest flex items-center gap-1.5"><Pill class="w-3 h-3 text-amber-500" /> Fármacos Registados</span>
                            </div>
                            <div class="flex-1 overflow-auto bg-slate-50">
                                <table class="w-full text-left border-collapse">
                                    <tbody>
                                        <tr v-for="p in details.prescricoes" :key="p.Id" class="border-b border-slate-200 bg-white">
                                            <td class="p-2 border-r border-slate-50 w-8 text-center">
                                                <CheckCircle class="w-3.5 h-3.5 text-emerald-500 mx-auto" />
                                            </td>
                                            <td class="p-2">
                                                <div class="font-black uppercase text-[10px] text-slate-700">{{ p.Farmaco }}</div>
                                                <div class="text-[8px] font-bold uppercase mt-0.5 text-slate-500">{{ p.Dosagem }} | {{ p.Dias }}</div>
                                            </td>
                                        </tr>
                                        <tr v-if="details.prescricoes?.length === 0">
                                            <td colspan="2" class="p-8 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">Sem fármacos</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Input Resultado -->
                    <div class="h-1/2 flex flex-col bg-white rounded shadow-sm border border-slate-300 overflow-hidden">
                        <div class="bg-slate-100/50 p-2 border-b border-slate-200 flex items-center justify-between shrink-0">
                            <span class="font-black text-[10px] uppercase text-slate-500 tracking-widest flex items-center gap-1.5"><FileText class="w-3 h-3 text-blue-500" /> Registo de Resultados / Observações</span>
                            <span v-if="selectedExame" class="text-[9px] font-black text-blue-600 uppercase">{{ selectedExame.Descricao }}</span>
                        </div>
                        
                        <div class="flex-1 flex flex-col relative p-2 bg-slate-50">
                            <textarea v-if="selectedExame" v-model="resultadoText" 
                                class="flex-1 border border-slate-200 rounded px-3 py-2 text-[11px] uppercase focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none w-full resize-none font-medium text-slate-700 bg-white"
                                placeholder="Digite aqui o resultado ou observações do procedimento selecionado..."></textarea>
                            
                            <div v-else class="flex-1 flex items-center justify-center text-[10px] font-bold text-slate-400 uppercase tracking-widest border border-dashed border-slate-300 rounded">
                                Selecione um item solicitado para registar resultado
                            </div>
                        </div>

                        <div class="p-2 bg-white border-t border-slate-200 flex justify-end shrink-0">
                            <button @click="salvarResultado" :disabled="!selectedExame" class="bg-slate-800 text-white px-6 py-2 font-black uppercase text-[9px] tracking-widest hover:bg-slate-900 transition-all rounded shadow-sm flex items-center gap-2 disabled:opacity-50">
                                <Save class="w-3.5 h-3.5" /> Gravar Resultado
                            </button>
                        </div>
                    </div>

                </div>

                <!-- Empty State -->
                <div v-else class="flex-1 flex flex-col items-center justify-center bg-white rounded shadow-sm border border-slate-300 p-12 text-center relative overflow-hidden">
                    <ClipboardList class="w-16 h-16 text-slate-200 mb-4" />
                    <h2 class="text-base font-black text-slate-400 uppercase tracking-[0.2em]">Enfermaria</h2>
                    <p class="text-[10px] font-bold text-slate-400 mt-2 uppercase tracking-widest">Selecione uma solicitação na lista para iniciar</p>
                </div>

            </div>
        </div>

        <!-- Modal: Adicionar Fármaco (Refined) -->
        <Transition enter-active-class="duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="duration-150 ease-in" leave-to-class="opacity-0">
            <div v-if="isFarmacoModalOpen" class="fixed inset-0 z-[2000] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white rounded shadow-2xl w-full max-w-2xl border border-slate-200 flex flex-col max-h-[85vh]">
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-2"><Pill class="w-4 h-4 text-blue-600" /> Administrar Fármaco</h2>
                        <button @click="isFarmacoModalOpen = false" class="text-slate-400 hover:text-slate-600 transition-all">
                            <Plus class="w-5 h-5 rotate-45" />
                        </button>
                    </div>

                    <div class="p-4 overflow-y-auto space-y-4 custom-scrollbar bg-white">
                        <div>
                            <input v-model="farmacoSearch" placeholder="Pesquisar fármaco..." class="w-full border border-slate-200 rounded px-3 py-2 text-[10px] font-bold uppercase focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all bg-slate-50" />
                        </div>

                        <div class="grid grid-cols-1 gap-1 max-h-[250px] overflow-y-auto custom-scrollbar border border-slate-200 rounded p-1 bg-slate-50">
                            <div v-for="f in filteredFarmacos" :key="f.ID_ARTIGO" 
                                 @click="selecionarFarmaco(f)"
                                 :class="farmacoForm.IdProduto === f.ID_ARTIGO ? 'bg-blue-600 text-white' : 'bg-white hover:bg-blue-50 text-slate-700 border border-slate-100'"
                                 class="p-2 rounded cursor-pointer transition-all flex items-center justify-between shadow-sm">
                                <span class="text-[9px] font-black uppercase">{{ f.DESCRICAO }}</span>
                                <span class="text-[8px] font-bold opacity-80 uppercase">{{ f.PV }} KZ</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex flex-col">
                                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Dosagem / Posologia</label>
                                <input v-model="farmacoForm.Dosagem" placeholder="Ex: 1 comp de 8/8h" class="border border-slate-200 rounded px-2 py-1.5 text-[10px] font-black uppercase focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none bg-slate-50" />
                            </div>
                            <div class="flex flex-col">
                                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Tempo / Dias</label>
                                <input v-model="farmacoForm.Dias" placeholder="Ex: 5 Dias" class="border border-slate-200 rounded px-2 py-1.5 text-[10px] font-black uppercase focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none bg-slate-50" />
                            </div>
                        </div>
                    </div>

                    <div class="p-3 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
                        <button @click="isFarmacoModalOpen = false" class="px-4 py-1.5 bg-slate-200 text-slate-600 rounded text-[9px] font-black uppercase tracking-widest hover:bg-slate-300 transition-all">Cancelar</button>
                        <button @click="adicionarFarmaco" :disabled="!farmacoForm.IdProduto" class="bg-blue-600 text-white px-6 py-1.5 rounded text-[9px] font-black uppercase tracking-widest hover:bg-blue-700 transition-all disabled:opacity-50">Confirmar</button>
                    </div>
                </div>
            </div>
        </Transition>
    </DashboardLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>

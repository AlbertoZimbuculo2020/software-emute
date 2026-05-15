<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import { 
    Search, User, Calendar, Clock, Activity, 
    FileText, Check, X, FlaskConical, Stethoscope,
    ChevronRight, Save, Printer, Trash2, Plus, 
    Microscope, Radiation, History, Package, CheckCircle
} from 'lucide-vue-next';
import debounce from 'lodash/debounce';

const props = defineProps({
    aguardando: Array,
    depositos: Array,
    materiais: Array
});

const activePatient = ref(null);
const activeExames = ref([]);
const historico = ref([]);
const materiaisUsados = ref([]);
const loading = ref(false);
const saving = ref({});
const notification = ref({ show: false, message: '', type: 'success' });
const activeTab = ref('exames'); // 'exames', 'historico', 'materiais'

const showNotification = (message, type = 'success') => {
    notification.value = { show: true, message, type };
    setTimeout(() => notification.value.show = false, 4000);
};

const selectPatient = async (patient) => {
    loading.value = true;
    try {
        const response = await axios.get(route('hospitalar.laboratorio.details', patient.Codigo));
        activePatient.value = response.data.paciente;
        activeExames.value = response.data.exames.map(ex => {
            // Se tiver filhos, inicializar o objeto de resultados
            let resultados = {};
            if (ex.MetaFilhos) {
                const campos = ex.MetaFilhos.split('|');
                const valoresAtuais = ex.Resultado ? ex.Resultado.split('|') : [];
                campos.forEach((campo, idx) => {
                    if (campo.trim()) {
                        resultados[campo.trim()] = valoresAtuais[idx] ? valoresAtuais[idx].trim() : '';
                    }
                });
            }
            return { ...ex, resultadosForm: resultados };
        });
        historico.value = response.data.historico;
        materiaisUsados.value = response.data.materiaisUsados;
        activeTab.value = 'exames';
    } catch (error) {
        showNotification('Erro ao carregar detalhes do paciente', 'error');
    } finally {
        loading.value = false;
    }
};

const saveExameResult = async (exame) => {
    saving.value[exame.Id] = true;
    try {
        let finalResult = exame.Resultado;
        
        if (exame.MetaFilhos) {
            // Concatenar resultados dos campos filhos
            const campos = exame.MetaFilhos.split('|').filter(c => c.trim());
            finalResult = campos.map(c => exame.resultadosForm[c.trim()] || '').join(' | ');
        }

        await axios.post(route('hospitalar.laboratorio.resultado'), {
            idExame: exame.Id,
            resultado: finalResult,
            obs: exame.Obs
        });
        
        exame.Estado = 'Finalizado';
        exame.Resultado = finalResult;
        showNotification(`${exame.Descricao} gravado com sucesso!`);
    } catch (error) {
        showNotification('Erro ao salvar resultado', 'error');
    } finally {
        saving.value[exame.Id] = false;
    }
};

const finalizeAtendimento = () => {
    if (!activePatient.value) return;
    
    router.post(route('hospitalar.laboratorio.finalizar', activePatient.value.Codigo), {}, {
        onSuccess: () => {
            activePatient.value = null;
            showNotification('Atendimento laboratorial finalizado!');
        },
        onError: (errors) => {
            showNotification(errors.error || 'Erro ao finalizar atendimento', 'error');
        }
    });
};

const printResult = () => {
    if (!activePatient.value) return;
    window.open(route('hospitalar.laboratorio.imprimir', activePatient.value.Codigo), '_blank');
};

// Polling para novos pacientes
onMounted(() => {
    setInterval(() => {
        router.reload({ only: ['aguardando'], preserveState: true });
    }, 30000);
});

// Helpers para UI
const getStatusColor = (situacao) => {
    switch (situacao) {
        case 'Laboratorio': return 'bg-blue-500';
        case 'RAIO X': return 'bg-purple-500';
        case 'Internado': return 'bg-orange-500';
        default: return 'bg-gray-500';
    }
};

const calculateAge = (date) => {
    if (!date) return '';
    const birth = new Date(date);
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
    return age;
};

// Gerenciamento de Materiais
const showMaterialModal = ref(false);
const materialForm = useForm({
    idAgenda: '',
    idPaciente: '',
    produto: '',
    descricao: '',
    quantidade: 1,
    preco: 0
});

const addMaterial = (material) => {
    materialForm.idAgenda = activePatient.value.Codigo;
    materialForm.idPaciente = activePatient.value.IdPaciente;
    materialForm.produto = material.CODIGO;
    materialForm.descricao = material.DESCRICAO;
    materialForm.preco = material.PV;
    
    materialForm.post(route('hospitalar.laboratorio.material.store'), {
        onSuccess: () => {
            showMaterialModal.value = false;
            // Recarregar detalhes para atualizar lista
            selectPatient(activePatient.value);
        }
    });
};

const removeMaterial = (id) => {
    router.delete(route('hospitalar.laboratorio.material.destroy', id), {
        onSuccess: () => selectPatient(activePatient.value)
    });
};

</script>

<template>
    <Head title="Laboratório & Diagnóstico" />

    <DashboardLayout>
        <!-- Toast Notification -->
        <Transition enter-active-class="duration-300 ease-out" enter-from-class="translate-y-4 opacity-0" enter-to-class="translate-y-0 opacity-100" leave-active-class="duration-200 ease-in" leave-to-class="translate-y-4 opacity-0">
            <div v-if="notification.show" class="fixed bottom-10 right-10 z-[1000] bg-white px-6 py-4 rounded shadow-2xl border-l-4 flex items-center gap-4 min-w-[300px]" :class="notification.type === 'success' ? 'border-emerald-500' : 'border-red-500'">
                <Check v-if="notification.type === 'success'" class="w-5 h-5 text-emerald-500" />
                <X v-else class="w-5 h-5 text-red-500" />
                <span class="text-[11px] font-bold text-slate-800 uppercase">{{ notification.message }}</span>
            </div>
        </Transition>

        <div class="h-[calc(100vh-64px)] flex flex-col bg-slate-100 text-[11px] text-slate-800 overflow-hidden font-sans relative">
            
            <!-- Segmented Top Action Bar -->
            <div class="flex items-center gap-2 p-2 bg-white border-b border-slate-200 shrink-0 h-[72px] shadow-sm z-10 justify-between">
                <div class="flex flex-col mx-2 justify-center h-full">
                    <div class="text-blue-600 font-black text-[9px] uppercase mb-1.5 tracking-widest flex items-center gap-1.5">
                        <Microscope class="w-3 h-3" /> Laboratório
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="font-black text-xs uppercase tracking-tighter text-slate-800">{{ activePatient?.PacienteNome || 'Nenhum paciente selecionado' }}</span>
                        <span v-if="activePatient" class="bg-blue-100 text-blue-600 px-2 py-0.5 rounded text-[9px] font-black tracking-widest uppercase">{{ activePatient.Codigo }}</span>
                    </div>
                </div>

                <div class="flex gap-4 pr-4 h-full items-center">
                    <button @click="printResult" :disabled="!activePatient" class="bg-slate-100 text-slate-700 px-4 py-2 font-black uppercase text-[10px] tracking-widest hover:bg-slate-200 transition-all rounded shadow-sm flex items-center gap-2 disabled:opacity-50">
                        <Printer class="w-3.5 h-3.5" /> Imprimir
                    </button>

                    <button @click="showMaterialModal = true" :disabled="!activePatient" class="bg-slate-100 text-slate-700 px-4 py-2 font-black uppercase text-[10px] tracking-widest hover:bg-slate-200 transition-all rounded shadow-sm flex items-center gap-2 disabled:opacity-50 border border-slate-200">
                        <Package class="w-3.5 h-3.5" /> Lançar Insumos
                    </button>

                    <button @click="finalizeAtendimento" :disabled="!activePatient" class="bg-emerald-600 text-white px-6 py-2 font-black uppercase text-[10px] tracking-widest hover:bg-emerald-700 transition-all rounded shadow-sm flex items-center gap-2 disabled:opacity-50">
                        <CheckCircle class="w-3.5 h-3.5" /> Finalizar Laboratório
                    </button>
                </div>
            </div>

            <!-- Dashboard Sub-Header (Blue Bar) -->
            <div class="bg-slate-800 text-slate-300 h-8 flex items-center px-4 justify-between shrink-0 shadow-sm z-10 text-[10px]">
                <div class="flex items-center gap-4">
                    <span class="font-bold flex items-center gap-2"><Clock class="w-3 h-3 text-blue-400" /> Data: <span class="text-white">{{ new Date().toLocaleDateString('pt-PT') }}</span></span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="font-bold">Aguardando: <span class="text-emerald-400 font-black">{{ aguardando.length }} Pacientes</span></span>
                    <div class="w-px h-3 bg-slate-600"></div>
                    <span class="font-bold">Módulo: <span class="text-blue-400">Laboratório e Imagiologia</span></span>
                </div>
            </div>

            <!-- Main Layout -->
            <div class="flex-1 flex overflow-hidden p-2 gap-2 relative z-0">
                
                <!-- LEFT COLUMN: FILA DE ESPERA -->
                <div class="w-1/3 flex flex-col bg-white rounded shadow-sm border border-slate-300 overflow-hidden shrink-0">
                    <div class="bg-slate-100/50 p-2 border-b border-slate-200 flex items-center justify-between shrink-0">
                        <span class="font-black text-[10px] uppercase text-slate-500 tracking-widest">Pacientes Aguardando</span>
                        <input type="text" placeholder="Pesquisar paciente..." class="border border-slate-200 rounded px-2 py-1 text-[9px] uppercase focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none w-40" />
                    </div>
                    
                    <div class="flex-1 overflow-auto bg-slate-50 relative custom-scrollbar">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-slate-200 sticky top-0 z-10 shadow-sm">
                                <tr class="text-[9px] font-black text-slate-500 uppercase tracking-widest">
                                    <th class="p-2 border-r border-slate-300">Agenda</th>
                                    <th class="p-2 border-r border-slate-300">Paciente</th>
                                    <th class="p-2 text-center w-10">...</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="p in aguardando" :key="p.Id" @click="selectPatient(p)" 
                                    class="border-b border-slate-200 cursor-pointer hover:bg-blue-50 transition-colors"
                                    :class="activePatient?.Codigo === p.Codigo ? 'bg-blue-100/80 border-l-4 border-l-blue-500' : 'bg-white border-l-4 border-l-transparent'">
                                    <td class="p-2 border-r border-slate-50 font-bold text-slate-700">
                                        {{ p.Codigo }}
                                        <div class="mt-1">
                                            <span 
                                                class="px-1 py-0.5 rounded text-[7px] font-black uppercase tracking-wider text-white"
                                                :class="getStatusColor(p.Situacao)"
                                            >
                                                {{ p.Situacao }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="p-2 border-r border-slate-50">
                                        <div class="font-bold uppercase text-[10px] text-slate-800">{{ p.PacienteNome }}</div>
                                        <div class="text-[8px] text-slate-500 font-bold uppercase mt-0.5 flex items-center gap-2">
                                            <span>Médico: {{ p.MedicoNome || 'N/D' }}</span>
                                            <span v-if="p.TotalExames > 0" class="bg-blue-100 text-blue-600 px-1 py-0.5 rounded flex items-center gap-1 font-black">
                                                <FlaskConical class="w-2.5 h-2.5" /> {{ p.TotalExames }} Exame(s)
                                            </span>
                                        </div>
                                    </td>
                                    <td class="p-2 text-center text-blue-600"><ChevronRight class="w-4 h-4 mx-auto" /></td>
                                </tr>
                                <tr v-if="aguardando.length === 0">
                                    <td colspan="3" class="p-8 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">Nenhum paciente aguardando</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- RIGHT COLUMN: EXAMES, MATERIAIS E RESULTADOS -->
                <div v-if="activePatient" class="flex-1 flex gap-2 overflow-hidden">
                    
                    <!-- Middle Column: Exames Ativos -->
                    <div class="flex-1 flex flex-col bg-white rounded shadow-sm border border-slate-300 overflow-hidden">
                        <div class="bg-slate-100/50 p-2 border-b border-slate-200 flex items-center justify-between shrink-0">
                            <span class="font-black text-[10px] uppercase text-slate-500 tracking-widest flex items-center gap-1.5">
                                <FlaskConical class="w-3 h-3" /> Exames Solicitados
                            </span>
                            <span class="bg-blue-600 text-white px-2 py-0.5 rounded text-[9px] font-black">{{ activeExames.length }} Total</span>
                        </div>
                        
                        <div class="flex-1 overflow-auto bg-slate-50 p-2 custom-scrollbar">
                            <div class="grid grid-cols-1 gap-2">
                                <div 
                                    v-for="ex in activeExames" 
                                    :key="ex.Id"
                                    class="bg-white border border-slate-200 rounded shadow-sm overflow-hidden flex flex-col"
                                >
                                    <div class="p-2 border-b bg-slate-100/50 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <Radiation v-if="ex.MetaTipo === 'RAIO X'" class="w-3.5 h-3.5 text-purple-600" />
                                            <Microscope v-else class="w-3.5 h-3.5 text-blue-600" />
                                            <div>
                                                <div class="font-black uppercase text-[10px] text-slate-800">{{ ex.Descricao }}</div>
                                                <div class="text-[8px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ ex.MetaTipo || 'GERAL' }}</div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <CheckCircle v-if="ex.Estado === 'Finalizado'" class="w-3.5 h-3.5 text-emerald-500" />
                                            <Activity v-else class="w-3.5 h-3.5 text-amber-500" />
                                        </div>
                                    </div>

                                    <div class="p-2 space-y-2 bg-white">
                                        <!-- Multi-field Inputs (Filhos) -->
                                        <div v-if="ex.MetaFilhos" class="grid grid-cols-2 gap-2">
                                            <div v-for="campo in ex.MetaFilhos.split('|').filter(c => c.trim())" :key="campo" class="flex flex-col">
                                                <label class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-0.5">{{ campo.trim() }}</label>
                                                <input 
                                                    type="text" 
                                                    v-model="ex.resultadosForm[campo.trim()]"
                                                    class="w-full border border-slate-200 rounded px-2 py-1 text-[10px] uppercase focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none bg-slate-50"
                                                />
                                            </div>
                                        </div>

                                        <!-- Simple Result Input -->
                                        <div v-else class="flex flex-col">
                                            <label class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Resultado</label>
                                            <textarea 
                                                v-model="ex.Resultado"
                                                rows="2"
                                                class="w-full border border-slate-200 rounded px-2 py-1 text-[10px] uppercase focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none resize-none bg-slate-50"
                                            ></textarea>
                                        </div>

                                        <!-- Observations -->
                                        <div class="flex flex-col">
                                            <label class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Observações (Opcional)</label>
                                            <input 
                                                type="text" 
                                                v-model="ex.Obs"
                                                class="w-full border border-slate-200 rounded px-2 py-1 text-[10px] italic focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none bg-slate-50"
                                            />
                                        </div>
                                    </div>

                                    <div class="px-2 py-1.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                                        <div class="text-[8px] font-bold text-slate-400 uppercase tracking-widest">
                                            <span v-if="ex.MetaReferencia">Ref: {{ ex.MetaReferencia }}</span>
                                        </div>
                                        <button 
                                            @click="saveExameResult(ex)"
                                            :disabled="saving[ex.Id]"
                                            class="bg-slate-800 text-white px-4 py-1 font-black uppercase text-[8px] tracking-widest hover:bg-slate-900 transition-all rounded shadow-sm disabled:opacity-50"
                                        >
                                            {{ saving[ex.Id] ? 'Gravando...' : 'Gravar' }}
                                        </button>
                                    </div>
                                </div>
                                <div v-if="activeExames.length === 0" class="p-8 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    Nenhum exame solicitado ativo.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Histórico & Materiais -->
                    <div class="w-80 flex flex-col gap-2 shrink-0 overflow-hidden">
                        
                        <!-- Historico -->
                        <div class="h-1/2 flex flex-col bg-white rounded shadow-sm border border-slate-300 overflow-hidden">
                            <div class="bg-slate-100/50 p-2 border-b border-slate-200 flex items-center justify-between shrink-0">
                                <span class="font-black text-[10px] uppercase text-slate-500 tracking-widest flex items-center gap-1.5">
                                    <History class="w-3 h-3 text-slate-500" /> Histórico Recente
                                </span>
                            </div>
                            <div class="flex-1 overflow-auto bg-slate-50 custom-scrollbar">
                                <table class="w-full text-left border-collapse">
                                    <tbody>
                                        <tr v-for="h in historico" :key="h.Id" class="border-b border-slate-200 bg-white">
                                            <td class="p-2">
                                                <div class="font-black uppercase text-[9px] text-slate-700">{{ h.Descricao }}</div>
                                                <div class="text-[8px] font-bold uppercase mt-0.5 text-slate-500 truncate w-48">{{ h.Resultado || 'S/ Resultado' }}</div>
                                            </td>
                                            <td class="p-2 text-right w-10">
                                                <span class="text-[7px] font-black bg-slate-100 px-1 py-0.5 rounded text-slate-500">{{ new Date(h.DataAgendamento).toLocaleDateString() }}</span>
                                            </td>
                                        </tr>
                                        <tr v-if="historico.length === 0">
                                            <td colspan="2" class="p-4 text-center text-[9px] font-bold text-slate-400 uppercase tracking-widest">Sem histórico</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Materiais Usados -->
                        <div class="h-1/2 flex flex-col bg-white rounded shadow-sm border border-slate-300 overflow-hidden">
                            <div class="bg-slate-100/50 p-2 border-b border-slate-200 flex items-center justify-between shrink-0">
                                <span class="font-black text-[10px] uppercase text-slate-500 tracking-widest flex items-center gap-1.5">
                                    <Package class="w-3 h-3 text-amber-600" /> Insumos Consumidos
                                </span>
                            </div>
                            <div class="flex-1 overflow-auto bg-slate-50 custom-scrollbar">
                                <table class="w-full text-left border-collapse">
                                    <tbody>
                                        <tr v-for="m in materiaisUsados" :key="m.Id" class="border-b border-slate-200 bg-white">
                                            <td class="p-2">
                                                <div class="font-black uppercase text-[9px] text-slate-700">{{ m.Descricao }}</div>
                                                <div class="text-[8px] font-bold uppercase mt-0.5 text-slate-500">{{ m.Quantidade }}x | {{ Number(m.Total).toLocaleString('pt-AO', { style: 'currency', currency: 'AOA' }) }}</div>
                                            </td>
                                            <td class="p-2 text-right w-8">
                                                <button @click="removeMaterial(m.Id)" class="text-slate-400 hover:text-red-500 transition-colors">
                                                    <Trash2 class="w-3.5 h-3.5" />
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="materiaisUsados.length === 0">
                                            <td colspan="2" class="p-4 text-center text-[9px] font-bold text-slate-400 uppercase tracking-widest">Nenhum insumo registado</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="flex-1 flex flex-col items-center justify-center bg-white rounded shadow-sm border border-slate-300 p-12 text-center relative overflow-hidden">
                    <Microscope class="w-16 h-16 text-slate-200 mb-4" />
                    <h2 class="text-base font-black text-slate-400 uppercase tracking-[0.2em]">Painel Laboratorial</h2>
                    <p class="text-[10px] font-bold text-slate-400 mt-2 uppercase tracking-widest">Selecione um paciente na fila para visualizar os exames solicitados e lançar os resultados</p>
                </div>

            </div>
        </div>

        <!-- Material Selection Modal -->
        <Transition enter-active-class="duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="duration-150 ease-in" leave-to-class="opacity-0">
            <div v-if="showMaterialModal" class="fixed inset-0 z-[2000] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white rounded shadow-2xl w-full max-w-xl border border-slate-200 flex flex-col max-h-[85vh]">
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                            <Package class="w-4 h-4 text-blue-600" /> Insumos & Reagentes Utilizados
                        </h2>
                        <button @click="showMaterialModal = false" class="text-slate-400 hover:text-slate-600 transition-all">
                            <Plus class="w-5 h-5 rotate-45" />
                        </button>
                    </div>
                    
                    <div class="p-4 overflow-y-auto space-y-4 custom-scrollbar bg-white">
                        <div class="grid grid-cols-1 gap-1">
                            <div 
                                v-for="mat in materiais" 
                                :key="mat.CODIGO"
                                @click="addMaterial(mat)"
                                class="p-3 border border-slate-100 hover:border-blue-500 hover:bg-blue-50 rounded cursor-pointer transition-all flex items-center justify-between group shadow-sm bg-white"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-slate-50 border border-slate-100 rounded flex items-center justify-center text-slate-400 group-hover:text-blue-600 transition-colors">
                                        <Package class="w-4 h-4" />
                                    </div>
                                    <div>
                                        <p class="font-black text-[10px] uppercase text-slate-800">{{ mat.DESCRICAO }}</p>
                                        <p class="text-[8px] text-slate-400 font-bold uppercase tracking-widest">Cód: {{ mat.CODIGO }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] font-black text-slate-900">{{ Number(mat.PV).toLocaleString('pt-AO', { style: 'currency', currency: 'AOA' }) }}</p>
                                    <span class="text-[8px] text-blue-600 font-bold opacity-0 group-hover:opacity-100 transition-opacity uppercase">Adicionar</span>
                                </div>
                            </div>
                        </div>
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

input[type="text"], textarea {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>

<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import { 
    Search, User, Calendar, Clock, Activity, 
    FileText, Check, X, FlaskConical, Stethoscope,
    ChevronRight, Save, Printer, Trash2, Plus, 
    Microscope, Radiation, History, Package
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
        <div class="h-[calc(100vh-4rem)] flex overflow-hidden bg-slate-50">
            <!-- Sidebar: Lista de Espera -->
            <div class="w-80 bg-white border-r flex flex-col shadow-sm">
                <div class="p-4 border-b bg-slate-50/50">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                            <Clock class="w-5 h-5 text-blue-600" />
                            Fila de Espera
                        </h2>
                        <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs font-bold">
                            {{ aguardando.length }}
                        </span>
                    </div>
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <input 
                            type="text" 
                            placeholder="Buscar paciente..."
                            class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all"
                        />
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-2">
                    <div 
                        v-for="p in aguardando" 
                        :key="p.Id"
                        @click="selectPatient(p)"
                        class="p-3 rounded-xl border border-slate-100 cursor-pointer transition-all hover:shadow-md hover:border-blue-200 group"
                        :class="[activePatient?.Codigo === p.Codigo ? 'bg-blue-50 border-blue-200 ring-1 ring-blue-500/10' : 'bg-white']"
                    >
                        <div class="flex items-start justify-between mb-2">
                            <span class="text-xs font-bold text-slate-400">#{{ p.Codigo }}</span>
                            <span 
                                class="px-2 py-0.5 rounded text-[10px] font-bold text-white uppercase tracking-wider"
                                :class="getStatusColor(p.Situacao)"
                            >
                                {{ p.Situacao }}
                            </span>
                        </div>
                        <h3 class="font-bold text-slate-800 truncate group-hover:text-blue-600 transition-colors">
                            {{ p.PacienteNome }}
                        </h3>
                        <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                            <Stethoscope class="w-3 h-3" />
                            {{ p.MedicoNome || 'Médico não atribuído' }}
                        </p>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded flex items-center gap-1">
                                <Calendar class="w-2.5 h-2.5" />
                                {{ new Date(p.DataAgendamento).toLocaleDateString() }}
                            </span>
                        </div>
                    </div>

                    <div v-if="aguardando.length === 0" class="flex flex-col items-center justify-center h-40 text-slate-400 opacity-50">
                        <Activity class="w-12 h-12 mb-2" />
                        <p class="text-sm">Nenhum paciente aguardando</p>
                    </div>
                </div>
            </div>

            <!-- Main Workspace -->
            <div class="flex-1 overflow-y-auto custom-scrollbar relative p-6 bg-[#f8fafc]">
                <div v-if="activePatient" class="max-w-6xl mx-auto space-y-6 animate-in fade-in duration-300">
                    <!-- Patient Profile Header -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/60 relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-8 opacity-5">
                            <Microscope class="w-32 h-32" />
                        </div>
                        
                        <div class="relative flex flex-wrap items-center gap-6">
                            <div class="w-20 h-20 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-blue-500/20">
                                <User class="w-10 h-10" />
                            </div>
                            
                            <div class="flex-1 min-w-[200px]">
                                <div class="flex items-center gap-3 mb-1">
                                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">{{ activePatient.PacienteNome }}</h1>
                                    <span v-if="activePatient.Seguradora" class="px-2 py-0.5 bg-indigo-50 text-indigo-700 text-[10px] font-bold rounded uppercase border border-indigo-100">
                                        {{ activePatient.Seguradora }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap items-center gap-y-2 gap-x-4 text-sm text-slate-500">
                                    <span class="flex items-center gap-1.5 font-medium">
                                        <Calendar class="w-4 h-4 text-blue-500" />
                                        {{ calculateAge(activePatient.DataNascimento) }} Anos ({{ new Date(activePatient.DataNascimento).toLocaleDateString() }})
                                    </span>
                                    <span class="flex items-center gap-1.5 font-medium">
                                        <Activity class="w-4 h-4 text-blue-500" />
                                        {{ activePatient.Genero }}
                                    </span>
                                    <span v-if="activePatient.Telefone" class="flex items-center gap-1.5 font-medium">
                                        <Clock class="w-4 h-4 text-blue-500" />
                                        {{ activePatient.Telefone }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <button 
                                    @click="printResult"
                                    class="flex items-center gap-2 px-4 py-2.5 bg-slate-100 text-slate-700 rounded-xl font-bold hover:bg-slate-200 transition-all text-sm"
                                >
                                    <Printer class="w-4 h-4" />
                                    Imprimir
                                </button>
                                <button 
                                    @click="finalizeAtendimento"
                                    class="flex items-center gap-2 px-6 py-2.5 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-600/20 transition-all text-sm"
                                >
                                    <Check class="w-4 h-4" />
                                    Finalizar Atendimento
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs Navigation -->
                    <div class="flex items-center gap-2 bg-white p-1 rounded-2xl border border-slate-200 shadow-sm w-fit">
                        <button 
                            @click="activeTab = 'exames'"
                            :class="[activeTab === 'exames' ? 'bg-blue-600 text-white' : 'text-slate-500 hover:bg-slate-50']"
                            class="px-5 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2"
                        >
                            <FlaskConical class="w-4 h-4" />
                            Exames Ativos
                        </button>
                        <button 
                            @click="activeTab = 'historico'"
                            :class="[activeTab === 'historico' ? 'bg-blue-600 text-white' : 'text-slate-500 hover:bg-slate-50']"
                            class="px-5 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2"
                        >
                            <History class="w-4 h-4" />
                            Histórico
                        </button>
                        <button 
                            @click="activeTab = 'materiais'"
                            :class="[activeTab === 'materiais' ? 'bg-blue-600 text-white' : 'text-slate-500 hover:bg-slate-50']"
                            class="px-5 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2"
                        >
                            <Package class="w-4 h-4" />
                            Materiais Usados
                        </button>
                    </div>

                    <!-- Tab Content: Exames Ativos -->
                    <div v-if="activeTab === 'exames'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div 
                            v-for="ex in activeExames" 
                            :key="ex.Id"
                            class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden flex flex-col group"
                        >
                            <div class="p-5 border-b bg-slate-50/50 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600">
                                        <Radiation v-if="ex.MetaTipo === 'RAIO X'" class="w-5 h-5" />
                                        <Microscope v-else class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-800 tracking-tight">{{ ex.Descricao }}</h3>
                                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ ex.MetaTipo || 'NORMAL' }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span 
                                        :class="[ex.Estado === 'Finalizado' ? 'bg-emerald-500' : 'bg-orange-500']"
                                        class="w-2.5 h-2.5 rounded-full"
                                    ></span>
                                    <span class="text-[10px] font-black text-slate-500 uppercase">{{ ex.Estado }}</span>
                                </div>
                            </div>

                            <div class="p-5 flex-1 space-y-4">
                                <!-- Multi-field Inputs (Filhos) -->
                                <div v-if="ex.MetaFilhos" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div v-for="campo in ex.MetaFilhos.split('|').filter(c => c.trim())" :key="campo" class="space-y-1.5">
                                        <label class="text-[10px] font-black text-slate-500 uppercase px-1">{{ campo.trim() }}</label>
                                        <input 
                                            type="text" 
                                            v-model="ex.resultadosForm[campo.trim()]"
                                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all"
                                            placeholder="Inserir resultado..."
                                        />
                                    </div>
                                </div>

                                <!-- Simple Result Input -->
                                <div v-else class="space-y-1.5">
                                    <label class="text-[10px] font-black text-slate-500 uppercase px-1">Resultado Final</label>
                                    <textarea 
                                        v-model="ex.Resultado"
                                        rows="3"
                                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all resize-none"
                                        placeholder="Descreva o diagnóstico ou resultado..."
                                    ></textarea>
                                </div>

                                <!-- Observations -->
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-black text-slate-500 uppercase px-1">Observações / Notas</label>
                                    <input 
                                        type="text" 
                                        v-model="ex.Obs"
                                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm italic"
                                        placeholder="Opcional..."
                                    />
                                </div>
                            </div>

                            <div class="px-5 py-4 bg-slate-50/50 border-t flex items-center justify-between">
                                <div class="flex items-center gap-1.5">
                                    <span v-if="ex.MetaReferencia" class="text-[10px] font-medium text-slate-400 bg-white px-2 py-1 rounded-lg border border-slate-200">
                                        Ref: {{ ex.MetaReferencia }}
                                    </span>
                                </div>
                                <button 
                                    @click="saveExameResult(ex)"
                                    :disabled="saving[ex.Id]"
                                    class="flex items-center gap-2 px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-blue-600 transition-all disabled:opacity-50"
                                >
                                    <Save v-if="!saving[ex.Id]" class="w-3.5 h-3.5" />
                                    <Activity v-else class="w-3.5 h-3.5 animate-spin" />
                                    {{ saving[ex.Id] ? 'Gravando...' : 'Gravar Resultado' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Content: Histórico -->
                    <div v-if="activeTab === 'historico'" class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b">
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase">Data</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase">Exame</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase">Resultado</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="h in historico" :key="h.Id" class="border-b last:border-0 hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-slate-700">{{ new Date(h.DataAgendamento).toLocaleDateString() }}</span>
                                            <span class="text-[10px] text-slate-400 uppercase font-bold">{{ new Date(h.DataAgendamento).toLocaleTimeString() }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm font-black text-blue-600">{{ h.Descricao }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-sm text-slate-600 truncate max-w-xs">{{ h.Resultado }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button class="p-2 text-slate-400 hover:text-blue-600 transition-colors">
                                            <Printer class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="historico.length === 0">
                                    <td colspan="4" class="px-6 py-12 text-center text-slate-400 italic">Nenhum histórico encontrado para este paciente.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tab Content: Materiais -->
                    <div v-if="activeTab === 'materiais'" class="space-y-6">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-bold text-slate-800">Materiais Consumidos</h2>
                            <button 
                                @click="showMaterialModal = true"
                                class="flex items-center gap-2 px-4 py-2 bg-slate-900 text-white rounded-xl text-sm font-bold hover:bg-slate-800 transition-all shadow-lg shadow-slate-900/10"
                            >
                                <Plus class="w-4 h-4" />
                                Adicionar Material
                            </button>
                        </div>

                        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 border-b">
                                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase">Descrição</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase">Qtd</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase">Preço Unit.</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase">Total</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase text-right">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="m in materiaisUsados" :key="m.Id" class="border-b last:border-0 hover:bg-slate-50">
                                        <td class="px-6 py-4">
                                            <span class="text-sm font-bold text-slate-700">{{ m.Descricao }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-lg text-xs font-black">{{ m.Quantidade }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-600">
                                            {{ Number(m.Preco).toLocaleString('pt-AO', { style: 'currency', currency: 'AOA' }) }}
                                        </td>
                                        <td class="px-6 py-4 text-sm font-black text-slate-900">
                                            {{ Number(m.Total).toLocaleString('pt-AO', { style: 'currency', currency: 'AOA' }) }}
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button 
                                                @click="removeMaterial(m.Id)"
                                                class="p-2 text-slate-400 hover:text-red-500 transition-colors"
                                            >
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="h-full flex flex-col items-center justify-center text-slate-300">
                    <div class="w-40 h-40 rounded-full bg-white flex items-center justify-center mb-6 shadow-xl shadow-slate-200/50">
                        <Microscope class="w-20 h-20 text-slate-100" />
                    </div>
                    <h2 class="text-2xl font-black text-slate-400 tracking-tight">Painel de Diagnóstico</h2>
                    <p class="text-slate-400/60 font-medium">Selecione um paciente na lista para iniciar os lançamentos</p>
                </div>
            </div>
        </div>

        <!-- Material Selection Modal -->
        <div v-if="showMaterialModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="bg-white w-full max-w-2xl rounded-[2.5rem] shadow-2xl overflow-hidden animate-in zoom-in-95 duration-200 border border-white/20">
                <div class="p-8 border-b bg-slate-50/50 flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-black text-slate-900">Registrar Material Consumido</h3>
                        <p class="text-sm text-slate-500">Selecione os reagentes ou insumos utilizados</p>
                    </div>
                    <button @click="showMaterialModal = false" class="p-2 hover:bg-slate-200 rounded-full transition-colors">
                        <X class="w-6 h-6 text-slate-400" />
                    </button>
                </div>
                
                <div class="p-8 max-h-[60vh] overflow-y-auto custom-scrollbar">
                    <div class="grid grid-cols-1 gap-3">
                        <div 
                            v-for="mat in materiais" 
                            :key="mat.CODIGO"
                            @click="addMaterial(mat)"
                            class="p-4 rounded-2xl border border-slate-100 hover:border-blue-500 hover:bg-blue-50 cursor-pointer transition-all flex items-center justify-between group"
                        >
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-slate-400 group-hover:text-blue-600 transition-colors border">
                                    <Package class="w-5 h-5" />
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800">{{ mat.DESCRICAO }}</p>
                                    <p class="text-xs text-slate-400 font-medium">Cód: {{ mat.CODIGO }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black text-slate-900">{{ Number(mat.PV).toLocaleString('pt-AO', { style: 'currency', currency: 'AOA' }) }}</p>
                                <span class="text-[10px] text-blue-600 font-bold opacity-0 group-hover:opacity-100 transition-opacity uppercase">Clique para Adicionar</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification Toast -->
        <div 
            v-if="notification.show" 
            class="fixed bottom-8 right-8 z-[100] animate-in slide-in-from-bottom-5 duration-300"
        >
            <div 
                :class="[notification.type === 'success' ? 'bg-slate-900 text-white' : 'bg-red-600 text-white']"
                class="px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-3 border border-white/10"
            >
                <Check v-if="notification.type === 'success'" class="w-5 h-5 text-emerald-400" />
                <X v-else class="w-5 h-5 text-white" />
                <span class="font-bold text-sm">{{ notification.message }}</span>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}

input[type="text"], textarea {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.animate-in {
    animation-fill-mode: forwards;
}
</style>

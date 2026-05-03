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
    ScanLine, Monitor, Bone, Radio, Camera,
    UserCircle, Phone, MapPin, Zap
} from 'lucide-vue-next';

const props = defineProps({
    aguardando: Array
});

const searchTerm = ref('');
const selectedPaciente = ref(null);
const details = ref({ exames: [], historico: [], paciente: {} });
const selectedExame = ref(null);
const resultadoText = ref('');
const isLoading = ref(false);
const showConfirmModal = ref(false);

const filteredAguardando = computed(() => {
    if (!searchTerm.value) return props.aguardando;
    const term = searchTerm.value.toLowerCase();
    return props.aguardando.filter(a => 
        a.PacienteNome.toLowerCase().includes(term) ||
        a.Codigo.toLowerCase().includes(term)
    );
});

const selecionarPaciente = async (paciente) => {
    selectedPaciente.value = paciente;
    isLoading.value = true;
    selectedExame.value = null;
    resultadoText.value = '';

    try {
        const response = await axios.get(route('hospitalar.raiox.details', paciente.Codigo));
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

    router.post(route('hospitalar.raiox.resultado'), {
        idExame: selectedExame.value.Id,
        resultado: resultadoText.value
    }, {
        onSuccess: () => {
            selecionarPaciente(selectedPaciente.value);
        }
    });
};

const finalizarRaioX = () => {
    showConfirmModal.value = true;
};

const confirmarFinalizacao = () => {
    showConfirmModal.value = false;
    router.post(route('hospitalar.raiox.finalizar', selectedPaciente.value.Codigo), {}, {
        onSuccess: () => {
            selectedPaciente.value = null;
            details.value = { exames: [], historico: [], paciente: {} };
        }
    });
};

const calcularIdade = (nascimento) => {
    if (!nascimento) return 'N/D';
    const birthDate = new Date(nascimento);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) age--;
    return age + ' anos';
};
</script>

<template>
    <Head title="Raio-X / Imagiologia" />

    <DashboardLayout>
        <div class="min-h-screen bg-slate-100 font-sans text-[10px]">
            <!-- TOP HEADER BAR -->
            <div class="bg-blue-900 text-white text-center py-1.5 font-black uppercase tracking-[0.4em] text-[12px] shadow-sm">
                LABORATÓRIO DE RAIO X
            </div>

            <div class="p-1 lg:p-2 grid grid-cols-1 lg:grid-cols-12 gap-1 h-[calc(100vh-100px)] overflow-hidden">
                
                <!-- LEFT COLUMN: TABLES (Waitlist & History) -->
                <div class="col-span-1 lg:col-span-4 flex flex-col gap-1 h-full overflow-hidden">
                    
                    <!-- Waitlist -->
                    <div class="h-1/2 bg-white border border-slate-300 flex flex-col shadow-sm overflow-hidden">
                        <div class="bg-slate-50 text-slate-800 text-center py-1 font-black uppercase tracking-widest border-b border-slate-200">Lista de Espera</div>
                        <div class="p-1 bg-slate-50 border-b border-slate-200 text-[8px] text-slate-400 font-bold uppercase italic">Drag a column header here to group by that column</div>
                        
                        <div class="flex-grow overflow-auto custom-scrollbar">
                            <table class="w-full border-collapse">
                                <thead class="bg-slate-50 sticky top-0 border-b border-slate-200 z-10 font-bold text-slate-500 text-[8px] uppercase">
                                    <tr class="text-left">
                                        <th class="p-2 border-r border-slate-100">Nome</th>
                                        <th class="p-2 border-r border-slate-100">Data Exame</th>
                                        <th class="p-2">Médico</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="a in filteredAguardando" :key="a.Codigo" 
                                        @click="selecionarPaciente(a)"
                                        :class="selectedPaciente?.Codigo === a.Codigo ? 'bg-blue-600 text-white' : 'hover:bg-blue-50 text-slate-600'"
                                        class="cursor-pointer border-b border-slate-100 font-black uppercase transition-colors">
                                        <td class="p-2 border-r border-slate-100/30">{{ a.PacienteNome }}</td>
                                        <td class="p-2 border-r border-slate-100/30">{{ a.DataAgendamento }}</td>
                                        <td class="p-2 uppercase">{{ a.Medico || 'MED1' }}</td>
                                    </tr>
                                    <tr v-if="filteredAguardando.length === 0">
                                        <td colspan="3" class="p-10 text-center italic text-slate-400 uppercase">Ninguém na fila</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Patient History -->
                    <div class="h-1/2 bg-white border border-slate-300 flex flex-col shadow-sm overflow-hidden">
                        <div class="bg-slate-50 text-slate-800 text-center py-1 font-black uppercase tracking-widest border-b border-slate-200">Histórico do Paciente</div>
                        <div class="p-2 bg-slate-50 border-b border-slate-200 flex items-center gap-2">
                            <div class="relative flex-grow">
                                <input placeholder="Buscar Paciente..." class="w-full border border-slate-300 px-8 py-1 rounded text-[9px] font-bold uppercase outline-none focus:border-blue-500" />
                                <Search class="absolute left-2 top-1.5 w-3 h-3 text-slate-400" />
                            </div>
                        </div>
                        <div class="p-1 bg-slate-50 border-b border-slate-200 text-[8px] text-slate-400 font-bold uppercase italic">Drag a column header here to group by that column</div>

                        <div class="flex-grow overflow-auto custom-scrollbar">
                            <table class="w-full border-collapse">
                                <thead class="bg-slate-50 sticky top-0 border-b border-slate-200 z-10 font-bold text-slate-500 text-[8px] uppercase">
                                    <tr class="text-left">
                                        <th class="p-2 border-r border-slate-100">Data</th>
                                        <th class="p-2 border-r border-slate-100">Consulta</th>
                                        <th class="p-2 border-r border-slate-100">Paciente</th>
                                        <th class="p-2 border-r border-slate-100">Medico</th>
                                        <th class="p-2 border-r border-slate-100">Situação</th>
                                        <th class="p-2">Visualizar</th>
                                    </tr>
                                </thead>
                                <tbody class="text-[9px] font-bold text-slate-500 uppercase">
                                    <tr v-for="h in details.historico" :key="h.Id" class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                        <td class="p-2 border-r border-slate-100/30">{{ h.Data }}</td>
                                        <td class="p-2 border-r border-slate-100/30">{{ h.Consulta }}</td>
                                        <td class="p-2 border-r border-slate-100/30">{{ h.Paciente }}</td>
                                        <td class="p-2 border-r border-slate-100/30">{{ h.Medico }}</td>
                                        <td class="p-2 border-r border-slate-100/30">{{ h.Situacao }}</td>
                                        <td class="p-2 text-center"><Printer class="w-3 h-3 inline-block cursor-pointer text-slate-400 hover:text-blue-500" /></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: DETAILS & RESULTS -->
                <div class="col-span-1 lg:col-span-8 flex flex-col gap-1 h-full overflow-hidden bg-slate-200">
                    
                    <!-- Patient Banner -->
                    <div class="bg-blue-800 text-white text-center py-1 font-black uppercase tracking-[0.2em] text-[11px] shadow-sm">
                        {{ details.paciente.PacienteNome || 'Selecione um Paciente' }}
                    </div>

                    <!-- Dados do Paciente -->
                    <div class="bg-white border border-slate-300 p-2 shadow-sm">
                        <div class="bg-slate-50 text-slate-500 text-center py-0.5 font-black uppercase text-[8px] mb-2 border-b border-slate-200">Dados do Paciente</div>
                        <div class="grid grid-cols-12 gap-x-4 gap-y-2">
                            <div class="col-span-4 flex items-center gap-2">
                                <label class="w-20 font-black text-slate-400 uppercase text-[8px]">Código:</label>
                                <input :value="details.paciente.IdPaciente" readonly class="flex-grow border-b border-slate-200 px-2 py-0.5 font-bold text-slate-700 bg-slate-50/50" />
                            </div>
                            <div class="col-span-8 flex items-center gap-2">
                                <label class="w-20 font-black text-slate-400 uppercase text-[8px]">Nome:</label>
                                <input :value="details.paciente.PacienteNome" readonly class="flex-grow border-b border-slate-200 px-2 py-0.5 font-bold text-slate-700 bg-slate-50/50" />
                            </div>
                            <div class="col-span-8 flex items-center gap-2">
                                <label class="w-20 font-black text-slate-400 uppercase text-[8px]">Data Nasc.:</label>
                                <input :value="details.paciente.DataNascimento" readonly class="flex-grow border-b border-slate-200 px-2 py-0.5 font-bold text-slate-700 bg-slate-50/50" />
                            </div>
                            <div class="col-span-4 flex items-center gap-2">
                                <label class="w-10 font-black text-slate-400 uppercase text-[8px]">Idade:</label>
                                <input :value="calcularIdade(details.paciente.DataNascimento)" readonly class="flex-grow border-b border-slate-200 px-2 py-0.5 font-bold text-slate-700 bg-slate-50/50" />
                            </div>
                            <div class="col-span-6 flex items-center gap-2">
                                <label class="w-20 font-black text-slate-400 uppercase text-[8px]">Telefone:</label>
                                <input :value="details.paciente.Telefone" readonly class="flex-grow border-b border-slate-200 px-2 py-0.5 font-bold text-slate-700 bg-slate-50/50" />
                            </div>
                            <div class="col-span-6 flex items-center gap-2">
                                <label class="w-10 font-black text-slate-400 uppercase text-[8px]">Sexo:</label>
                                <input :value="details.paciente.Genero" readonly class="flex-grow border-b border-slate-200 px-2 py-0.5 font-bold text-slate-700 bg-slate-50/50" />
                            </div>
                            <div class="col-span-12 flex items-center gap-2">
                                <label class="w-20 font-black text-slate-400 uppercase text-[8px]">Morada:</label>
                                <input :value="details.paciente.Morada" readonly class="flex-grow border-b border-slate-200 px-2 py-0.5 font-bold text-slate-700 bg-slate-50/50" />
                            </div>
                            <div class="col-span-12 flex gap-4 mt-1 border-t border-slate-100 pt-2">
                                <label class="flex items-center gap-1 font-black text-slate-500 uppercase text-[8px]"><input type="radio" checked disabled /> Particular</label>
                                <label class="flex items-center gap-1 font-black text-slate-500 uppercase text-[8px]"><input type="radio" disabled /> Assegurado</label>
                            </div>
                            <div class="col-span-12 grid grid-cols-2 gap-4">
                                <div class="flex items-center gap-2">
                                    <label class="w-20 font-black text-slate-400 uppercase text-[8px]">Consulta:</label>
                                    <input value="Clínica Geral" readonly class="flex-grow border-b border-slate-200 px-2 py-0.5 font-bold text-slate-700 bg-slate-50/50" />
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="w-10 font-black text-slate-400 uppercase text-[8px]">Médico:</label>
                                    <input value="MED1" readonly class="flex-grow border-b border-slate-200 px-2 py-0.5 font-bold text-slate-700 bg-slate-50/50" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resultados do Exames -->
                    <div class="bg-white border border-slate-300 flex-grow flex flex-col shadow-sm overflow-hidden">
                        <div class="bg-slate-50 text-slate-500 text-center py-0.5 font-black uppercase text-[8px] border-b border-slate-200">Resultados do Exames</div>
                        
                        <div class="flex flex-grow overflow-hidden">
                            <!-- Left: Action Buttons & Exam Table -->
                            <div class="w-3/5 flex flex-col border-r border-slate-200">
                                <div class="p-2 space-y-1 bg-slate-50 border-b border-slate-200">
                                    <button class="w-full bg-white border border-slate-300 py-1.5 flex items-center justify-center gap-3 text-slate-600 font-black uppercase hover:bg-blue-50 transition-all shadow-sm">
                                        <Printer class="w-4 h-4 text-blue-500" /> IMPRIMIR RESULTADOS
                                    </button>
                                    <button @click="finalizarRaioX" class="w-full bg-white border border-slate-300 py-1.5 flex items-center justify-center gap-3 text-slate-600 font-black uppercase hover:bg-emerald-50 transition-all shadow-sm">
                                        <Zap class="w-4 h-4 text-emerald-500" /> FINALIZAR
                                    </button>
                                </div>
                                
                                <div class="flex-grow overflow-auto custom-scrollbar">
                                    <div class="p-1 bg-slate-50 border-b border-slate-200 text-[7px] text-slate-400 font-bold uppercase italic">Drag a column header here to group by that column</div>
                                    <table class="w-full border-collapse">
                                        <thead class="bg-slate-50 sticky top-0 border-b border-slate-200 z-10 font-bold text-slate-500 text-[8px] uppercase">
                                            <tr class="text-left">
                                                <th class="p-2 border-r border-slate-100">Exame</th>
                                                <th class="p-2">Resultado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="e in details.exames" :key="e.Id" 
                                                @click="selecionarExame(e)"
                                                :class="selectedExame?.Id === e.Id ? 'bg-blue-50' : 'hover:bg-slate-50'"
                                                class="cursor-pointer border-b border-slate-100 text-[9px] font-bold text-slate-600 uppercase">
                                                <td class="p-2 border-r border-slate-100/30">{{ e.Descricao }}</td>
                                                <td class="p-2">
                                                    <span v-if="e.Resultado" class="text-emerald-600">Registrado</span>
                                                    <span v-else class="text-slate-300">Pendente</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Right: Report Editor / Placeholder -->
                            <div class="w-2/5 flex flex-col bg-slate-900">
                                <div v-if="selectedExame" class="p-4 flex flex-col h-full bg-white animate-fadeIn">
                                    <h4 class="text-[9px] font-black text-blue-900 uppercase border-b border-blue-100 pb-2 mb-4">Laudo: {{ selectedExame.Descricao }}</h4>
                                    <textarea v-model="resultadoText" class="flex-grow w-full p-4 text-[10px] font-medium text-slate-700 bg-slate-50 border-none focus:ring-0 resize-none rounded-xl" placeholder="Escreva o laudo detalhado aqui..."></textarea>
                                    <div class="mt-4 flex justify-end">
                                        <button @click="salvarResultado" class="bg-blue-600 text-white px-6 py-2 rounded-xl font-black uppercase text-[9px] tracking-widest shadow-lg hover:bg-blue-700 transition-all">
                                            Salvar Laudo
                                        </button>
                                    </div>
                                </div>
                                <div v-else class="flex flex-col items-center justify-center h-full text-blue-800/40 opacity-50 p-8 text-center">
                                    <Monitor class="w-16 h-16 mb-4" />
                                    <p class="text-[9px] font-black uppercase tracking-[0.3em]">Selecione um exame para laudar</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>

    <!-- ELEGANT CONFIRMATION MODAL -->
    <Transition enter-active-class="duration-300 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="duration-200 ease-in" leave-to-class="opacity-0 scale-95">
        <div v-if="showConfirmModal" class="fixed inset-0 z-[2000] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-md" @click="showConfirmModal = false"></div>
            <div class="relative bg-white rounded-[2.5rem] shadow-2xl p-10 max-w-md w-full border border-white/20 animate-fadeIn">
                <div class="flex flex-col items-center text-center">
                    <div class="w-20 h-20 bg-blue-50 rounded-[2rem] flex items-center justify-center mb-6 shadow-inner">
                        <AlertCircle class="w-10 h-10 text-blue-600" />
                    </div>
                    <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight mb-2">Finalizar Atendimento?</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest leading-relaxed mb-8 px-4">
                        Esta ação concluirá o processo radiológico para este paciente e o removerá da fila ativa.
                    </p>
                    
                    <div class="grid grid-cols-2 gap-4 w-full">
                        <button @click="showConfirmModal = false" class="py-4 bg-slate-100 text-slate-500 rounded-2xl font-black uppercase text-[10px] tracking-widest hover:bg-slate-200 transition-all">
                            Cancelar
                        </button>
                        <button @click="confirmarFinalizacao" class="py-4 bg-blue-600 text-white rounded-2xl font-black uppercase text-[10px] tracking-widest hover:bg-blue-700 transition-all shadow-xl shadow-blue-200">
                            Confirmar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
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
    background: #1e293b;
    border-radius: 10px;
}
</style>
